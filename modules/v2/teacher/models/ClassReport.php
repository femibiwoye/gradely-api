<?php
namespace app\modules\v2\teacher\models;

use app\modules\v2\models\{Subjects, SubjectTopics};
use Yii;
use yii\base\Model;
use app\modules\v2\components\SharedConstant;

/**
 * Password reset request form
 */
class ClassReport extends Model {
	public function getReport()
	{
		return [
			'subjects' => $this->subjects,
			'current_subject' => $this->currentSubject,
			'current_term' => $this->currentTerm,
			'topic_list' => $this->topicList,
			'topic_performance' => $this->topicPerformance,
		];
	}

	public function getSubjects()
	{
		return Subjects::find()
				->innerJoin('class_subjects', 'class_subjects.subject_id = subjects.id')
				->where(['class_subjects.class_id' => Yii::$app->request->get('class_id')])
				->all();
	}

	public function getCurrentSubject()
	{
		return Subjects::findOne(['name' => Yii::$app->request->get('subject')]);
	}

	public function getCurrentTerm()
	{
		return Yii::$app->request->get('term');
	}

	public function getTopicList()
	{
		$record = SubjectTopics::find()
				->innerJoin('classes', 'classes.global_class_id = subject_topics.class_id')
				->where(['subject_topics.subject_id' => $this->currentSubject->id, 'classes.id' => Yii::$app->request->get('class_id')]);

		if (Yii::$app->request->get('term')) {
			$record = $record->andWhere(['subject_topics.term' => Yii::$app->request->get('term')]);
		}

		if (Yii::$app->request->get('subject')) {
			$record = $record->andWhere(['subject_topics.subject_id' => $this->currentSubject->id]);
		}

		return $record->all();
	}

	public function getTopicPerformance()
	{
		if (Yii::$app->request->get('topic_id')) {
			
		}

		$record = SubjectTopics::find()
				->innerJoin('classes', 'classes.global_class_id = subject_topics.class_id')
				->where(['classes.id' => Yii::$app->request->get('class_id')])
				->one();

		return $record->score;

	}
}
