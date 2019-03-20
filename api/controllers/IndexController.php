<?php
/**
 * Created by PhpStorm.
 * User: twitf
 * Date: 2018/5/16
 * Time: 13:32
 */

namespace api\controllers;

use Yii;
use api\models\User;

class IndexController extends BasicActiveController
{
    public $modelClass = 'api\models\User';

    /**
     * @throws \yii\base\Exception
     */
    public function actionIndex()
    {
        return ['name'=>'test'];
    }
}
