<?php

namespace app\modules\v2\teacher\controllers;

use Yii;
use yii\rest\ActiveController;
use yii\filters\auth\{HttpBearerAuth, CompositeAuth};
use app\modules\v2\models\{feed, ApiResponse, Homeworks, TutorSession};

class FeedController extends ActiveController {
	public $modelClass = 'app\modules\v2\models\Feed';
	private $new_announcements = []; 
	private $new_homeworks = [];
	private $new_live_session = [];

	public function behaviors() {
		$behaviors = parent::behaviors();

		//For CORS
		$auth = $behaviors['authenticator'];
		unset($behaviors['authenticator']);
		$behaviors['corsFilter'] = [
			'class' => \yii\filters\Cors::className(),
		];
		$behaviors['authenticator'] = $auth;
		$behaviors['authenticator'] = [
			'class' => CompositeAuth::className(),
			'authMethods' => [
				HttpBearerAuth::className(),
			],
		];

		return $behaviors;
	}

	public function actions() {
		$actions = parent::actions();
		unset($actions['create']);
		unset($actions['update']);
		unset($actions['delete']);
		unset($actions['index']);
		unset($actions['view']);
		return $actions;
	}

	public function actionUpcoming() {
		$Homeworks = (new Homeworks)->getnewHomeworks();
		if ($Homeworks) {
			$this->new_homeworks = [
				'Homeworks' => $Homeworks,
			];

			array_push($this->new_announcements, $this->new_homeworks);
		}

		$Live_Classes = (new TutorSession)->getNewSessions();
		if ($Live_Classes) {
			$this->new_live_session = [
				'Live_Classes' => $Live_Classes,
			];

			array_push($this->new_announcements, $this->new_live_session);
		}

		if (!$this->new_announcements) {
			return (new ApiResponse)->error(null, ApiResponse::UNABLE_TO_PERFORM_ACTION, 'Annoucements not found!');
		}

		return (new ApiResponse)->success($this->new_announcements, ApiResponse::SUCCESSFUL, 'Annoucements found');
	}
}
