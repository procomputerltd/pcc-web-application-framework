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

use Procomputer\Pcclib\FileSystem;

class CssFramework {

    /**
     * CSS Framework Object.
     * @var Bootstrap
     */
    protected $_cssFramework;
    
    public function __construct(string $cssFramework) {
        $vendorClass = ucfirst($cssFramework);
        $file = FileSystem::joinPath(DIRECTORY_SEPARATOR, __DIR__, $vendorClass . '.php');
        if(! file_exists($file)) {
            throw new \RuntimeException("CSS Framework vendor not found: {$cssFramework}");
        }
        require_once $file;
        $this->_cssFramework = new $vendorClass();
    }
    
    /**
     * Returns an array containing one or more 'js' and 'css' elements describing the attributes for the specified release.
     * @param int    $release The bootstrap release index e.g. 5
     * @return string
     */
    public function get($release = null) : string {
        return $this->_cssFramework->get($release);
    }
        
    /**
     * Returns an HTML script declarations for the specified css framework release.
     * @param string $type    The type of attributes to return eg 'js' and 'css'
     * @param string $release The css framework release.
     * @return string
     */
    public function getScript(string $type, $release = null) {
        return $this->_cssFramework->getScript($type, $release);
    }
    
    /**
     * Returns the css framework releases supported.
     * @return array
     */
    public function getReleases() : array {
        return $this->_cssFramework->getReleases();
    }

    /**
     * Set the default release for this css framework. For example bootstrap release '5'.
     * @param mixed $release
     * @return $this
     */
    public function setDefaultRelease($release) {
        $this->_cssFramework->setDefaultRelease($release);
        return $this;
    }
    
    /**
     * Returns the default release for this css framework. For example bootstrap release '5'.
     * @return mixed Returns the default release.
     */
    public function getDefaultRelease() {
        return $this->_cssFramework->getDefaultRelease();
    }
}   