<?php
namespace Procomputer\WebApplicationFramework\Form;

/* 
 * Copyright (C) 2022 Pro Computer James R. Steel <jim-steel@pccglobal.com>
 * Pro Computer (pccglobal.com)
 * Tacoma Washington USA 253-272-4243
 *
 * This program is distributed WITHOUT ANY WARRANTY; without 
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR 
 * A PARTICULAR PURPOSE. See the GNU General Public License 
 * for more details.
 */
use Procomputer\Pcclib\Types;
use Procomputer\WebApplicationFramework\CommonUtilities;

class FormHelper {

    use CommonUtilities;
    
    /**
     * Constructor
     * @param array $options (optional)
     */
    public function __construct(array $options = []) {
        if(null!== $options) {
            $options = array_change_key_case($options);
            foreach($options as $k => $v) {
                $this->$k = $v;
            }
        }
    }
    
    /**
     * Callback on each element from from FormBuilder.
     * @param stdClass $attributes      Element attributes object.
     * @param stdClass $labelAttributes (optional) Label attributes object.
     */
    public function formatFormElements(\stdClass $attributes, \stdClass $labelAttributes = null) {
        $type = $attributes->type ?? null;
        if(Types::isBlank($type)) {
            $type = 'text';
        }
        switch($type) {
        case 'radio':
        case 'checkbox':
            /*
              <div class="form-check">
                <input type="checkbox" class="form-check-input" id="exampleCheck1">
                <label class="form-check-label" for="exampleCheck1">Check me out</label>
              </div>
             */
            $attributes->class = $this->addValues($attributes->class ?? '', 'form-check-input');
            if($labelAttributes) {
                $labelAttributes->class = $this->addValues($labelAttributes->class ?? '', 'form-check-label');
            }
            $attributes->wrapper = "<div class=\"form-check\">\n{{wrapper}}\n</div>";
            break;
        case 'submit':
        case 'button':
            $attributes->class = $this->addValues($attributes->class ?? '', ['btn', 'btn-primary']);
            // <button type="submit" class="btn btn-primary">Submit</button>
            break;
        default: // text, hidden, select
            $attributes->class = $this->addValues($attributes->class ?? '', 'form-control');
        }
    }
    
    protected function addValues($value, $add) {
        $trimmed = trim($value);
        $split = strlen($trimmed) ? preg_split('/\\s/', $trimmed) : [];
        $lower = array_map('strtolower', $split);
        foreach((array)$add as $val) {
            if(is_string($val) && strlen($val = trim($val))) {
                if(false === array_search(strtolower($val), $lower)) {
                    $split[] = $val;
                    $lower[] = $val;
                }
            }
        }
        return implode(' ', $split);
    }
}
