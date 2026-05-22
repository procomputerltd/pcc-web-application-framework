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
use Procomputer\WebApplicationFramework\CommonUtilities;
use Procomputer\Pcclib\Html\Common;

class FormHelper {

    use CommonUtilities;

    /**
     * 
     * @var Common
     */
    protected $_common;
    
    /**
     * Constructor
     * @param array $options (optional)
     */
    public function __construct(array $options = []) {
        $this->_common = new Common();
        if(null!== $options) {
            $options = array_change_key_case($options);
            foreach($options as $k => $v) {
                $this->$k = $v;
            }
        }
    }
    
    /**
     * Callback on each element from Form.
     * @param FormElement $element      Element object.
     * @param stdClass $labelAttributes (optional) Label attributes object.
     */
    public function formatFormElement(FormElement $element, \stdClass $labelAttributes = null) {
        $attributes = $element->attributes()->getArrayCopy();
        if(empty($attributes['class'])) {
            $attributes['class'] = '';
        }
        switch(strtolower($element->type)) {
        case 'radio':
        case 'checkbox':
            /*
              <div class="form-check">
                <input type="checkbox" class="form-check-input" id="exampleCheck1">
                <label class="form-check-label" for="exampleCheck1">Check me out</label>
              </div>
             */
            $attributes['class'] = $this->_common->addClass($attributes['class'], 'form-check-input');
            if($labelAttributes) {
                $labelAttributes->class = $this->_common->addClass($labelAttributes->class ?? '', 'form-check-label');
            }
            $attributes['wrapper'] = "<div class=\"form-check\">\n{{wrapper}}\n</div>";
            break;
        case 'submit':
        case 'button':
            $attributes['class'] = $this->_common->addClass($attributes['class'], ['btn', 'btn-primary']);
            // <button type="submit" class="btn btn-primary">Submit</button>
            break;
        default: // text, hidden, select
            $attributes['class'] = $this->_common->addClass($attributes['class'], 'form-control');
        }
        $element->attributes()->exchangeArray($attributes);
    }
}
