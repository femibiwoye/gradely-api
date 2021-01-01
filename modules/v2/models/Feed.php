<?php

namespace app\modules\v2\models;

use Yii;
use app\modules\v2\components\SharedConstant;

/**
 * This is the model class for table "feed".
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $reference_id it is a post created for homework or live class or any other activities that is not just a plain text post.
 * @property string|null $subject_id If the post is related to a subject
 * @property string|null $description This is body of post
 * @property string|null $type Post is plain text, homework is homework related post, lesson is live class, recommendation is for recommendation post
 * @property int|null $likes
 * @property string $token Is a unique string for each post. Case be used to share or access post.
 * @property int|null $class_id class_id from classes table.
 * @property int|null $global_class_id
 * @property string|null $view_by Who can see it. school means all member of school, teacher, student, parent. \nTeacher means teachers in class only. \nClass means teachers, students and parents in class. \nParent means parents of students in a class only. Student on teacher end means to be seen by student only.
 * @property int $status 1 means post can be seem, 0 means post should not be seen.
 * @property string $created_at
 * @property string|null $updated_at
 * @property string|null $tag
 *
 * @property Classes $class
 * @property GlobalClass $globalClass
 * @property Homeworks $reference
 * @property User $user
 * @property FeedComment[] $feedComments
 */
class Feed extends \yii\db\ActiveRecord
{

    public static $database = 'db';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'feed';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['type', 'class_id', 'view_by'], 'required'],
            ['view_by', 'teacherViewBy'],
            [['user_id', 'reference_id', 'likes', 'class_id', 'global_class_id', 'status'], 'integer'],
            [['description', 'type', 'view_by'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['subject_id'], 'string', 'max' => 45],
            [['token'], 'string', 'max' => 100],
            [['token'], 'unique'],
            [['class_id'], 'exist', 'skipOnError' => true, 'targetClass' => Classes::className(), 'targetAttribute' => ['class_id' => 'id']],
            [['global_class_id'], 'exist', 'skipOnError' => true, 'targetClass' => GlobalClass::className(), 'targetAttribute' => ['global_class_id' => 'id']],
            [['reference_id'], 'exist', 'skipOnError' => true, 'targetClass' => Homeworks::className(), 'targetAttribute' => ['reference_id' => 'id']],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'reference_id' => 'Reference ID',
            'subject_id' => 'Subject ID',
            'description' => 'Description',
            'type' => 'Type',
            'likes' => 'Likes',
            'token' => 'Token',
            'class_id' => 'Class ID',
            'global_class_id' => 'Global Class ID',
            'view_by' => 'View By',
            'status' => 'Status',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    public function fields()
    {
        $fields = [
            'id',
            'description',
            'type',
            'likesCount' => 'feedLikeCount',
            'commentCount' => 'feedCommentCount',
            'token',
            'view_by',
            'myLike',
            'user',
            'reference',
            'subject',
            'class',
            'isOwner',
            'created_at',
            'updated_at',
            'createdTimestamp',
            'updatedTimestamp',
            'global_class_id' => 'globalClass',
            'comment' => 'miniComment',
            'attachments' => 'attachments',
        ];

        if ($this->isRelationPopulated('participants')) {
            $fields = array_merge($fields, ['participants' => 'participants']);
        }
        return $fields;
    }

    public function savePost()
    {
        $form = new Feed();
        $form->attributes = Yii::$app->request->post();
        $form->teacher_id = Yii::$app->user->id;
        $form->homework_type = SharedConstant::FEED_TYPES[3];
        $form->attachments = Yii::$app->request->post('lesson_notes');
        $form->feed_attachments = Yii::$app->request->post('feed_attachments');
        if (!$form->validate()) {
            return (new ApiResponse)->error($form->getErrors(), ApiResponse::VALIDATION_ERROR);
        }

        if (!$model = $form->createHomework()) {
            return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Lesson record not inserted!');
        }

        return (new ApiResponse)->success($model, ApiResponse::SUCCESSFUL, 'Lesson record inserted successfully');

    }


    public function attachmentSave($feed_id)
    {
        $file = Yii::$app->request->post('file');
        if (!$file) {
            return false;
        }

        $model = new PracticeMaterial(['scenario' => 'feed-material']);
        $model->attributes = $file;
        $model->filetype = SharedConstant::FEED_TYPES[4];
        $model->type = SharedConstant::PRACTICE_TYPES[0];
        $model->user_id = $this->user_id;
        $model->practice_id = $feed_id;
        if (!$model->save()) {
            return false;
        }

        return true;
    }

    public function teacherViewBy($value)
    {
        if (!in_array($this->view_by, SharedConstant::TEACHER_VIEW_BY)) {
            $this->addError('view_by', "'$this->view_by' is not a valid option");
            return false;
        }
        return true;
    }

    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }

    public function getReference()
    {
        if ($this->type == SharedConstant::FEED_TYPES[2]) {
            return $this->hasOne(Homeworks::className(), ['id' => 'reference_id'])->andWhere(['status' => 1, 'publish_status' => 1]);
        }

        if ($this->type == SharedConstant::FEED_TYPES[3]) {
            return $this->hasOne(Homeworks::className(), ['id' => 'reference_id'])->andWhere(['status' => 1]);
        }

        if ($this->type == SharedConstant::FEED_TYPES[1]) {
            return null;
        }

        if ($this->type == SharedConstant::FEED_TYPES[4] || $this->type == SharedConstant::FEED_TYPES[5]) {
            return $this->hasOne(PracticeMaterial::className(), ['id' => 'reference_id']);
        }

        if ($this->type == SharedConstant::FEED_TYPES[6]) {
            return $this->hasOne(TutorSession::className(), ['id' => 'reference_id']);
        }
    }

    public function getSubject()
    {
        return $this->hasOne(Subjects::className(), ['id' => 'subject_id']);
    }

    public function getClass()
    {
        return $this->hasOne(Classes::className(), ['id' => 'class_id']);
    }

    public function getGlobalClass()
    {
        return GlobalClass::findOne(['id' => $this->global_class_id]);
    }

    public function getFeedLike()
    {
        return $this->hasOne(FeedLike::className(), ['parent_id' => 'id'])->andWhere(['type' => SharedConstant::FEED_TYPE]);
    }

    public function getMyLike()
    {
        return $this->hasOne(FeedLike::className(), ['parent_id' => 'id'])->andWhere(['type' => SharedConstant::FEED_TYPE, 'user_id' => Yii::$app->user->id])->exists() ? 1 : 0;
    }

    public function getFeedLikeCount()
    {
        return $this->hasOne(FeedLike::className(), ['parent_id' => 'id'])->andWhere(['type' => SharedConstant::FEED_TYPE])->count();
    }

    public function getFeedCommentCount()
    {
        return $this->hasMany(FeedComment::className(), ['feed_id' => 'id'])->where(['type' => 'feed'])->count();
    }

    public function getIsOwner()
    {
        if ($this->user_id == Yii::$app->user->id)
            return 1;
        return 0;
    }

    /*public function getMiniComment()
    {
        $id = $this->id;
        $connection = Yii::$app->getDb();
        $command = $connection->createCommand("select * from (
    select * from feed_comment where type = 'feed' AND feed_id = $id order by id desc limit 3
) fc order by fc.id asc");
        return $result = $command->queryAll();
    }*/

    public function getMiniComment()
    {
        return $this->hasMany(FeedComment::className(),
            ['feed_id' => 'id'])
            ->andWhere(['type' => 'feed'])
            ->limit(3)
            ->orderBy('id DESC');
    }

    public function FeedDisliked()
    {
        $model = $this->getFeedLike()->andWhere(['user_id' => Yii::$app->user->id])->one();
        if (!$model->delete()) {
            return false;
        }

        return true;
    }

    public function getAttachments()
    {
        return $this->hasMany(PracticeMaterial::className(), ['practice_id' => 'id']);
    }

    public function getParticipants()
    {
        return $this->hasMany(User::className(), ['id' => 'user_id'])->select(['id', 'firstname', 'lastname', 'image'])->asArray();
    }

    public function getCreatedTimestamp()
    {
        return strtotime($this->created_at);
    }

    public function getUpdatedTimestamp()
    {
        return strtotime($this->updated_at);
    }

    public function beforeSave($insert)
    {
        $this->global_class_id = Classes::findOne(['id' => $this->class_id])->global_class_id;
        if ($this->isNewRecord) {
            $this->token = GenerateString::widget();
            $this->created_at = date('Y-m-d H:i:s');
            //$this->updated_at = date('y-m-d H-i-s');
        } else {
            $this->updated_at = date('Y-m-d H:i:s');
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
