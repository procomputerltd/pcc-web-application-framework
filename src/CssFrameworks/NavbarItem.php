<?php
namespace Procomputer\WebApplicationFramework\CssFrameworks;

/* 
 * Copyright (C) 2023 Pro Computer James R. Steel <jim-steel@pccglobal.com>
 * Pro Computer (pccglobal.com)
 * Tacoma Washington USA 253-272-4243
 *
 * This program is distributed WITHOUT ANY WARRANTY; without 
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR 
 * A PARTICULAR PURPOSE. See the GNU General Public License 
 * for more details.
 */
use Procomputer\WebApplicationFramework\CommonUtilities;

class NavbarItem {
    
    use CommonUtilities;
    
    protected $_label = '';
    
    protected $_action = '';
    
    protected $_children = [];

    protected $_parent = null;
    
    public function __construct(string $label, string $action = '#', $parent = null) {
        $this->_label = $label;
        $this->_action = $action;
        $this->_parent = $parent;
    }
    
    /**
     * 
     * @param string $label
     * @param string $action
     * @return NavbarItem
     */
    public function addChild(string $label, string $action = '#') {                  
        $this->_children[] = $m = new NavbarItem($label, $action, $this); 
        return $m;
    }
    
    /**
     * 
     * @param mixed  $item
     * @param string $action
     * @return self
     */
    public function addChildren(mixed $item, string $action = '#') {
        $items = is_scalar($item) ? [(string)$item => $action] : $this->_getArrayableItems($item);
        foreach($items as $label => $action) {
            $this->_children[] = new NavbarItem($label, $action, $this); 
        }
        return $this;
    }
    
    /**
     * 
     * @return string
     */
    public function getLabel() {
        return $this->_label;
    }
    
    /**
     * 
     * @return string
     */
    public function getAction() {
        return $this->_action;
    }
    
    /**
     * 
     * @return array
     */
    public function getChildren() {
        return $this->_children;
    }
    
    /**
     * 
     * @return bool
     */
    public function hasChildren() {
        return (bool)count($this->_children);
    }
    
    /**
     * 
     * @return mixed
     */
    public function getParent() {
        return $this->_parent;
    }
    
}
