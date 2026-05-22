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
use ArrayIterator;
use ArrayObject;

/**
 * As the name implise, a simple array collection object.
 */
class SimpleCollection extends ArrayObject {
    
    public function __construct(array|object $array = [], int $flags = 0, string $iteratorClass = ArrayIterator::class) {
        parent::__construct($array, ArrayObject::ARRAY_AS_PROPS, $iteratorClass);
    }
    
    /**
     * Adds an item to the collection.
     * @param string|iterable $data    Data to add.
     * @param array           $options (optional)  (bool) noDuplicate: omit duplicate value(s), (bool) prepend: prepend the data to the collection.
     * @return $this
     */
    public function add(string|iterable $data, array $options = []) {
        $lcOptions = array_change_key_case($options);
        $prepend = $lcOptions['prepend'] ?? false;
        if(! is_string($data)) {
            if(is_array($data)) {
                $attributes = $data;
            }
            else {
                // To array.
                $attributes = [];
                foreach($data as $k => $v) {
                    $attributes[$k] = $v;
                }
            }
            $c = $this->count();
            if($prepend && $c) {
                for(; $c > 0; $c--) {
                    $this->offsetSet($c, $this->offsetGet($c - 1));
                }
            }
            $this->offsetSet($c, $attributes);
        }
        else {
            $array = $this->_getArrayableItems($data);
            $omitDuplicate = $lcOptions['noduplicate'] ?? false;
            $omit = false;
            foreach($array as $value) {
                if(! is_string($value) || ! strlen(trim($value))) {
                    continue;
                }
                if($omitDuplicate) {
                    $omit = false;
                    foreach($this as $existing) {
                        if($value === $existing) {
                            $omit = true;
                            break;
                        }
                    }
                }
                if(! $omit) {
                    $c = $this->count();
                    if($prepend && $c) {
                        for(; $c > 0; $c--) {
                            $this->offsetSet($c, $this->offsetGet($c - 1));
                        }
                    }
                    $this->offsetSet($c, $value);
                }
            }
        }
        return $this;
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