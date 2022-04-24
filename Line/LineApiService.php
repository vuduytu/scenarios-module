<?php

namespace Modules\Scenarios\Line;

use Illuminate\Support\Facades\Log;
use LINE\LINEBot;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;

class LineApiService
{
    protected $setting;
    protected $bot;
    protected $httpClient;

    public function __construct($setting)
    {
        $this->setting = [
            'channelId' => $setting['channel_id'],
            'channelAccessToken' => $setting['access_token'],
            'channelSecret' => $setting['channel_secret'],
        ];
        $this->httpClient = new CurlHTTPClient($this->setting['channelAccessToken']);
        $this->bot = new LINEBot($this->httpClient, $this->setting);
    }

    public function replyTextMessage($replyToken, $messages)
    {
        $response = $this->httpClient->post(LINEBot::DEFAULT_ENDPOINT_BASE . '/v2/bot/message/reply', [
            'replyToken' => $replyToken,
            'messages' => $messages,
        ]);
        if ($response->isSucceeded()) {
            return $response->getRawBody();
        }
        return $response->getRawBody();
    }

    public function getUserById($userId)
    {
        $response = $this->bot->getProfile($userId);
        if ($response->isSucceeded()) {
            return json_decode($response->getRawBody(), true);
        }
        Log::error('get user id line fail: '.$userId);
        return false;
    }

    public function pushMessage($to, $messages, $itemId = null)
    {
        $response = $this->sendMessageLineByApi($to, $messages);
        if ($response->isSucceeded()) {
            return json_decode($response->getRawBody(), true);
        }
        return false;
    }

    public function sendMessageLineByApi($to, $message) {
        $uri = '';
        $params = [
            'messages' => $message,
            'notificationDisabled' => false,
        ];
        if ($to === 'all') {
            $uri = 'broadcast';
        }
        else if (is_array($to)) {
            $uri = 'multicast';
            $params['to'] = $to;
        } else {
            $uri = 'push';
            $params['to'] = $to;
        }
        $headers = ['Content-Type: application/json; charset=utf-8'];
        return $this->httpClient->post(LINEBot::DEFAULT_ENDPOINT_BASE . '/v2/bot/message/' . $uri, $params , $headers);
    }
}

