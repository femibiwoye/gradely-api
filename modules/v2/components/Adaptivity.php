<?php

namespace app\modules\v2\components;


use app\modules\v2\models\SubjectTopics;
use app\modules\v2\models\VideoContent;
use Yii;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\db\Expression;


class Adaptivity extends ActiveRecord
{


    public static function SingleMixTopic($type, $inner)
    {
        if ($type == 'single') {
            $questionCount = SharedConstant::SINGLE_PRACTICE_QUESTION_COUNT;
            $is_recommended = $inner['is_recommended'];
        } else {
            $questionCount = count($inner) * SharedConstant::MIX_PRACTICE_QUESTION_COUNT;
            $is_recommended = $inner[0]['is_recommended'];
        }

        return ['type' => $type, 'question_count' => $questionCount, 'is_recommended' => $is_recommended, 'topic' => $inner];
    }

    public static function GenerateSingleMixPractices($topicModels)
    {
        $topicOrders = [];
        foreach ($topicModels as $key => $inner) {
            if ($key == 0) {
                $topicOrders[] = self::SingleMixTopic('single', $inner);
            }

            if ($key >= 1 && $key <= 4) {
                if (isset($topicModels[1])) {
                    $temp = array_splice($topicModels, 1, 4);
                    if (count($temp) == 1)
                        $topicOrders[] = self::SingleMixTopic('single', $inner);
                    else
                        $topicOrders[] = self::SingleMixTopic('mix', $temp);
                }
            }

            if ($key > 4 && $key <= 7) {
                if (isset($topicModels[5])) {
                    $temp = array_splice($topicModels, 6, 3);
                    if (count($temp) == 1)
                        $topicOrders[] = self::SingleMixTopic('single', $inner);
                    else
                        $topicOrders[] = self::SingleMixTopic('mix', $temp);
                }
            }

            if ($key > 7 && $key <= 10) {
                $topicOrders[] = self::SingleMixTopic('single', $inner);
            }
        }

        return $topicOrders;
    }

    public static function PracticeVideoRecommendation($topic_id, $receiverID, $referenceType, $referenceID)
    {
        $topic_objects = SubjectTopics::find()
            ->leftJoin('practice_topics pt', 'pt.topic_id = subject_topics.id')
            ->leftJoin('homeworks h', 'h.id = pt.practice_id')
            ->select([
                'subject_topics.*',
                Utility::ImageQuery('subject_topics'),
                new Expression("'practice' as type"),
                $referenceType == 'class' ? new Expression('(case when (SELECT id FROM homeworks WHERE homeworks.id = h.id AND reference_id = ' . $referenceID . ' AND type = "recommendation" AND reference_type = "class" AND student_id = ' . $receiverID . ' AND teacher_id = ' . Yii::$app->user->id . ') then 1 else 0 end) as is_recommended') :
                    new Expression('(case when (select id from homeworks where reference_id = ' . $referenceID . ' AND type = "recommendation" AND reference_type = "homework"  AND student_id = ' . $receiverID . ' AND teacher_id = ' . Yii::$app->user->id . ') then 1 else 0 end) as is_recommended'),
            ])
            ->where(['subject_topics.id' => $topic_id])
            ->asArray()
            ->all();

        //retrieves assign videos to the topic
        $video = VideoContent::find()
            ->select([
                'video_content.*',
                new Expression("'video' as type"),
                new Expression('(case when (select resources_id from recommended_resources where creator_id = ' . Yii::$app->user->id . ' AND resources_type = "video" AND receiver_id = ' . $receiverID . ' AND resources_id = video_content.id AND reference_type = "' . $referenceType . '" AND reference_id = ' . $referenceID . ') then 1 else 0 end) as is_recommended'),
                'gc.id class_id',
                'gc.description class_name',
            ])
            ->innerJoin('video_assign', 'video_assign.content_id = video_content.id')
            ->innerJoin('subject_topics st', 'st.id = video_assign.topic_id')
            ->innerJoin('global_class gc', 'gc.id = st.class_id')
            ->where(['video_assign.topic_id' => $topic_id])
            ->limit(SharedConstant::VALUE_THREE)
            ->asArray()
            ->all();

        if (!$topic_objects) {
            return SharedConstant::VALUE_NULL;
        }

        $topicOrders = Adaptivity::generateSingleMixPractices($topic_objects);

        return array_merge($topicOrders, $video);
    }


}