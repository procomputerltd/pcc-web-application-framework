<?php
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

class Bootstrap {
    
    // Some bootstrap version constants.
    const BOOTSTRAP2 = 2;
    const BOOTSTRAP3 = 3;
    const BOOTSTRAP4 = 4;
    const BOOTSTRAP5 = 5;

    protected $_defaultRelease = self::BOOTSTRAP5; // Must be declared in extended classes.
    //
    // Supported bootstrap release-specific framework instances.
    protected $_releases = [
        self::BOOTSTRAP5 => [
            'css' => [
                [
                    'href' => 'https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css',
                    'rel' => 'stylesheet',
                    'integrity' => 'sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65',
                    'crossorigin' => 'anonymous'
                ],
            ],
            'js' => [
                [
                    'src' => 'https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js',
                    'integrity' => 'sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4',
                    'crossorigin' => 'anonymous'
                ]
            ]
        ],
        self::BOOTSTRAP4 => [
            'css' => [
                [
                    'href' => 'https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css',
                    'integrity' => 'sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N',
                    'crossorigin' => 'anonymous'
                ]
            ],
            'js' => [
                [
                    'src' => 'https://cdn.jsdelivr.net/npm/popper.js@1.14.3/dist/umd/popper.min.js',
                    'integrity' => 'sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49',
                    'crossorigin' => 'anonymous'
                ],
                [
                    'src' => 'https://cdn.jsdelivr.net/npm/bootstrap@4.1.3/dist/js/bootstrap.min.js',
                    'integrity' => 'sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy',
                    'crossorigin' => 'anonymous'
                ]
            ]
        ],
        self::BOOTSTRAP3 => [
            'css' => [
                [
                    'href' => 'https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/css/bootstrap.min.css',
                    'integrity' => 'sha384-HSMxcRTRxnN+Bdg0JdbxYKrThecOKuH5zCYotlSAcp1+c8xmyTe9GYg1l9a69psu',
                    'crossorigin' => 'anonymous'
                ]
            ],
            'js' => [
                [
                    'src' => 'https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/js/bootstrap.min.js',
                    'integrity' => 'sha384-aJ21OjlMXNL5UyIl/XNwTMqvzeRMZH2w8c5cRVpzpU8Y5bApTppSuUkhZXN0VxHd',
                    'crossorigin' => 'anonymous'
                ]
            ]    
        ],
        self::BOOTSTRAP2 => [
            'css' => [
                [
                    'href' => "https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/2.3.2/css/bootstrap-responsive.min.css",
                    'integrity' => "sha512-S6hLYzz2hVBjcFOZkAOO+qEkytvbg2k9yZ1oO+zwXNYnQU71syCWhWtIk3UYDvUW2FCIwkzsTcwkEE58EZPnIQ==",
                    'crossorigin' => "anonymous",
                    'referrerpolicy' => "no-referrer"
                ]
            ],
            'js' => [
                [
                    'src' => "https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/2.3.2/js/bootstrap.min.js",
                    'integrity' => "sha512-28e47INXBDaAH0F91T8tup57lcH+iIqq9Fefp6/p+6cgF7RKnqIMSmZqZKceq7WWo9upYMBLMYyMsFq7zHGlug==",
                    'crossorigin' => "anonymous",
                    'referrerpolicy' => "no-referrer"
                ]
            ]
        ]
    ];
        
    /**
     * Returns an HTML script declarations for the specified Bootstrap release index.
     * @param string $type    The type of attributes to return eg 'js' and 'css'
     * @param string $release (optional) The Bootstrap release.
     * @return string
     */
    public function getScript(string $type, $release = null) {
        $attributes = $this->get($release);
        if(strlen($type) < 2 || 'js' === substr(strtolower($type), 0, 2)) {
            $typeKey = 'js';
            $isJs = true;
        }
        else {
            $typeKey = 'css';
            $isJs = false;
        }
        $declarations = [];
        foreach($attributes[$typeKey] as $attributes) {
            if(! $isJs && ! isset($attributes['rel'])) {
                $attributes['rel'] = 'stylesheet';
            }
            foreach($attributes as $attribute => $value) {
                $attributes[$attribute] = $attribute . '="' . $value . '"';
            }
            $attrStr = implode(' ', $attributes);
            $declarations[] = $isJs ? "<script {$attrStr}></script>" : "<link {$attrStr} />";
        }
        return implode(' ', $declarations);
    }

    /**
     * Returns an array containing one or more 'js' and 'css' elements describing 
     * the attributes for the specified Bootstrap release.
     * @param int    $release (optional) The bootstrap release index e.g. 5
     * @return string
     */
    public function get($release = null) : array {
        return $this->_releases[$this->releaseExists($release) ? (int)$release : $this->getDefaultRelease()];
    }
    
    /**
     * Returns the Bootstrap releases supported.
     * @return array
     */
    public function getReleases() : array {
        return array_keys($this->_releases);
    }

    /**
     * Set the default release for this css framework. For example bootstrap release '5'.
     * @param mixed $release
     * @return $this
     */
    public function setDefaultRelease($release) {
        if($this->releaseExists($release)) {
            $this->_defaultRelease = (int)$release;
        }
        return $this;
    }
    
    /**
     * Returns the default release for this css framework. For example bootstrap release '5'.
     * @return mixed Returns the default release.
     */
    public function getDefaultRelease() {
        return $this->_defaultRelease;
    }
    
    /**
     * Returns the default release for this css framework. For example bootstrap release '5'.
     * @return mixed Returns the default release.
     */
    public function releaseExists($release) {
        return (is_numeric($release) && isset($this->getReleases()[(int)$release]));
    }
}