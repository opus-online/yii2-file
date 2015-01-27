<?php
namespace opus\file\uploader;

use netiarst\file\image\FileHelper;
use opus\file\FileSystem;
use Yii;
use yii\base\InvalidParamException;
use yii\base\Model;
use yii\base\Object;
use yii\helpers\Html;
use yii\validators\Validator;
use yii\web\UploadedFile;
use yii\web\UrlManager;

/**
 * Class UploadHandler
 * Uploads images
 * Initiates thumbnail generating
 *
 * @author Mihkel Viilveer <mihkel@opus.ee>
 * @package netiarst\file\image\uploader
 */
class UploadHandler extends Object
{
    /**
     * Location where files are saved
     * @var string
     */
    public $filePath;

    /**
     * Name of the file field (can be property of a model or a field exact name)
     * @var string
     */
    public $fileFieldName;

    /**
     * Specify this, when loading image from ActiveForm form
     * @var Model
     */
    public $formModel;

    /**
     * File name of uploaded file
     * @var string
     */
    public $randomHashAsFileName = false;

    /**
     * Attach custom validator to validate files
     * https://github.com/yiisoft/yii2/blob/master/docs/guide/input-validation.md#ad-hoc-validation-
     * @var Validator
     */
    public $fileValidator;

    /**
     * File system component id
     * @var string
     */
    public $fileSystem = 'fs';

    /**
     * Paths of uploaded files
     * @var array
     */
    private $uploadedFileNames = [];

    /**
     * 1. get uploaded images OR imported images
     * 2. Save the original
     * 3. generate and save the thumbs
     * 4. create a new relation
     */
    public function handleUploadedFiles()
    {
        foreach ($this->getUploadedFiles() as $tempFile) {
            $this->validateFile($tempFile);
            $fileSavePath = $this->filePath . '/' . $this->getFileName($tempFile);
            $this->getFileSystem()->prepareDirectoryForFile($fileSavePath);
            $tempFile->saveAs($fileSavePath);
            $this->uploadedFileNames[] = $this->getFileName($tempFile);
        }
        return $this;
    }

    /**
     * @return \yii\web\UploadedFile[]
     */
    public function getUploadedFiles()
    {
        if ($this->formModel instanceof Model) {
            $files = UploadedFile::getInstances($this->formModel, $this->fileFieldName);
        } else {
            $files = UploadedFile::getInstancesByName($this->fileFieldName);
        }
        return $files;
    }

    /**
     * @param UploadedFile $file
     * @return bool
     */
    protected function validateFile(UploadedFile $file)
    {
        if ($this->fileValidator instanceof Validator) {
            if ($this->fileValidator->validate($file, $error) === false) {
                throw new InvalidParamException($error);
            }
        }
        return true;
    }

    /**
     * @param UploadedFile $file
     * @return string
     */
    protected function getFileName(UploadedFile $file)
    {
        $filename = $file->name;
        if ($this->randomHashAsFileName === true) {
            self::generateFileName($filename, $this->fileNameLength);
        }
        return $filename;
    }

    /**
     * Returns uploaded file names
     * If one file was uploaded, string is returned
     * @return array|string
     */
    public function getUploadedFileNames()
    {
        if (count($this->uploadedFileNames) === 1) {
            return reset($this->uploadedFileNames);
        }
        return $this->uploadedFileNames;
    }

    /**
     * @return FileSystem
     * @throws \yii\base\InvalidConfigException
     */
    private function getFileSystem()
    {
        return Yii::$app->get($this->fileSystem);
    }

    /**
     * Creates a cryptographically safe pseudo-random number with a specified length
     *
     * @param int $length
     * @param string $originalName
     * @return string
     */
    static public function generateFileName($originalName, $length = null)
    {
        $hash = hash('sha512', base64_encode(openssl_random_pseudo_bytes(512)));
        is_null($length) && $length = strlen($hash);
        return sprintf('%s.%s', substr($hash, 0, $length), pathinfo($originalName, PATHINFO_EXTENSION));
    }
}
