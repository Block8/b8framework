<?php

namespace b8\Image;

class GdImage
{
    protected $resource;
    protected $outputFormat = 'jpeg';

    public function __construct($path)
    {
        $extension = strrpos($path, '.');
        $extension = substr($path, $extension + 1);

        switch (strtolower($extension)) {
            case 'jpg':
            case 'jpeg':
                $this->resource = imagecreatefromjpeg($path);
                break;

            case 'png':
                $this->resource = imagecreatefrompng($path);
                break;

            case 'gif':
                $this->resource = imagecreatefromgif($path);
                break;
        }

        if (!is_resource($this->resource)) {
            throw new \Exception('Could not load image: ' . $path);
        }
    }

    /**
     * @return int
     */
    public function getImageWidth()
    {
        return imagesx($this->resource);
    }

    /**
     * @return int
     */
    public function getImageHeight()
    {
        return imagesy($this->resource);
    }

    public function scaleImage($width, $height)
    {
        $new = imagecreatetruecolor($width, $height);

        imagecopyresampled($new, $this->resource, 0, 0, 0, 0, $width, $height, $this->getImageWidth(), $this->getImageHeight());
        imagedestroy($this->resource);

        $this->resource = $new;

        if (!is_resource($this->resource)) {
            throw new \Exception('Could not scale image.');
        }
    }

    public function cropImage($width, $height, $left = 0, $top = 0)
    {
        $new = imagecrop($this->resource, [
            'width' => $width,
            'height' => $height,
            'x' => $left,
            'y' => $top,
        ]);

        imagedestroy($this->resource);
        $this->resource = $new;

        if (!is_resource($this->resource)) {
            throw new \Exception('Could not crop image.');
        }
    }

    public function setImageFormat($format = 'jpeg') {
        switch ($format) {
            case 'jpeg':
            case 'jpg':
                $this->outputFormat = 'jpeg';
                break;

            case 'png':
                $this->outputFormat = 'png';
                break;
        }
    }

    public function getImageBlob()
    {
        ob_start();

        switch ($this->outputFormat) {
            case 'jpeg':
                imagejpeg($this->resource);
                break;

            case 'png':
                imagepng($this->resource);
                break;
        }

        $blob = ob_get_contents();
        ob_end_clean();

        imagedestroy($this->resource);

        return $blob;
    }
}