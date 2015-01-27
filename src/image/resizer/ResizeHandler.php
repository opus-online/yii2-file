<?php
/**
 *
 * @author Mihkel Viilveer <mihkel@opus.ee>
 * @date 21.01.2015
 */

namespace opus\file\image\resizer;


use Gregwar\Image\Image;
use opus\file\FileSystem;
use yii\base\InvalidParamException;
use yii\base\Object;
use yii\web\UrlManager;

/**
 * Class ResizeHandler
 *
 * @author Mihkel Viilveer <mihkel@opus.ee>
 * @package netiarst\file\image\resizer
 */
class ResizeHandler extends Object
{
    /**
     * Location for thumbnail
     * @var string
     */
    public $thumbnailPath;

    /**
     * Original image file with path
     * @var string
     */
    public $originalImage;

    /**
     * Thumb height
     * @var int
     */
    public $height;

    /**
     * Thumb width
     * @var int
     */
    public $width;

    /**
     * Name of thumbnail name
     * @var string
     */
    public $thumbnailName;

    /**
     * File system component id
     * @var string
     */
    public $fileSystem = 'fs';

    /**
     * Requires Gregwar's Image class
     *
     * @return bool|mixed|string
     * @throws \Exception
     */
    public function resize()
    {
        $image = new Image($this->originalImage);
        $this->getFileSystem()->prepareDirectoryForFile($this->thumbnailPath);
        return $this
            ->resizeImage($image)
            ->save(sprintf('%s/%s', $this->thumbnailPath, $this->thumbnailName));
    }

    /**
     * Resizes image according to the Thumbnail attributes
     *
     * @param Image $image
     * @return Image
     */
    protected function resizeImage(Image $image)
    {
        // scaleResize/cropResize work this way
        if ($this->width > $this->height) {
            $image->scale($this->width, $this->height);
        } else {
            $image->cropResize($this->width, $this->height);
        }

        return $image;
    }

    /**
     * @return FileSystem
     * @throws \yii\base\InvalidConfigException
     */
    private function getFileSystem()
    {
        return \Yii::$app->get($this->fileSystem);
    }


}