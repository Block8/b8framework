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
        $width = (int)$width;
        $height = (int)$height;

        $source = $this->getSource();
        $sourceWidth = $source->getImageWidth();
        $sourceHeight = $source->getImageHeight();
        $sourceRatio = $sourceWidth / $sourceHeight;
        $targetRatio = $height != 'auto' ? $width / $height : $sourceRatio;

        $useQuad = $this->getQuadrant($sourceWidth, $sourceHeight);

        if ($sourceRatio <= $targetRatio) {
            $scale = $sourceWidth / $width;
        } else {
            $scale = $sourceHeight / $height;
        }

        $resizeWidth = (int)($sourceWidth / $scale);
        $resizeHeight = (int)($sourceHeight / $scale);

        if ($height == 'auto') {
            $height = $resizeHeight;
        }

        $source->scaleImage($resizeWidth, $resizeHeight);

        list($cropX, $cropY) = $this->getCropPosition($useQuad, $width, $height, $resizeWidth, $resizeHeight);

        $source->cropImage($width, $height, $cropX, $cropY);
        $source->setImageFormat($format);

        return $source;
    }

    protected function getCropPosition($useQuad, $width, $height, $resizeWidth, $resizeHeight)
    {
        switch ($useQuad) {
            case 'top_left':
                $cropX = 0;
                $cropY = 0;
                break;

            case 'top_right':
                $cropX = ($resizeWidth - $width);
                $cropY = 0;
                break;

            case 'middle_left':
                $cropX = 0;
                $cropY = ($resizeHeight - $height) / 2;
                break;

            case 'middle-right':
                $cropX = ($resizeWidth - $width);
                $cropY = ($resizeHeight - $height) / 2;
                break;

            case 'bottom_left':
                $cropX = 0;
                $cropY = ($resizeHeight - $height);
                break;

            case 'bottom_right':
                $cropX = ($resizeWidth - $width);
                $cropY = ($resizeHeight - $height);
                break;
        }

        return array($cropX, $cropY);
    }

    protected function getQuadrant($sourceWidth, $sourceHeight)
    {
        $focal = array(0, 0);

        if (!empty($this->focalPoint)) {
            $focal = $this->focalPoint;
        }

        $focalX = (int)$focal[0];
        $focalY = (int)$focal[1];

        $quads = $this->getQuadrants($sourceWidth, $sourceHeight);

        foreach ($quads as $name => $l) {
            if ($focalX >= $l[0] && $focalX <= $l[1] && $focalY >= $l[2] && $focalY <= $l[3]) {
                $useQuad = $name;
            }
        }

        return $useQuad;
    }

    protected function getQuadrants($imageX, $imageY)
    {
        $rtn = array();
        $rtn['top_left'] = array(0, $imageX / 2, 0, $imageY / 3);
        $rtn['top_right'] = array(($imageX / 2) + 1, $imageX, 0, $imageY / 3);
        $rtn['middle_left'] = array(0, $imageY / 2, ($imageY / 3) + 1, (($imageY / 3) * 2));
        $rtn['middle_right'] = array(($imageX / 2) + 1, $imageX, ($imageY / 3) + 1, (($imageY / 3) * 2));
        $rtn['bottom_left'] = array(0, $imageY / 2, (($imageY / 3) * 2) + 1, $imageY);
        $rtn['bottom_right'] = array(($imageX / 2) + 1, $imageX, (($imageY / 3) * 2) + 1, $imageY);

        return $rtn;
    }
}
