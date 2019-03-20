<?php

namespace api\models;

use Yii;
use yii\web\IdentityInterface;
use yii\filters\RateLimitInterface;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "{{%user}}".
 *
 * @property int $id 自增用户id
 * @property string $username 账号
 * @property string $nickname 昵称
 * @property string $auth_key
 * @property string $password_hash 密码哈希
 * @property string $password_reset_token 重置密码token
 * @property string $name 邮箱
 * @property int $status 状态
 * @property int $created_at 创建时间
 * @property int $updated_at 修改时间
 * @property string $access_token 授权令牌
 * @property int $access_token_created 授权令牌生成时间
 * @property int $allowance restful剩余的允许的请求数
 * @property int $allowance_updated_at restful请求的UNIX时间戳数
 */
class User extends \yii\db\ActiveRecord implements IdentityInterface, RateLimitInterface
{
    //正常
    const STATUS_ACTIVE = 1;

    //已删除
    const STATUS_DELETED = 2;

    //密码
    public $password;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user}}';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::className(),
        ];
    }

    public function scenarios()
    {
        return [
            'default' => ['username', 'password_hash', 'name', 'name', 'status'],
            'login' => ['username', 'password'],
            'update-self' => ['username', 'password_hash', 'name', 'name', 'icon', 'repeat_password', 'old_password'],
            'create' => ['username', 'password_hash']
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['username', 'auth_key', 'password', 'password_hash', 'name', 'created_at', 'updated_at'], 'required'],
            [['status', 'created_at', 'updated_at', 'access_token_created', 'allowance', 'allowance_updated_at'], 'integer'],
            [['username', 'password_hash', 'password_reset_token', 'name'], 'string', 'max' => 255],
            [['auth_key'], 'string', 'max' => 32],
            [['access_token'], 'string', 'max' => 60],
            [['username'], 'unique', 'on' => 'create'],
            [['name'], 'unique'],
            [['password_reset_token'], 'unique'],
            [['access_token'], 'unique'],
            ['password', 'validatePassword', 'on' => 'login'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'username' => '账号',
            'password' => '密码',
            'auth_key' => 'Auth Key',
            'password_hash' => 'Password Hash',
            'password_reset_token' => 'Password Reset Token',
            'name' => '姓名',
            'status' => 'Status',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'access_token' => 'Access Token',
            'access_token_created' => 'Access Token Created',
            'allowance' => 'Allowance',
            'allowance_updated_at' => 'Allowance Updated At',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function findIdentity($id)
    {
        return static::findOne(['id' => $id, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * 通过access_token 获取用户实例
     * @param mixed $token
     * @param null $type
     * @return User|IdentityInterface|null
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        return static::findOne(['access_token' => $token]);
    }

    /**
     * Finds user by username
     *
     * @param string $username
     * @return static|null
     */
    public static function findByUsername($username)
    {
        return static::findOne(['username' => $username, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * Finds user by password reset token
     *
     * @param string $token password reset token
     * @return static|null
     */
    public static function findByPasswordResetToken($token)
    {
        if (!static::isPasswordResetTokenValid($token)) {
            return null;
        }

        return static::findOne([
            'password_reset_token' => $token,
            'status' => self::STATUS_ACTIVE,
        ]);
    }

    /**
     * Finds out if password reset token is valid
     *
     * @param string $token password reset token
     * @return bool
     */
    public static function isPasswordResetTokenValid($token)
    {
        if (empty($token)) {
            return false;
        }

        $timestamp = (int)substr($token, strrpos($token, '_') + 1);
        $expire = Yii::$app->params['user.passwordResetTokenExpire'];
        return $timestamp + $expire >= time();
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->getPrimaryKey();
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthKey()
    {
        return $this->auth_key;
    }

    /**
     * {@inheritdoc}
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }


    /**
     * 验证密码
     * @param $attribute
     */
    public function validatePassword($attribute)
    {
        $user = static::findByUsername($this->username);
        if (empty($user)) {
            $this->addError($attribute,'用户不存在');
        } else {
            if (!$this->hasErrors()) {
                if (!Yii::$app->getSecurity()->validatePassword($this->password, $user->password_hash)) {
                    $this->addError($attribute, '账号或者密码错误.');
                }
            }
        }
    }


    /**
     * Generates password hash from password and sets it to the model
     * @param $password
     * @throws \yii\base\Exception
     */
    public function setPassword($password)
    {
        $this->password_hash = Yii::$app->getSecurity()->generatePasswordHash($password);
    }


    /**
     *  Generates "remember me" authentication key
     * @throws \yii\base\Exception
     */
    public function generateAuthKey()
    {
        $this->auth_key = Yii::$app->getSecurity()->generateRandomString();
    }


    /**
     * Generates new password reset token
     * @throws \yii\base\Exception
     */
    public function generatePasswordResetToken()
    {
        $this->password_reset_token = Yii::$app->getSecurity()->generateRandomString() . '_' . time();
    }

    /**
     * Removes password reset token
     */
    public function removePasswordResetToken()
    {
        $this->password_reset_token = null;
    }

    /**
     * 登录时获取Token
     * @param User $user
     * @return array
     * @throws \yii\base\Exception
     */
    public static function getAccessToken(User $user)
    {
        $model = static::findOne($user->id);
        $model->allowance = 2;
        $model->allowance_updated_at = time();
        $model->generateApiToken();
        $model->access_token_created = time();
        if (!$model->save()) {
            return self::getAccessToken($user);
        }
        $result = [];
        $result['access_token'] = $model->access_token;
        $result['expiration_time'] = Yii::$app->params['user.accessTokenExpire'];
        $user->save();
        return $result;
    }

    /**
     * 生成 api_token
     * @throws \yii\base\Exception
     */
    public function generateApiToken()
    {
        $this->access_token = Yii::$app->security->generateRandomString();
    }

    //返回在单位时间内允许的请求的最大数目，例如，[5, 10] 表示在10秒内最多请求5次。
    public function getRateLimit($request, $action)
    {
        return Yii::$app->params['user.rateLimit'];
    }

    // 返回剩余的允许的请求数。
    public function loadAllowance($request, $action)
    {
        return [$this->allowance, $this->allowance_updated_at];
    }

    // 保存请求时的UNIX时间戳。
    public function saveAllowance($request, $action, $allowance, $timestamp)
    {
        $this->allowance = $allowance;
        $this->allowance_updated_at = $timestamp;
        $this->save();
    }
}
