<?php
/* 
 * Copyright (c) Pro Computer Consultants
 * All rights reserved
 */
namespace Procomputer\WebApplicationFramework\Form;

use Procomputer\Pcclib\Media\Image;
use Procomputer\Pcclib\Media\ImageProperties;
use Procomputer\Pcclib\Media\MediaConst;
use Procomputer\Pcclib\Http\File as HttpFile;
use Procomputer\Pcclib\FileInformation;
use Procomputer\Pcclib\FileSystem;
use Procomputer\Pcclib\Types;
use Procomputer\Pcclib\PhpErrorHandler;
use Procomputer\Pcclib\Messages\Messages;
use Procomputer\WebApplicationFramework\Exception\InvalidArgumentException;

class FormImageTransform {
    
    use Messages;
    
    /**
     * Constructor
     * @param array $files Array of HttpFile objects.
     */
    public function __construct(?array $files = null) {
        if(is_array($files)) {
            $this->getImages($files);
        }
    }
    
    /**
     * 
     * @param array  $uploadList
     * @param string $destFolder
     * @param array  $transformOptions
     * @return void
     */
    public function transForm(array $uploadList, string $destFolder, array $transformOptions) {
        $fileInformation = new FileInformation();
        foreach($uploadList as $upload) {
            /** @var HttpFile $upload */
            $image = $upload->getProperty('image');
            if(! empty($image)) {
                try {
                    $newImageFile = $this->resizeImage($upload, $destFolder, $transformOptions);
                } catch (Throwable $exc) {
                    $msg = $exc->getMessage();
                    $this->addMessage($msg);
                    $newImageFile = false;
                }
                if($newImageFile) {
                    $upload->setProperty('newimage', $newImageFile);
                }
            }
            else {
                $path = $upload->getFullPath();
                $ext = pathinfo($path, PATHINFO_EXTENSION);
                $error = $upload->getProperty('error');
                $desc = $fileInformation->getFileExtensionDescription($ext);
                $desc = empty($desc) ? '' : ": it may be type '<b>{$desc}</b>'";
                $msg = "File '<b>{$path}</b>' cannot be interpreted as an image{$desc}<br><small style=\"color:red\">{$error}</small>";
                $this->addMessage($msg, 'warning');
                // Not an image.
            }
        }
    }
    
    /**
     * Discover image-type files and set the image properties into the file object's 'image' property.
     * @param array $files Array of HttpFile objects.
     * @return void
     */
    public function getImages(array $files) {
        $imageProperties = new ImageProperties();
        foreach($files as $upload) {
            if(! $upload instanceof HttpFile) {
                $var = Types::getVartype($upload);
                $msg = "Invalid 'files' parameter element: expecting 'Http\File' object. Got '{$var}'";
                throw new InvalidArgumentException($msg);
            }
            /** @var File $upload */
            $filename = $upload->getTmpName();
            try {
                $info = $imageProperties->getImageProperties($filename);
                /**
                 * getImageProperties() returns an array of image information if the specified file is an image.
                 * If specified file is not an image [errno] element is set to the error code.
                 *  filename           File path like /tmp/{file} and C:\Windows\Temp\{file}
                 *  file_ext           File extension without dot.
                 *  width              Image pixel width.
                 *  height             Image pixel height.
                 *  type               A PHP 'IMAGETYPE_*' image type specifier.
                 *  htmlSizeAttributes HTML <IMG%gt; tag string like 'width"1024" height"768"'
                 *  mime               Mime-type like 'image/jp2' and 'image/png'
                 *  channels           '3' for RGB pictures and '4' for CMYK pictures
                 *  bits               The number of bits for each color.
                 *  errno              Error code number.
                 *  error              Error message.
                 *  throw              Indicates the error is a critical error that should be thrown.
                 *  info               Extra information extracted using getimagesize().
                 */
                $prop = $info['errno'] ?? null;
                $errno = is_numeric($prop) ? intval($prop) : -1;
                if(! $errno) {
                    $fileSize = $this->_getFileSize($filename);
                    $upload->setProperty('filesize', $fileSize);
                    $image = new Image(['image' => $filename]);
                    $image->setProperty('filesize', $fileSize);
                    $upload->setProperty('image', $image);
                }
                else {
                    $upload->setProperty('error', "Error {$errno}: {$info['error']} ");
                }
            } catch (\Throwable $exc) {
                $upload->setProperty('error', "Error {$exc->getCode()}: {$exc->getMessage()}");
            }
        }
    }
    
    /**
     * Resizes an image.
     * @param HttpFile $upload
     * @param string   $destFolder
     * @param array    $saveAsOptions
     * @return array|boolean Returns the file information returned by ImageProperties else false.
     */
    public function resizeImage(HttpFile $upload, string $destFolder, array $saveAsOptions = []) {
        $image = $upload->getProperty('image');
        if(! $image instanceof Image) {
            $msg = "Invalid 'upload' parameter: the expected 'image' property is missing.";
            throw new InvalidArgumentException($msg);
        }
        if(! is_dir($destFolder) || ! ($w = is_writable($destFolder))) {
            $var = Types::getVarType($destFolder);
            $w = isset($w) ? ' writable' : '';
            $msg = "Invalid 'destFolder' parameter '{$var}' is not a{$w} directory.";
            throw new InvalidArgumentException($msg);
        }            
        $imageProperties = new ImageProperties();
        $basename = pathinfo($upload->getFullPath(), PATHINFO_FILENAME);
        $destPath = FileSystem::joinPath(DIRECTORY_SEPARATOR, $destFolder, $basename);
        try {
            $this->_applySaveOptions($image, $saveAsOptions);
            $options = $saveAsOptions['options'] ?? null;
            if(empty($options)) {
                $options = 0;
            }
            $image->options = $options | MediaConst::IMG_OPTION_ADD_FILE_EXTENSION;
            $resultFile = $image->saveAs($destPath);
            if(false === $resultFile) {
                $this->addMessage($image->getErrors(), 'error');
                return false;
            }
            $info = $imageProperties->getImageProperties($resultFile);
            $info['filesize'] = $this->_getFileSize($resultFile);
            if($info['errno']) {
                $msg = "Cannot read the image properties from the saved image file: (" . $info['errno'] . ") " . $info['error'];
                $this->addMessage($msg, 'warning');
            }
            return $info;
        } catch (\Throwable $exc) {
            $this->addMessage($exc->getMessage(), 'error');
        }
        return false;
    }

    /**
     *
     * @param Image $image
     */
    private function _applySaveOptions(Image $image, array $saveAsOptions) {
        foreach($saveAsOptions as $name => $value) {
            $lcName = strtolower($name);
            switch($lcName) {
            case 'phptype':
            case 'alignment':
            case 'quality':
            case 'width':
            case 'height':
            case 'sizing':
            case 'overlayalign':
            case 'overlaymergepct':
            case 'overlayrotate':
                $value = (int)$value;
                break;
            // case 'overlayfile':
            // case 'overlaytranscolor':
            // case 'basename':
            // case 'imagefilter':
            // case 'interlace':
            }
            $image->$lcName = $value;
        }
    }


    /**
     * Returns a file's size in bytes or zero if filesize() fails.
     * @param string $filename
     * @return int
     */
    private function _getFileSize(string $filename): int {
        if(is_file($filename) && file_exists($filename)) {
            $phpErrorHandler = new PhpErrorHandler();
            $size = $phpErrorHandler->call(function() use($filename) {return filesize($filename);});
            if(false !== $size) {
                return (int)$size;
            }
        }
        return 0;
    }

    /**
     * Returns image properties array including 'filesize' file size element.
     * @param string $file
     * @return array
     */
    public function getImageProperties(string $file): array {
        $imageProperties = new ImageProperties();
        $properties = $imageProperties->getImageProperties($file);
        $phpErrorHandler = new PhpErrorHandler();
        $size = $phpErrorHandler->call(function() use($file) {return filesize($file);});
        return array_merge(
            ['filesize' => (! $size || is_nan($size)) ? 0 : $size],
            $properties
        );
    }
    
}