<?php

namespace api\behaviors;

use Yii;
use yii\base\Behavior;

class BeforeSend extends Behavior
{
    /**
     * {@inheritdoc}
     */
    public function events()
    {
        return [
            'beforeSend' => 'beforeSend',
        ];
    }

    /**
     * 格式化返回
     *
     * @param $event
     */
    public function beforeSend($event)
    {

        $response = $event->sender;
        $options=['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'];
        $response->headers->set('Allow', implode(', ', $options));
        $response->headers->set('Access-Control-Allow-Methods', implode(', ', $options));
        $response->headers->set('Access-Control-Allow-Headers', "authorization,content-type");
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->data = [
            'code' => $response->statusCode,
            'message' => $response->data['message'] ?? $response->statusText,
            'data' => $response->statusCode == 200 ? $response->data : [],
        ];
        // 格式化报错输入格式
        if ($response->statusCode >= 500) {
            if (YII_DEBUG) {
                $exception = Yii::$app->getErrorHandler()->exception;
                $errData = [
                    'type' => get_class($exception),
                    'file' => method_exists($exception, 'getFile') ? $exception->getFile() : '',
                    'errorMessage' => $exception->getMessage(),
                    'line' => $exception->getLine(),
                    'stack-trace' => explode("\n", $exception->getTraceAsString()),
                ];
            } else {
                $errData = '内部服务器错误,请联系管理员';
            }
            $response->data['data'] = $errData;
        }
        $response->format = yii\web\Response::FORMAT_JSON;
        $response->statusCode = 200; // 考虑到了某些前端必须返回成功操作，所以这里可以设置为都返回200的状态码
    }
}