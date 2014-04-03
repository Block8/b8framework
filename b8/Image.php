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

        $quad = $this->getQuadrant($sourceWidth, $sourceHeight);

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

        list($cropX, $cropY) = $this->getCropPosition($quad, $scale, $width, $height, $resizeWidth, $resizeHeight);
        $source->cropImage($width, $height, $cropX, $cropY);
        $source->setImageFormat($format);

        return $source->getImageBlob();
    }

    protected function getCropPosition($quad, $scale, $width, $height, $resizeWidth, $resizeHeight)
    {
        $quadLeft = round($quad[1][0] / $scale);
        $quadRight = round($quad[1][1] / $scale);
        $quadTop = round($quad[1][2] / $scale);
        $quadBottom = round($quad[1][3] / $scale);

        $centerX = round($resizeWidth / 2);
        $centerY = round($resizeHeight / 2);


        $halfWidth = ($resizeWidth - $width) / 2;
        $halfHeight = ($resizeHeight - $height) / 2;

        if ($quadLeft <= $centerX && $quadRight >= $centerX) {
            $cropX = $halfWidth;
        } elseif ($quadLeft <= $centerX && $quadRight <= $centerX) {
            $cropX = $quadLeft;
        } else {
            $cropX = $quadRight;
        }

        if ($quadTop <= $centerY && $quadBottom >= $centerY) {
            $cropY = $halfHeight;
        } elseif ($quadTop <= $centerY && $quadBottom <= $centerY) {
            $cropY = $quadTop;
        } else {
            $cropY = $quadBottom;
        }

        return array($cropX, $cropY);
    }

    protected function getQuadrant($sourceWidth, $sourceHeight)
    {
        $focal = array(round($sourceWidth / 2), round($sourceHeight / 2));

        if (!empty($this->focalPoint)) {
            $focal = $this->focalPoint;
        }

        $focalX = (int)$focal[0];
        $focalY = (int)$focal[1];
        $quads = $this->getQuadrants($sourceWidth, $sourceHeight);
        $useQuad = null;

        foreach ($quads as $name => $l) {
            if ($focalX >= $l[0] && $focalX <= $l[1] && $focalY >= $l[2] && $focalY <= $l[3]) {
                $useQuad = [$name, $l];
                break;
            }
        }

        return $useQuad;
    }

    protected function getQuadrants($imageX, $imageY)
    {
        $split = 4;

        $rtn = [];

        $quadrantWidth = round($imageX / $split);
        $quadrantHeight = round($imageY / $split);

        for ($i = 0; $i < $split; $i++) {
            for ($j = 0; $j < $split; $j++) {
                $rtn[$i . 'x' . $j] = [$quadrantWidth * $i, $quadrantWidth * ($i + 1), $quadrantHeight * $j, $quadrantHeight * ($j+1)];
            }
        }

        return $rtn;
    }
}
