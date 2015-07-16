<?php

namespace b8;

class Image
{
    public static $cachePath = '/tmp/';
    public static $sourcePath = './';

    /**
     * @var \Imagick
     */
    protected $source;

    /**
     * @var array
     */
    protected $focalPoint;

    protected $imageId;

    public function __construct($imagePath)
    {
        $this->imageId = md5($imagePath);
        $this->setSource(new \Imagick(self::$sourcePath . $imagePath));
    }

    /**
     * @return \Imagick
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param \Imagick $image
     */
    public function setSource(\Imagick $image)
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

        if (file_exists($cachePath) && 0) {
            $output = file_get_contents($cachePath);
        } else {
            $output = $this->doRender($width, $height, $format);
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

        $crop = false;

        if ($sourceRatio < $targetRatio) {
            $crop = true;
            $scale = $sourceWidth / $width;
            $resizeWidth = $width;
            $resizeHeight = ceil($sourceHeight / $scale);

            if ($autoHeight) {
                $height = $resizeHeight;
            }

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

            if ($autoHeight) {
                $height = $resizeHeight;
            }

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
