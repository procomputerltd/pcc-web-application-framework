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
use Procomputer\Pcclib\Messages\Messages;
use Procomputer\Pcclib\Messages\Message;
use Procomputer\Pcclib\Messages\MessageStore;

use JsonSerializable;
use Traversable;
use Throwable;

trait CommonUtilities {
    
    use Messages;
    
    public $indent = 0;
    public $cssFrameworkRelease = 5;

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
     * Get alert dialog box HTML.
     * @return string
     */
    public function getAlertsHtml() : string {
        return $this->getMessagesHtml($this->getMessages());
    }
        
    /**
     * Returns HTML messages dialog box.
     * @param array $messages
     * @return string
     */
    public function getMessagesHtml(array $messages) : string {
        /* alert alert-primary" role="alert">
            primary
            secondary
            success
            danger
            warning
            info
            light
            dark
        <div class="alert alert-dismissible alert-warning" role="alert">
            <strong><?= ucfirst($alert) ?></strong><br />
            <button type="button" class="btn-close close" data-bs-dismiss="alert" data-dismiss="alert">
                <span aria-hidden="true">&times;</span>
            </button>
            Message here
        </div>
        */        
        $element = new Element();
        $button = new Button();
        $div = new Div();
        $span = new Span();
        $closeX = ($this->cssFrameworkRelease < 5) ? $span->render('&times;', ['aria-hidden' => 'true']) : '';
        $buttonHtml = $button->render($closeX, ['type' => 'button',  'class' => 'btn-close close',  'data-bs-dismiss' => 'alert',  'data-dismiss' => 'alert']);
        $messageList = [];
        foreach($messages as $class => $alerts) {
            foreach($alerts as $title => $messages) {
                $alert = $element->render('strong', $title, [], true) . '<br />';
                $attributes = ['class' => "alert alert-{$class} alert-dismissible", 'role' => 'alert'];
                $messageList[] = $div->render($alert . $buttonHtml . implode("<br>\n", (array)$messages), $attributes);
            }
        }
        return implode("\n", $messageList);
    }
        
    /**
     * Add an alert message or multiple alert messages to the message queue.
     * @param string|array  $messages   Messages to add.
     * @param string        $alertClass (optional) Alerts Bootstrap class like 'warning', 'danger'
     * @param string        $title      (optional) Alert title like 'NOTICE:'. Unspecified uses the alert class.
     * @return $this
     */
    public function enqueueMessage(string|array|Traversable|Throwable|Message $messages, string $alertClass = 'warning', string $title = '') {
        $messageStore = new MessageStore();
        $key = md5(get_class($this));
        $sessval = $_SESSION[$key] ?? null;
        if(! is_array($sessval)) {
            $sessval = [];
        }
        else {
            $messageStore->addMessage($sessval);
        }
        $messageStore->addMessage($messages, $alertClass, $title);
        
        $_SESSION[$key] = $messageStore->toArray();
        return $this;
    }
    
    /**
     * Returns messages in the message queue.
     * @return MessageStore
     */
    public function getEnqueuedMessages(bool $clearEnqueuedMessages = false) : MessageStore {
        $messageStore = new MessageStore();
        $key = md5(get_class($this));
        $sessval = $_SESSION[$key] ?? null;
        if(! is_array($sessval)) {
            $sessval = [];
        }
        else {
            $messageStore->addMessage($sessval);
        }
        if($clearEnqueuedMessages) {
            unset($_SESSION[$key]);
        }
        return $messageStore;
    }
    
    /**
     * Return enqueued messages dialog box HTML.
     * @return string
     */
    public function getEnqueuedMessagesHtml(bool $clearEnqueuedMessages = true) : string {
        return $this->getMessagesHtml($this->getEnqueuedMessages($clearEnqueuedMessages));
    }
        
    /**
     * Return ALL enqueued and regular messages dialog box HTML.
     * @return string
     */
    public function getAllAlertsHtml(bool $clearEnqueuedMessages = true) : string {
        $messages = $this->messageStore()->merge($this->getEnqueuedMessages($clearEnqueuedMessages))->toArray();
        // $storage[$class][$title][] = $messages;
        foreach($messages as $class => $titles) {
            foreach($titles as $title => $msg) {
                if(! isset($messages[$class][$title])) {
                    $messages[$class][$title] = (array)$msg;
                }
                else {
                    array_merge($messages[$class][$title], (array)$msg);
                }
            }
        }
        return $this->getMessagesHtml($messages);
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
    public function renderPhpFile(string $file, array $vars = []) {
        if(! is_file($file)) {
            throw new \InvalidArgumentException("'file' parameter is not a file");
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

    public function addLastPhpErrorAlert(string $default = 'unknown error') {
        $msg = $this->getLastPhpErrorMessage($default);
        $this->addAlert($msg, 'danger');
        return $this;
    }
    
    /**
     * Sets the last PHP error info to $this->lastError
     * @param string $default
     * @return string
     */
    public function getLastPhpErrorMessage(string $default = 'unknown error') {
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
    protected function _getArrayableItems(mixed $items) {
        if (is_array($items)) {
            return $items;
        } elseif ($items instanceof JsonSerializable) {
            return (array) $items->jsonSerialize();
        } elseif ($items instanceof Traversable) {
            return iterator_to_array($items);
        }
        return (array) $items;
    }
}