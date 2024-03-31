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
use Procomputer\WebApplicationFramework\Application;

class BusyIndicator {

    /**
     * Parent application;
     * @var Application
     */
    protected $_application;

    /**
     * Constructor.
     * @param Application $application
     */
    public function __construct(Application $application) {
        $this->_application = $application;

    }

    /**
     * Display busy indicator when an event occurrs on an element.
     * @param string $event             (optional) jQuery event. Default is 'click'
     * @param string $elementSelector   (optional) jQuery selector string or array of. Default is '.busy-indicator'
     * @param string $appendTo          (optional) Element in which to append the busy indicator. Default is 'body'
     * @param int    $delay             (optional) Milliseconds delay before the busy indicator is displayed. Default is 1000
     * @param int    $fadeIn            (optional) Milliseconds of busy indicator fade-in. Default is 1000
     * @return self
     */
    public function when(string $event = 'click', mixed $elementSelector = '.busy-indicator', string $appendTo = 'body', int $delay = 1000, int $fadeIn = 1000) {
        $selector = $this->_resolveParam($elementSelector);
        if(! count($selector)) {
            $selector = '.busy-indicator';
        }
        else {
            $selector = implode(",", $selector);
        }
        $append = trim($appendTo, "\"' \n\r\t\v\x00");
        if(empty($append)) {
            $append = 'body';
        }
        $style = ('body' === strtolower($append)) 
            ? ['margin-top:-50px', 'margin-left:-50px', 'top:50%', 'left:50%', 'position:absolute'] 
            : ['margin-left:auto', 'margin-right:auto'] ;
        $this->_application->styles()->add($this->getCssScript($style), true);
        $this->_application->scripts()->add($this->getJsScript(), true);
        $this->_application->scripts()->add(<<<EOD
    var elm = findElement('{$selector}');
    if(elm) {
        var target = findElement('{$append}');
        if(! target.length) {
            target = jQuery('body');
        }
        elm.on('{$event}', function() {
            showBusyIndicator(true, target, {$delay}, {$fadeIn});
        })
    }
EOD
        );
        return $this;
    }

    public function getJsScript() {
        return <<<EOD
    /**
     * Attempt to display a busy indicator.
     */    
    function showBusyIndicator(show, appendTo, delay, fadeIn) {
        var elmId = 'busy-indicator-9bdc170c';
        var elm = $('#' + elmId);
        if(! elm.length) {
            if(! show) {
                return;
            }
            if(undefined === appendTo) {
                appendTo = 'body';
            }
            //
            if(! Array.isArray(appendTo)) {
                appendTo = [appendTo];
            }
            for(var i = 0; i < appendTo.length; i++) {
                elm = $('<div />').appendTo(appendTo[i]);
                elm.attr('id', elmId);
                elm.attr('class', 'pcc-busy-indicator');
                /* elm.attr('style', 'position:' + position); */
                elm.css('display', 'none');
                for(var j = 12; j; j--) {
                    $('<div />').appendTo(elm);
                }
            }
        }
        if(show) {
            delay = Math.min(Math.max(isNaN(delay) ? 0 : parseInt(delay), 0), 10000);
            fadeIn = Math.min(Math.max(isNaN(fadeIn) ? 0 : parseInt(fadeIn), 0), 3000);
            if(delay || fadeIn) {
                elm.delay(delay).fadeIn(fadeIn);
            }
            else {
                elm.show();
            }
            return;
        }
        elm.hide();
    }
        
    /**
     * Attempt to find an element from the specified selector.
     */    
    function findElement(selector) {
        if('string' !== typeof selector) {
            return false;
        }
        selector = selector.trim();
        if(! selector.length) {
            return false;
        }
        var elm = jQuery(selector);
        if(elm.length) {
            return elm;
        }
        if(selector.match(/^[^\w]/)) {
            return false;
        }
        elm = jQuery('#' + selector);
        if(elm.length) {
            return elm;
        }
        elm = jQuery('.' + selector);
        if(elm.length) {
            return elm;
        }
        return false;
    }
EOD;
    }

    /**
     * @param string $extraStyles Extra CSS styles appended to pcc-busy-indicator.
     * @return string
     */
    public function getCssScript($extraStyles = null) {
        if(empty($extraStyles)) {
            $css = '';
        }
        else {
            if(! is_array($extraStyles)) {
                $extraStyles = [$extraStyles];
            }
            $css = implode(";", $extraStyles);
        }
        $return = <<<EOD
.pcc-busy-indicator{width:80px;height:80px;{$css}}
.pcc-busy-indicator div{transform-origin:40px 40px;animation:pcc-busy-indicator 1.2s linear infinite;}
.pcc-busy-indicator div:after{content:" ";display:block;position:absolute;top:3px;left:37px;width:6px;height:18px;border-radius:20%;background:gray;}
.pcc-busy-indicator div:nth-child(1){transform:rotate(0deg);animation-delay:-1.1s;}
.pcc-busy-indicator div:nth-child(2){transform:rotate(30deg);animation-delay:-1s;}
.pcc-busy-indicator div:nth-child(3){transform:rotate(60deg);animation-delay:-0.9s;}
.pcc-busy-indicator div:nth-child(4){transform:rotate(90deg);animation-delay:-0.8s;}
.pcc-busy-indicator div:nth-child(5){transform:rotate(120deg);animation-delay:-0.7s;}
.pcc-busy-indicator div:nth-child(6){transform:rotate(150deg);animation-delay:-0.6s;}
.pcc-busy-indicator div:nth-child(7){transform:rotate(180deg);animation-delay:-0.5s;}
.pcc-busy-indicator div:nth-child(8){transform:rotate(210deg);animation-delay:-0.4s;}
.pcc-busy-indicator div:nth-child(9){transform:rotate(240deg);animation-delay:-0.3s;}
.pcc-busy-indicator div:nth-child(10){transform:rotate(270deg);animation-delay:-0.2s;}
.pcc-busy-indicator div:nth-child(11){transform:rotate(300deg);animation-delay:-0.1s;}
.pcc-busy-indicator div:nth-child(12){transform:rotate(330deg);animation-delay:0s;}
@keyframes pcc-busy-indicator{0%{opacity:1;}100%{opacity:0;}}
EOD;
        return str_replace(["\n", "\r"],['',''], $return);
    }
    
    protected function _resolveParam($item) {
        $return = [];
        foreach(is_array($item) ? $item : [$item] as $item) {
            if(is_scalar($item) && !is_bool($item)) {
                $item = trim(strval($item), "\"' \n\r\t\v\x00");
                if(strlen($item)) {
                    // avoid duplicates
                    $return[$item] = $item;
                }
            }
        }
        return $return;
    }
    
}
