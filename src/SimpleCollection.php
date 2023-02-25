<?php
namespace Procomputer\WebApplicationFramework;

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
/**
 * As the name implise, a simple array collection object.
 */
class SimpleCollection {
    
    /**
     * 
     * @var \ArrayObject
     */
    protected $_storage;
    
    /**
     * Ctor.
     */
    public function __construct() {
        $this->_storage = new \ArrayObject();
    }

    /**
     * Adds an item to the collection.
     * @param array|string|Traversable $data          Data to add.
     * @param bool                     $omitDuplicate Omit duplicate value(s).
     * @param bool                     $prepend       Prepend the data to the collection.
     * @return $this
     */
    public function add($data, bool $omitDuplicate = false, bool $prepend = false) {
        $array = $this->_getArrayableItems($data);
        $omit = false;
        foreach($array as $value) {
            if($omitDuplicate) {
                $omit = false;
                foreach($this->_storage as $existing) {
                    if($value === $existing) {
                        $omit = true;
                        break;
                    }
                }
            }
            if(! $omit) {
                $c = $this->_storage->count();
                if($prepend && $c) {
                    for(; $c > 0; $c--) {
                        $this->_storage->offsetSet($c, $this->_storage->offsetGet($c - 1));
                    }
                }
                $this->_storage->offsetSet($c, $value);
            }
        }
        return $this;
    }
    
    /**
     * Returns the collection items joined into a string.
     * @return string
     */
    public function getString() : string {
        return implode("\n", $this->_storage->getArrayCopy());
    }
    
    /**
     * Returns the collection in a PHP array.
     * @return array
     */
    public function getArray() : array {
        return $this->_storage->getArrayCopy();
    }
    
    /**
     * Returns the collection ArrayObject.
     * @return \ArrayObject
     */
    public function get() : \ArrayObject {
        return $this->_storage;
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
        } elseif ($items instanceof Enumerable) {
            return $items->all();
        } elseif ($items instanceof Arrayable) {
            return $items->toArray();
        } elseif ($items instanceof Jsonable) {
            return json_decode($items->toJson(), true);
        } elseif ($items instanceof JsonSerializable) {
            return (array) $items->jsonSerialize();
        } elseif ($items instanceof Traversable) {
            return iterator_to_array($items);
        }

        return (array) $items;
    }

}