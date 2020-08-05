<?php
namespace app\modules\v2\teacher\models;

use Yii;
use yii\base\Model;
use app\modules\v2\models\{QuizSummary};
use app\modules\v2\components\SharedConstant;

/**
 * Password reset request form
 */
class HomeworkSummary extends QuizSummary {
	private $total = SharedConstant::VALUE_ZERO;
	private $struggling_students = SharedConstant::VALUE_ZERO;
	private $average_students = SharedConstant::VALUE_ZERO;
	private $excellent_students = SharedConstant::VALUE_ZERO;

	public function fields() {
		return [
			'average_score' => 'averageScore',
			'completion_rate' => 'completedRate',
			'expected_student' => 'studentExpected',
			'submitted_student' => 'studentsSubmitted',
			'struggling' => 'strugglingStudents',
			'average' => 'averageStudents',
			'excellence' => 'excellentStudents'
		];
	}

	public function getRecord() {
		return parent::find()->where(['type' => SharedConstant::QUIZ_SUMMARY_TYPE[0], 'homework_id' => Yii::$app->request->get('id')]);
	}

	public function getAverageScore() {
		$models = $this->getRecord()->andWhere(['submit' => SharedConstant::VALUE_ONE])->all();
		foreach ($models as $model) {
			$this->total = $this->total + $model->correct;
		}

		return $this->total / count($models);
	}

	public function getCompletedRate() {
		$completed = $this->getRecord()->andWhere(['submit' => SharedConstant::VALUE_ONE])->all();

		return count($completed) * 100 / count($this->getRecord()->all());
	}

	public function getStudentExpected() {
		return count($this->getRecord()->andWhere(['submit' => SharedConstant::VALUE_ZERO]));
	}

	public function getStudentsSubmitted() {
		return $this->getRecord()->andWhere(['submit' => SharedConstant::VALUE_ONE])->all();
	}

	public function getStrugglingStudents() {
		$models = $this->getRecord()->andWhere(['submit' => SharedConstant::VALUE_ONE])->all();
		foreach ($models as $model) {
			$marks = $model->correct * 100 / $model->total_questions;
			if ($marks < 50) {
				$this->struggling_students = $this->struggling_students + 1;
			}
		}

		return $this->struggling_students;
	}

	public function getAverageStudents() {
		$models = $this->getRecord()->andWhere(['submit' => SharedConstant::VALUE_ONE])->all();
		foreach ($models as $model) {
			$marks = $model->correct * 100 / $model->total_questions;
			if ($marks > 50 && $marks < 75) {
				$this->average_students = $this->average_students + 1;
			}
		}

		return $this->average_students;
	}

	public function getExcellentStudents() {
		$models = $this->getRecord()->andWhere(['submit' => SharedConstant::VALUE_ONE])->all();
		foreach ($models as $model) {
			$marks = $model->correct * 100 / $model->total_questions;
			if ($marks > 75) {
				$this->excellent_students = $this->excellent_students + 1;
			}
		}

		return $this->excellent_students;
	}
}
