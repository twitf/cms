<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/24
 * Time: 22:58
 */

namespace api\modules\v1\controllers;

use api\controllers\BasicActiveController;
use common\models\Client;
use yii\data\ActiveDataProvider;
use Yii;

class ClientController extends BasicActiveController
{
    /**
     * @var $modelClass Client
     */
    public $modelClass = 'common\models\Client';

    /**
     * {@inheritdoc}
     */
    protected function verbs()
    {
        return [
            'index' => ['OPTIONS', 'GET'],
            'post' => ['OPTIONS', 'POST']
        ];
    }

    /**
     * 列表
     * @return ActiveDataProvider
     */
    public function actionIndex()
    {
        $get = Yii::$app->getRequest()->get();
        $model = $this->modelClass::find();
        if (isset($get['search'])) {
            $model->andWhere(['like', 'name', $get['search']])
                ->orWhere(['like', 'company', $get['search']]);
        }
        $query = $model->andWhere(['user_id' => Yii::$app->getUser()->getId()])
            ->orderBy('id desc')
            ->asArray();
        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => $this->pageSize,
                'validatePage' => false,// 超出分页不返回data
            ],
        ]);
    }

    public function actionCreate()
    {
        /**
         * @var $model Client
         */
        $model = new $this->modelClass();
        $model->attributes = Yii::$app->request->post();
        $model->user_id = Yii::$app->getUser()->getId();
        $client=$this->modelClass::find()
            ->andWhere(['company'=>$model->company])
            ->andWhere(['bank'=>$model->bank])
            ->andWhere(['user_id'=>Yii::$app->getUser()->getId()])
            ->one();
        if ($client){
            return $this->error(422, "客户已存在");
        }
        if ($model->save()) {
            return [];
        } else {
            return $this->error(422, $this->analysisError($model->getFirstErrors()));
        }
    }
}