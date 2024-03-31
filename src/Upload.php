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

use Procomputer\Pcclib\Media\ImageProperties;
use Procomputer\Pcclib\Types;

class Upload {
    
    public $debugMode = false; // For testing.
    
    protected $_lastError = '';
    
    /**
     * Moves uploaded files derived from assembleUploadedFiles() to the specified directory path.
     * @param string $destPath      Destination directory path.
     * @param array  $uploadedFiles Uploaded files derived from assembleUploadedFiles(). 
     * @return type
     */
    public function moveUploadedFiles(string $destPath, array $uploadedFiles) {
        $imageProperties = new ImageProperties();
        $index = 1;
        foreach($uploadedFiles as $elmName => $items) {
            foreach($items as $key => $properties) {
                $errors = [];
                $name = $properties['name'] ?? null;
                if(! is_string($name) || ! strlen($name = trim($name))) {
                    $name = 'File number ' . $index;
                }
                $error = $properties['error'] ?? 0;
                if(is_numeric($error) && intval($error)) {
                    $errors[] =  "Cannot copy uploaded file {$name}: " . Types::isBlank ($properties['errorMessage'] ?? '') 
                            ? $properties['errorMessage'] : "an unknown uploaded file error ocurred."; 
                }
                else {
                    $file = $properties['tmp_name'] ?? null;
                    if(! $file) {
                        $errors[] = "Cannot copy uploaded file {$name}. The 'tmp_name' temporary filename is missing.";
                    }
                    elseif(! is_file($file)) {
                        $errors[] = "Cannot copy uploaded file {$name}. The 'tmp_name'temporary filename is missing or not a file.";
                    }
                    elseif(! is_readable($file)) {
                        $errors[] = "Cannot copy uploaded file {$name}. The 'tmp_name'temporary filename is not readable.";
                    }
                    else {
                        $destFile = $destPath . DIRECTORY_SEPARATOR . $name;
                        
                        $res = $this->_execPhp(function() use($file, $destFile) { 
                            return move_uploaded_file($file, $destFile); 
                        });
                        if(false === $res) {
                            $errors[] = "Cannot copy uploaded file {$name}. {$this->_lastError}";
                        }
                        else {
                            $properties['destfile'] = $destFile;
                            $properties['type'] = $properties['type'] ?? 'unknown';
                            $imageInfo = $imageProperties->getImageProperties($destFile, false);
                            if(is_array($imageInfo) && ! $imageInfo['errno']) {
                                $properties['image'] = $imageInfo;
                            }
                        }
                    }
                }
                $properties['errors'] = $errors;
                $items[$key] = $properties;
                $index++;
            }
            $uploadedFiles[$elmName] = $items;
        }
        return $uploadedFiles;
    }
    
    /**
     * Assembles $_FILES uploaded files into individual arrays having these elements:
     *   [name]         => (string) Chinese Balloon.jpg
     *   [type]         => (string) image/jpeg
     *   [size]         => (int) 21892
     *   [tmp_name]     => (string) C:\Windows\Temp\phpDCE8.tmp
     *   [error]        => (int) 0
     *   [full_path]    => (string) Chinese Balloon.jpg
     *   [errorMessage] => (string)
     * 
     * @return array Returns the assembled files.
     */
    public function assembleUploadedFiles() {
        $files = $_FILES ?? null;
        if(! is_array($files) || ! count($files)) {
            return [];
        }
        $fileItems = [];
        foreach($files as $elmName => $fileData) {
            $fileItems[$elmName] = $this->_processUploadedFile($fileData);
        }
        return $fileItems;
    }
     /** The $_FILES data.
     * Process an individual $_FILES element.
     * @param array $fileData The $_FILES data.
     */
    protected function _processUploadedFile($fileData) {
        /*  The possible properties in each file download:
            array(5) (
              [name]     => (string) 2018-05-19 Contacts Tacoma Washington Bicycle Club.csv
              [type]     => (string) application/octet-stream
              [tmp_name] => (string) C:\Windows\Temp\php60F2.tmp
              [error]    => (int) 0
              [size]     => (int) 130214
            )
        */
        $fileList = [];
        foreach($fileData as $propName => $values) {
            if(! is_array($values)) {
                $values = [$values];
            }
            $index = 0;
            foreach($values as $value) {
                $fileList[$index++][$propName] = $value;
            }
        }
        foreach($fileList as $key => $fileProperties) {
            if(empty($fileProperties['name'])) {
                $fileProperties['name'] = 'downloaded_file_' . ++$index;
            }
            if($this->debugMode) {
                $keys = array_keys($this->getUploadErrorList());
                $fileProperties['error'] = $keys[mt_rand(0, count($keys) - 1)];
            }
            $errno = $this->getUploadError($fileProperties['error'] ?? null);
            $errMsg = '';
            if(! $errno) {
                $filename = $fileProperties['tmp_name'];
                if(empty($filename)) {
                    $errno = 65536;
                }
                elseif(! is_file($filename)) {
                    $errno = 65537;
                }
                elseif(! is_readable($filename)) {
                    $errno = 65537;
                }
                if($errno) {
                    $var = Types::getVartype($filename ?? '');
                    $errMsg = sprintf($this->getUploadErrorMessage($errno, $var), $var);
                    if($this->debugMode) {
                        $errMsg = "NOTICE: DEBUG MODE: random error generated in file '" . basename(__FILE__) . ": " . $errMsg;
                    }
                }
            }
            if(0 !== $errno && ! strlen($errMsg)) {
                $errMsg = $this->getUploadErrorMessage($errno);
                if(! strlen($errMsg)) {
                    $errMsg = $this->getUploadErrorMessage(65535); // The file download did not complete: an unknown error code was submitted.
                }
            }
            $fileProperties['errorMessage'] = $errMsg;
            $fileList[$key] = $fileProperties;
        }
        return $fileList;
    }

    /**
     * Resolves an upload error number.
     * @param int   $errno   Error number to resolve.
     * @param mixed $default Value returned when error is invalid.
     * @return string
     */
    public function getUploadError($errno, mixed $default = null) {
        if(! is_numeric($errno)) {
            return $default;
        }
        $e = intval($errno);
        if(! $e) {
            return 0;
        }
        return ($e < 0) ? $default : $e;
    }
    
    /**
     * Returns an upload error message for the specified error number. Return $default if not found.
     * @param int   $errno   Error number for which to return message.
     * @param mixed $default Value returned when no error message is found.
     * @return string
     */
    public function getUploadErrorMessage($errno, mixed $default = '') {
        if(! is_numeric($errno)) {
            return $default;
        }
        $e = intval($errno);
        if(! $e) {
            return $default;
        }
        $a = $this->getUploadErrorList();
        return $a[$e] ?? $default;
    }
    
    /**
     * Returns a file upload error_number => message list.
     * @param function $callback
     * @return mixed
     */
    public function getUploadErrorList() {
        static $a = [
            // UPLOAD_ERR_OK Value 0 = no error
            0 => 'The file is uploaded successfully.',
            // UPLOAD_ERR_INI_SIZE Value 1 = The uploaded file exceeds the upload_max_filesize directive in php.ini.
            1 => 'The uploaded file exceeds the maximim file size.',
            // UPLOAD_ERR_FORM_SIZE Value 2 = The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.
            2 => 'The uploaded file exceeds the maximim file size.',
            // UPLOAD_ERR_PARTIAL Value 3 = The uploaded file was only partially uploaded.
            3 => 'The file download did not complete: the file was only partially downloaded.',
            // UPLOAD_ERR_NO_FILE Value 4 = No file was uploaded. No file was selected using the file browse button.
            4 => 'No file was selected using the file browse button.',
            // UPLOAD_ERR_NO_TMP_DIR Value 6 = Missing a temporary folder.
            6 => 'The temporary file download folder is missing.',
            // UPLOAD_ERR_CANT_WRITE Value 7 = Failed to write file to disk.
            7 => 'The file download did not complete: disk write failed.',
            // UPLOAD_ERR_EXTENSION Value: 8; A PHP extension stopped the file upload. PHP does not provide a way to
            // ascertain which extension caused the file upload to stop; examining the list of loaded extensions with phpinfo() may help.
            8 => 'The file download stopped unexpectedly.',
            // Value 99 = An unknown error code was submitted.
            65535 => 'The file download did not complete: an unknown error code was submitted.',
            65536 => "'tmp_name' file path property is missing from the file download.",
            65537 => "file '%s' not found : file path does not exist",
            65538 => "file '%s' is not readable"
            ];
        return $a;
    }
    
    /**
     * 
     * @param function $callback
     * @return mixed
     */
    protected function _execPhp($callback) {
        $errorObj = new \stdClass();
        $errorObj->fail = false;
        $errorObj->error = '';
        $errorHandler = set_error_handler(function($errno, $errstr, $errfile, $errline) use($errorObj) {
            $errorObj->fail = true;
            $errorObj->error = $errstr;
        });
        try {
            $return = $callback();
        } catch (\Throwable $exc) {
            $errorObj->fail = true;
            $errorObj->error = $exc->getMessage();
            $return = false;
        }
        finally {
            set_error_handler($errorHandler);
        }
        $this->_lastError = $errorObj->error;
        return $errorObj->fail ? false : $return;
    }
}