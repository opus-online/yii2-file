
Filesystem helpers for Yii2 projects and modules
=========

Usage
-----

### File uploader
To upload files using UploadHandler you must use ActiveForm. To validate file input you must specify validation rules in the model which is used with ActiveForm. If there are no validators assigned to file attribute, file will be not validated. If multiple validators are assigned then all the validators are executed.

Following code sample demonstrates how to use UploadHandler with ActiveForm, how to pass validation errors back to ActiveForm

```php
use opus\file\uploader\UploadHandler;

/** @var User $user */
$user = $this->getUser();
/** @var Model $this */
try {
  $fileUpload = new UploadHandler($profileImage->getImageLocationPath(), $this, 'photo');
  $uploadedFileNames = $fileUpload->handleUploadedFiles();
  foreach ($uploadedFileNames as $fileName) {
    $user->setAttribute('photo_file_name', $fileName);
  }
  $user->saveSafe(['photo_file_name']);
} catch (InvalidFileUploadException $e) {
  // now form model will receive the error and assign it to its attribute
  // you can now throw exception again to catch it in controller level
  $this->addError('photo', $e->getMessage());
}
```
If you want to hash file names before storing files you can pass callback to ```handleUploadedFiles```
```php
$hashFileNamesCallback = function($filename, $fileExtension) {
  return sprintf('%s.%s', md5($filename), $fileExtension);
};
$fileName = $fileUpload->handleUploadedFiles($hashFileNamesCallback)->getUploadedFileNames();
```


Running tests
-------------
Run `composer install` and then in the project root directory
```
./vendor/bin/phpunit
```

Changelog
---------
1.0 - Initial commit