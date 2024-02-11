<?php

class Maven_Html5uploader_Model_Media_Uploader
{

    private $_validateCallbacks = array();

    private $_allowedExtensions = array();

    private $_fileExists = false;

    private $_enableFilesDispersion = false;

    private $_validMimeTypes;

    private $_fileName ;

    private $_allowRenameFiles = false;

    private $_allowCreateFolders = false;

    private $_caseInsensitiveFilenames = true;
    
    function __construct()
    {
        $this->_fileName = isset($_SERVER['HTTP_X_FILE_NAME']) ? $_SERVER['HTTP_X_FILE_NAME'] : false;
        if ($this->_fileName) {
            $this->_fileExists = true;
        }
    }

    public function setAllowedExtensions($extensions = array())
    {
        foreach ((array)$extensions as $extension) {
            $this->_allowedExtensions[] = strtolower($extension);
        }
        return $this;
    }

    public function setValidMimeTypes($mimeTypes = array())
    {
        $this->_validMimeTypes = array();
        foreach ((array) $mimeTypes as $mimeType) {
            $this->_validMimeTypes[] = $mimeType;
        }
        return $this;
    }

    public function addValidateCallback($callbackName, $callbackObject, $callbackMethod)
    {
        $this->_validateCallbacks[$callbackName] = array(
            'object' => $callbackObject,
            'method' => $callbackMethod
        );
        return $this;
    }
    
    public function save($destinationFolder, $newFileName = null)
    {
        $this->_validateFile();

        if ($this->_allowCreateFolders) {
            $this->_createDestinationFolder($destinationFolder);
        }

        if (!is_writable($destinationFolder)) {
            throw new Exception('Destination folder is not writable or does not exists.');
        }

        $this->_result = false;

        $destinationFile = $destinationFolder;
        $fileName = isset($newFileName) ? $newFileName : $this->_fileName;
        $fileName = self::getCorrectFileName($fileName);
        if ($this->_enableFilesDispersion) {
            $fileName = $this->_correctFileNameCase($fileName);
            $this->setAllowCreateFolders(true);
            $this->_dispretionPath = self::getDispretionPath($fileName);
            $destinationFile.= $this->_dispretionPath;
            $this->_createDestinationFolder($destinationFile);
        }

        if ($this->_allowRenameFiles) {
            $fileName = self::getNewFileName(self::_addDirSeparator($destinationFile) . $fileName);
        }

        $destinationFile = self::_addDirSeparator($destinationFile) . $fileName;

        $_result = file_put_contents(
            $destinationFile,
            file_get_contents('php://input')
        );
        //    echo
        if ($_result) {
            chmod($destinationFile, 0666);
            if ($this->_enableFilesDispersion) {
                $fileName = str_replace(DIRECTORY_SEPARATOR, '/',
                        self::_addDirSeparator($this->_dispretionPath)) . $fileName;
            }
            $this->_uploadedFileName = $fileName;
            $this->_uploadedFileDir = $destinationFolder;
          //  $this->_result = $this->_file;
            $this->_result['path'] = $destinationFolder;
            $this->_result['file'] = $fileName;
            $this->_result['url'] = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA).'tmp/catalog/product'.$fileName;
        }

        return $this->_result;
    }

    public function checkAllowedExtension($extension)
    {
        if (!is_array($this->_allowedExtensions) || empty($this->_allowedExtensions)) {
            return true;
        }

        return in_array(strtolower($extension), $this->_allowedExtensions);
    }

    public function checkMimeType($validTypes = array())
    {
        try {
            if ($validTypes && count($validTypes) > 0) {
                $validator = new Zend_Validate_File_MimeType($validTypes);
                return $validator->isValid($this->_fileName);
            }
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function getFileExtension()
    {
        return $this->_fileExists ? pathinfo($this->_fileName, PATHINFO_EXTENSION) : '';
    }
    
    protected function _validateFile()
    {
        //is file extension allowed
        if (!$this->checkAllowedExtension($this->getFileExtension())) {
            throw new Exception('Disallowed file type.');
        }

        /*
         * Validate MIME-Types.
         */
        if (!$this->checkMimeType($this->_validMimeTypes)) {
            throw new Exception('Invalid MIME type.');
        }
    }

    public function setAllowRenameFiles($flag)
    {
        $this->_allowRenameFiles = $flag;
        return $this;
    }

    public function setAllowCreateFolders($flag)
    {
        $this->_allowCreateFolders = $flag;
        return $this;
    }

    public function setFilesDispersion($flag)
    {
        $this->_enableFilesDispersion = $flag;
        return $this;
    }
    
    public function remove($file, $path)
    {
        if (file_exists($path.$file)){
            return unlink($path.$file);
        }
        return false;
    }

    private function _correctFileNameCase($fileName)
    {
        if ($this->_caseInsensitiveFilenames) {
            return strtolower($fileName);
        }
        return $fileName;
    }

    private function _createDestinationFolder($destinationFolder)
    {
        if (!$destinationFolder) {
            return $this;
        }

        if (substr($destinationFolder, -1) == DIRECTORY_SEPARATOR) {
            $destinationFolder = substr($destinationFolder, 0, -1);
        }

        if (!(@is_dir($destinationFolder) || @mkdir($destinationFolder, 0777, true))) {
            throw new Exception("Unable to create directory '{$destinationFolder}'.");
        }
        return $this;
    }

    static public function getCorrectFileName($fileName)
    {
        $fileName = preg_replace('/[^a-z0-9_\\-\\.]+/i', '_', $fileName);
        $fileInfo = pathinfo($fileName);

        if (preg_match('/^_+$/', $fileInfo['filename'])) {
            $fileName = 'file.' . $fileInfo['extension'];
        }
        return $fileName;
    }

    static public function getNewFileName($destFile)
    {
        $fileInfo = pathinfo($destFile);
        if (file_exists($destFile)) {
            $index = 1;
            $baseName = $fileInfo['filename'] . '.' . $fileInfo['extension'];
            while( file_exists($fileInfo['dirname'] . DIRECTORY_SEPARATOR . $baseName) ) {
                $baseName = $fileInfo['filename']. '_' . $index . '.' . $fileInfo['extension'];
                $index ++;
            }
            $destFileName = $baseName;
        } else {
            return $fileInfo['basename'];
        }

        return $destFileName;
    }

    static public function getDispretionPath($fileName)
    {
        $char = 0;
        $dispretionPath = '';
        while (($char < 2) && ($char < strlen($fileName))) {
            if (empty($dispretionPath)) {
                $dispretionPath = DIRECTORY_SEPARATOR
                    . ('.' == $fileName[$char] ? '_' : $fileName[$char]);
            } else {
                $dispretionPath = self::_addDirSeparator($dispretionPath)
                    . ('.' == $fileName[$char] ? '_' : $fileName[$char]);
            }
            $char ++;
        }
        return $dispretionPath;
    }
    static protected function _addDirSeparator($dir)
    {
        if (substr($dir,-1) != DIRECTORY_SEPARATOR) {
            $dir.= DIRECTORY_SEPARATOR;
        }
        return $dir;
    }
}