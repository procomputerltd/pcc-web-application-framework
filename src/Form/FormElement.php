<?php
namespace Procomputer\WebApplicationFramework\Form;
/* 
 * Copyright (C) 2025 Pro Computer James R. Steel <jim-steel@pccglobal.com>
 * Pro Computer (pccglobal.com)
 * Tacoma Washington USA 253-272-4243
 *
 * This program is distributed WITHOUT ANY WARRANTY; without 
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR 
 * A PARTICULAR PURPOSE. See the GNU General Public License 
 * for more details.
 */
use Procomputer\Pcclib\Types;

class FormElement extends FormCommon {

    protected $_properties = [
        'name' => '',
        'parent' => '',
	'datatype' => 'string',
	'type' => 'text',
	'multiple' => false,
	'values' => [],
	'options' => [],
	'content' => '',
	'default' => '',
	'label' => '',
	'value' => '',
	'errors' => [],
    ];
    
    /**
     * Constructor
     * @param array $options (optional) Options.
     */
    public function __construct(array $options = []) {
        parent::__construct($options);
        foreach($options as $name => $value) {
            $this->$name = $value;
        }
        $this->type = Types::isBlank($this->type) ? 'text' : trim($this->type);
    }
    
    /**
     * @param string $message
     * @param mixed  $code
     * @return $this
     */
    public function addError(string $message, $code = null) {
        $errors = $this->errors;
        $errors[] = [$message, $code];
        $this->errors = $errors;
        return $this;
    }
    
    /**
     * 
     * @param string $elmName
     * @param array $errorData
     */
    public function getErrors(): array {
        return $this->errors;
    }
}
