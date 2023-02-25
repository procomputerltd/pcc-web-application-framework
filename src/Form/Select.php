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

use Procomputer\Pcclib\Html\Element;

class Select extends Element {
    
    public function renderHtml(array $values, array $attributes = [], array $options = []) {
        $selected = isset($options['selected']) 
            ? (is_array($options['selected']) ? $options['selected'] : [$options['selected'] => $options['selected']]) 
            : [];
        if(empty($selected)) {
            $default = $options['default'] ?? null;
            if(null !== $default) {
                if(is_array($default)) {
                    $default = reset($default);
                }
                if(is_scalar($default) && ! is_bool($default)) {
                    $default = strval($default);
                    $selected = [$default => $default];
                }
            }
        }
        $indent = isset($options['indent'])
            ? (is_numeric($options['indent']) ? str_repeat("\t", intval($options['indent'])) : $options['indent'])
            : '';
        $optionIndent = "\t" . $indent;
        $element = new Element();
        $selectOptions = [];
        foreach($values as $value => $label) {
            $optionSttributes = (false !== array_search($value, $selected)) ? ['selected' => 'true'] : [];
            $optionSttributes['value'] = $value;
            $selectOptions[] = $optionIndent . $element->render('option', $label, $optionSttributes, true);
        }
        
        $html = $indent . $element->render('select', "\n" . implode("\n", $selectOptions), $attributes, true);
        return $html;
    }
}