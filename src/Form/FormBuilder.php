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
// use Procomputer\Pcclib\Html\Form\Button;
use Procomputer\Pcclib\Html\Form\Submit;

use Procomputer\WebApplicationFramework\Form\FormHelper;
use Procomputer\WebApplicationFramework\Form\Select;

class FormBuilder {

    const CALLBACK_FORMAT = 1;
    const CALLBACK_VALIDATE = 2;
    
    public $form = null;

    public $options = [];

    public $debugMode = false;

    protected $_folderBrowser;

    protected $_formIndex = 0;

    protected $_initialized = false;

    /**
     * 
     * @var FormHelper
     */
    protected $_formHelper = null;
    
    /**
     * Constructour
     * @param stdClass|array  $formDefinition (optional) If null you must use setFormdefinition() or init()
     * @param array           $options        (optional)
     */
    public function __construct($formDefinition = null, array $options = []) {
        $lcOptions = array_change_key_case($options);
        $this->setOptions($lcOptions);
        if(null !== $formDefinition) {
            $this->setFormdefinition($formDefinition);
        }
    }

    /**
     * Set FormBuilder form definitions.
     * @param stdClass  $formDefinition
     * @return $this FormBuilder 
     */
    public function setFormdefinition($formDefinition) {
        if(is_array($formDefinition)) {
            $formDefinition = (object)$formDefinition;
        }
        elseif(! is_object($formDefinition)) {
            $msg = "invalid form definition parameter: expecting an object or array";
            throw new \InvalidArgumentException($msg, 255);
        }
        $this->form = $formDefinition;
        // Must be initialized or re-initialized
        $this->_initialized = false;
        return $this;
    }
    
    /**
     * Set FormBuilder options.
     * @param array $options
     * @return $this FormBuilder 
     */
    public function setOptions(array $options) {
        $this->options = $options;
        return $this;
    }
    
    /**
     * Creates form elements.
     * @return $this
     * @throws RuntimeException
     */
    public function init() {
        if($this->_initialized) {
            $msg = "Cannot initialize form: the form was already initialized.";
            throw new \RuntimeException($msg);
        }
        $form = $this->form;
        if(! isset($form->elements) || ! is_array($form->elements) || empty($form->elements)) {
            throw new \RuntimeException("The form properties 'elements' parameter is missing from the form definition or is empty.");
        }
        if(! isset($form->callback)) {
            $form->callback = null;
        }
        $options['usesession'] = isset($options['usesession']) ? boolval($options['usesession']) : false;
        $this->_getFormName($form);
        $this->_getFormId($form);

        if(! isset($form->scripts)) {
            $form->scripts = '';
        }
        $form->hasfiles = false;
        $form->isPost = false;

        $defaults = [
            'name' => '',
            'attributes' => [],
            'datatype' => 'string',
            'type' => 'text',
            'multiple' => false,
            'options' => [],
            'values' => [],
            'errors' => [],
            'id' => '',
            'default' => '',
            'label' => '',
            'formvalue' => '',
            'content' => '',
            'scripts' => ''
            ];
        $elements = [];
        foreach($form->elements as $name => $elmDef) {
            // Convert to object for function reference calls.
            $elmProperties = (object)$defaults;
            // Copy name and other values to the properties;
            $elmProperties->name = $name;
            foreach($elmDef as $k => $v) {
                if(isset($elmProperties->$k)) {
                    $elmProperties->$k = $v;
                }
            }
            // Create $elmProperties->attributes array using existing $elmProperties->attributes if specified.
            // Create $elmProperties->attributes array using existing $elmProperties->attributes if specified.
            $this->_validateElementProperties($elmProperties)
                 ->_resolveProperties($elmProperties, $form);   
            $elements[$name] = $elmProperties;
        }
        $form->elements = $elements;
        
        $this->_initialized = true;
        
        return $this;
    }

    /**
     * Creates form elements.
     * @param stdClass $form    (optional)
     * @return stdClass|boolean
     * @throws RuntimeException
     */
    public function render() {
        if(! $this->_initialized) {
            $this->init();
        }
        if(! is_object($this->form) || ! isset($this->form->elements)) {
            $msg = "The form properties 'elements' parameter is missing from the form definition or is empty.";
            throw new \RuntimeException($msg);
        }
        $form = $this->form;

        $elements = [];
        foreach($form->elements as $name => $elmDef) {
            // Convert to object for function reference calls.
            $elmProperties = (object)$elmDef;

            // SELECT and FILE types have their own input filter code.
            // Use _filter_input() for others.
            switch($elmProperties->type) {
            case 'select':
                $this->_createSelectElement($elmProperties, $form);
                break;
            case 'file':
                $this->_createFileElement($elmProperties, $form);
                break;
            case 'radio':
            case 'checkbox':
                $this->_createCheckboxElement($elmProperties, $form);
                break;
            case 'submit':
                $this->_createSubmitElement($elmProperties, $form);
                break;
            case 'textarea':
                $this->_createTextareaElement($elmProperties, $form);
                break;
            default: // text, hidden, password
                $this->_createTextElement($elmProperties, $form);
            }
            $elements[$name] = $elmProperties;
        }
        $form->elements = $elements;
        $this->form = $form;
        
        return $form;
    }

    /**
     * @return boolean Returns true if the form input valid.
     */
    public function isValid() {
        return true;
    }
    
    /**
     * Returns a value from the '_properties' array referenced by '$name'.
     * @param string $name Property key name.
     * @return mixed
     * @throws \RuntimeException
     */
    public function __get($name) {
        if(! is_object($this->form)) {
            $msg = "\$this->form property is not initialized";
        }
        elseif(isset($this->form->elements) && isset($this->form->elements[$name])) {
            return $this->form->elements[$name];
        }
        elseif(isset($this->form->$name)) {
            return $this->form->$name;
        }
        else {
            $var = Types::getVarType($name);
            $msg = "property/element '{$var}' not found";
        }
        $trace =  debug_backtrace();
        if(isset($trace[0]) && isset($trace[0]['file'])) {
            $file = $trace[0]['file'];
            if(! empty($file)) {
                $msg .= ' in file ' . $file;
                if(! empty($trace[0]['line'])) {
                    $msg .= ' line ' . $trace[0]['line'];
                }
            }
        }
        throw new \RuntimeException($msg, 255);
        // throw new \RuntimeException($msg);
    }

    /**
     * Sets a value in the '_properties' array referenced by '$name'.
     * @param string $name Property key name.
     * @param mixed  $val Property value.
     * @return $this
     * @throws \RuntimeException
     */
    public function __set($name, $val) {
        if(! is_object($this->form)) {
            $msg = "\$this->form property is not initialized";
        }
        elseif(isset($this->form->elements) && isset($this->form->elements[$name])) {
            $this->form->elements[$name] = $val;
            return $this;
        }
        else {
            $var = Types::getVarType($name);
            $msg = "property '{$var}' not found";
        }
        throw new \RuntimeException($msg);
    }

    /**
     * Returns a value from the '_properties' array referenced by '$name'.
     * @param string $name Property key name.
     * @return mixed
     * @throws \RuntimeException
     */
    public function getScripts() {
        if(! is_object($this->form)) {
            $msg = "\$this->form property is not initialized";
            throw new \RuntimeException($msg);
        }
        return isset($this->form->scripts) ? $this->form->scripts : '';
    }
    
    /**
     * Creates an HTML SELECT element.
     * @param stdClass $elmProperties
     * @param array    $formProperties
     */
    protected function _createSelectElement(\stdClass $elmProperties, \stdClass $formProperties) {
        $options = $elmProperties->options;
        if(empty($options) || ! is_array($options)) {
            $options = [];
        }
        $options['selected'] = $elmProperties->formvalue;
        $values = $elmProperties->values;
        if(empty($values)) {
            $values = ['' => 'NOTHING TO SELECT'];
        }
        
        $this->_formatElementHtml($elmProperties, $formProperties);
        
        $attributes = $elmProperties->attributes;
        // SELECT element does not require a type.
        unset($attributes['type']);
        $labelAttributes = ['id' => $attributes['id'] . '_label', 'for' => $attributes['id']];
        
        $select = new Select();
        $elementHtml = $select->renderHtml($values, $attributes, $options);
        if(! Types::isBlank($elmProperties->label)) {
            $element = new Element();
            $labelHtml = $element->render('label', $elmProperties->label, $labelAttributes, true);
            $elementHtml = $labelHtml . "\n" . $elementHtml;
        }
        $elmProperties->content = $elementHtml;
        return $this;
    }

    /**
     * Creates an HTML TEXT, PASSWORD or HIDDEN element.
     * @param stdClass $elmProperties
     * @param array    $formProperties
     */
    protected function _createTextAreaElement(\stdClass $elmProperties, \stdClass $formProperties) {
        $label = $elmProperties->label ?? '';
        if(is_array($label)) {
            $label = reset($label);
        }
        if(Types::isBlank($label)) {
            $label = $elmProperties->name;
        }
        
        $this->_formatElementHtml($elmProperties, $formProperties);
        
        $attributes = $elmProperties->attributes;
        $labelAttributes = ['id' => $attributes['id'] . '_label', 'for' => $attributes['id']];
        $element = new Element();
        // render($tag, $innerScript = '', array $attributes = [], $closeTag = false)
        $elementHtml = $element->render('textarea', $elmProperties->formvalue, $attributes, true);
        $labelHtml = $element->render('label', $label, $labelAttributes, true);
        $elementHtml = $labelHtml . "\n" . $elementHtml;
        $elmProperties->content = $elementHtml;
        return $this;
    }

    /**
     * Creates an HTML TEXT, PASSWORD or HIDDEN element.
     * @param stdClass $elmProperties
     * @param array    $formProperties
     */
    protected function _createTextElement(\stdClass $elmProperties, \stdClass $formProperties) {
        $this->_formatElementHtml($elmProperties, $formProperties);
        
        $attributes = $elmProperties->attributes;
        if(empty($attributes['type'])) {
            $attributes['type'] = $elmProperties->type;
        }
        if('password' === $attributes['type']) {
            $attributes['value'] = '';
        }
        else {
            $attributes['value'] = $elmProperties->formvalue;
        }
        $labelAttributes = ['id' => $attributes['id'] . '_label', 'for' => $attributes['id']];
        $element = new Element();
        // render($tag, $innerScript = '', array $attributes = [], $closeTag = false)
        $elementHtml = $element->render('input', '', $attributes, false);
        // No lables for hidden elements.
        if('hidden' !== $attributes['type']) {
            $label = $elmProperties->label ?? '';
            if(is_array($label)) {
                $label = reset($label);
            }
            if(Types::isBlank($label)) {
                $label = $elmProperties->name;
            }
            $labelHtml = $element->render('label', $label, $labelAttributes, true);
            $elementHtml = $labelHtml . "\n" . $elementHtml;
        }
        $elmProperties->content = $elementHtml;
        return $this;
    }

    /**
     * Creates an HTML CHECKBOX element.
     * @param stdClass $elmProperties
     * @param array    $formProperties
     */
    protected function _createCheckboxElement(\stdClass $elmProperties, \stdClass $formProperties) {
        $strValues = $elmProperties->formvalue ?? [];
        if(! is_array($strValues)) {
            $strValues = [$strValues => $strValues];
        }
        $attributes = $elmProperties->attributes;
        if(empty($attributes['type'])) {
            $attributes['type'] = $elmProperties->type;
        }
        $values = $elmProperties->values ?? null;
        if(! is_array($values) && null !== $values) {
            $values = [$values];
        }
        if(empty($values)) {
            $label = $elmProperties->label ?? null;
            if(Types::isBlank($label)) {
                $label = 'Checkbox';
            }
            $values = [$label => 1];
        }
        $elmName = $multiName = $attributes['name'];
        if($elmProperties->multiple) {
            $multiName .= '[]';
        }

        $content = [];
        $formValues = [];
        $index = 0;
        $callback = $formProperties->callback ?? null;
        foreach($values as $label => $value) {
            if(is_int($label)) {
                $temp = $label + 1;
                $label = $value;
                $value = $temp;
            }
            $strVal = (string)$value;
            $checked = (false !== array_search($strVal, $strValues));
            $attributes['id'] = $elmName . '_' . ++$index;
            $formValues[$strVal] = $checked ? true : false;
            $labelAttributes = ['id' => $attributes['id'] . '_label', 'for' => $attributes['id']];
            $format = $formProperties->format ?? null;
            if($format) {
                $helper = $this->getFormHelper();
                $attrObj = (object)$attributes;
                $labelAttrObj = (object)$labelAttributes;
                $helper->formatFormElements($attrObj, $labelAttrObj);
                $attributes = (array)$attrObj;
                $labelAttributes = (array)$labelAttrObj;
            }
            elseif(is_callable($callback)) {
                $callback(self::CALLBACK_FORMAT, $elmProperties, $this);
            }
            $wrapper = $attributes['wrapper'] ?? null;
            unset($attributes['wrapper']);
            $checkbox = new Checkbox();
            $html = $checkbox($multiName, $value, $checked, $attributes) . ' ' . $label;
            if($wrapper) {
                $html = str_replace('{{wrapper}}', $html, $wrapper);
            }
            // Checkbox::__invoke($name, $value = '1', $checked = false, array $attr = [])
            $content[] = $html;
        }

        $elmProperties->content = implode("\n", $content);
        $elmProperties->formvalue = $elmProperties->multiple ? $formValues : reset($formValues);
        return $this;
    }

    /**
     * Creates an HTML FILE element.
     * @param stdClass $elmProperties
     * @param array    $formProperties
     */
    protected function _createFileElement(\stdClass $elmProperties, \stdClass $formProperties) {
        
        $this->_formatElementHtml($elmProperties, $formProperties);
        
        $attributes = $elmProperties->attributes;
        if(isset($attributes['multiple'])) {
            $multi = $this->_getBoolVal($attributes['multiple'] ?? false);
        }
        else {
            $multi = $this->_getBoolVal($elmProperties->multiple ?? false);
        }
        if($multi) {
            $attributes['multiple'] = 'true';
            $attributes['name'] .= '[]';
        }
        else {
            unset($attributes['multiple']);
        }
        $labelAttributes = ['id' => $attributes['id'] . '_label', 'for' => $attributes['id']];
        // render($tag, $innerScript = '', array $attributes = [], $closeTag = false)
        $element = new Element();
        $elementHtml = $element->render('input', '', $attributes, false);
        $label = $elmProperties->label ?? '';
        if(is_array($label)) {
            $label = reset($label);
        }
        if(empty(trim($label))) {
            $label = 'Browse file';
        }
        if(! Types::isBlank($label)) {
            $labelHtml = $element->render('label', $label, $labelAttributes, true);
            $elementHtml = $labelHtml . "\n" . $elementHtml;
        }
        $elmProperties->content = $elementHtml;
        $iniGet = ini_get('max_file_uploads');

        $maxFileUploads = is_numeric($iniGet) ? intval($iniGet) : 0;
        if($maxFileUploads < 1) {
            $maxFileUploads = 1;
        }
        $formId = $formProperties->attributes['id'];
        $scripts = <<<EOD
if(undefined !== jQuery) {
    (function($) {
        $('#{$formId}').submit(function(event) {
            var numFiles = 0;
            $("form").each(function() {
                $(this).find("input[type='file']").each(function(){
                    numFiles += parseInt($(this).get(0).files.length)
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
    })(jQuery);
}
EOD;
        if(false === strpos($formProperties->scripts, $scripts)) {
            $formProperties->scripts .= $scripts . "\n";
        }

        $files = $_FILES ?? null;
        if(! is_array($files) || ! count($files) || ! isset($files[$elmProperties->name])) {
            $elmProperties->formvalue = [];
            return;
        }
        // The possible properties in each file download.
        /*
            array(5) (
              [name]     => (string) 2018-05-19 Contacts Tacoma Washington Bicycle Club.csv
              [type]     => (string) application/octet-stream
              [tmp_name] => (string) C:\Windows\Temp\php60F2.tmp
              [error]    => (int) 0
              [size]     => (int) 130214
            )
        */
        $fileItems = [];
        foreach($files as $name => $fileData) {
            $fileItems[$name] = $this->_processFileProperties($fileData, $elmProperties);
        }
        $elmProperties->formvalue = $fileItems;
        return $this;
    }

    /**
     *
     * @param array     $fileData
     * @param stdClass  $elmProperties
     */
    protected function _processFileProperties($fileData, $elmProperties) {
        /*  The possible properties in each file download:
            array(5) (
              [name]     => (string) 2018-05-19 Contacts Tacoma Washington Bicycle Club.csv
              [type]     => (string) application/octet-stream
              [tmp_name] => (string) C:\Windows\Temp\php60F2.tmp
              [error]    => (int) 0
              [size]     => (int) 130214
            )
        */
        $fileList = [];
        foreach($fileData as $propName => $values) {
            if(! is_array($values)) {
                $values = [$values];
            }
            $index = 0;
            foreach($values as $value) {
                $fileList[$index++][$propName] = $value;
            }
        }
        $errorList = [
            // UPLOAD_ERR_OK Value 0 = no error
            0 => 'The file is uploaded successfully.',
            // UPLOAD_ERR_INI_SIZE Value 1 = The uploaded file exceeds the upload_max_filesize directive in php.ini.
            1 => 'The uploaded file exceeds the maximim file size.',
            // UPLOAD_ERR_FORM_SIZE Value 2 = The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.
            2 => 'The uploaded file exceeds the maximim file size.',
            // UPLOAD_ERR_PARTIAL Value 3 = The uploaded file was only partially uploaded.
            3 => 'The file download did not complete: the file was only partially downloaded.',
            // UPLOAD_ERR_NO_FILE Value 4 = No file was uploaded. No file was selected using the file browse button.
            4 => 'No file was selected using the file browse button.',
            // UPLOAD_ERR_NO_TMP_DIR Value 6 = Missing a temporary folder.
            6 => 'The temporary file download folder is missing.',
            // UPLOAD_ERR_CANT_WRITE Value 7 = Failed to write file to disk.
            7 => 'The file download did not complete: disk write failed.',
            // UPLOAD_ERR_EXTENSION Value: 8; A PHP extension stopped the file upload. PHP does not provide a way to
            // ascertain which extension caused the file upload to stop; examining the list of loaded extensions with phpinfo() may help.
            8 => 'The file download stopped unexpectedly.',
            // Value 99 = An unknown error code was submitted.
            65535 => 'The file download did not complete: an unknown error code was submitted.',
            65536 => "'tmp_name' file path property is missing from the file download.",
            65537 => "file '%s' not found : file path does not exist",
            65538 => "file '%s' is not readable"
            ];
        foreach($fileList as $key => $fileProperties) {
            if(empty($fileProperties['name'])) {
                $fileProperties['name'] = 'downloaded_file_' . ++$index;
            }
            if($this->debugMode) {
                $keys = array_keys($errorList);
                $fileProperties['error'] = $keys[mt_rand(0, count($keys) - 1)];
            }
            $errno = is_numeric($fileProperties['error']) ? intval($fileProperties['error']) : 0;
            if(! isset($errorList[$errno])) {
                $errno = 65535;
            }
            if(! $errno) {
                $filename = $fileProperties['tmp_name'];
                if(empty($filename)) {
                    $errno = 65536;
                }
                elseif(! is_file($filename)) {
                    $errno = 65537;
                }
                elseif(! is_readable($filename)) {
                    $errno = 65537;
                }
            }
            if($errno) {
                $var = Types::getVartype($filename ?? '');
                $errMsg = sprintf($errorList[$errno], $var);
                if($this->debugMode) {
                    $errMsg = "NOTICE: DEBUG MODE: random error generated in file '" . basename(__FILE__) . ": " . $errMsg;
                }
                $elmProperties->errors[$elmProperties->name]['error'] = [$errno => $errMsg];
            }
            else {
                $errMsg = '';
            }
            $fileProperties['errorMessage'] = $errMsg;
            $fileList[$key] = $fileProperties;
        }
        return $fileList;
    }

    /**
     * Creates an HTML SUBMIT element.
     * @param stdClass $elmProperties
     * @param stdClass $formProperties
     */
    protected function _createSubmitElement(\stdClass $elmProperties, \stdClass $formProperties) {
        $this->_formatElementHtml($elmProperties, $formProperties);

        $attributes = $elmProperties->attributes;
        $elmName = $attributes['name'];
        unset($attributes['name']);
        $label = $elmProperties->label ?? null;
        if(Types::isBlank($label)) {
            $label = 'Submit';
        }
        // __invoke($name, $label = '', array $attr = [])
        $submit = new Submit();
        $elmProperties->content = $submit($elmName, $label, $attributes);
        return $this;
    }

    /**
     * 
     */
    protected function _formatElementHtml($elmProperties, $formProperties) {
        $format = $formProperties->format ?? null;
        if($format) {
            $attributes = $elmProperties->attributes;
            $labelAttributes = ['id' => $attributes['id'] . '_label', 'for' => $attributes['id']];
            $helper = $this->getFormHelper();
            $attrObj = (object)$attributes;
            $labelAttrObj = (object)$labelAttributes;
            $helper->formatFormElements($attrObj, $labelAttrObj);
            $elmProperties->attributes = (array)$attrObj;
        }
        else {
            $callback = $formProperties->callback ?? null;
            if(is_callable($callback)) {
                $callback(self::CALLBACK_FORMAT, $elmProperties, $this);
            }
        }
    }
    
    /**
     *
     * @param stdClass $elmProperties
     * @return $this
     * @throws RuntimeException
     */
    protected function _validateElementProperties(\stdClass $elmProperties) {
        if(! is_string($elmProperties->type) || Types::isBlank($elmProperties->type)) {
            $var = Types::getVartype($elmProperties->type);
            throw new RuntimeException("Missing or unsupported element type '{$var}'");
        }
        $elmProperties->type = strtolower($elmProperties->type);
        $elmProperties->datatype = $this->_resolveDataType($elmProperties->datatype ?? 'string');
        $elmProperties->multiple = $this->_getBoolVal($elmProperties->multiple ?? false);

        $attr = $elmProperties->attributes ?? null;
        $attributes = [];
        if(is_array($attr)) {
            foreach($attr as $k => $v) {
                if(! is_int($k) && ! Types::isBlank($k)) {
                    $attributes[$k] = $v;
                }
            }
        }
        $elmProperties->attributes = $attributes;
        $elmProperties->attributes['name'] = $elmProperties->name;
        $value = $elmProperties->id ?? null;
        if(Types::isBlank($value)) {
            $value = $elmProperties->name . '_id';
        }
        $elmProperties->attributes['id'] = $value;

        switch($elmProperties->type) {
        case 'radio':
        case 'checkbox':
        case 'submit':
        case 'button':
        case 'hidden':
        case 'file':
        case 'password':
        case 'text':
            $elmProperties->attributes['type'] = $elmProperties->type;
            break;
        case 'textarea':
            unset($elmProperties->attributes['type']);
            break;
        case 'select':
            // SELECT element doesn't require a type attribute.
            unset($elmProperties->attributes['type']);
            break;
        default:
            throw new RuntimeException("Unsupported element type '{$elmProperties->type}'");
        }
        return $this;
    }

    /**
     *
     * @param stdClass $elmProperties
     * @param stdClass $formProperties
     * @return $this
     */
    protected function _resolveProperties(\stdClass $elmProperties, \stdClass $formProperties) {
        $setDefault = true;
        switch($elmProperties->type) {
        case 'select':
            $elmProperties->multiple = $this->_getBoolVal($elmProperties->attributes['multiple'] ?? false);
            break;
        case 'checkbox':
        case 'radio':
            $elmProperties->multiple = (is_array($elmProperties->values) && count($elmProperties->values));
            break;
        case 'file':
            $formProperties->hasfiles = true;
            $setDefault = false;
            break;
        case 'password':
            $setDefault = false;
            break;
        default:
        }
        
        if($setDefault) {
            $default = $elmProperties->default ?? null;
            if(null !== $default) {
                $elmProperties->formvalue = $default;
            }
        }
        
        // Fetch the REQUEST input if available and store in formValue property
        $dataType = $elmProperties->multiple ? 'array' : ($elmProperties->datatype ?? 'string');
        $requestValue = $this->_filter_input($elmProperties->name, $dataType);
        if(null === $requestValue || false === $requestValue) {
            $formValue = $elmProperties->formvalue ?? null;
        }
        else {
            $formProperties->isPost = true;
            if(is_array($requestValue)) {
                $formValue = array_map('strval', $requestValue);
            } 
            else {
                $formValue = $requestValue;
            }
        }
            
        if($elmProperties->multiple) {
            if(! is_array($formValue)) {
                $formValue = (null === $formValue) ? [] : [$formValue];
            }
        }
        elseif(null === $formValue) {
            $formValue = '';
        }
        elseif(! is_array($formValue)) {
            $formValue = strval($formValue);
        }
        if('radio' === $elmProperties->type && is_array($formValue) && count($formValue)) {
            $formValue = reset($formValue);
        }
        $elmProperties->formvalue = $formValue;
        return $this;
    }

    /**
     * Returns true if data ias in the $_POST global array.
     * @return boolean
     */
    public function isPost() {
        return $this->form->isPost;
    }
    
    /**
     *
     * @param stdClass $elmProperties
     * @return mixed
     */
    public function setDefaultProperties() {
        if(! is_object($this->form) || ! isset($this->form->elements)) {
            $msg = "\$this->form property is not initialized";
            throw new \RuntimeException($msg, 255);
        }
        if(isset($this->form->elements)) {
            $elements = $this->elements;
        } 
        elseif(isset($this->form['elements'])) {
            $elements = (object)$this->form['elements'];
        }
        else {
            $elements = null;
        }
        if(null !== $elements) {
            foreach($elements as $elmName => $element) {
                if(isset($element->default) && null !== $element->default) {
                    $element->formvalue = $element->default;
                }
                // $this->form->elements[$elmName] = $element;
            }
        }
        return $this;
    }

    /**
     *
     */
    public function saveSession() {
        if(! $this->_initialized) {
            $this->init();
        }
        if(! is_object($this->form) || ! isset($this->form->elements)) {
            $msg = "\$this->form property is not initialized";
            throw new \RuntimeException($msg, 255);
        }
        $sessionActive = (session_status() === PHP_SESSION_ACTIVE || session_start());
        if(! $sessionActive) {
            return false;
        }
        $formName = $this->_getFormName($this->form);
        $formId = $this->_getFormId($this->form);
        $formsessionhash = md5($formName . '_' . ($formId ?? ($formName . '_id')));
        if(isset($_SESSION) && isset($_SESSION['forms']) && isset($_SESSION['forms'][$formsessionhash])) {
            // Get rid of existing values.
            unset($_SESSION['forms'][$formsessionhash]);
        }
        $formValues = [];
        foreach($this->form->elements as $element) {
            $formValue = $element->formvalue;
            switch($element->type) {
            case 'radio';
            case 'checkbox';
                if(is_array($formValue)) {
                    $values = [];
                    foreach($formValue as $k => $v) {
                        if(true === $v) {
                            $values[] = $k;
                        }
                    }
                    if('radio' === $element->type) {
                        $values = count($values) ? reset($values) : '';
                    }
                    $formValue = $values;
                }
                break;
            default:    
            }
            $formValues[$this->form->name][$element->name] = $formValue;
        }
        $_SESSION['forms'][$formsessionhash] = $formValues;
        return $this;
    }

    /**
     *
     */
    public function restoreSession() {
        if(! $this->_initialized) {
            $this->init();
        }
        if(! is_object($this->form) || ! isset($this->form->elements)) {
            $msg = "\$formObject property is not initialized";
            throw new \RuntimeException($msg, 255);
        }
        $formObject = $this->form;
        $sessionActive = (session_status() === PHP_SESSION_ACTIVE || session_start());
        if(! $sessionActive) {
            return false;
        }
        $formName = $this->_getFormName($formObject);
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
                    $formValue = $sessData[$elmName];
                    switch($element->type) {
                    case 'checkbox';
                        break;
                        if(is_array($formValue)) {
                            $values = [];
                            foreach($element->values as $k => $v) {
                                $values[$v] = (false !== array_search($v, $formValue));
                            }
                            $formValue = $values;
                        }
                        break;
                    case 'radio';
                        if(is_array($formValue)) {
                            $values = count($formValue) ? reset($values) : '';
                        }
                        break;
                    default:    
                    }
                    $element->formvalue = $formValue;
                }
                $formObject->elements[$elmName] = $element;
            }
        }
        $this->form->sessionrestored = true;
        // $_SESSION['forms'][$formsessionhash][$formObject->name] = $formValue;
        return true;
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
     *
     * @param string    $varName    REQUEST variable name
     * @param string    $dataType   (optional) Data type: string, int, float, bool, boolean, email, url
     * @param int       $type       (optional) One of INPUT_GET, INPUT_POST, INPUT_COOKIE, INPUT_SERVER, or INPUT_ENV.
     */
    protected function _filter_input($varName, $dataType = 'string', $type = INPUT_POST) {
        list($filter, $options) = $this->_resolveInputFilter($dataType);
        switch($type) {
        case INPUT_GET:
        case INPUT_COOKIE:
        case INPUT_SERVER:
        case INPUT_ENV:
        default:
            $type = INPUT_POST;
        }
        $value = filter_input($type, $varName, $filter, $options);
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
     * Returns the form's NAME attribute. Creates the value if not found.
     * @param stdClass $form
     * @return $this
     */
    protected function _getFormName(\stdClass $form) {
        $formName = $form->name ?? null;
        if(Types::isBlank($formName)) {
            $formName = isset($form->attributes) ? ($form->attributes['name'] ?? null) : null;
            if(Types::isBlank($formName)) {
                $formName = isset($form->attributes) ? ($form->attributes['id'] ?? null) : null;
                if(Types::isBlank($formName)) {
                    $formName = "Form" . $this->_formIndex++;
                }
                $form->name = $formName;
            }
        }
        // Fill in missing form properties.
        if(! isset($form->attributes)) {
            $form->attributes = [];
        }
        $form->attributes['name'] = $form->name;
        return $form->name;
    }

    /**
     * Returns the form's ID attribute. Creates the value if not found.
     * @param stdClass $form
     * @return $this
     */
    protected function _getFormId(\stdClass $form) {
       $formId = isset($form->attributes) ? ($form->attributes['id'] ?? null) : null;
        if(Types::isBlank($formId)) {
            $form->attributes['id'] = $this->_getFormName($form);
        }
        $form->id = $form->attributes['id'];
        return $form->attributes['id'];
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