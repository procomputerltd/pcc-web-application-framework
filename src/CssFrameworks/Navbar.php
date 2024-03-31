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
use Procomputer\Pcclib\Html\Element;
use Procomputer\Pcclib\Html\Hyperlink;
use Procomputer\WebApplicationFramework\CssFrameworks\NavbarItem;

class Navbar {
    
    protected $_navbarItems = [];

    /**
     * 
     * @param string $label
     * @param string $action
     * @return NavbarItem
     */
    public function add(string $label, string $action = '#') {
        $this->_navbarItems[] = $m = new NavbarItem($label, $action, $this); 
        return $m;
    }
    
    public function render(array $options = []) {
        $mainMenu = $this->_renderMainMenu();
        $brand = $options['brand'] ?? 'My Website';
        if(! preg_match('/^\\s*<a\\s/', $brand)) {
            $brand = '<a class="navbar-brand" href="#">' . $brand . '</a>';
        }
        $return = <<<EOD
<nav class="navbar navbar-expand-lg bg-body-tertiary">
  <div class="container-fluid">
    {$brand}
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarSupportedContent">
        {$mainMenu}
    </div>
  </div>
</nav>

EOD;
            return $return;
        }
    
    protected function _renderMainMenu() {
        $element = new Element();
        $hyperlink = new Hyperlink();
        $html = [];
        foreach($this->_navbarItems as $navbarItem) {
            /** @var NavbarItem $navbarItem */
            /*            
            <li class="nav-item">
              <a class="nav-link active" aria-current="page" href="#">Home</a>
            </li>
            */
            $label = $navbarItem->getLabel();
            if($navbarItem->hasChildren()) {
                $html[] = $this->_renderChildren($navbarItem->getChildren(), $label);
            }
            else {
                $link = $hyperlink->render($navbarItem->getAction(), $label, ['class' => 'nav-link active', 'aria-current' => 'page']);
                $html[] = $element->render('li', $link, ['class' => 'nav-item'], true);
            }
        }
        return $element->render('ul', "\n\t\t\t" . implode("\n\t\t\t", $html), ['class' => 'navbar-nav me-auto mb-2 mb-lg-0'], true);
    }
    
    /**
     * 
     * @param type $items
     * @return string
     */
    protected function _renderChildren($items, $dropdownLabel) {
        /*
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            Dropdown
          </a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="#">Action</a></li>
            <li><a class="dropdown-item" href="#">Another action</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="#">Something else here</a></li>
          </ul>
        </li>
        */        
        $element = new Element();
        $hyperlink = new Hyperlink();
        // class="" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"
        $attributes = [
            'class' => 'nav-link dropdown-toggle', 
            'role' => 'button', 
            'data-bs-toggle' => 'dropdown', 
            'aria-expanded' => 'false'];
        $html = [];
        $html[] = $hyperlink->render('#', $dropdownLabel, $attributes);
        $list = [];
        foreach($items as $navbarItem) {
            $label = $navbarItem->getLabel();
            if(empty($label)) {
                 $inner = $element->render('hr', '', ['class' => 'dropdown-divider']);
            }
            else {
                $inner = $hyperlink->render($navbarItem->getAction(), $label, ['class' => 'dropdown-item']);
            }
            $list[] = $element->render('li', $inner, [], true);
        }
        $tabs = "\t\t\t";
        $html[] = $element->render('ul', "\t" . implode("\n{$tabs}", $list), ['class' => 'dropdown-menu'], true);
        $return = $element->render('li', implode("\n" . $tabs, $html), ['class' => 'nav-item dropdown'], true);
        return $return;
    }
}