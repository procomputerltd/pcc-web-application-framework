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

class CssFrameworks {
    
    /**
     * Returns specified CSS framework vendor.
     * @param string $vendor  The framework vendor. Default is bootstrap.
     * @return CssFramework
     */
    public function __invoke(string $vendor = 'bootstrap') {
        return $this->get($vendor);
    }
    
    /**
     * Returns specified CSS framework vendor.
     * @param string $vendor  The framework vendor. Default is bootstrap.
     * @return CssFramework
     */
    public function get(string $vendor) {
        $parts = $this->_resolve($vendor);
        if(false === $parts) {
            throw new RuntimeException("Cannot resolve the css framework vendor name from '{$vendor}'");
        }
        /** @var Bootstrap $obj */
        $obj = new CssFramework($parts[0]);
        if($parts[1]) {
            $obj->setDefaultRelease($parts[1]);
        }
        return $obj;
    }
    
    /**
     * Resolves a CSS framework vendor and release. Expects syntax 'vendor.release' like 'bootstrap.5' 
     * @param string $vendor The vendor and optional release.
     * @return array Returns a 2-element array where element[0] holds the vendor name like 'bootstrap', element[1] holds the release like '5'
     */
    protected function _resolve(string $vendor) {
        $parts = preg_split('/[^a-zA-Z0-9_]+/', trim($vendor));
        if(! is_array($parts) || ! ($c = count($parts))) {
            return false;
        }
        return ($c > 2) ? array_slice($parts, 0, 2) : array_pad($parts, 2, null) ;
    }
}