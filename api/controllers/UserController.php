<?php
/**
 * Created by PhpStorm.
 * User: twitf
 * Date: 2018/5/16
 * Time: 10:35
 */

namespace api\controllers;

class UserController extends BasicActiveController
{
    public $modelClass = 'common\models\User';

    public function actiuonIndex(){
        echo 1;
    }
}
