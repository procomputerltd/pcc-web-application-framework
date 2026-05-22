<?php
namespace Procomputer\WebApplicationFramework\Form;
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

use Procomputer\Pcclib\Types;
use Procomputer\Pcclib\Html\Element;
use Procomputer\Pcclib\Html\Form\Checkbox;
use Procomputer\Pcclib\Html\Form\Submit;
use Procomputer\Pcclib\Http\Upload;
use Procomputer\Pcclib\Messages\Messages;
use Procomputer\WebApplicationFramework\SimpleCollection;
use Procomputer\WebApplicationFramework\Exception\InvalidArgumentException;
use Procomputer\WebApplicationFramework\Exception\ElementNotFoundException;
use Procomputer\WebApplicationFramework\Exception\UnsupportedElementTypeException;

class Form extends FormCommon {

    use Messages;
    
    const CALLBACK_FORMAT = 1;
    const CALLBACK_VALIDATE = 2;

    protected $_properties = [
        'name' => '',
        'parent' => '',
        'ispost' => false,
        'format' => false,
        'callback' => false,
        'scripts' => '',
        'folderbrowser' => '',
        'formindex' => 0
    ];

    /**
     * Form elements.
     * @var SimpleCollection
     */
    protected $_elements = null;
    
    /**
     * Form element rendering helper.
     * @var FormHelper
     */
    protected $_formHelper = null;

    protected $_isRendered = false;
    
    /**
     * Constructor
     * @param array $options (optional) Options.
     */
    public function __construct(array $options = []) {
        parent::__construct($options);
        $this->_elements = new SimpleCollection();
        foreach($options as $name => $value) {
            $this->$name = $value;
        }
    }
    
    /**
     * Add a form element.
     * @param string      $name    The element name.
     * @param FormElement $element The FormElement object.
     * @return $this
     */
    public function addElement(FormElement $element) {
        $element->parent = $this;
        $elements = $this->elements();
        $elements->offsetSet($element->name, $element);
        return $this;
    }
    
    /**
     * Remove a form element.
     * @param string $name The element name.
     * @return $this
     */
    public function removeElement(string $name) {
        $elements = $this->elements();
        if($elements->offsetExists($name)) {
            unset($elements[$name]);
        }
        return $this;
    }
    
    /**
     * Returns an element properties array.
     * @param string $name
     * @return array
     */
    public function getElement(string $name): FormElement {
        if(! $this->elements()->offsetExists($name)) {
            $msg = "cannot get element: ";
            if(Types::isBlank($name)) {
                throw new InvalidArgumentException($msg . "the argument that specifies the element name is empty.");
            }
            $var = Types::getVartype($name);
            throw new ElementNotFoundException($msg . "element '{$var}' not found");
        }
        return $this->elements()->offsetGet($name);
    }
    
    /**
     * Returns the form elements.
     * @return SimpleCollection
     */
    public function elements(): SimpleCollection {
        return $this->_elements;
    }
    
    /**
     * Returns the number of form elements.
     * @return int
     */
    public function getElementCount() {
        return $this->elements()->count();
    }
    
    /**
     * Returns rendered element HTML elements sorted by type.
     * @param array $sortBy (optional) Order elements by these types.
     * @return array
     */
    public function getElementsSortedByType(array $sortBy = []) {
        if(empty($sortBy)) {
            // default = text, hidden, password
            $sortBy = ['file', 'select', 'checkbox', 'radio', 'textarea', 'submit'];
        }
        $elements = [];
        foreach($this->elements() as $element) {
            /** @var FormElement $element */
            $i = array_search($element->type, $sortBy);
            $elements[(false === $i) ? 'default' : $sortBy[$i]][] = $element->content;
        }
        return $elements;
    }
    
    /**
     * Returns the form helper.
     * @return FormHelper
     */
    public function getFormHelper() {
        if(null === $this->_formHelper) {
            $this->_formHelper = new FormHelper();
        }
        return $this->_formHelper;
    }
    
    /**
     * Renders form element html scripts.
     * @return $this
     */
    public function render() {
        foreach($this->elements() as $element) {
            /** @var FormElement $element */

            $this->_setElementId($element);
            $this->_resolveProperties($element);

            // SELECT and FILE types have their own input filter code.
            // Use _filter_input() for others.
            switch($element->type) {
            case 'select':
                $this->_createSelectElement($element);
                break;
            case 'file':
                $this->_createFileElement($element);
                break;
            case 'radio':
            case 'checkbox':
                $this->_createCheckboxElement($element);
                break;
            case 'submit':
                $this->_createSubmitElement($element);
                break;
            case 'textarea':
                $this->_createTextareaElement($element);
                break;
            default: // text, hidden, password
                $this->_createTextElement($element);
            }
        }
        $this->_isRendered = true;
        return $this;
    }

    public function isRendered(): bool {
        return $this->_isRendered;
    }
    
    /**
     * Creates an HTML SELECT element.
     * @param FormElement $element
     */
    protected function _createSelectElement(FormElement $element) {
        $options = $element->options;
        if(empty($options) || ! is_array($options)) {
            $options = [];
        }
        $options['selected'] = $element->value;
        $values = $element->values;
        if(empty($values)) {
            $values = ['' => 'NOTHING TO SELECT'];
        }
        
        $this->_formatElementHtml($element);
        
        $elmAttr = $element->attributes()->getArrayCopy();
        $elmAttr['name'] = $element->name;
        $id = $elmAttr['id'];
        // SELECT element does not require a type.
        unset($elmAttr['type']);
        $labelAttributes = ['id' => $id . '_label', 'for' => $id];
        $select = new Select();
        $elementHtml = $select->renderHtml($values, $elmAttr, $options);
        $label = $this->_resolveLabel($element);
        if(! Types::isBlank($label)) {
            $htmlElement = new Element();
            $labelHtml = $htmlElement->render('label', $label, $labelAttributes, true);
            $elementHtml = $labelHtml . "\n" . $elementHtml;
        }
        $element->content = $elementHtml;
        return $this;
    }

    /**
     * Creates an HTML TEXT, PASSWORD or HIDDEN element.
     * @param FormElement $element
     */
    protected function _createTextAreaElement(FormElement $element) {
        $label = $this->_resolveLabel($element);
        
        $this->_formatElementHtml($element);
        
        $elmAttr = $element->attributes()->getArrayCopy();
        $elmAttr['name'] = $element->name;
        $id = $elmAttr['id'];
        $labelAttributes = ['id' => $id . '_label', 'for' => $id];
        $elm = new Element();
        // render($tag, $innerScript = '', array $elmAttr = [], $closeTag = false)
        $elementHtml = $elm->render('textarea', $element->value, $elmAttr, true);
        $labelHtml = $elm->render('label', $label, $labelAttributes, true);
        $elementHtml = $labelHtml . "\n" . $elementHtml;
        $element->content = $elementHtml;
        return $this;
    }

    /**
     * Creates an HTML TEXT, PASSWORD or HIDDEN element.
     * @param FormElement $element
     */
    protected function _createTextElement(FormElement $element) {
        $this->_formatElementHtml($element);
        
        $elmAttr = $element->attributes()->getArrayCopy();
        $elmAttr['name'] = $element->name;
        if(empty($elmAttr['type'])) {
            $elmAttr['type'] = $element->type;
        }
        if('password' === $elmAttr['type']) {
            $elmAttr['value'] = '';
        }
        else {
            $elmAttr['value'] = $element->value;
        }
        $id = $elmAttr['id'];
        $labelAttributes = ['id' => $id . '_label', 'for' => $id];
        $htmlElement = new Element();
        // render($tag, $innerScript = '', array $elmAttr = [], $closeTag = false)
        $elementHtml = $htmlElement->render('input', '', $elmAttr, false);
        // No lables for hidden elements.
        if('hidden' !== $elmAttr['type']) {
            $label = $this->_resolveLabel($element);
            $labelHtml = $htmlElement->render('label', $label, $labelAttributes, true);
            $elementHtml = $labelHtml . "\n" . $elementHtml;
        }
        $element->content = $elementHtml;
        return $this;
    }

    /**
     * Creates an HTML CHECKBOX element.
     * @param FormElement $element
     */
    protected function _createCheckboxElement(FormElement $element) {
        $strValues = $element->value;
        if(! is_array($strValues)) {
            $strValues = [$strValues => $strValues];
        }
        $elmAttr = $element->attributes()->getArrayCopy();
        if(empty($elmAttr['type'])) {
            $elmAttr['type'] = $element->type;
        }
        $values = $element->values;
        if(! is_array($values) && null !== $values) {
            $values = [$values];
        }
        if(empty($values)) {
            $label = $this->_resolveLabel($element);
            $values = [$label => 1];
        }
        $elmName = $multiName = $element->name;
        if($element->multiple) {
            $multiName .= '[]';
        }

        $content = [];
        $formValues = [];
        $index = 0;
        $callback = $this->callback;
        foreach($values as $label => $value) {
            if(is_int($label)) {
                $temp = $label + 1;
                $label = $value;
                $value = $temp;
            }
            $strVal = (string)$value;
            $checked = (false !== array_search($strVal, $strValues));
            $elmAttr['id'] = $elmName . '_' . ++$index;
            if(! $checked) {
                $attrChecked = $elmAttr['checked'] ?? null;
                if($attrChecked) {
                    if(is_string($attrChecked)) {
                        $attrChecked = trim($attrChecked);
                    }
                    if(is_numeric($attrChecked)) {
                        $isChecked = intval($attrChecked);
                    }
                    elseif(is_string($attrChecked)) {
                        $lower = strtolower($attrChecked);
                        $isChecked = 'true' === $lower || 'on' === $lower || 'checked' === $lower;
                    }
                    else {
                        $isChecked = $attrChecked;
                    }
                    if($isChecked) {
                        $checked = 'checked';
                    }
                }
            }
            $formValues[$strVal] = $checked ? true : false;
            $labelAttributes = ['id' => $elmAttr['id'] . '_label', 'for' => $elmAttr['id']];
            if(is_string($this->format)) {
                $format = 'bootstrap' === strtolower(trim($this->format));
            }
            else {
                $format = $this->format ? true : false;
            }
            if($format) {
                $helper = $this->getFormHelper();
                $labelAttributes = (object)$labelAttributes;
                $helper->formatFormElement($element, $labelAttributes);
                $labelAttributes = (array)$labelAttributes;
            }
            elseif(is_callable($callback)) {
                $callback(self::CALLBACK_FORMAT, $element, $this);
            }
            $wrapper = $elmAttr['wrapper'] ?? null;
            unset($elmAttr['wrapper']);
            $checkbox = new Checkbox();
            $html = $checkbox($multiName, $value, $checked, $elmAttr) . ' ' . $label;
            if($wrapper) {
                $html = str_replace('{{wrapper}}', $html, $wrapper);
            }
            // Checkbox::__invoke($name, $value = '1', $checked = false, array $attr = [])
            $content[] = $html;
        }

        $element->content = implode("\n", $content);
        $element->value = $element->multiple ? $formValues : reset($formValues);
        return $this;
    }

    /**
     * Creates an HTML FILE element.
     * @param FormElement $element
     */
    protected function _createFileElement(FormElement $element) {
        
        $this->_formatElementHtml($element);
        
        $elmAttr = $element->attributes()->getArrayCopy();
        $elmAttr['name'] = $element->name;
        $elmAttr['type'] = 'file';
        if(isset($elmAttr['multiple'])) {
            $multi = $this->_getBoolVal($elmAttr['multiple'] ?? false);
        }
        else {
            $multi = $this->_getBoolVal($element->multiple);
        }
        $elmAttr['name'] = $element->name;
        if($multi) {
            $elmAttr['multiple'] = 'true';
            $elmAttr['name'] .= '[]';
        }
        else {
            unset($elmAttr['multiple']);
        }
        $id = $elmAttr['id'];
        $labelAttributes = ['id' => $id . '_label', 'for' => $id];
        // render($tag, $innerScript = '', array $elmAttr = [], $closeTag = false)
        $htmlElement = new Element();
        $elementHtml = $htmlElement->render('input', '', $elmAttr, false);
        $label = $element->label;
        if(is_array($label)) {
            $label = reset($label);
        }
        if(empty(trim($label))) {
            $label = 'Browse files';
        }
        if(! Types::isBlank($label)) {
            $labelHtml = $htmlElement->render('label', $label, $labelAttributes, true);
            $elementHtml = $labelHtml . "\n" . $elementHtml;
        }
        $element->content = $elementHtml;
        $iniGet = ini_get('max_file_uploads');

        $maxFileUploads = is_numeric($iniGet) ? intval($iniGet) : 0;
        if($maxFileUploads < 1) {
            $maxFileUploads = 1;
        }
        $formId = $this->attributes()->offsetGet('id');
        $scripts = <<<EOD
    if(undefined === jQuery) {            
        if(undefined !== console) {
            console.warn('In class Form: jQuery library not found.');
        }
    }
    else {            
        jQuery('#{$formId}').submit(function(event) {
            var numFiles = 0;
            jQuery("form").each(function() {
                jQuery(this).find("input[type='file']").each(function(){
                    numFiles += parseInt(jQuery(this).get(0).files.length)
                });
            });
            if(numFiles >= {$maxFileUploads}){
                event.preventDefault();
                var max = {$maxFileUploads};
                max = max.toString();
                $.submitCancelled = true;
                alert(numFiles + ' is too many files to download. Download ' + max + ' files or less');
                return false;
            }
            return true;
        });
    }
EOD;
        if(false === strpos($this->scripts, $scripts)) {
            $this->scripts .= $scripts . "\n";
        }

        // If $_FILES holds uploaded files process those files.
        $upload = new Upload($_FILES, ['raw' => true]);
        $fileList = $upload->getFiles();
        foreach($fileList as $file) {
            /** @var \Procomputer\Pcclib\Http\File $file */
            /*
            name         (string) Chinese Balloon.jpg
            type         (string) image/jpeg
            size         (int) 21892
            tmp_name     (string) C:\Windows\Temp\phpDCE8.tmp
            error        (int) 0
            full_path    (string) Chinese Balloon.jpg
            errorMessage (string)
            */        
            $element->addError($file->getErrorMessage(), $file->getError());
        }
        $element->value = $fileList;
        return $this;
    }

    /**
     * Creates an HTML SUBMIT element.
     * @param FormElement $element
     */
    protected function _createSubmitElement(FormElement $element) {
        $this->_formatElementHtml($element);
        $label = $element->label;
        if(Types::isBlank($label)) {
            $label = 'Submit';
        }
        $elmAttr = $element->attributes()->getArrayCopy();
        unset($elmAttr['name']);
        $submit = new Submit();
        // __invoke($name, $label = '', array $attr = [])
        $element->content = $submit($element->name, $label, $elmAttr);
        return $this;
    }

    /**
     * 
     */
    protected function _formatElementHtml($element) {
        $format = $this->format;
        if($format) {
            $helper = $this->getFormHelper();
            $elmAttr = $element->attributes()->getArrayCopy();
            $labelAttributes = (object)['id' => $elmAttr['id'] . '_label', 'for' => $elmAttr['id']];
            $helper->formatFormElement($element, $labelAttributes);
        }
        else {
            $callback = $this->callback;
            if(is_callable($callback)) {
                $callback(self::CALLBACK_FORMAT, $element, $this);
            }
        }
    }
    
    /**
     *
     * @param FormElement $element
     * @return $this
     * @throws RuntimeException
     */
    protected function _validateElementProperties(FormElement $element) {
        if(! is_string($element->type) || Types::isBlank($element->type)) {
            $var = Types::getVartype($element->type);
            throw new RuntimeException("Missing or unsupported element type '{$var}'");
        }
        $element->type = strtolower($element->type);
        $element->datatype = $this->_resolveDataType($element->datatype);
        $element->multiple = $this->_getBoolVal($element->multiple);

        $attributes = [];
        $elmAttr = $element->attributes();
        foreach($elmAttr->getArrayCopy() as $k => $v) {
            if(! is_int($k) && ! Types::isBlank($k)) {
                $attributes[$k] = $v;
            }
        }
        $attributes['name'] = $element->name;
        $value = $attributes['id'] ?? null;
        if(Types::isBlank($value)) {
            $value = $element->name . '_id';
        }
        $attributes['id'] = $value;

        switch($element->type) {
        case 'radio':
        case 'checkbox':
        case 'submit':
        case 'button':
        case 'hidden':
        case 'file':
        case 'password':
        case 'text':
            $attributes['type'] = $element->type;
            break;
        case 'textarea':
            unset($attributes['type']);
            break;
        case 'select':
            // SELECT element doesn't require a type attribute.
            unset($attributes['type']);
            break;
        default:
            throw new UnsupportedElementTypeException("Unsupported element type '{$element->type}'");
        }
        $elmAttr->exchangeArray($attributes);
        return $this;
    }

    /**
     *
     * @param FormElement $element
     * @return $this
     */
    protected function _resolveProperties(FormElement $element) {
        $attributes = $element->attributes();
        $setDefault = true;
        switch($element->type) {
        case 'select':
            $element->multiple = $this->_getBoolVal($attributes['multiple'] ?? false);
            break;
        case 'checkbox':
        case 'radio':
            $element->multiple = (is_array($element->values) && count($element->values));
            break;
        case 'file':
        case 'password':
            $setDefault = false;
            break;
        default:
        }
        
        if($setDefault) {
            $default = $element->default;
            if(null !== $default) {
                $element->value = $default;
            }
        }
        
        // Fetch the REQUEST input if available and store in value property
        $requestValue = $this->_filter_input($element);
        if(null === $requestValue || false === $requestValue) {
            $elementValue = $element->value;
        }
        else {
            $this->isPost = true;
            if(is_array($requestValue)) {
                $elementValue = array_map('strval', $requestValue);
            } 
            else {
                $elementValue = $requestValue;
            }
        }
            
        if($element->multiple) {
            if(! is_array($elementValue)) {
                $elementValue = (null === $elementValue) ? [] : [$elementValue];
            }
        }
        elseif(null === $elementValue) {
            $elementValue = '';
        }
        elseif(! is_array($elementValue)) {
            $elementValue = strval($elementValue);
        }
        if('radio' === $element->type && is_array($elementValue) && count($elementValue)) {
            $elementValue = reset($elementValue);
        }
        $element->value = $elementValue;
        return $this;
    }

    private function _resolveLabel(FormElement $element): string {
        $label = $element->label;
        if(is_array($label)) {
            $label = reset($label);
            if(! is_string($label) || Types::isBlank($label)) {
                $label = '';
            }
        }
        if(Types::isBlank($label) && ! Types::isBlank($element->name)) {
            if(false !== strpos($element->name, '_')) {
                $label = ucfirst(str_replace('_', ' ', $element->name));
            }
            else {
                $split = str_split($element->name);
                $label = strtoupper($split[0]);
                array_shift($split);
                foreach($split as $c) {
                    $l = strtolower($c);
                    if($c !== $l) {
                        $label .= ' ';
                    }
                    $label .= $l;
                }
            }
        }
        return $label;
    }
    
    /**
     * Returns true if data is in the $_POST global array.
     * @return boolean
     */
    public function isPost():bool {
        $post = filter_input_array(INPUT_POST);
        return (is_array($post) && ! empty($post));
    }
    
    /**
     * Returns true if data is in the $_POST global array.
     * @return boolean
     */
    public function getFormat() {
        return $this->format;
    }
    
    /**
     * Returns true if data ias in the $_POST global array.
     * @return boolean
     */
    public function setFormat(string|bool $format) {
        $this->format = $format;
        return $this;
    }
    
    /**
     *
     */
    public function saveSession() {
        if(! is_object($this->form) || ! isset($this->form->elements)) {
            $msg = "\$this->form property is not initialized";
            throw new \RuntimeException($msg, 255);
        }
        $sessionActive = (session_status() === PHP_SESSION_ACTIVE || session_start());
        if(! $sessionActive) {
            return false;
        }
        $formName = $this->_setFormNameAndId($this->form);
        $formId = $this->_getFormId($this->form);
        $formsessionhash = md5($formName . '_' . ($formId ?? ($formName . '_id')));
        if(isset($_SESSION) && isset($_SESSION['forms']) && isset($_SESSION['forms'][$formsessionhash])) {
            // Get rid of existing values.
            unset($_SESSION['forms'][$formsessionhash]);
        }
        $formValues = [];
        foreach($this->form->elements as $element) {
            $elementValue = $element->value;
            switch($element->type) {
            case 'radio';
            case 'checkbox';
                if(is_array($elementValue)) {
                    $values = [];
                    foreach($elementValue as $k => $v) {
                        if(true === $v) {
                            $values[] = $k;
                        }
                    }
                    if('radio' === $element->type) {
                        $values = count($values) ? reset($values) : '';
                    }
                    $elementValue = $values;
                }
                break;
            default:    
            }
            $formValues[$this->form->name][$element->name] = $elementValue;
        }
        $_SESSION['forms'][$formsessionhash] = $formValues;
        return $this;
    }

    /**
     *
     */
    public function restoreSession() {
        if(! is_object($this->form) || ! isset($this->form->elements)) {
            $msg = "\$formObject property is not initialized";
            throw new \RuntimeException($msg, 255);
        }
        $formObject = $this->form;
        $sessionActive = (session_status() === PHP_SESSION_ACTIVE || session_start());
        if(! $sessionActive) {
            return false;
        }
        $formName = $this->_setFormNameAndId($formObject);
        $formId = $this->_getFormId($formObject);
        $formsessionhash = md5($formName . '_' . ($formId ?? ($formName . '_id')));
        $sessForms = $_SESSION['forms'] ?? null;
        if(! is_array($sessForms)) {
            return false;
        }
        if(isset($sessForms[$formsessionhash]) && isset($sessForms[$formsessionhash][$formId])
            && is_array($sessForms[$formsessionhash][$formId])) {
            $sessData = $sessForms[$formsessionhash][$formId];
            foreach($formObject->elements as $elmName => $element) {
                if(isset($sessData[$elmName])) {
                    $elementValue = $sessData[$elmName];
                    switch($element->type) {
                    case 'checkbox';
                        if(is_array($elementValue)) {
                            $values = [];
                            foreach($element->values as $v) {
                                $values[$v] = (false !== array_search($v, $elementValue));
                            }
                            $elementValue = $values;
                        }
                        break;
                    case 'radio';
                        if(is_array($elementValue)) {
                            $values = count($elementValue) ? reset($values) : '';
                        }
                        break;
                    default:    
                    }
                    $element->value = $elementValue;
                }
                $formObject->elements[$elmName] = $element;
            }
        }
        $this->form->sessionrestored = true;
        // $_SESSION['forms'][$formsessionhash][$formObject->name] = $elementValue;
        return true;
    }

    /**
     *
     * @param FormElement $element
     * @param int         $type       (optional) One of INPUT_GET, INPUT_POST, INPUT_COOKIE, INPUT_SERVER, or INPUT_ENV.
     */
    protected function _filter_input(FormElement $element, $type = INPUT_POST) {
        $dataType = $element->multiple ? 'array' : ($element->datatype);
        list($filter, $options) = $this->_resolveInputFilter($dataType);
        switch($type) {
        case INPUT_GET:
        case INPUT_COOKIE:
        case INPUT_SERVER:
        case INPUT_ENV:
        default:
            $type = INPUT_POST;
        }
        $value = filter_input($type, $element->name, $filter, $options);
        if(null === $value) {
            return null;
        }
        if(($options & FILTER_REQUIRE_ARRAY) && ! is_array($value)) {
            $value = [];
        }
        return $value;
    }

    /**
     *
     * @param string    $dataType
     * @return array
     */
    protected function _resolveInputFilter($dataType) {
        /*
        FILTER_SANITIZE_EMAIL
        FILTER_SANITIZE_ENCODED
        FILTER_SANITIZE_MAGIC_QUOTES
        FILTER_SANITIZE_ADD_SLASHES
        FILTER_SANITIZE_NUMBER_FLOAT
        FILTER_SANITIZE_NUMBER_INT
        FILTER_SANITIZE_SPECIAL_CHARS
        FILTER_SANITIZE_FULL_SPECIAL_CHARS
        FILTER_SANITIZE_STRING
        FILTER_SANITIZE_STRIPPED
        FILTER_SANITIZE_URL
        FILTER_UNSAFE_RAW

        FILTER_FLAG_STRIP_LOW
        FILTER_FLAG_STRIP_HIGH
        FILTER_FLAG_STRIP_BACKTICK
        FILTER_FLAG_ALLOW_FRACTION
        FILTER_FLAG_ALLOW_THOUSAND
        FILTER_FLAG_ALLOW_SCIENTIFIC
        FILTER_FLAG_NO_ENCODE_QUOTES
        FILTER_FLAG_ENCODE_LOW
        FILTER_FLAG_ENCODE_HIGH
        FILTER_FLAG_ENCODE_AMP
        FILTER_NULL_ON_FAILURE
        FILTER_FLAG_ALLOW_OCTAL
        FILTER_FLAG_ALLOW_HEX
        FILTER_FLAG_EMAIL_UNICODE
        FILTER_FLAG_IPV4
        FILTER_FLAG_IPV6
        FILTER_FLAG_NO_PRIV_RANGE
        FILTER_FLAG_NO_RES_RANGE
        FILTER_FLAG_SCHEME_REQUIRED
        FILTER_FLAG_HOST_REQUIRED
        FILTER_FLAG_PATH_REQUIRED
        FILTER_FLAG_QUERY_REQUIRED
        FILTER_REQUIRE_SCALAR
        FILTER_REQUIRE_ARRAY
        FILTER_FORCE_ARRAY
        */
        $filter = FILTER_DEFAULT;
        $options = 0;
        $dType = $this->_resolveDataType($dataType);
        switch($dType) {
        case 'array':
            $options = FILTER_REQUIRE_ARRAY;
            break;
        case 'int':
            $filter = FILTER_VALIDATE_INT;
            break;
        case 'float':
            $filter = FILTER_VALIDATE_FLOAT;
            break;
        case 'bool':
        case 'boolean':
            $filter = FILTER_VALIDATE_BOOLEAN;
            break;
        case 'email':
            $filter = FILTER_VALIDATE_EMAIL;
            break;
        case 'url':
            $filter = FILTER_VALIDATE_URL;
            break;
        default: // string
            $filter = FILTER_DEFAULT;
        }
        return [$filter, $options];
    }

    protected function _setElementId(FormElement $element): string {
        $attributes = $element->attributes();
        $id = $attributes->offsetExists('id') ? $attributes->offsetGet('id') : null;
        if(Types::isBlank($id)) {
            $id = $element->name . '-id';
            $attributes->offsetSet('id', $id);
        }
        return $id;
    }
    
    /**
     * Returns a string datatype eg 'string' for the type specified in $dataType
     *
     * @param string $dataType
     *
     * @return string Returns a string data type.
     */
    protected function _resolveDataType($dataType) {
        $lcase = strtolower($dataType);
        switch($lcase) {
        case 'int':
        case 'float':
        case 'array':
        case 'email':
        case 'url':
            return $lcase;
        case 'bool':
        case 'boolean':
            return 'boolean';
        }
        return 'string';
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