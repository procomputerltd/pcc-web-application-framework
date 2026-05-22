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
use Procomputer\WebApplicationFramework\SimpleCollection;
use RuntimeException;

class FormCommon {

    protected $_properties = [
        'name' => '',
        'parent' => ''
    ];
    
    protected $_attributes = null;
        
    /**
     * Constructor
     * @param array $options (optional) Options.
     */
    public function __construct(array $options = []) {
        $this->_attributes = new SimpleCollection();
    }
    
    /**
     * Returns the form or element attributes.
     * @return array
     */
    public function attributes(): SimpleCollection {
        if(! $this->_attributes instanceof SimpleCollection) {
            $break = 1;
        }
        return $this->_attributes;
    }
    
    /**
     * Returns a value from the '_properties' array referenced by '$name'.
     * @param string $name Property key name.
     * @return mixed
     * @throws \RuntimeException
     */
    public function __get(string $name) : mixed {
        $lcName = strtolower($name);
        if(array_key_exists($lcName, $this->_properties)) {
            return $this->_properties[$lcName];
        }
        $var = Types::getVarType($name);
        $msg = "property '{$var}' not found";
        throw new RuntimeException($msg);
    }

    /**
     * Sets a value in the '_properties' array referenced by '$name'.
     * @param string $name Property key name.
     * @param mixed  $val  Property value.
     * @return $this
     * @throws \RuntimeException
     */
    public function __set(string $name, mixed $val) {
        $lcName = strtolower($name);
        if('attributes' === $lcName) {
            $this->attributes()->exchangeArray($val);
            return $this;
        }
        if(array_key_exists($lcName, $this->_properties)) {
            $this->_properties[$lcName] = $val;
            return $this;
        }
        $var = Types::getVarType($name);
        $msg = "property '{$var}' not found";
        throw new RuntimeException($msg);
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
            $action = substr($name, 0, 3);
            $set = 'set' === $action ;
            if($set || ('get' === $action)) {
                $lcName = strtolower(substr($name, 3));
                if(array_key_exists($lcName, $this->_properties)) {
                    if($set) {
                        $this->_properties[$lcName] = (is_array($args) && count($args)) ? reset($args) : $args;
                        return $this;
                    }
                    return $this->_properties[$lcName];
                }
            }
        }
        $var = Types::getVarType($name);
        throw new RuntimeException("In " . get_class($this) . "::__call(): method not found: '{$var}'");
    }
}
