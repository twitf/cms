<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/20
 * Time: 21:32
 */

namespace api\modules\v1\controllers;

use api\controllers\BasicActiveController;
use api\models\Ticket;
use common\models\Client;
use common\models\TicketChangeLog;
use yii\data\ActiveDataProvider;
use Yii;
use yii\helpers\ArrayHelper;

class TicketController extends BasicActiveController
{
    /**
     * @var $modelClass Ticket
     */
    public $modelClass = 'api\models\Ticket';

    /**
     * {@inheritdoc}
     */
    protected function verbs()
    {
        return [
            'index' => ['OPTIONS', 'GET'],
            'details' => ['OPTIONS', 'POST'],
            'create' => ['OPTIONS', 'POST'],
            'confirm' => ['OPTIONS', 'POST'],
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
            $model->andWhere(['like', 'company', $get['search']]);
        }
        $query = $model
            ->select('id,ticket_type,client_type,client_name,company,ticket_price,price,type,status,created_at')
            ->orderBy('id desc')
            ->andWhere(['user_id' => Yii::$app->getUser()->getId()])
            ->asArray();
        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => $this->pageSize,
                'validatePage' => false,// 超出分页不返回data
            ],
        ]);
    }

    /**
     * 详情
     * @return Ticket|mixed|null
     */
    public function actionDetails()
    {
        $id = Yii::$app->getRequest()->post('id');
        $model = $this->modelClass::findOne((int)$id);
        if (!$model && $model->user_id == Yii::$app->getUser()->getId()) {
            return $this->error(422, '参数错误');
        }

        $change = TicketChangeLog::find()->where(['ticket_id' => $id])->orderBy('id DESC')->one();
        $data=ArrayHelper::toArray($model);
        $data['message'] = $change->message ?? '';
        return $data;
    }


    /**
     * @return array|mixed
     * @throws \yii\db\Exception
     */
    public function actionCreate()
    {
        //开启事务
        $trans = Yii::$app->getDb()->beginTransaction();
        try {
            /**
             * @var $model Ticket
             */
            $model = new $this->modelClass();
            $model->setScenario('create');
            $model->attributes = Yii::$app->request->post();
            $model->user_id = Yii::$app->getUser()->getId();
            if ($model->save()) {
                $change = new TicketChangeLog();
                $change->user_id = $model->user_id;
                $change->status = Ticket::WAIT_AUDIT_STATUS;
                $change->ticket_id = $model->id;
                if (!$change->save()) {
                    return $this->error(422, $this->analysisError($change->getFirstErrors()));
                }
                //DingTalk::text([], '18717265483');
                $trans->commit();
                return [];
            } else {
                return $this->error(422, $this->analysisError($model->getFirstErrors()));
            }
        } catch (\Exception $e) {
            //事务回滚
            $trans->rollback();
            return $this->error(422, $e->getMessage());
        }
    }

    /**
     * 确认完成
     * @return array|mixed
     */
    public function actionConfirm()
    {
        /**
         * @var $model Ticket
         */
        $id = Yii::$app->getRequest()->post('id');
        $model = $this->modelClass::findOne((int)$id);
        $model->setScenario('confirm');
        if (!$model && $model->user_id == Yii::$app->getUser()->getId()) {
            return $this->error(422, '参数错误');
        }
        if($model->type==Ticket::COMPLETE_STATUS){
            return $this->error(422, '该票据已经完成');
        }
        if ($model->type == Ticket::BUY_TYPE && $model->is_back == Ticket::REMIT) {
            return $this->error(422, '该状态下无需业务员确认完成');
        }
        if ($model->type == Ticket::SELL_TYPE && $model->is_back == Ticket::BACK) {
            return $this->error(422, '该状态下无需业务员确认完成');
        }
        $change = new TicketChangeLog();
        $change->user_id = $model->user_id;
        $change->status = Ticket::COMPLETE_STATUS;
        $change->old_status = $model->status;
        $change->ticket_id = $model->id;


        $model->status = Ticket::COMPLETE_STATUS;
        if (!$model->save()) {
            return $this->error(422, $this->analysisError($model->getFirstErrors()));
        }

        if (!$change->save()) {
            return $this->error(422, $this->analysisError($change->getFirstErrors()));
        }

        $client = new Client;
        $client->name = $model->client_name;
        $client->phone = $model->client_phone;
        $client->company = $model->company;
        $client->bank = $model->opening_bank;
        $client->account = $model->opening_account;
        $client->number = $model->opening_number;
        $client->user_id = $model->user_id;

        if (!$client->save()) {
            return $this->error(422, $this->analysisError($client->getFirstErrors()));
        }
        return [];
    }
}