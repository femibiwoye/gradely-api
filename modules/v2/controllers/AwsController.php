<?php

namespace app\modules\v2\controllers;

use app\modules\v2\models\ApiResponse;
use app\modules\v2\models\GenerateString;
use Aws\Credentials\Credentials;
use Aws\Exception\AwsException;
use Aws\S3\S3Client;
use Yii;
use yii\rest\Controller;
use yii\filters\auth\HttpBearerAuth;


/**
 * Amazon controller
 */
class AwsController extends Controller
{
    public $config = [];
    public $bucketName = 'gradly';

    public function behaviors()
    {
        $behaviors = parent::behaviors();

        //For CORS
        $auth = $behaviors['authenticator'];
        unset($behaviors['authenticator']);
        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::className(),
        ];
        //$behaviors['authenticator'] = $auth;
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::className(),
        ];

        return $behaviors;
    }

    public function beforeAction($action)
    {
        $credentials = new Credentials(Yii::$app->params['AwsS3Key'], Yii::$app->params['AwsS3Secret']);

        $this->config = [
            'version' => 'latest',
            'region' => 'eu-west-2',
            'credentials' => $credentials
        ];
        return parent::beforeAction($action);
    }

    public function actionListBucket()
    {
        $s3Client = new S3Client($this->config);
        $buckets = $s3Client->listBuckets();
        return $buckets['Buckets'];
    }

    public function createBucket($s3Client, $bucketName)
    {
        try {
            $result = $s3Client->createBucket([
                'Bucket' => $bucketName,
            ]);
            return $result;
        } catch (AwsException $e) {
            return 'Error: ' . $e->getAwsErrorMessage();
        }
    }

    public function actionCreateBucket($name)
    {
        $s3Client = new S3Client($this->config);

        return $this->createBucket($s3Client, $name);
    }


    public function actionUploadFile($folder)
    {
        $fileTmpPath = $_FILES['file']['tmp_name'];
        $fileName = $_FILES['file']['name'];
        $fileSize = $_FILES['file']['size'];
        $fileType = $_FILES['file']['type'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            return (new ApiResponse)->error(null, ApiResponse::VALIDATION_ERROR, 'Invalid file');
        }

        $allowedfileExtensions = ['jpg', 'png', 'mp4', 'pdf', 'xls', 'doc', 'docx', 'xlsx', 'ppt', 'pptx'];
        if (in_array($fileExtension, $allowedfileExtensions) === false) {
            return (new ApiResponse)->error(null, ApiResponse::VALIDATION_ERROR, 'Invalid file extension');
        }

        $mbLimit = 10;
        $limit = $mbLimit * 1048576;
        if ($fileSize > $limit) {
            return (new ApiResponse)->error(null, ApiResponse::VALIDATION_ERROR, "Size cannot be more than {$mbLimit} MB");
        }


        $bucket = $this->bucketName;
        $file_Path = $fileTmpPath;
        $key = $folder . '/' . GenerateString::widget(['length' => 50]) . '.' . $fileExtension;

        try {
            //Create a S3Client
            $s3Client = new S3Client($this->config);
            return $result = $s3Client->putObject([
                'Bucket' => $bucket,
                'Key' => $key,
                'SourceFile' => $file_Path,
            ]);
        } catch (S3Exception $e) {
            return $e->getMessage() . "\n";
        }
    }

    public function actionVerifyFile($url)
    {
        $key = explode("/", $url, 4);
        $name = $key[3]; //This get the folder/filename.ext
        $s3 = new S3Client($this->config);
        return $s3->doesObjectExist($this->bucketName, $name);
    }

    public function actionDeleteFile($url)
    {
        $key = explode("/", $url, 4);
        $name = $key[3]; //This get the folder/filename.ext
        $s3 = new S3Client($this->config);
        return $s3->deleteObject(['Bucket' => $this->bucketName, 'Key' => $name]);
    }
}

