<?php

namespace b8\Http;

class Upload
{
    protected $upload;
    protected $postKey;
    protected $fileInfo;

    public function __construct($postKey)
    {
        $this->postKey = $postKey;

        if (!array_key_exists($postKey, $_FILES)) {
            throw new \Exception('No file upload with key: '. $postKey);
        }

        $this->upload = $_FILES[$postKey];

        $this->handleUploadErrors($this->upload['error']);
        $this->setFileInfo();
    }

    protected function handleUploadErrors($errorCode)
    {
        $exception = null;

        switch ($errorCode) {
            case UPLOAD_ERR_CANT_WRITE:
                $exception = 'Could not write file to disk.';
                break;

            case UPLOAD_ERR_EXTENSION:
                $exception = 'A PHP extension prevented the file upload.';
                break;

            case UPLOAD_ERR_INI_SIZE:
                $exception = 'The file size exceeded upload_max_filesize in php.ini.';
                break;

            case UPLOAD_ERR_FORM_SIZE:
                $exception = 'The file size exceeded the form\'s MAX_FILE_SIZE directive.';
                break;

            case UPLOAD_ERR_PARTIAL:
                $exception = 'The file was only partially uploaded.';
                break;

            case UPLOAD_ERR_NO_FILE:
                $exception = 'No file was uploaded.';
                break;

            case UPLOAD_ERR_NO_TMP_DIR:
                $exception = 'The temporary folder for uploads does not exist.';
                break;
        }

        if (!is_null($exception)) {
            throw new \Exception('Could not upload file: ' . $exception);
        }
    }

    protected function setFileInfo()
    {
        $info = pathinfo($this->upload['name']);

        $this->fileInfo = array(
            'basename' => $info['basename'],
            'filename' => $info['filename'],
            'extension' => $info['extension'],
            'type' => $this->upload['type'],
            'size' => $this->upload['size'],
            'hash' => md5_file($this->upload['tmp_name']),
        );

        if (function_exists('finfo_open')) {
            $this->fileInfo['type'] = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $this->upload['tmp_name']);
        }
    }

    public function getFileInfo()
    {
        return $this->fileInfo;
    }

    public function copyTo($destinationFile)
    {
        $dir = dirname($destinationFile);

        if (!is_dir($dir)) {
            throw new \Exception('Destination does not exist.');
        }

        if (!move_uploaded_file($this->upload['tmp_name'], $destinationFile)) {
            throw new \Exception('Could not copy file to: ' . $destinationFile);
        }
    }
}