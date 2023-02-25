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
use Procomputer\Pcclib\Html\Hyperlink;
use Procomputer\Pcclib\Html\BulletList;
use Procomputer\Pcclib\Html\Button;
use Procomputer\Pcclib\Html\Div;

class Menu {
    
    use CommonUtilities;
    
    public function dropdown($items, $buttonLabel = 'Select', array $options = []) {
        $hyperlink = new Hyperlink();
        foreach($items as $label => $action) {
            $items[$label] = $hyperlink->render($action, $label, ['class' => 'dropdown-item']);
        }
        $html = [];
        $button = new Button();
        $btnAttr = [
            'type' => 'button',
            'class' => 'btn btn-primary dropdown-toggle', 
            'data-bs-toggle' => 'dropdown',
            'aria-expanded' => 'false'
        ];
        $btnOptions = $options['button'] ?? null;
        if(is_array($btnOptions)) {
            $btnAttr = $this->_mergeAttributes($btnAttr, $btnOptions);
        }
        $html[] = $button->render($buttonLabel, $btnAttr);
        $bullets = new BulletList();
        $html[] = $bullets->render($items, false, ['class' => 'dropdown-menu'], false);
        
        $div = new Div();
        return $div->render(implode("\n", $html), ['class' => 'dropdown']);
/*
        <div class="dropdown">
          <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            Dropdown button
          </button>
          
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="#">Action</a></li>
            <li><a class="dropdown-item" href="#">Another action</a></li>
            <li><a class="dropdown-item" href="#">Something else here</a></li>
          </ul>
        </div>    
 */
    }
    
    private function _mergeAttributes(array $attributes, array $newAttributes) {
        if(isset($newAttributes['class'])) {
            $str = trim(implode(' ', $this->_getArrayableItems($newAttributes['class'])));
            if(strlen($str)) {
                $attributes['class'] = implode(' ', [trim($attributes['class'] ?? ''), $str]);
            }
            unset($newAttributes['class']);
        }
        return array_merge($attributes, $newAttributes);
    }
}