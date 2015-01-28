<?php
namespace opus\file\uploader;

use opus\file\FileSystem;
use Yii;
use yii\base\InvalidParamException;
use yii\base\Model;
use yii\base\Object;
use yii\validators\Validator;
use yii\web\UploadedFile;

/**
 * Class UploadHandler
 * Uploads images
 * Initiates thumbnail generating
 *
 * @author Mihkel Viilveer <mihkel@opus.ee>
 * @package opus\file\uploader
 */
class UploadHandler extends Object
{
    /**
     * Location where files are saved
     * @var string
     */
    private $filePath;

    /**
     * Name of the file field (can be property of a model or a field exact name)
     * @var string
     */
    private $fileFieldName;

    /**
     * File system component id
     * @var FileSystem
     */
    private $fileSystem;

    /**
     * Paths of uploaded files
     * @var array
     */
    private $uploadedFileNames = [];

    /**
     * Specify this, when loading image from ActiveForm form
     * @var Model
     */
    public $formModel;

    /**
     * Attach custom validator to validate files
     * https://github.com/yiisoft/yii2/blob/master/docs/guide/input-validation.md#ad-hoc-validation-
     * @var Validator
     */
    public $fileValidator;

    /**
     * @param array $filePath
     * @param $fileFieldName
     * @param Model $formModel
     */
    public function __construct($filePath, Model $formModel, $fileFieldName)
    {
        $this->filePath = $filePath;
        $this->fileFieldName = $fileFieldName;
        $this->formModel = $formModel;
    }

    /**
     * @param callable $formatFileName This is a function to format file names.
     * Callback signature is function($filename, $fileExtension)
     * @return $this
     */
    public function handleUploadedFiles(\Closure $formatFileName = null)
    {
        foreach ($this->getUploadedFiles() as $tempFile) {
            $this->validateFile($tempFile);
            $fileName = $this->getFileName($tempFile, $formatFileName);
            $fileSavePath = $this->filePath . DIRECTORY_SEPARATOR . $fileName;
            $this->getFileSystem()->prepareDirectoryForFile($fileSavePath);
            $tempFile->saveAs($fileSavePath);
            $this->uploadedFileNames[] = $fileName;
        }
        return $this;
    }

    /**
     * @return \yii\web\UploadedFile[]
     */
    public function getUploadedFiles()
    {
        return UploadedFile::getInstances($this->formModel, $this->fileFieldName);
    }

    /**
     * @param UploadedFile $file
     * @return bool
     */
    protected function validateFile(UploadedFile $file)
    {
        $validators = $this->formModel->getActiveValidators($this->fileFieldName);
        foreach ($validators as $validator) {
            if ($validator->validate($file, $error) === false) {
                throw new InvalidParamException($error);
            }
        }
        return true;
    }

    /**
     * @param UploadedFile $file
     * @param callable $formatFileName
     * @return string
     */
    protected function getFileName(UploadedFile $file, \Closure $formatFileName = null)
    {
        $filename = $file->name;
        if (!is_null($formatFileName)) {
            $filename = call_user_func($formatFileName, $file->getBaseName(), $file->getExtension());
        }
        return $filename;
    }

    /**
     * Returns uploaded file names
     * @return array
     */
    public function getUploadedFileNames()
    {
        return $this->uploadedFileNames;
    }

    /**
     * @return FileSystem
     * @throws \yii\base\InvalidConfigException
     */
    private function getFileSystem()
    {
        if (empty($this->fileSystem)) {
            $this->fileSystem = new FileSystem();
        }
        return $this->fileSystem;
    }
}
