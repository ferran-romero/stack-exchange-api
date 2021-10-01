<?php

namespace app\controllers;

use yii\rest\Controller;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use yii\base\DynamicModel;
use yii\web\BadRequestHttpException;

class QuestionController extends Controller
{
    const API_BASE_URL = 'https://api.stackexchange.com';
    const API_VERSION = '2.3';
    const API_METHOD = 'questions';

    /**
     * @var Client HTTP client.
     */
    private $httpClient;

    /**
     * Inicializa el cliente HTTP
     */
    public function init()
    {
        $this->httpClient = new Client([
            'base_uri' => self::API_BASE_URL . '/' . self::API_VERSION . '/',
        ]);
    }

    
    public function behaviors()
    {
        return [
            [ 
                'class' => \yii\filters\ContentNegotiator::class,
                'formats' => [
                    'application/json' => \yii\web\Response::FORMAT_JSON,
                ],
            ],
        ];
    }

    public function actionIndex($tagged, $fromDate = null, $toDate = null, $site = 'stackoverflow')
    {    
        $model = DynamicModel::validateData(compact('tagged', 'fromDate', 'toDate'), [
            [['tagged', 'fromDate', 'toDate'], 'default', 'value' => null],
            ['tagged', 'required'],
            ['fromDate', 'date', 'format' => 'php:Y-m-d', 'timestampAttribute' => 'fromDate'],
            ['toDate', 'date', 'format' => 'php:Y-m-d', 'timestampAttribute' => 'toDate'],
        ]);

        if ($model->hasErrors()) {
            throw new BadRequestHttpException(json_encode($model->getErrors()));
        }
        
        try {
            $questionResponse = $this->httpClient->get(SELF::API_METHOD, [
                'query' => [
                    'tagged' => $tagged,
                    'fromdate' => $model->attributes['fromDate'],
                    'todate' => $model->attributes['toDate'],
                    'site' => $site,
                ]
            ]);
        } catch (ClientException $e) {
            throw new \yii\web\HttpException(
                $e->getCode(),
                $e->getMessage() 
            );
        }
    
        return json_decode($questionResponse->getBody(), true);
    }

}
