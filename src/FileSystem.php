<?php
/**
 *
 * @author Ivo Kund <ivo@opus.ee>
 * @date 12.05.2014
 */

namespace opus\file;

use opus\file\exception\FileSystemException;
use yii\base\Component;

/**
 * Class FileSystem
 *
 * @author Ivo Kund <ivo@opus.ee>
 * @package common\components
 */
class FileSystem extends Component
{
    const COMPONENT_NAMESPACE = 'application.FileSystem';
    /**
     * @var string
     */
    public $tempAlias = '@app/runtime';
    /**
     * @var string
     */
    public $tempPath;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->tempPath = \Yii::getAlias($this->tempAlias);
    }

    /**
     * Wrapper for php-s built-in tempnam
     *
     * @param string $prefix
     * @return string
     */
    public function createTempFile($prefix)
    {
        return tempnam($this->tempPath, $prefix);
    }

    /**
     * Moves a file to a location, creates a directory if necessary
     *
     * @param string $fileFrom
     * @param string $fileTo
     * @param int $chmod
     * @throws FileSystemException
     * @return string The final path of file
     */
    public function moveFile($fileFrom, $fileTo, $chmod = null)
    {
        $this->trace(sprintf('Moving file "%s" to "%s"', $fileFrom, $fileTo));
        if (@rename($fileFrom, $this->prepareDirectoryForFile($fileTo))) {
            if (!is_null($chmod)) {
                $this->chmodFile($fileTo, $chmod);
            }

            return $fileTo;
        }
        throw new FileSystemException(sprintf('Could not move file from %s to: %s', $fileFrom, $fileTo));
    }

    /**
     * @param $message
     */
    protected function trace($message)
    {
        \Yii::trace($message, self::COMPONENT_NAMESPACE);
    }

    /**
     * Checks if a directory exists for a file path and creates it if not
     *
     * @param string $file
     * @return mixed The file path
     */
    public function prepareDirectoryForFile($file)
    {
        $this->prepareDirectory(dirname($file));

        return $file;
    }

    /**
     * Checks if a directory exists and creates it if not
     *
     * @param string $directory
     * @return string The directory path
     * @throws \opus\file\exception\FileSystemException
     */
    public function prepareDirectory($directory)
    {
        is_dir($directory) || $this->createDirectory($directory);

        return $directory;
    }

    /**
     * @param string $path
     * @return string The directory path
     * @throws FileSystemException
     */
    public function createDirectory($path)
    {
        $this->trace(sprintf('Creating directory "%s"', $path));
        if (@mkdir($path, 0777, true)) {
            return $path;
        }
        throw new FileSystemException('Could not create directory: ' . $path);
    }

    /**
     * Sets access rules
     *
     * @param string $path
     * @param int $chmod
     * @return bool
     */
    public function chmodFile($path, $chmod)
    {
        return chmod($path, $chmod);
    }

    /**
     * Copies a file to a location, creates a directory if necessary
     *
     * @param string $fileFrom
     * @param string $fileTo
     * @param int $chmod
     * @throws \opus\file\exception\FileSystemException
     * @return string The final path of file
     */
    public function copyFile($fileFrom, $fileTo, $chmod = null)
    {
        $this->trace(sprintf('Copying file "%s" to "%s"', $fileFrom, $fileTo));
        if (@copy($fileFrom, $this->prepareDirectoryForFile($fileTo))) {
            if (!is_null($chmod)) {
                $this->chmodFile($fileTo, $chmod);
            }

            return $fileTo;
        }
        throw new FileSystemException(sprintf('Could not copy file from %s to: %s', $fileFrom, $fileTo));
    }

    /**
     * Deletes a file
     *
     * @param string $file
     * @return bool
     * @throws FileSystemException
     */
    public function delete($file)
    {
        $this->trace(sprintf('Deleting file "%s"', $file));

        if (file_exists($file)) {
            if (@unlink($file)) {
                return true;
            }
        }

        throw new FileSystemException('Could not delete file: ' . $file);
    }
}
