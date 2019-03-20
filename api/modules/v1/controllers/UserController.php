<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/17
 * Time: 19:34
 */

namespace api\modules\v1\controllers;

use api\models\User;
use Yii;
use api\controllers\BasicActiveController;

class UserController extends BasicActiveController
{
    public $modelClass = 'api\models\User';

    /**
     * {@inheritdoc}
     */
    protected function verbs()
    {
        return [
            'login' => ['OPTIONS','POST'],
        ];
    }

    /**
     * @return array
     * @throws \yii\base\Exception
     */
    public function actionLogin()
    {
        $model = new User();
        $model->setScenario('login');
        $model->attributes = Yii::$app->getRequest()->post();
        if ($model->validate()) {
            return $model->getAccessToken(User::findByUsername($model->username));
        } else {
            return $this->error(422, $this->analysisError($model->getFirstErrors()));
        }
    }
}