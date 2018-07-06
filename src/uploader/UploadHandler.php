<?php
namespace opus\file\uploader;

use opus\file\exception\InvalidFileUploadException;
use opus\file\FileSystem;
use yii\base\BaseObject;
use yii\base\Model;
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
class UploadHandler extends BaseObject
{
    /**
     * Location where files are saved
     * @var string
     */
    private $filePath;

    /**
     * Name of the file field (can be property of a model or a field exact name)
     *
     * @var string
     */
    private $fileAttribute;

    /**
     * File system component id
     * @var FileSystem
     */
    private $fileSystem;

    /**
     * Model instance, this model should provide validators to validate file input
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
     * @param string $targetDir Directory where file is saved
     * @param string $fileAttribute Name of the file attribute in model
     * @param Model $formModel Model instance, used to get validators
     */
    public function __construct($targetDir , Model $formModel, $fileAttribute)
    {
        $this->filePath = $targetDir ;
        $this->fileAttribute = $fileAttribute;
        $this->formModel = $formModel;
    }

    /**
     * First all files are validated and added to queue
     * if validation is okay for all files then files are moved to server
     * @param callable $formatFileName This is a function to format file names.
     * Callback signature is function($filename, $fileExtension) Exception is thrown if validation fails
     * @throws InvalidFileUploadException
     * @return array List of file names
     */
    public function handleUploadedFiles(\Closure $formatFileName = null)
    {
        /** @var UploadedFile[] $queuedFiles */
        $queuedFiles = [];
        $uploadedFileNames = [];

        foreach ($this->getUploadedFiles() as $tempFile) {
            $this->validateFile($tempFile);
            $queuedFiles[] = $tempFile;
        }

        foreach ($queuedFiles as $file) {
            $fileName = $this->getFileName($file, $formatFileName);
            $fileSavePath = $this->filePath . DIRECTORY_SEPARATOR . $fileName;
            $this->getFileSystem()->prepareDirectoryForFile($fileSavePath);
            $file->saveAs($fileSavePath);
            $uploadedFileNames[] = $fileName;
        }
        return $uploadedFileNames;
    }

    /**
     * @return \yii\web\UploadedFile[]
     */
    public function getUploadedFiles()
    {
        return UploadedFile::getInstances($this->formModel, $this->fileAttribute);
    }

    /**
     * @param UploadedFile $file
     * @return bool
     * @throws InvalidFileUploadException
     */
    protected function validateFile(UploadedFile $file)
    {
        $validators = $this->formModel->getActiveValidators($this->fileAttribute);
        foreach ($validators as $validator) {
            if ($validator->validate($file, $error) === false) {
                throw new InvalidFileUploadException($error);
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
