<?php
namespace Procomputer\WebApplicationFramework\Widgets;

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
class Clipboard {
    
    /**
     * BusyIndicator scripts are added to the application;
     * @var bool
     */
    protected $_inited = false;
    
    /**
     * jQuery selectors for action elements.
     * @var array
     */
    protected $_selectors = [];
    
    /**
     * Callback js functions to call after clipboard action.
     * @var array
     */
    protected $_callback = [];
    
    /**
     * Add a selector from which to copy clipboard.
     * @param string $selector
     * @return $this
     */
    public function addAction(string $selector, string $action = 'click') {
        $this->_selectors[$action][] = $selector;
        return $this;
    }
    
    /**
     * Add a callback js function to call after clipboard action.
     * @param string $functionName
     * @return $this
     */
    public function addCallback(string $functionName) {
        $this->_callback[] = $functionName;
        return $this;
    }
    
    /**
     * Returns the selectors added.
     * @return array
     */
    public function getSelectors(): array {
        return $this->_selectors;
    }
    
    /**
     * Returns the number of selectors added.
     * @return int
     */
    public function getSelectorCount(): int {
        return count($this->_selectors);
    }
    
    public function getJsScript() {
        if(! $this->getSelectorCount()) {
            return '';
        }
        if(count($this->_callback)) {
            $scripts = [];
            foreach($this->_callback as $function) {
                $scripts[] = $function . '(res)';
            }
            $callbacks = "\n" . implode("\n", $scripts);
        }
        else {
            $callbacks = '';
        }
        
        $scripts = [];
        foreach($this->_selectors as $action => $selectors) {
            $jqSelectors = implode(',', $selectors); 
            $scripts[] = <<<EOD
$('{$jqSelectors}').{$action}(function(){
    var res = copyToClipboard(this);{$callbacks}
})
EOD;
        $combined = implode("\n", $scripts);
        }
        return <<<EOD
        
    copyToClipboard = function(element) {
        let attr = 'data-target', val, elm, text, temp, res;
        elm = $(element);
        if(! elm || ! elm.length) {
            console.warn("copy-clipboard() failed. Element parameter is not a valid element");
            return false;
        }
        val = elm.attr(attr);
        if(! val || ! val.length) {
            console.warn('copy-clipboard(): "' + attr + '" attribute not found.');
            return false;
        }
        elm = $('#' + val);
        if(! elm.length) {
            console.warn('copy-clipboard(): element "' + val + '" targeted by "data-target" attribute not found.');
            return false;
        }
        text = elm.val();
        if('string' !== typeof text || ! text.length) {
            text = elm.val();
            if('string' !== typeof text || ! text.length) {
                text = elm.html();
            }
        }
        temp = $("<input>");
        $("body").append(temp);
        temp.val(text).select();
        res = document.execCommand("copy");
        temp.remove();
        if(res) {
            return text;
        }
        console.warn('copy-clipboard() failed on element "' + val + '" targeted by "data-target".');
        return false;
    }
    {$combined}
EOD;
    }
    
}