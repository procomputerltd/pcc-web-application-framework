<?php
namespace Procomputer\WebApplicationFramework\CssFrameworks;

/* 
 * Copyright (C) 2022 Pro Computer James R. Steel <jim-steel@pccglobal.com>
 * Pro Computer (pccglobal.com)
 * Tacoma Washington USA 253-272-4243
 *
 * This program is distributed WITHOUT ANY WARRANTY; without 
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR 
 * A PARTICULAR PURPOSE. See the GNU General Public License 
 * for more details.
 */

// use Procomputer\WebApplicationFramework\CommonUtilities;
use Procomputer\Pcclib\Html\Element;

class Bootswatch {
    
    // Some bootstrap version constants.
    const BOOTSTRAP4 = 4;
    const BOOTSTRAP5 = 5;

    // use CommonUtilities;
    
    // Supported bootstrap release-specific framework instances.
    protected $_releases = [
        self::BOOTSTRAP5 => [
            'css' => [
                [
                    'href' => 'https://cdn.jsdelivr.net/npm/bootswatch@5/dist/{{style}}/bootstrap.min.css',
                    // 'href' => 'https://cdnjs.cloudflare.com/ajax/libs/bootswatch/5.2.3/{{style}}/bootstrap.min.css',
                    'rel' => 'stylesheet',
                ],
            ],
            'js' => [
                [
                    'src' => 'https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js',
                    'integrity' => 'sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4',
                    'crossorigin' => 'anonymous'
                ]
            ],
            'styles' => [
                'cerulean',
                'cosmo',
                'cyborg',
                'darkly',
                'flatly',
                'journal',
                'litera',
                'lumen',
                'lux',
                'materia',
                'minty',
                'morph',
                'pulse',
                'quartz',
                'sandstone',
                'simplex',
                'sketchy',
                'slate',
                'solar',
                'spacelab',
                'superhero',
                'united',
                'vapor',
                'yeti',
                'zephyr'
                ]
        ],
        self::BOOTSTRAP4 => [
            'css' => [
                [
                    'href' => 'https://cdn.jsdelivr.net/npm/bootswatch@4/dist/{{style}}/bootstrap.min.css',
                    // 'href' => 'https://cdnjs.cloudflare.com/ajax/libs/bootswatch/4.6.2/{{style}}/bootstrap.min.css',
                    'rel' => 'stylesheet',
                ],
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
            ],
            'styles' => [
                'cerulean',
                'cosmo',
                'cyborg',
                'darkly',
                'flatly',
                'journal',
                'litera',
                'lumen',
                'lux',
                'materia',
                'minty',
                // 'morph', // Not implemented this BW version.
                'pulse',
                // 'quartz', // Not implemented.
                'sandstone',
                'simplex',
                'sketchy',
                'slate',
                'solar',
                'spacelab',
                'superhero',
                'united',
                // 'vapor', // Not implemented.
                'yeti',
                // 'zephyr' // Not implemented.
                ]
        ],
    ];
    
    /**
     * Builds a style script for the selected Bootswatch style name.
     * 
     * @param string $bwStyleName         Bootswatch style name like 'cerulean'
     * @param int    $bootstrapVersion  Associated bootstrap version.
     * @return string
     */
    public function getStyleScript(string $bwStyleName, $bootstrapVersion = 5, string $type = 'css') {
        $properties = $this->getStyleProperties($bwStyleName, $bootstrapVersion);
        switch($t = strtolower($type)) {
        case 'js':
            $tag = 'script';
            $isLink = false;
            $closeTag = true;
            break;
        case 'css':
            $tag = 'link';
            $isLink = true;
            $closeTag = false;
            break;
        default:
            $msg = "Script type '{$type}' is invalid. Expecting 'js' or 'css'";
            throw new \RuntimeException($msg);
        }
        $elm = new Element();
        $return = [];
        foreach((array)$properties[$type] as $attributes) {
            $return[] = $elm->render($tag, '', $attributes, $closeTag);
        }
        return implode("\n", $return);
    }
    
    /**
     * Builds a style script for the selected Bootswatch style name.
     * 
     * @param string $bwStyleName         Bootswatch style name like 'cerulean'
     * @param int    $bootstrapVersion  Associated bootstrap version.
     * @return string
     */
    public function getStyleProperties(string $bwStyleName, $bootstrapVersion = 5) {
        $properties = $this->_releases[$bootstrapVersion] ?? null;
        if(! is_array($properties)) {
            $msg = "Bootswatch release '{$bootstrapVersion}' not found";
            throw new \RuntimeException($msg);
        }
        $bwStyleNames = $this->getStyles($bootstrapVersion);
        if(false === $bwStyleNames) {
            $msg = "Cannot get Bootswatch style name list.";
            throw new \RuntimeException($msg);
        }
        $styleName = strtolower($bwStyleName);
        if(! isset($bwStyleNames[$styleName])) {
            $msg = "Bootswatch style name '{$bwStyleName}' not found.";
            throw new \RuntimeException($msg);
        }
        foreach($properties['css'] as $key => $attributes) {
            foreach($attributes as $t => $attribute) {
                $attributes[$t] = str_replace('{{style}}', $styleName, $attribute);
            }
            $properties['css'][$key] = $attributes;
        }
        return $properties;
    }
    
    /**
     * Returns Bootswatch styles list.
     * @param mixed $bootstrapVersion
     * @return array|boolean
     * @throws RuntimeException
     */
    public function getStyles($bootstrapVersion = 5) {
        $properties = $this->_releases[$bootstrapVersion] ?? null;
        if(! is_array($properties)) {
            $msg = "Bootswatch release '{$bootstrapVersion}' not found";
            throw new \RuntimeException($msg);
        }
        return array_combine($properties['styles'], array_map('ucfirst', $properties['styles']));
    }
    
    /**
     * Returns the Bootstrap releases supported.
     * @return array
     */
    public function getReleases() : array {
        return array_keys($this->_releases);
    }
}