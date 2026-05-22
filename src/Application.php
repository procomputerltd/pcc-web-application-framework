<?php
namespace Procomputer\WebApplicationFramework;

use Procomputer\Pcclib\Types;
use Procomputer\Pcclib\Html\Element;
use Procomputer\WebApplicationFramework\CssFrameworks\CssFrameworks;
use Procomputer\WebApplicationFramework\CssFrameworks\Navbar;
use Procomputer\WebApplicationFramework\Db\Db as AppDatabase;
use Procomputer\WebApplicationFramework\Http;
use Procomputer\WebApplicationFramework\Widgets\Clipboard;
use ModernPHPException\ModernPHPException;
use RuntimeException;

/**
 * @method Application setBrand(string $arg)
 * @method Application setCssFramework(string $arg)
 * @method Application setFontAwesome(boolean $arg)
 * @method Application setFooter(string $arg)
 * @method Application setJqueryVersion(string $arg)
 * @method Application setPageTitle(string $arg)
 * @method Application setPrependScripts(boolean $arg)
 * @method Application setRenderThisFile(string $arg)
 * @method Application setSessionLifetime(int $arg)
 * @method Application setStartSession(boolean $arg)
 * @method Application setWrapperClass(string $arg)
 * @method Application setWrapperWidth(string|int $arg)
 */
class Application {

    use CommonUtilities;

    protected $_properties = [
        'brand' => '',
        'cssframework' => 'bootstrap.5',
        'errorreporting' => E_ALL,
        'fontawesome' => false,
        'footer' => '',
        'jqueryversion' => '3.6.0',
        'pagetitle' => '',
        'prependscripts' => true,
        'renderthisfile' => '',
        'sessionlifetime' => 'auto', // auto = now until 09-Jan-2038
        'startsession' => true,
        'wrapperclass' => '', // CSS class used in a DIV around the content. Normally 'container'
        'wrapperwidth' => '1720px',
        ];

    protected $_jqueryUrl = 'https://code.jquery.com/jquery-%s.js';
    protected $_fontAwesomeUrl = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css';

    /**
     * Application options.
     * @var array
     */
    protected $_options = [];

    /**
     * SimpleCollection object; storage for scripts.
     * @var SimpleCollection
     */
    protected $_scripts;

    /**
     * SimpleCollection object; storage for script files.
     * @var SimpleCollection
     */
    protected $_scriptFiles;

    /**
     * SimpleCollection object; storage for styles.
     * @var SimpleCollection
     */
    protected $_styleSheets;

    /**
     * SimpleCollection object; storage for stylesheet files.
     * @var SimpleCollection
     */
    protected $_styleSheetFiles;

    /**
     * SimpleCollection object; storage for modules.
     * @var SimpleCollection
     */
    protected $_modules;

    /**
     *
     * @var CssFrameworks
     */
    protected $_cssFrameworks;

    /**
     *
     * @var Procomputer\WebApplicationFramework\Http
     */
    protected $_http;

    /**
     *
     * @var Procomputer\WebApplicationFramework\CssFrameworks\Navbar
     */
    protected $_navbar;

    /**
     * Clipboard object.
     * @var Clipboard
     */
    protected $_clipboard;

    /**
     * Set true if a Location redirect header sent.
     * @var bool
     */
    protected $_redirect = false;
    
    /**
     *
     * @param array   $dbConfig  Configuration parameters.
     * @return \Procomputer\WebApplicationFramework\Db\Db
     * @throws RuntimeException
     */
    public function db(array $dbConfig) {
        return new AppDatabase($dbConfig);
    }
    
    /**
     * Ctor
     * @param array $options
     * @return void
     */
    public function __construct(array $options = []) {
        // Init the exception handler if it's declared.
        if(class_exists('ModernPHPException\ModernPHPException')) {
            $exc = new ModernPHPException(['title' => 'Pro Computer']);
            $exc->start();
        }
        
        if(! empty($options)) {
            $this->_parseOptions($options);
        }
        
        // Set the error reporting.
        $bitFlags = $this->setErrorReporting($this->_options['errorreporting']);
        // The following checks for E_ALL while excluding E_DEPRECATED, E_USER_DEPRECATED, E_ALL
        $errors = E_ERROR|E_WARNING|E_PARSE|E_NOTICE|E_CORE_ERROR|E_CORE_WARNING|E_COMPILE_ERROR|E_COMPILE_WARNING
            |E_USER_ERROR|E_USER_WARNING|E_USER_NOTICE|E_STRICT|E_RECOVERABLE_ERROR;
        if(E_ALL === $bitFlags || ($bitFlags & $errors)) {
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
        }
        // If php.ini has error_reporting set non-zero and headers are already sent an error 
        // may have been trigerred and output started before the session handler was started.
        if(headers_sent()) {
            $er = error_get_last();
            $msg = is_array($er) ? ($er['message'] ?? '') : '';
            exit(($msg && preg_match('/file\\s+uploads.*exceeded/', strtolower($msg))) ? "Upload fewer files" : "Cannot continue: {$msg}}");
        }
        
        if($this->_options['startsession']) {
            // Expire on 09-Jan-2038 See: https://en.wikipedia.org/wiki/Year_2038_problem
            $value = $this->_options['sessionlifetime'] ?? null;
            if(is_string($value) && 'auto' === strtolower(trim($value))) {
                $life = mktime(0, 0, 0, 1, 9, 2038) - time();
            }
            elseif(is_numeric($value)) {
                $value = intval($value);
                if($value >= 0) {
                    $life = $value;
                }
            }
            if(isset($life)) {
                session_set_cookie_params($life);
            }
            $this->_startSession();
        }
        
        /**
         * Start output buffer to capture content generated.
         */
        ob_start();
        register_shutdown_function(function() {
            // Skip output if a Location redirect() was called.
            if($this->_redirect) {
                ob_end_clean();
                return;
            }
            if($this->_options['renderthisfile']) {
                $this->renderPhpFile($this->_options['renderthisfile']);
            }
            /**
             * Output
             *  The HTML head section.
             *  Whatever is left in the output buffer.
             *  The HTML tail section.
             */
            /**
             * Get content captured in the output buffer.
             */
            $content = ob_get_clean();
            // Output the JS scripts followed by the BODY and HTML end tabs.
            echo $this->getHtmlHead() . "\n" . $content . "\n" . $this->getHtmlTail() ;
        });
    }

    /**
     * 
     * @param int $bitFlags One or more 'E_*' constants OR'd.
     * @return int Returns the previous error reporting.
     */
    public function setErrorReporting(int $bitFlags) {
        return error_reporting($bitFlags);
    }
    
    /**
     * Set an option
     * @param string $name
     * @param mixed  $args
     * @return $this
     * @throws RuntimeException
     */
    public function __call(string $name, mixed $args): mixed {
        $l = strlen($name);
        if($l > 3) {
            $set = 'set' === substr($name, 0, 3);
            if($set || ('get' === substr($name, 0, 3))) {
                $property = strtolower(substr($name, 3));
                if(array_key_exists($property, $this->_properties)) {
                    if($set) {
                        $this->_options[$property] = (is_array($args) && count($args)) ? reset($args) : $args;
                        return $this;
                    }
                    return $this->_options[$property];
                }
            }
        }
        throw new RuntimeException("In " . __CLASS__ . "::__call(): method not found: '{$name}'");
    }

    /**
     *
     * @param \Throwable $ex
     */
    protected function _formatStackTrace($ex) {
        if(is_object($ex)) {
            if(method_exists($ex, 'getTrace')) {
                $trace = $ex->getTrace();
                $rowTemplate = '<tr><td>#%s</td><td>%s</td><td>%s</td></tr>';
                $index = 0;
                $rows = [];
                foreach($trace as $data) {
                    $function = $data['function'] ?? null;
                    $function = empty($function) ? '(no function)' : ($function . '()');
                    $rows[] = sprintf($rowTemplate, $index++, ($data['file'] ?? '(no file)')
                        . '(' . ($data['line'] ?? 'no line') . ')', $function);
                }
                $rows = implode("\n", $rows);
                if(method_exists($ex, 'getMessage')) {
                    $message = $ex->getMessage();
                    if(method_exists($ex, 'getLine')) {
                        $message .= ' on line ' . $ex->getLine();
                    }
                }
                else {
                    $message = 'Unknown message: getMessage() not available.';
                }
                $table = <<<EOD
<table><tbody>
    <tr style="padding:0px">
        <td colspan="3" style="padding:0px;background-color:orange;font-weight:bold">
            <span style="padding:0px;background-color:red">(!)</span>
            <span><span style="background-color:orange">{$message}</span>
        </td>
    </tr>
    <tr style="padding:0px">
        <td colspan="3" style="padding:0px;background-color:wheat;font-weight:bold">
            Call Stack:
        </td>
    </tr>
    <tr>
        <th>&nbsp</th><th>FILE</th><th>FUNCTION</th>
    </tr>
    {$rows}
</tbody></table>'
EOD;
            }
        }
        return $table;
    }

    /**
     * Parses options passed to this object.
     * @param array $options
     * @return $this;
     * @throws RuntimeException
     */
    protected function _parseOptions(array $options = []) {

        $lcOptions = array_change_key_case($options);

        $properties = $this->_properties;
        foreach($properties as $propKey => $value) {
            if(isset($lcOptions[$propKey])) {
                switch($propKey) {
                case 'renderthisfile':
                case 'pagetitle':
                    if(! empty($lcOptions[$propKey])) {
                        $properties[$propKey] = str_replace('{date}', date('Y'), $lcOptions[$propKey]);
                    }
                    break;
                case 'cssframework':
                    $properties[$propKey] = (false === $lcOptions[$propKey]) ? false : trim(strval($lcOptions[$propKey]));
                    break;
                case 'jqueryversion':
                    if(false === $lcOptions[$propKey]) {
                        $properties[$propKey] = false;
                    }
                    else {
                        $value = trim(strval($lcOptions[$propKey]));
                        if(preg_match('/^[0-9]\\.[0-9]\\.[0-9]$/', $value)) {
                            $properties[$propKey] = $value;
                        }
                        else {
                            $v = (strlen($value) && is_numeric($value[0])) ? $value[0] : $value;
                            switch($v) {
                            case '2';
                                $properties[$propKey] = '2.2.5';
                                break;
                            case '3';
                                $properties[$propKey] = '3.6.1';
                                break;
                            case 'none':
                            case 'false':
                                $properties[$propKey] = false;
                                break;
                            default:
                                throw new RuntimeException("Bad property specified for property '{$propKey}'");
                            }
                        }
                    }
                    break;
                default:
                    // brand
                    // footer
                    $properties[$propKey] = $lcOptions[$propKey];
                }
                unset($lcOptions[$propKey]);
            }
        }

        $this->_options = $properties;

        if(count($lcOptions)) {
            $keys = implode(", ", array_keys($lcOptions));
            throw new RuntimeException("Unrecognized option(s) '{$keys}'");
        }
        return $this;
    }

    /**
     * Returns the HTML head scripts;
     * @return string
     */
    public function getHtmlHead() {
        $scriptArray = [];
        $options = $this->_options;
        $elm = new Element();
        /**
         * Add bootstrap CSS url.
         */
        if(! Types::isBlank($options['cssframework']) && false !== $options['cssframework']) {
            $scriptArray[] = $this->cssFrameworks()->get($options['cssframework'])->getScript('css');
        }
        // Add fontawesome url
        if(! Types::isBlank($options['fontawesome'])) {
            $value = $options['fontawesome'];
            $include = true;
            $useDefault = false;
            if(is_string($value)) {
                if(is_numeric($value)) {
                    $include = $useDefault = intval($value);
                }
                elseif('true' === strtolower($value)) {
                    $useDefault = true;
                }
                elseif('false' === strtolower($value)) {
                    $include = false;
                }
            }
            else {
                $include = $useDefault = (bool)$value;
            }
            if($include) {
                $attr = [
                    'id' => 'font-awesome-style-link',
                    'href' => $useDefault ? $this->_fontAwesomeUrl : $value,
                    'media' => 'screen',
                    'rel' => 'stylesheet',
                    'type' => 'text/css'
                    ];
                $scriptArray[] = $elm->render('link', '', $attr);
            }
        }
        
        /**
         * Add stylesheet urls.
         */
        $index = 0;
        foreach($this->stylesheetFiles() as $file) {
            if(! preg_match('~^<link.*/>~i', $file)) {
                $attr['id'] = 'style-link-' . ++$index;
                $attr['href'] = $file;
                $file = $elm->render('link', '', $attr);
            }
            $scriptArray[] = $file;
        }
        $styleSheetFiles = implode("\n", $scriptArray);

        /**
         * Add CSS stylesheets.
         */
        $styles = $this->stylesheets();
        // Add some custom default styles.
        $css = <<<EOD
table, td {
    border:thin solid silver;
    padding:.25em;
}
.message{
    background-color:red;
    color:white;
    padding:1em
}
.hidden{
    display:none
}
.button {
    display:inline-block;
    background-color:orange;
    color:white;
    padding:.25em;
    border-radius: 1em;
    cursor:pointer;
}
.container {
    max-width:{$options['wrapperwidth']};
}
EOD;
        $styles->add($css, ['noDuplicates' => true]);
        $styleScripts = implode("\n", $styles->getArrayCopy());
        $styleSheets = "\n<style>\n{$styleScripts}\n</style>";

        $element = new Element();
        $elmArray = [];
        foreach($this->scripts() as $k => $attributes) {
            if(is_array($attributes)) {
                $elmArray[] = $element('script', '', $attributes, true);
            }
        }
        $elements = implode("\n", $elmArray);

        $return = <<<EOD
<!doctype html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-gb" lang="en-gb" dir="ltr" >
<head>
<meta charset="utf-8" />
<title>{$options['pagetitle']}</title>
{$elements}
{$styleSheetFiles}{$styleSheets}
</head>
<body>

EOD;
        $navbar = $this->navbar()->render($options);
        if($navbar) {
            $return .= $navbar;
        }

        if(! empty($options['wrapperclass'])) {
            $return .= <<<EOD
<!-- BEGIN wrapper class {$options['wrapperclass']} -->
<div class="{$options['wrapperclass']}">
EOD;
        }
        
        $messages = $this->getAllAlertsHtml();
        if(! empty($messages)) {
            $return .= $messages;
        }
        return $return;
    }

    /**
     * Returns the HTML tail scripts;
     * @return string
     */
    public function getHtmlTail() {
        $options = $this->_options;
        $htmlScripts = [];

        if(! empty($options['wrapperclass'])) {
            $htmlScripts[] = "</div>\n<!-- END wrapper class {$options['wrapperclass']} -->\n";
        }

        if(! empty($options['footer'])) {
            $htmlScripts[] = <<<EOD
<!-- START Footer -->
<div class="footer-copyright text-center p-4 bg-light text-muted">{$options['footer']}</div>
<!-- END Footer -->

EOD;
        }

        /**
         * Add the clipboard widget JS if selectors are present.
         */
        $clipboard = $this->clipboard();
        if($clipboard->getSelectorCount()) {
            $this->scripts()->add($clipboard->getJsScript());
        }

        /**
         * Add javascript files.
         */
        $scriptFiles = [];
        $elm = new Element();
        /**
         * Add jquery references.
         */
        if($options['jqueryversion']) {
            $url = sprintf($this->_jqueryUrl, $options['jqueryversion']);
             // Note: JavaScript is assumed; don't include 'type'=>'text/javascript'
            $scriptFiles[] = $elm->render('script', '', ['src' => $url], true);
        }
        foreach($this->scriptfiles() as $fileSpec) {
            if($fileSpec && 'none' !== strtolower($fileSpec)) {
                if(! preg_match('~^<script.*</script>~i', $fileSpec)) {
                     // Note: JavaScript is assumed; don't include 'type'=>'text/javascript'
                    $fileSpec = $elm->render('script', '', ['src' => $fileSpec], true);
                }
                $scriptFiles[] = $fileSpec;
            }
        }
        /**
         * Add bootstrap JS
         */
        if($options['cssframework']) {
            $parts = explode('.', $options['cssframework']);
            $scriptFiles[] = $this->cssFrameworks()->get($parts[0])->getScript('js', $parts[1]);
        }
        $htmlScripts[] = implode("\n", $scriptFiles);

        /*
         * |--------------------------------------------------------------------
         * | The javascripts
         * |--------------------------------------------------------------------
         * |
         * | Defer all javascript execution until the document is 'ready' using  $(function(){ 
         * |
         */
        $jsScripts = [];
        foreach($this->scripts() as $k => $v) {
            if(! is_array($v)) {
                $jsScripts[] = $v;
            }
        }
        $jScripts = implode("\n", $jsScripts);
        $script = <<<EOD
                
$(function(){
{$jScripts}
});

EOD;
         // Note: JavaScript is assumed; don't include 'type'=>'text/javascript'
        $inlineScripts = "\n" . $elm->render('script', $script, [], true);
        $htmlScripts[] = $inlineScripts;

        $htmlScripts[] = "</body></html>";
        $return = implode("\n", $htmlScripts);
        return $return;
    }

    /**
     * 
     * @param string $url
     */
    public function redirect(string $url) {
        $this->_redirect = true;
        header('Location: ' . $url);
    }
    
    /**
     * Returns the styles collection object.
     * @return SimpleCollection
     */
    public function stylesheets() {
        if(! isset($this->_styleSheets)) {
            $this->_styleSheets = new SimpleCollection();
        }
        return $this->_styleSheets;
    }

    /**
     * Returns the styles collection object.
     * @return SimpleCollection
     */
    public function stylesheetFiles() {
        if(! isset($this->_styleSheetFiles)) {
            $this->_styleSheetFiles = new SimpleCollection();
        }
        return $this->_styleSheetFiles;
    }

    /**
     * Returns the scripts collection object.
     * @return SimpleCollection
     */
    public function scripts() {
        if(! isset($this->_scripts)) {
            $this->_scripts = new SimpleCollection();
        }
        return $this->_scripts;
    }

    /**
     * Returns the scripts collection object.
     * @return SimpleCollection
     */
    public function scriptfiles() {
        if(! isset($this->_scriptFiles)) {
            $this->_scriptFiles = new SimpleCollection();
        }
        return $this->_scriptFiles;
    }

    /**
     * ALIAS of stylesheets()
     * Returns the styles collection object.
     * @return SimpleCollection
     */
    public function styles() {
        return $this->stylesheets();
    }

    /**
     * Returns the modules collection object.
     * @return SimpleCollection
     */
    public function modules() {
        if(! isset($this->_modules)) {
            $this->_modules = new SimpleCollection();
        }
        return $this->_modules;
    }

    /**
     * Returns the css frameworks object.
     * @return CssFrameworks
     */
    public function cssFrameworks() {
        if(! isset($this->_cssFrameworks)) {
            $this->_cssFrameworks = new CssFrameworks();
        }
        return $this->_cssFrameworks;
    }

    /**
     * Returns the css frameworks object.
     * @return Http
     */
    public function http() {
        if(! isset($this->_http)) {
            $this->_http = new Http();
        }
        return $this->_http;
    }

    /**
     * Returns the css frameworks object.
     * @return Navbar
     */
    public function navbar() {
        if(! isset($this->_navbar)) {
            $this->_navbar = new Navbar();
        }
        return $this->_navbar;
    }

    public function clipboard() {
        if(! isset($this->_clipboard)) {
            $this->_clipboard = new Clipboard();
        }
        return $this->_clipboard;
    }

    /**
     * Returns a desription of the underlying platform.
     * @return string
     */
    public function getPlatformDescription() {
        return 'PHP ' . PHP_VERSION . ($this->_options['cssframework']
            ? (': CSS ' .  $this->_options['cssframework'])
            : ": No CSS framework specified");
    }

    /**
     * Start PHP sessions.
     * @return boolean
     */
    protected function _startSession() {
        $savePath = session_save_path();
        if(! is_dir($savePath)) {
            $msg = "Session save path not found. Check PHP.ini 'session.save_path' setting: [{$savePath}]";
            trigger_error($msg, E_USER_NOTICE);
        }
        else {
            $sessionActive = (session_status() === PHP_SESSION_ACTIVE || session_start());
            return $sessionActive ? true : false;
        }
    }
}
