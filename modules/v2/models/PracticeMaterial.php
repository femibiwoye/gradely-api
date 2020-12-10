<?php

namespace app\modules\v2\models;

use app\modules\v2\components\SharedConstant;
use app\modules\v2\components\Utility;
use Yii;

/**
 * This is the model class for table "practice_material".
 *
 * @property int $id
 * @property int $practice_id This id is id from homework table or feed.id if type is feed
 * @property int $user_id
 * @property string $title The name seen
 * @property string $filename
 * @property string $filetype
 * @property string|null $description
 * @property string|null $filesize e.g 100kb
 * @property int|null $downloadable
 * @property string|null $download_count
 * @property string $extension e.g png, jpg, mp4, pdf, etc
 * @property string $type The file could either belong to practice assessment or feed.
 * @property string|null $raw This contains the object received from the cloud client. It is json encoded in database.
 * @property string $tag
 * @property string $thumbnail
 * @property string $token
 * @property string $created_at
 * @property string|null $updated_at
 *
 * @property User $user
 */
class PracticeMaterial extends \yii\db\ActiveRecord
{
    public static $database = 'db';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'practice_material';
    }

    public function rules()
    {
        return [
            [['user_id', 'title', 'filename', 'filetype', 'filesize', 'extension'], 'required', 'on' => 'feed-material'],
            [['user_id', 'title', 'filename', 'filetype', 'extension'], 'required', 'on' => 'live-class-material'],
            [['practice_id', 'user_id', 'downloadable', 'download_count'], 'integer'],
            [['filetype', 'description', 'raw', 'tag', 'thumbnail'], 'string'],
            [['created_at', 'updated_at', 'tag'], 'safe'],
            [['title', 'token'], 'string', 'max' => 100],
            [['filesize', 'extension', 'tag'], 'string', 'max' => 45],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    public function fields()
    {
        return [
            'id',
            'title',
            'extension',
            'created_at',
            'title',
            'filename',
            'filetype',
            'extension',
            'filesize',
            'raw',
            'tag',
            'description',
            'type',
            'downloadable',
            'download_count',
            'isOwner',
            'thumbnail' => 'materialThumbnail',
            'token',
            'updated_at',
            'user',
            'feed_likes_and_dislikes' => 'feedLike',
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'practice_id' => 'Practice ID',
            'user_id' => 'User ID',
            'title' => 'Title',
            'filename' => 'Filename',
            'filetype' => 'Filetype',
            'description' => 'Description',
            'filesize' => 'Filesize',
            'downloadable' => 'Downloadable',
            'download_count' => 'Download Count',
            'extension' => 'Extension',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    public function saveFileFeed($classID, $isTest = false)
    {
        $dbtransaction = Yii::$app->db->beginTransaction();
        try {

            if ($isTest) {
                //Feed::$database = SharedConstant::DB_CONNECTION_NAME[1];
                $db = SharedConstant::DB_CONNECTION_NAME[1];
                $global_class_id = Yii::$app->$db->createCommand('SELECT * FROM classes WHERE id=:class_id')
                    ->bindValue(':class_id', $classID)
                    ->queryOne();

                $token = GenerateString::widget();
                $model = Yii::$app->$db->createCommand()->insert('feed', [
                    'view_by' => 'class',
                    'type' => 'post',
                    'tag' => $this->filetype,
                    'user_id' => $this->user_id,
                    'reference_id' => $this->id,
                    'description' => $this->title,
                    'class_id' => $classID,
                    'global_class_id' => $global_class_id['global_class_id'],
                    'token' => $token,
                    'created_at' => date('Y-m-d H:i:s'),
                ])->execute();


                if (!$model) {
                    return false;
                }
                $object = Yii::$app->$db->createCommand('SELECT * FROM feed WHERE token=:token')
                    ->bindValue(':token', $token)
                    ->queryOne();
                $this->practice_id = $object['id'];

            } else {
                $model = new Feed();
                $model->view_by = 'class';
                $model->type = 'post';
                $model->tag = $this->filetype;
                $model->user_id = $this->user_id;
                $model->reference_id = $this->id;
                $model->description = $this->title;
                $model->class_id = $classID;
                if (!$model->save()) {
                    return false;
                }
                $this->practice_id = $model->id;
            }


            if (!$model = $this->save()) {
                return false;
            }

            $dbtransaction->commit();
        } catch (\Exception $ex) {
            $dbtransaction->rollBack();
            return false;
        }

        return $this;
    }

    protected function saveToFeed()
    {
        return true;
    }


    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }

    public function getMaterialThumbnail()
    {
        return Utility::AbsoluteImage($this->thumbnail, $this->type);
    }

    public function getFeedLike()
    {
        return $this->hasMany(FeedLike::className(), ['parent_id' => 'id']);
    }

    public function getIsOwner()
    {
        if ($this->user_id == Yii::$app->user->id)
            return 1;
        return 0;
    }

    public function beforeSave($insert)
    {
        if ($this->isNewRecord) {
            $token = GenerateString::widget(['length' => 50]);
            if (self::find()->where(['token' => $token])->exists()) {
                $this->token = GenerateString::widget(['length' => 50]);
            }
            $this->token = $token;
        }
        return parent::beforeSave($insert);
    }

    public static function getDb()
    {
        $database = self::$database;
        return Yii::$app->$database;
    }

    public static function setDatabase($database)
    {
        self::$database = $database;
    }
}
