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
    
    /**
     * Returns the current url.
     * @param bool $includeQuery Include the query string;
     * @return string Returns the current url.
     */
    public function url(bool $includeQuery = false) {
        $protocol = 'http';
        $proto = filter_input(INPUT_SERVER, 'REQUEST_SCHEME');
        if(is_string($proto) && strlen($proto = trim($proto, "/ \t"))) {
            $proto = strtolower($proto);
            $port = filter_input(INPUT_SERVER, 'SERVER_PORT');
            if('https' === $proto || is_numeric($port) && 443 === intval($port)) {
                $protocol .= 's'; // SSL
            }
        }
        else {
            $proto = filter_input(INPUT_SERVER, 'HTTPS');
            if(is_string($proto) && strlen($proto = strtolower(trim($proto, "/ \t")))) {
                if(is_numeric($proto) && intval($proto) || 'on' === $proto || 'true' === $proto || '1' === $proto) {
                    $protocol .= 's'; // SSL
                }
            }
        }
        $url = $protocol . '://';
        $host = filter_input(INPUT_SERVER, 'HTTP_HOST');
        if(is_string($host) && strlen($host = trim($host, "/ \t"))) {
            $url .= $host;
        }
        $scriptName = filter_input(INPUT_SERVER, 'SCRIPT_NAME');
        if(is_string($scriptName) && strlen($scriptName = trim($scriptName, "/ \t"))) {
            $uri = $scriptName;
        }
        else {
            $reqUri = filter_input(INPUT_SERVER, 'REQUEST_URI');
            if(is_string($reqUri) && strlen($reqUri = trim($reqUri, "/ \t"))) {
                $uri = parse_url($reqUri, PHP_URL_PATH);
            }
            else {
                $uri = null;
            }
        }
        if($uri) {
            $url .= '/' . $uri;
        }
        if($includeQuery) {
            $query = filter_input(INPUT_SERVER, 'QUERY_STRING');
            if(is_string($query) && strlen($query = ltrim(trim($query), '?&'))) {
                $url .= '?' . $query;
            }
        }
        return $url;
    }

    public function getCookie(string $name, mixed $default = null) {
       $value = filter_input(INPUT_COOKIE, $name) ;
       return (null === $value) ? $default : $value;
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
