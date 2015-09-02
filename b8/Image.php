<?php

namespace b8;

use b8\Image\GdImage;

class Image
{
    public static $forceGd = false;
    public static $cacheEnabled = true;
    public static $cachePath = '/tmp/';
    public static $sourcePath = './';

    protected $focalPoint;
    protected $imageId;

    /**
     * @var \b8\Image\GdImage|\Imagick $source
     */
    protected $source;


    public function __construct($imageData, $imageId = null)
    {
        $this->imageId = !is_null($imageId) ? $imageId : $imageData;

        if (!is_dir(self::$cachePath) || !is_writeable(self::$cachePath)) {
            self::$cacheEnabled = false;
        }

        $source = (!self::$forceGd && extension_loaded('imagick')) ? new \Imagick() : new GdImage();
        $source->readImageBlob($imageData);

        $this->setSource($source);
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
        $sourceRatio = $sourceWidth / $sourceHeight;
        $targetRatio = !$autoHeight ? $width / $height : $sourceRatio;

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

        $source->setImageFormat($format);

/*
        $draw = new \ImagickDraw();
        $draw->setfillcolor(new \ImagickPixel('green'));
        $draw->setfillalpha(0.5);
        $draw->setStrokeColor(new \ImagickPixel('green'));
        $draw->setStrokeWidth(2);
        $draw->rectangle($left, $top, $right, $bottom);
        $source->drawimage($draw);
*/


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
}
