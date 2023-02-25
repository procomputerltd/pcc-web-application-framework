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

class Http {
    
    public function getState(string $name, mixed $type = INPUT_GET) {
        switch($type) {
        case INPUT_GET:
        case INPUT_POST:
        case INPUT_REQUEST:
            $filterType = $type;
            break;
        default:
            $s = strtolower(strval($type));
            switch($s) {
            case 'get':
                $filterType = INPUT_GET;
                break;
            case 'post':
                $filterType = INPUT_POST;
                break;
            case 'request':
                $filterType = INPUT_REQUEST;
                break;
            default:
                $filterType = null;
            }
        }
        return filter_input($filterType, $name) ;
    }
    
    public function getCookie(string $name) {
       return filter_input(INPUT_COOKIE, $name) ;
    }
    
    /**
     * Returns a session key value.
     * @param string $name  Session key name.
     * @return mixed Returns the session key value or null if not found or sessions not active.
     */
    public function getSessionVar(string $name, mixed $default = null) {
        if(PHP_SESSION_ACTIVE !== session_status()) {
            return null;
        }
        $sessKey = $this->_createSessKey($name);
        return isset($_SESSION[$sessKey]) ? $_SESSION[$sessKey] : $default;
    }
    
    /**
     * Sets a session key value.
     * @param string $name  Session key name.
     * @param mixed  $value Session key value.
     * @return boolean Returns true if the value is set else false if sessions are not active.
     */
    public function setSessionVar(string $name, mixed $value = null) {
        if(PHP_SESSION_ACTIVE !== session_status()) {
            return false;
        }
        $sessKey = $this->_createSessKey($name);
        if(null === $value) {
            unset($_SESSION[$sessKey]);
        }
        else {
            $_SESSION[$sessKey] = $value;
        }
        return true;
    }
    
    protected function _createSessKey(string $name) {
        return md5($name . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $name));
    }
}