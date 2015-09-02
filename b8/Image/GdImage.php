<?php

namespace b8\Image;

class GdImage
{
    protected $resource;
    protected $outputFormat = 'jpeg';

    public static function blankImage($width = 1, $height = 1)
    {
        ob_start();

        $res = imagecreatetruecolor($width, $height);
        imagegif($res);

        $blob = ob_get_contents();
        ob_end_clean();

        imagedestroy($res);

        return $blob;
    }

    public function __construct($path = null)
    {
        if (!is_null($path)) {
            $extension = strrpos($path, '.');
            $extension = substr($path, $extension + 1);

            switch (strtolower($extension)) {
                case 'jpg':
                case 'jpeg':
                    $this->resource = @imagecreatefromjpeg($path);
                    break;

                case 'png':
                    $this->resource = @imagecreatefrompng($path);
                    break;

                case 'gif':
                    $this->resource = @imagecreatefromgif($path);
                    break;
            }

            if (!is_resource($this->resource)) {
                throw new \Exception('Could not load image: ' . $path);
            }
        }
    }

    /**
     * @param string $image
     * @throws \Exception
     */
    public function readImageBlob($image)
    {
        $this->resource = @imagecreatefromstring($image);

        if (!is_resource($this->resource)) {
            throw new \Exception('Could not create image from string.');
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

    /**
     * Polyfill for Imagick::scaleImage() - Note that we don't use GD's imagescale() function as it seems to mess
     * with the image's colours too much.
     *
     * @param int $width
     * @param int $height
     * @throws \Exception
     */
    public function scaleImage($width, $height)
    {
        $new = @imagecreatetruecolor($width, $height);

        if (!is_resource($new)) {
            throw new \Exception('Could not create scaled image: ' . $width . 'x' . $height);
        }

        imagecopyresampled($new, $this->resource, 0, 0, 0, 0, $width, $height, $this->getImageWidth(), $this->getImageHeight());
        imagedestroy($this->resource);

        $this->resource = $new;

        if (!is_resource($this->resource)) {
            throw new \Exception('Could not scale image to ' . $width . 'x' . $height);
        }
    }

    /**
     * Polyfill for Imagick::cropImage() - Note that we don't use GD's imagecrop() function as older versions
     * add weird black borders in some circumstances.
     * @param int $width
     * @param int $height
     * @param int $left
     * @param int $top
     * @throws \Exception
     */
    public function cropImage($width, $height, $left = 0, $top = 0)
    {
        $new = @imagecreatetruecolor($width, $height);

        if (!is_resource($new)) {
            throw new \Exception('Could not create cropped image: ' . $width . 'x' . $height);
        }

        $sourceWidth = $width > $this->getImageWidth() ? $this->getImageWidth() : $width;
        $sourceHeight = $height > $this->getImageHeight() ? $this->getImageHeight() : $height;

        imagecopyresampled($new, $this->resource, 0, 0, $left, $top, $width, $height, $sourceWidth, $sourceHeight);
        imagedestroy($this->resource);

        $this->resource = $new;

        if (!is_resource($this->resource)) {
            throw new \Exception('Could not crop image to ' . $width . 'x' . $height);
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
                imagealphablending($this->resource, true);
                imagesavealpha($this->resource, true);
                imagepng($this->resource);
                break;
        }

        $blob = ob_get_contents();
        ob_end_clean();

        imagedestroy($this->resource);

        return $blob;
    }
}