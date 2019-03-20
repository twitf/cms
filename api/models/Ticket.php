<?php

namespace api\models;

use yii\helpers\ArrayHelper;

class Ticket extends \common\models\Ticket
{
    const IMG_MAX_NUM = 10;

    public function scenarios()
    {
        return [
            'create' => ['ticket_type', 'client_type', 'client_name', 'client_phone', 'company', 'opening_bank', 'opening_account', 'opening_number', 'ticket_img', 'ticket_describe', 'is_back', 'ticket_price', 'price', 'type', 'point', 'user_id', 'kickbacks', 'ticket_number', 'deductions'],
            'confirm' => ['status']
        ];
    }


    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        $rules = parent::rules();
        return ArrayHelper::merge($rules, [
            //缺省状态下，行内验证器不会在关联特性的输入值为空或该特性已经在其他验证中失败的情况下起效。 若你想要确保该验证器始终启用的话，你可以在定义规则时，酌情将 skipOnEmpty 以及 skipOnError属性设为 false
            ['ticket_img', 'validateImg', 'skipOnError' => false, 'skipOnEmpty' => false, 'on' => 'create'],
        ]);
    }

    /**
     * 票据购买图片必须传 且最多10张
     * @param $attribute
     */
    public function validateImg($attribute)
    {
        if ($this->type == self::BUY_TYPE && in_array($this->ticket_type, [self::PAPER_BUSINESS, self::PAPER_SILVER])) {
            if (empty(trim($this->ticket_img))) {
                $this->addError($attribute, '票据图片不能为空');
            } else {
                $img = array_filter(explode(',', $this->ticket_img));
                if (count($img) > self::IMG_MAX_NUM) {
                    $this->addError($attribute, "票据图片最多上传" . self::IMG_MAX_NUM . "张");
                }
            }
        }
    }
}
