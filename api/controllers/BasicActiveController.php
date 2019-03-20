<?php
/**
 * Created by PhpStorm.
 * User: twitf
 * Date: 2018/5/16
 * Time: 14:29
 */

namespace api\controllers;

use Yii;
use yii\filters\Cors;
use yii\helpers\ArrayHelper;
use yii\web\Response;
use yii\filters\auth\CompositeAuth;
use yii\web\BadRequestHttpException;

class BasicActiveController extends \yii\rest\ActiveController
{
    /**
     * 普通获取每页数量
     *
     * @var int
     */
    protected $pageSize = 10;

    /**
     * 启始位移
     *
     * @var int
     */
    protected $offset = 0;

    /**
     * 获取每页数量
     *
     * @var
     */
    protected $limit;

    public $serializer = [
        'class' => 'yii\rest\Serializer',
        'collectionEnvelope' => 'items'
    ];

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['corsFilter'] = [
            'class' => Cors::className(),
            'cors' => [
                // 定义允许来源的数组
                'Origin' => ['http://ajax.test'],
                // 允许动作数组
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
                // 允许请求头部数组
                'Access-Control-Request-Headers' => ['*'],
                //定义当前请求是否使用证书
                'Access-Control-Allow-Credentials' => null,
                // 定义请求的有效时间
                'Access-Control-Max-Age' => 86400,
                // Allow the X-Pagination-Current-Page header to be exposed to the browser.
                'Access-Control-Expose-Headers' => [],
            ]
        ];

        $behaviors['authenticator'] = [
            'class' => CompositeAuth::className(),
            'authMethods' => [
                /* 下面是三种验证access_token方式 */

                // 1.HTTP 基本认证: access token 当作用户名发送，应用在access token可安全存在API使用端的场景，例如，API使用端是运行在一台服务器上的程序。
                // HttpBasicAuth::className(),

                // 2.OAuth : 使用者从认证服务器上获取基于OAuth2协议的access token，然后通过 HTTP Bearer Tokens 发送到API 服务器。
                // HttpBearerAuth::className(),

                // 3.请求参数: access token 当作API URL请求参数发送，这种方式应主要用于JSONP请求，因为它不能使用HTTP头来发送access token
                // QueryParamAuth::className()

//                [
//                    'class' => \yii\filters\auth\QueryParamAuth::className(),
//                    'tokenParam' => 'access_token'
//                ],
                [
                    'class' => \yii\filters\auth\HttpBearerAuth::className()
                ],
            ],
            // 不进行认证登录
            'optional' => Yii::$app->params['user.optional'],
        ];

        /**
         * limit部分，速度的设置是在User::getRateLimit($request, $action)
         * 当速率限制被激活，默认情况下每个响应将包含以下HTTP头发送 目前的速率限制信息：
         * X-Rate-Limit-Limit: 同一个时间段所允许的请求的最大数目;
         * X-Rate-Limit-Remaining: 在当前时间段内剩余的请求的数量;
         * X-Rate-Limit-Reset: 为了得到最大请求数所等待的秒数。
         * 你可以禁用这些头信息通过配置 yii\filters\RateLimiter::enableRateLimitHeaders 为false, 就像在上面的代码示例所示。
         */
        $behaviors['rateLimiter']['enableRateLimitHeaders'] = false;
        // 定义返回格式是：JSON
        $behaviors['contentNegotiator']['formats']['text/html'] = Response::FORMAT_JSON;
        return $behaviors;
    }

    /**
     *  前置操作验证token有效期
     * @param $action
     * @return bool
     * @throws \yii\web\BadRequestHttpException
     */
    public function beforeAction($action)
    {
        parent::beforeAction($action);
        //是否开启token过期验证
        if (Yii::$app->params['user.accessTokenValidity']) {
            $timestamp = Yii::$app->getUser()->identity['access_token_created'];
            $expire = Yii::$app->params['user.accessTokenExpire'];
            // 验证有效期
            if ($timestamp + $expire <= time() && !in_array($action->id, Yii::$app->params['user.optional'])) {
                throw new BadRequestHttpException('您的登录验证已经过期，请重新登陆');
            }
        }
        //初始化分页
        $this->pageSize = Yii::$app->request->get('per-page', $this->pageSize);
        return true;
    }

    /**
     * @return array
     */
    public function actions()
    {
        $actions = parent::actions();
        // 注销系统自带的实现方法
        unset($actions['index'], $actions['update'], $actions['create'], $actions['view'], $actions['delete']);
        // 自定义数据indexDataProvider覆盖IndexAction中的prepareDataProvider()方法
        // $actions['index']['prepareDataProvider'] = [$this, 'indexDataProvider'];
        return $actions;
    }

    /**
     * 格式化错误信息
     * @param $firstErrors
     * @return string
     */
    public function analysisError($firstErrors)
    {
        if (!is_array($firstErrors) || empty($firstErrors)) {
            return false;
        }

        $errors = array_values($firstErrors)[0];

        return $errors ?? '未捕获到错误信息';
    }

    /**
     *  返回信息
     * @param int $code
     * @param string $message
     * @param array $data
     * @return mixed
     */
    public function error($code = 404, $message = '未知错误', $data = [])
    {
        Yii::$app->response->setStatusCode($code, $message);
        Yii::$app->response->data = $data ? ArrayHelper::toArray($data) : [];
        return Yii::$app->response->data;
    }
}
