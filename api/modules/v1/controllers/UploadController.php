<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/23
 * Time: 15:10
 */

namespace api\modules\v1\controllers;

use Yii;
use api\controllers\BasicActiveController;
use yii\web\UploadedFile;

class UploadController extends BasicActiveController
{
    public $modelClass = '';

    public $fileName = 'file';
    public $config;
    public $saveName;
    public $savePath;
    public $extension = [
        'image' => ['jpg', 'png', 'jpeg']
    ];

    /**
     * {@inheritdoc}
     */
    protected function verbs()
    {
        return [
            'images' => ['OPTIONS','POST']
        ];
    }

    public function init()
    {
        $this->config['fileRoot'] = Yii::getAlias('@webroot');
        $this->config['filePathFormat'] = '/uploads/image/' . date('Ymd') . '/';
        $this->savePath = $this->config['fileRoot'] . $this->config['filePathFormat'];
    }

    /**
     * 上传图片
     * @return array|mixed
     */
    public function actionImages()
    {
        try {
            $uploadedFile = UploadedFile::getInstanceByName($this->fileName);
            if ($uploadedFile === null || $uploadedFile->hasError) {
                throw new \Exception('Upload Image Error', 422);
            }
            /**
                if (!in_array($uploadedFile->getExtension(), $this->extension['image'])) {
                    throw new \Exception('只允许上传jpg、png、jpeg格式的图片', 422);
                }
            */
            if (!file_exists($this->savePath)) {
                mkdir($this->savePath, 0777, true);
            }
            // $this->saveName = random_int(1000000, 9999999) . '.' . $uploadedFile->getExtension();
            $this->saveName = random_int(1000000, 9999999) . '.jpg';
            if ($uploadedFile->saveAs($this->savePath . $this->saveName, false)) {
                return ['filePath' => $this->config['filePathFormat'] . $this->saveName];
            }
        } catch (\Exception $e) {
            return $this->error($e->getCode(), $e->getMessage());
        }
    }
}