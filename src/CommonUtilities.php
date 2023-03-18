<?php
namespace Procomputer\WebApplicationFramework;

/* 
 * Copyright (C) 2021 Pro Computer James R. Steel <jim-steel@pccglobal.com>
 * Pro Computer (pccglobal.com)
 * Tacoma Washington USA 253-272-4243
 *
 * This program is distributed WITHOUT ANY WARRANTY; without 
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR 
 * A PARTICULAR PURPOSE. See the GNU General Public License 
 * for more details.
 */
use Procomputer\Pcclib\Html\Div;
use Procomputer\Pcclib\Html\Span;
use Procomputer\Pcclib\Html\Button;
use Procomputer\Pcclib\Html\Element;

use JsonSerializable;
use Traversable;

trait CommonUtilities {
    
    public $indent = 0;

    protected $_alertClasses = [
        'primary',
        'secondary',
        'success',
        'danger',
        'warning',
        'info',
        'light',
        'dark'
    ];
    
    /**
     * 
     * @var array Alert class=>[message] list.
     */
    protected $_alerts = [];
    
    /**
     * Add an alert message or multiple alert message.
     * @param atring|array  $msg   Alerts to add.
     * @param string        $class Alerts Bootstrap class like 'danger'
     * @return $this
     */
    public function addAlert($msg, $class = 'warning') {
        /*    
        alert alert-primary"    role="alert">
        alert alert-secondary"  role="alert">
        alert alert-success"    role="alert">
        alert alert-danger"     role="alert">
        alert alert-warning"    role="alert">
        alert alert-info"       role="alert">
        alert alert-light"      role="alert">
        alert alert-dark"       role="alert">    
        */
        if(is_array($msg)) {
            if(! empty($msg)) {
                foreach($msg as $k => $m) {
                    foreach((array)$m as $s) {
                        $this->_addAlertByClass($s, is_int($k) ? $class : $k);
                    }
                }
            }
            return $this;
        }
        return $this->_addAlertByClass(strval($msg), $class);
    }

    /**
     * Add an alert message.
     * @param atring|array  $msg
     * @param string        $class Alerts Bootstrap class like 'danger'
     * @return $this
     */
    private function _addAlertByClass($msg, $class) {
        $class = strtolower(strval($class));
        if(false === array_search($class, $this->_alertClasses)) {
            $class = reset($this->_alertClasses);
        }
        $this->_alerts[$class][] = $msg;
        return $this;
    }
    
    /**
     * Return alerts set by addAlert()
     * @return array
     */
    public function getAlerts() {
        return $this->_alerts;
    }

    public function getAlertsHtml(int $cssFrameworkRelease = 5) {
        /*
        alert alert-primary"    role="alert">
        alert alert-secondary"  role="alert">
        alert alert-success"    role="alert">
        alert alert-danger"     role="alert">
        alert alert-warning"    role="alert">
        alert alert-info"       role="alert">
        alert alert-light"      role="alert">
        alert alert-dark"       role="alert">    

        <div class="alert alert-dismissible alert-warning" role="alert">
            <strong><?= ucfirst($alert) ?></strong><br />
            <button type="button" class="btn-close close" data-bs-dismiss="alert" data-dismiss="alert">
                <span aria-hidden="true">&times;</span>
            </button>
            Message here
        </div>
        */        
        $messageList = [];
        foreach($this->_alerts as $class => $classMessages) {
            $trimmed = trim($class);
            if(is_array($classMessages)) {
                foreach($classMessages as $msg) {
                    $messageList[$trimmed][] = strval($msg);
                }
            }
            else {
                $messageList['alert alert-danger'][] = strval($classMessages);
            }
            $labels[$trimmed] = ucfirst($class);
        }
        if(! count($messageList)) {
            return '';
        }
        ksort($messageList);
        $element = new Element();
        $span = new Span();
        if($cssFrameworkRelease < 5) {
            $closeX = $span->render('&times;', ['aria-hidden' => 'true']);
        }
        else {
            $closeX = '';
        }
        $button = new Button();
        $buttonHtml = $button->render($closeX, ['type' => 'button',  'class' => 'btn-close close',  'data-bs-dismiss' => 'alert',  'data-dismiss' => 'alert']);
        $div = new Div();
        foreach($messageList as $class => $msgList) {
            $alert = $element->render('strong', $labels[$class], [], true) . '<br />';
            $attributes = ['class' => "alert alert-{$class} alert-dismissible", 'role' => 'alert'];
            $messageList[$class] = $div->render($alert . $buttonHtml . implode("<br>\n", $msgList), $attributes);
        }
        return implode("\n", $messageList);
    }
        
    /**
     * 
     * @param string        $content
     * @param int|string    $indent
     * @return type
     */
    public function indent($content, $indent = null) {
        if(null === $indent) {
            $indent = $this->indent;
        }
        if(is_numeric($indent)) {
            $indent = str_repeat("\t", intval($indent));
        }
        else {
            $indent = strval($indent);
        }
        return str_replace("\n", "\n$indent", $content) . "\n";
    }

    /**
     * Renders a PHP file.
     *
     * @param string $file File to render.
     *
     * @return string Returns the rendered script.
     */
    public function renderPhpFile($file, array $vars = []) {
        if(! is_file($file)) {
            throw new InvalidArgumentException("'file' parameter is not a file");
        }
        extract($vars);

        // Start capturing output into a buffer
        ob_start();

        try {
            // Include the requested template filename in the local scope.
            include $file;
        } catch (\Throwable $ex) {
            ob_end_clean();
            throw $ex;
        }

        // Get the output buffer content and clear buffer.
        return ob_get_clean();
    }

    public function addLastPhpErrorAlert($default = 'unknown error') {
        $msg = $this->getLastPhpErrorMessage($default);
        $this->addAlert($msg, 'danger');
        return $this;
    }
    
    /**
     * Sets the last PHP error info to $this->lastError
     * @param string $default
     * @return string
     */
    public function getLastPhpErrorMessage($default = 'unknown error') {
        /*  
        [type] => 8
        [message] => Undefined variable: a
        [file] => C:\WWW\index.php
        [line] => 2
        */        
        $lastError = error_get_last();
        $msg = $lastError['message'] ?? null;
        if(empty($msg)) {
            $msg = $default;
        }
        $file = $lastError['file'] ?? null;
        if(! empty($file)) {
            $msg .= ' in file ' . $file;
        }
        $line = $lastError['line'] ?? null;
        if(! empty($line)) {
            $msg .= '(' . $line . ')';
        }
        return $msg;
    }
    
    /**
     * Results array of items from Collection or Arrayable.
     *
     * @param  mixed  $items
     * @return array
     */
    protected function _getArrayableItems($items)
    {
        if (is_array($items)) {
            return $items;
        } elseif ($items instanceof JsonSerializable) {
            return (array) $items->jsonSerialize();
        } elseif ($items instanceof Traversable) {
            return iterator_to_array($items);
        }
        return (array) $items;
    }

    protected function _mergeAttributes(array $attributes, array $newAttributes) {
        if(isset($newAttributes['class'])) {
            $str = trim(implode(' ', $this->_getArrayableItems($newAttributes['class'])));
            if(strlen($str)) {
                $attributes['class'] = implode(' ', [trim($attributes['class'] ?? ''), $str]);
            }
            unset($newAttributes['class']);
        }
        return array_merge($attributes, $newAttributes);
    }
}