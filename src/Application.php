<?php
namespace Procomputer\WebApplicationFramework;

use Procomputer\Pcclib\Types;
use Procomputer\Pcclib\Html\Element;
use Procomputer\WebApplicationFramework\CssFrameworks\CssFrameworks;
use Procomputer\WebApplicationFramework\CssFrameworks\Navbar;
use Procomputer\WebApplicationFramework\Db\Db as AppDatabase;
use Procomputer\WebApplicationFramework\Http;
use Procomputer\WebApplicationFramework\Widgets\BusyIndicator;
use Procomputer\WebApplicationFramework\Widgets\Clipboard;
use ModernPHPException\ModernPHPException;

/**
 * @method Application setRenderThisFile(string $arg)
 * @method Application setPageTitle(string $arg)
 * @method Application setCssFramework(int $arg)
 * @method Application setJqueryVersion(string $arg)
 * @method Application setStartSession(boolean $arg)
 * @method Application setStyleSheets(array $arg)
 * @method Application setScripts(array $arg)
 * @method Application setWrapperClass(string $arg)
 * @method Application setWrapperWidth(string $arg)
 * @method Application setErrorReporting(int $arg)
 * @method Application setStyleSheetFiles(array $arg)
 * @method Application setScriptFiles(array $arg)
 */
class Application {

    use CommonUtilities;

    protected $_defaults = [
        'cssframework' => 'bootstrap.5',
        'errorreporting' => E_ALL,
        'fontawesome' => false,
        'footer' => '',
        'brand' => '',
        'jqueryversion' => '3.6.0',
        'pagetitle' => '',
        'renderthisfile' => '',
        'scriptfiles' => [],
        'scripts' => [],
        'sessionlifetime' => 'auto', // auto = now until 09-Jan-2038
        'startsession' => true,
        'stylesheetfiles' => [],
        'stylesheets' => [],
        'wrapperclass' => '', // CSS class used in a DIV around the content. Normally 'container'
        'wrapperwidth' => '1720px',
        ];

    protected $_jqueryUrl = 'https://code.jquery.com/jquery-%s.js';
    protected $_fontAwesomeUrl = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.3.0/css/all.min.css';

    /**
     * Application options.
     * @var array
     */
    protected $_options = [];

    /**
     * SimpleCollection object; storage for scripts.
     * @var SimpleCollection
     */
    protected $_styles;

    /**
     * SimpleCollection object; storage for scripts.
     * @var SimpleCollection
     */
    protected $_scripts;

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
     * BusyIndicator object.
     * @var BusyIndicator
     */
    protected $_busyIndicator;

    /**
     * BusyIndicator object.
     * @var Clipboard
     */
    protected $_clipboard;

    protected $_redirect = false;
    
    /**
     *
     * @param array   $dbConfig  Configuration parameters.
     * @return \Procomputer\WebApplicationFramework\Db\Db
     * @throws \RuntimeException
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
        if(class_exists('ModernPHPException\ModernPHPException')) {
            $exc = new ModernPHPException(['title' => 'Pro Computer']);
            $exc->start();
        }

        /*
        E_ERROR             E_WARNING           E_PARSE             E_NOTICE
        E_CORE_ERROR        E_CORE_WARNING      E_COMPILE_ERROR     E_COMPILE_WARNING
        E_USER_ERROR        E_USER_WARNING      E_USER_NOTICE       E_STRICT
        E_RECOVERABLE_ERROR E_DEPRECATED        E_USER_DEPRECATED   E_ALL
        */
        $errorReporting = $this->_defaults['errorreporting'] ?? null;
        if(! is_numeric($errorReporting)) {
            $errorReporting = E_ALL;
        }
        $errors = E_ERROR|E_WARNING|E_PARSE|E_NOTICE|E_CORE_ERROR|E_CORE_WARNING|E_COMPILE_ERROR|E_COMPILE_WARNING
            |E_USER_ERROR|E_USER_WARNING|E_USER_NOTICE|E_STRICT|E_RECOVERABLE_ERROR;
        if(E_ALL === $errorReporting || ($errorReporting & $errors)) {
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
        }
        error_reporting($errorReporting);

        if(! empty($options)) {
            $this->_parseOptions($options);
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
     * Set an option
     * @param string $name
     * @param mixed  $args
     * @return $this
     * @throws RuntimeException
     */
    public function __call($name, $args) {
        $l = strlen($name);
        if($l > 3) {
            $set = 'set' === substr($name, 0, 3);
            if($set || ('get' === substr($name, 0, 3))) {
                $property = strtolower(substr($name, 3));
                if(isset($this->_defaults[$property])) {
                    if($set) {
                        $this->_options[$property] = (is_array($args) && count($args)) ? reset($args) : $args;
                        return $this;
                    }
                    return $this->_options[$property];
                }
            }
        }
        throw new \RuntimeException("In " . __CLASS__ . "::__call(): method not found: '{$name}'");
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
     * @throws \RuntimeException
     */
    protected function _parseOptions($options) {

        $lcOptions = (null === $options) ? [] : array_change_key_case((array)$options);

        $properties = $this->_defaults;
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
                                throw new \RuntimeException("Bad property specified for property '{$propKey}'");
                            }
                        }
                    }
                    break;
                case 'stylesheets':
                case 'scripts':
                    $scripts = [];
                    foreach((array)$lcOptions[$propKey] as $val) {
                        if(! Types::isBlank($val)) {
                            $scripts[] = $val;
                        }
                    }
                    $properties[$propKey] = $scripts;
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
            throw new \RuntimeException("Unrecognized option(s) '{$keys}'");
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
        // Add bootstrap CSS
        // <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" 
        //   integrity="sha512-z3gLpd7yknf1YoNbCzqRKc4qyor8gaKU1qmn+CShxbuBusANI9QpRohGBreCFkKxLhei6S9CQXFEbbKuqLg0DA==" 
        //  crossorigin="anonymous" referrerpolicy="no-referrer" />
        if(! Types::isBlank($options['cssframework']) && false !== $options['cssframework']) {
            $scriptArray[] = $this->cssFrameworks()->get($options['cssframework'])->getScript('css');
        }
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

        $index = 0;
        foreach((array)$options['stylesheetfiles'] as $file) {
            if(! preg_match('~^<link.*/>~i', $file)) {
                $attr['id'] = 'style-link-' . ++$index;
                $attr['href'] = $file;
                $file = $elm->render('link', '', $attr);
            }
            $scriptArray[] = $file;
        }
        $styleSheetFiles = implode("\n", $scriptArray);

        $styles = $this->styles();
        $styles->add(<<<EOD
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
EOD, true);
        if(! empty($options['stylesheets'])) {
            $styles->add($options['stylesheets']);
        }
        $styleSheets = "\n<style>\n{$styles->getString()}\n</style>";

        $return = <<<EOD
<!doctype html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-gb" lang="en-gb" dir="ltr" >
<head>
<meta charset="utf-8" />
<title>{$options['pagetitle']}</title>
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
        $scripts = [];

        if(! empty($options['wrapperclass'])) {
            $scripts[] = "</div>\n<!-- END wrapper class {$options['wrapperclass']} -->\n";
        }

        if(! empty($options['footer'])) {
            $scripts[] = <<<EOD
<!-- START Footer -->
<div class="footer-copyright text-center p-4 bg-light text-muted">{$options['footer']}</div>
<!-- END Footer -->

EOD;
        }
        /**
         * Add the dialog widget.
         */
        $scripts[] = $this->_getDialogBox();

        /**
         * Add the clipboard widget JS if selectors are present.
         */
        $clipboard = $this->clipboard();
        if($clipboard->getSelectorCount()) {
            $this->scripts()->add($clipboard->getJsScript());
        }

        $scriptFiles = [];
        $elm = new Element();
        if($options['jqueryversion']) {
            $url = sprintf($this->_jqueryUrl, $options['jqueryversion']);
            $scriptFiles[] = $elm->render('script', '', ['type' => 'text/javascript', 'src' => $url], true);
        }
        foreach((array)$options['scriptfiles'] as $fileSpec) {
            if($fileSpec && 'none' !== strtolower($fileSpec)) {
                if(! preg_match('~^<script.*</script>~i', $fileSpec)) {
                    $fileSpec = $elm->render('script', '', ['type' => 'text/javascript', 'src' => $fileSpec], true);
                }
                $scriptFiles[] = $fileSpec;
            }
        }
        // Add bootstrap JS
        // <script src="https://cdn.jsdelivr. . . .min.js" integrity="sha384" crossorigin="anonymous"></script>
        if($options['cssframework']) {
            $parts = explode('.', $options['cssframework']);
            $scriptFiles[] = $this->cssFrameworks()->get($parts[0])->getScript('js', $parts[1]);
        }
        $scripts[] = implode("\n", $scriptFiles);

        if(! empty($options['scripts'])) {
            $this->scripts()->add($options['scripts']);
        }
        $script = <<<EOD

if(undefined !== jQuery) {
    (function($) {
{$this->scripts()->getString()}
    })(jQuery);
};

EOD;
        $inlineScripts = "\n" . $elm->render('script', $script, ['type' => 'text/javascript'], true);
        $scripts[] = $inlineScripts;

        $scripts[] = "</body></html>";
        $return = implode("\n", $scripts);
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
     * Returns boolen true or false for a value including string 'true' and 'false'
     * @param type $mixed
     * @return type
     */
    protected function _getDialogBox() {
        $this->scripts()->add(file_get_contents(__DIR__ . '/dialogBox.js'), false, true);
        return $this->renderPhpFile(__DIR__ . '/msgbox.phtml');
    }

    /**
     * Returns the BusyIndicator object.
     * @return BusyIndicator
     */
    public function busyIndicator() {
        if(! isset($this->_busyIndicator)) {
                $this->_busyIndicator = new BusyIndicator($this);
        }
        return $this->_busyIndicator;
    }

    /**
     * Returns the styles collection object.
     * @return SimpleCollection
     */
    public function styles() {
        if(! isset($this->_styles)) {
            $this->_styles = new SimpleCollection();
        }
        return $this->_styles;
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

    /**
     * Returns boolen true or false for a value including string 'true' and 'false'
     * @param type $mixed
     * @return type
     */
    protected function _getBoolVal($mixed) {
        if(Types::isBool($mixed)) {
            return Types::boolVal($mixed);
        }
        return (is_string($mixed) && 'true' === strtolower(strval($mixed))) ? true : false;
    }
}
