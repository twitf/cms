<?php

namespace api\modules\v1\controllers;

use Yii;
use api\controllers\BasicActiveController;

/**
 * Default controller for the `v1` module
 */
class DefaultController extends BasicActiveController
{
    public $modelClass = '';

    /**
     * {@inheritdoc}
     */
    protected function verbs()
    {
        return [
            'index' => ['POST'],
        ];
    }

    public function actionIndex()
    {
        return ['access_key' => '34da6c28be4b602e',
            'mobile' => Yii::$app->getSecurity()->generatePasswordHash('123456'),
            'content' => '您的验证码是1234，请查收！'];
    }
}
