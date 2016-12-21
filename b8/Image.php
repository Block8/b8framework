<?php

namespace b8;

use b8\Image\GdImage;

class Image
{
    public static $forceGd = false;
    public static $cacheEnabled = true;
    public static $baseCachePath = '/tmp/';
    public static $cachePath;
    public static $sourcePath = './';

    protected $focalPoint;
    protected $imageId;
    protected $mime;

    /**
     * @var \b8\Image\GdImage|\Imagick $source
     */
    protected $source;


    public function __construct($imageData, $imageId = null)
    {
        $this->imageId = !is_null($imageId) ? $imageId : md5($imageData);

        $this->prepareCache();

        $source = (!self::$forceGd && extension_loaded('imagick')) ? new \Imagick() : new GdImage();
        $source->readImageBlob($imageData);

        $this->setSource($source);
    }

    protected function prepareCache()
    {
        if (!self::$cacheEnabled) {
            return;
        }

        self::$cachePath = realpath(self::$baseCachePath) . '/images/' . substr($this->imageId, 0, 1) . '/';

        if (!is_dir(self::$cachePath) && !@mkdir(self::$cachePath, 0777, true)) {
            self::$cacheEnabled = false;
            return;
        }

        self::$cacheEnabled = true;
    }

    /**
     * @return \b8\Image\GdImage|\Imagick
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param \b8\Image\GdImage|\Imagick $image
     */
    public function setSource($image)
    {
        $this->source = $image;
        $this->mime = $image->getImageMimeType();
    }

    public function setFocalPoint($focalX, $focalY)
    {
        $this->focalPoint = array($focalX, $focalY);
    }

    public function render($width, $height, $format = 'jpeg')
    {
        $cachePath = self::$cachePath . $this->imageId . '.' . $width . 'x' . $height . '.' . $format;

        if (self::$cacheEnabled && file_exists($cachePath)) {
            $output = file_get_contents($cachePath);
            return $output;
        }

        $output = $this->doRender($width, $height, $format);

        if (self::$cacheEnabled) {
            file_put_contents($cachePath, $output);
        }

        return $output;
    }

    public function doRender($width, $height, $format = 'jpeg')
    {
        $autoHeight = ($height == 'auto');
        $width = (int)$width;
        $height = (int)$height;

        $source = $this->getSource();

        $sourceWidth = $source->getImageWidth();
        $sourceHeight = $source->getImageHeight();

        if ($width == 'auto' && $height == 'auto') {
            $width = $sourceWidth;
            $height = $sourceHeight;
        }

        $sourceRatio = $sourceWidth / $sourceHeight;
        $targetRatio = !$autoHeight ? $width / $height : $sourceRatio;

        if ($width == 'auto' && is_numeric($height)) {
            $width = ceil($height * ($sourceWidth / $sourceHeight));
        }

        if ($autoHeight) {
            $height = ceil($width * ($sourceHeight / $sourceWidth));
        }

        $crop = false;

        if ($sourceRatio < $targetRatio) {
            $crop = true;
            $scale = $sourceWidth / $width;
            $resizeWidth = $width;
            $resizeHeight = ceil($sourceHeight / $scale);

            list($focalX, $focalY) = $this->getFocalPoints();

            $focalPercentage = (100/$sourceHeight) * $focalY;

            $top = round((($resizeHeight / 100) * $focalPercentage) - ($height / 2));
            $bottom = round((($resizeHeight / 100) * $focalPercentage) + ($height / 2));
            $left = 0;
            $right = $resizeWidth;

            if ($top < 0) {
                $top = 0;
            } else if ($bottom > $resizeHeight) {
                $top = $resizeHeight - $height;
            }
        } elseif ($sourceRatio > $targetRatio) {
            $crop = true;
            $scale = $sourceHeight / $height;
            $resizeWidth = ceil($sourceWidth / $scale);
            $resizeHeight = $height;

            list($focalX, $focalY) = $this->getFocalPoints();

            $focalPercentage = (100/$sourceWidth) * $focalX;

            $top = 0;
            $bottom = $resizeHeight;
            $left = (($resizeWidth / 100) * $focalPercentage) - ($width / 2);
            $right = (($resizeWidth / 100) * $focalPercentage) + ($width / 2);

            if ($left < 0) {
                $left = 0;
            } else if ($right > $resizeWidth) {
                $left = $resizeWidth - $width;
            }
        } else {
            $resizeWidth = $width;
            $resizeHeight = $height;
        }

        $source->scaleImage($resizeWidth, $resizeHeight);

        if ($crop) {
            $source->cropImage($width, $height, $left, $top);
        }

        $this->setImageFormat($source, $format);

        return $source->getImageBlob();
    }

    protected function getFocalPoints()
    {
        $focal = [0, 0];

        if (!empty($this->focalPoint)) {
            $focal = $this->focalPoint;
        }

        $focalX = (int)$focal[0];
        $focalY = (int)$focal[1];

        return [$focalX, $focalY];
    }

    public function getImageWidth()
    {
        return $this->getSource()->getImageWidth();
    }

    public function getImageHeight()
    {
        return $this->getSource()->getImageHeight();
    }

    /**
     * @param \Imagick|GdImage $source
     * @param string $format
     */
    protected function setImageFormat($source, $format)
    {
        if ($format == 'jpg') {
            $format = 'jpeg';
        }

        try {
            $source->setImageFormat($format);

            if ($format == 'webp' && $source instanceof \Imagick) {
                $source->setImageAlphaChannel(\Imagick::ALPHACHANNEL_ACTIVATE);
                $source->setBackgroundColor(new \ImagickPixel('transparent'));
            }

            $this->mime = 'image/' . $format;
        } catch (\Exception $ex) {
            $this->mime = 'image/jpeg';
            $source->setImageFormat('jpeg');
        }
    }

    public function getMime()
    {
        return $this->mime;
    }
}
