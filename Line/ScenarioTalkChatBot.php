<?php

namespace Modules\Scenarios\Line;

use Illuminate\Support\Facades\Log;
use Modules\Scenarios\Models\Scenario;
use Modules\Scenarios\Models\ScenarioMessageModel;
use Modules\Scenarios\Models\ScenarioSettingModel;
use Modules\Scenarios\Models\ScenarioTalkModel;
use Modules\Scenarios\Models\ScenarioTextMappingModel;
use Modules\Scenarios\Services\_Exception\AppServiceException;

class ScenarioTalkChatBot
{
    /**
     * @var array
     */
    protected $lineConfig;
    /**
     * @var array
     */
    protected $eventHook;

    protected $lineApiService;
    protected $storeId;
    protected $type;

    const DATA_ID_FILE_NAME = [
        'BOSAI_EARTHQUAKE_TALK',
       'BOSAI_RAIN_TYPHOON_TALK',
        'BOSAI_FLOW_SEARCH_TALK',
        'BOSAI_FLOW_TALK',
        'DAMAGE_REPORT_TALK',
    ];

    public function __construct($lineConfig = [],
                                $eventHook = [],
                                $storeId,
                                $type)
    {
        $this->lineConfig = $lineConfig;
        $this->eventHook = $eventHook;
        $this->storeId = $storeId;
        $this->type = $type;
        $this->init();
    }

    private function init()
    {
        if(config('scenario.use_multi_store')) {
            if (!isset($this->lineConfig['channel_id']) || !isset($this->lineConfig['access_token']) || !isset($this->lineConfig['channel_secret'])) {
                throw new AppServiceException('Config not found!');
            }
            $this->lineApiService = new LineApiService($this->lineConfig);
        } else {
            if (!config('scenario.line_channel_id', false) || !config('scenario.access_token', false) || !config('scenario.channel_secret', false)) {
                throw new AppServiceException('Config not found!');
            }
            $this->lineApiService = new LineApiService([
                'channel_id' => config('scenario.line_channel_id'),
                'access_token' => config('scenario.access_token'),
                'channel_secret' => config('scenario.channel_secret'),
            ]);
        }
    }

    public function sendReplyMessage()
    {
        $event = $this->eventHook;
        $replyToken = $event['replyToken'];
        if ($event['type'] === 'message') {
            if (!isset($event['message']['text'])) {
                throw new AppServiceException('Type message not text');
            }
            $message = $event['message']['text'];

            $scenarioSetting = ScenarioSettingModel::where('store_id', $this->storeId)->first();
            if ($scenarioSetting) {
                $scenarioId = @json_decode($scenarioSetting->envMapping, true)[$this->type];
                if ($scenarioId) {
                    $scenario = Scenario::find($scenarioId);
                    $bosaiSpecialTalk = ScenarioTalkModel::where('dataId', 'DAMAGE_REPORT_TALK')->where('scenario_id', $scenarioId)->first();
                    $textMappingData = ScenarioTextMappingModel::where('scenario_id', $scenarioId)->first();
                    $textMaps = $textMappingData->textMapping;
                    if (array_key_exists($message, $textMaps)) {
                        // TODO: query get message
                        $messageId = $textMaps[$message];
                        $messageScenario =  ScenarioMessageModel::where('scenario_id', $scenarioId)->where('dataId', $messageId)->orWhere('id', $messageId)->first();
                        if ($messageScenario) {
                            $scenario->update(['disable_msg' => false]);
                            $this->lineApiService->replyTextMessage($replyToken, $this->formatMessage($messageScenario, $scenarioId));
                        }
                    } else {
                        if ($bosaiSpecialTalk && $scenario->specialTalks && isset($scenario->specialTalks['damageReport']) && $message === '損傷報告') {
                            if ($scenario->specialTalks['damageReport']) {
//                                $bosaiSpecialTalk->update(['special_talk_msg' => true]);
                                $messageScenario =  ScenarioMessageModel::where('scenario_id', $scenarioId)->where('dataId', 'REPORT_MODE_BUTTON')->first();
                                if ($messageScenario) {
                                    $this->lineApiService->replyTextMessage($replyToken, $this->formatMessage($messageScenario, $scenarioId));
                                }
                                return;
                            }
                            else {
//                                $bosaiSpecialTalk->update(['special_talk_msg' => false]);
                                $messageScenario =  ScenarioMessageModel::where('scenario_id', $scenarioId)->where('dataId', 'DISABLED_DAMAGE_REPORT_MODE')->first();
                                if ($messageScenario) {
                                    $this->lineApiService->replyTextMessage($replyToken, $this->formatMessage($messageScenario, $scenarioId));
                                }
                                return;
                            }
                        } else {
                            if ($scenario->disable_msg && $message !== '損傷報告') {
                                $scenario->update(['disable_msg' => false]);
                                return;
                            } else {
                                $this->defaultTrashMessage($scenarioId, $replyToken, $textMaps);
                            }

                        }
                    }
                }
            }
        }
        if ($event['type'] === 'postback') {
            $scenarioSetting = ScenarioSettingModel::where('store_id', $this->storeId)->first();
            if ($scenarioSetting) {
                $scenarioId = @json_decode($scenarioSetting->envMapping, true)[$this->type];
                $scenarioData = Scenario::find($scenarioId);
                $messageScenario =  ScenarioMessageModel::where('scenario_id', $scenarioId)->where('dataId', $event['postback']['data'])->orWhere('id', $event['postback']['data'])->first();
                if ($messageScenario) {
                    if ($scenarioData) {
                        $scenarioData->update(['disable_msg' => true]);
                    }
//                    if ($messageScenario->talkModel && $messageScenario->talkModel->dataId === 'DAMAGE_REPORT_TALK') {
//                        $bosaiSpecialTalk = ScenarioTalkModel::where('dataId', 'DAMAGE_REPORT_TALK')->where('scenario_id', $scenarioId)->first();
//                        $bosaiSpecialTalk->update(['special_talk_msg' => true]);
//                    }
                    $this->lineApiService->replyTextMessage($replyToken, $this->formatMessage($messageScenario, $scenarioId));
                }
            }
        }
    }

    private function defaultTrashMessage($scenarioId, $replyToken, $textMaps)
    {
        $talkTrash = ScenarioTalkModel::where('scenario_id', $scenarioId)->where('dataId', 'TRASH_SEPARATION_TALK')->first();
        if ($talkTrash) {
            $defaultId = isset($textMaps['TRASH_NOT_FOUND_DEFAULT_MESSAGE']) ? $textMaps['TRASH_NOT_FOUND_DEFAULT_MESSAGE'] : '';
            if ($defaultId) {
                $messageScenario = ScenarioMessageModel::where('scenario_id', $scenarioId)->where('dataId', $defaultId)->orWhere('id', $defaultId)->first();
                if ($messageScenario) {
                    $this->lineApiService->replyTextMessage($replyToken, $this->formatMessage($messageScenario, $scenarioId));
                }
            }
        }
    }

    public function formatMessage($messageScenario, $scenarioId) {
        $alttextSupport = [
            "buttons", "imagemap", "carousel", "bubbleFlex", "carouselFlex", "confirm"
        ];
        $messageGenerator = [
            "buttons", "imagemap", "carousel", "bubbleFlex", "carouselFlex", "confirm", "text", "sticker", "audio", "video", "location", "image"
        ];
        $messages = [];
        if ($messageScenario->dataType === 'compositeMessage') {
            $messageList = $messageScenario->messages;
            if ($messageList && count($messageList)) {
                $items = [];
                foreach ($messageList as $messageId) {
                    $messageData = ScenarioMessageModel::where('scenario_id', $scenarioId)->where('dataId', $messageId)->orWhere('id', $messageId)->first();
                    if($messageData) {
                        $items[] = $messageData;
                    }
                }
                $replyMsgs = [];
                foreach ($items as $item) {
                    if (in_array($item->dataType, $alttextSupport) && $item->nameLBD) {
                        $replyMsgs[] = $this->formatMessageDataToLine($item->dataType, $item->params, $item->nameLBD);
                    } else {
                        $replyMsgs[] = $this->formatMessageDataToLine($item->dataType, $item->params);
                    }
                }
                $messages = $replyMsgs;
            }
        } elseif ($messageScenario->dataType === 'apiCall') {
            //TODO: chưa tìm được tài liệu
        } elseif (in_array($messageScenario->dataType, $messageGenerator)) {
            if (in_array($messageScenario->dataType, $alttextSupport) && $messageScenario->nameLBD) {
                $messages[] = $this->formatMessageDataToLine($messageScenario->dataType, $messageScenario->params, $messageScenario->nameLBD);
            } else {
                $messages[] = $this->formatMessageDataToLine($messageScenario->dataType, $messageScenario->params);
            }
        }
        return $messages;
    }

    public function formatMessageDataToLine($type, $params, $nameLBD = '')
    {
        switch ($type) {
            case 'text': {
                return [
                    'type' => 'text',
                    'text' => $params['text']
                ];
            }
            case 'sticker': {
                return [
                    'type' => 'sticker',
                    'packageId' => $params['packageId'],
                    'stickerId' => $params['stickerId'],
                ];
            }
            case 'image': {
                return [
                    'type' => 'image',
                    'originalContentUrl' => $params['originalContentUrl'],
                    'previewImageUrl' => $params['previewImageUrl'],
                ];
            }
            case 'location': {
                return [
                    'type' => 'location',
                    'title' => @$params['title'],
                    'address' => @$params['address'],
                    'latitude' => $params['latitude'],
                    'longitude' => $params['longitude'],
                ];
            }
            case 'video': {
                return [
                    'type' => 'video',
                    'originalContentUrl' => @$params['originalContentUrl'],
                    'previewImageUrl' => $params['previewImageUrl'],
                    'trackingId' => @$params['trackingId'],
                ];
            }
            case 'audio': {
                return [
                    'type' => 'audio',
                    'originalContentUrl' => @$params['originalContentUrl'],
                    'duration' => @$params['duration'],
                ];
            }
            case 'imagemap': {
                $actionListImageMap = [];
                for($i = 0; $i < $params['actionCount']; $i++) {
                    $actionData = $params['action.'.$i];
                    $action = [
                        'type' => @$actionData['type'],
                        'area' => [
                            'x' => $actionData['x'],
                            'y' => $actionData['y'],
                            'width' => $actionData['width'] == 0 ? 1 : $actionData['width'],
                            'height' => $actionData['height'] == 0 ? 1: $actionData['height'],
                        ]
                    ];
                    if ($actionData['type'] == 'message') {
                        $action['text'] = @$actionData['text'];
                        $actionListImageMap[] = $action;
                    }
                    if ($actionData['type'] == 'uri') {
                        $action['linkUri'] = @$actionData['uri'];
                        $actionListImageMap[] = $action;
                    }
                }
                return [
                    "type" => "imagemap",
                    "baseUrl" => $params['baseUrl'],
                    "altText" => $nameLBD,
                    "baseSize" => [
                        "width" => $params['baseWidth'],
                        "height" => $params['autoDetectedBaseHeight'],
                    ],
                    "actions" => $actionListImageMap,
                ];
            }
            case 'buttons': {
                $actionList = [];
                for($i = 0; $i < $params['actionCount']; $i++) {
                    if (isset($params['actions.'.$i])) {
                        $actionData = $params['actions.'.$i];
                        $actionList[] = $this->actionGenerator($actionData, $actionData['type']);
                    }
                }
                $template = [
                    'type' => 'buttons',
                    'imageAspectRatio' => @$params['imageAspectRatio'] ?? 'rectangle',
                    'imageSize' => @$params['imageSize'] ?? 'cover',
                    'text' => @$params['text'],
                    'imageBackgroundColor' => @$params['imageBackgroundColor'] ?? '#ffffff',
                    'actions' => $actionList
                ];
                if (isset($params['thumbnailImageUrl']) && $params['thumbnailImageUrl'] != '') {
                    $template['thumbnailImageUrl'] = $params['thumbnailImageUrl'];
                }
                if (isset($params['title']) && $params['title'] != '') {
                    $template['title'] = $params['title'];
                }
                return [
                    'type' => 'template',
                    'altText' => $nameLBD,
                    'template' => $template
                ];
            }
            case 'confirm': {
                $actionList = [];
                if (isset($params['actionLeft'])) {
                    $actionList[] = $this->actionGenerator($params['actionLeft'], $params['actionLeft']['type']);
                }
                if (isset($params['actionRight'])) {
                    $actionList[] = $this->actionGenerator($params['actionRight'], $params['actionRight']['type']);
                }
                return [
                    'type' => 'template',
                    'altText' => $nameLBD,
                    'template' => [
                        'type' => 'confirm',
                        'text' => @$params['text'],
                        'actions' => $actionList
                    ]
                ];
            }
            case 'bubbleFlex': {
                if (isset($params['specialScenarioTalk'])) {
                    unset($params['specialScenarioTalk']);
                }
                $params['type'] = 'bubble';
                return [
                    'type' => 'flex',
                    'altText' => $nameLBD,
                    'contents' => $params
                ];
            }
            case 'carouselFlex': {
                if (isset($params['specialScenarioTalk'])) {
                    unset($params['specialScenarioTalk']);
                }
                $params['type'] = 'carousel';
                return [
                    'type' => 'flex',
                    'altText' => $nameLBD,
                    'contents' => $params
                ];
            }
            case 'carousel': {
                $columns = [];
                Log::info('dsadasdsa', $params);
                $columnNum = $params['columnCount'];
                $use_thumbnail = $params["useThumbnailImage"];
                $use_title = $params["useTitle"];
                for($i = 0; $i < $columnNum; $i++) {
                    $actionList = [];
                    for($idx = 0; $idx < $params['actionCount']; $idx++) {
                        $actionData = $params['action.'.$i.'.'.$idx];
                        $actionList[] = $this->actionGenerator($actionData, $actionData['type']);
                    }
                    $args = [
                        'actions' => $actionList,
                        'text' => $params['text.'.$i]
                    ];
                    if ($use_thumbnail) {
                        $args['thumbnailImageUrl'] = $params['thumbnail.'.$i];
                        $args['imageBackgroundColor'] = "#FFFFFF";
                    }
                    if ($use_title) {
                        $args['title'] = $params['title.'.$i];
                    }
                    $columns[] = $args;
                }

                return [
                    'type' => 'template',
                    'altText' => $nameLBD,
                    'template' => [
                        'type' => 'carousel',
                        'columns' => $columns,
                    ]
                ];
            }
            default: {
                return [
                    'type' => 'text',
                    'text' => $params['text']
                ];
            }
        }
    }

    public function actionGenerator($actionData, $type)
    {
        switch ($type) {
            case 'postback': {
                return [
                    'type' => 'postback',
                    'label' => @$actionData['label'],
                    'data' => @$actionData['data'],
                    'text' => @$actionData['text'],
                ];
            }
            case 'message': {
                return [
                    'type' => 'message',
                    'label' => @$actionData['label'],
                    'text' => @$actionData['text']
                ];
            }
            case 'uri': {
                return [
                    'type' => 'uri',
                    'label' => @$actionData['label'],
                    'uri' => @$actionData['uri']
                ];
            }
            case 'datetimepicker': {
                return [
                    'type' => 'datetimepicker',
                    'label' => @$actionData['label'],
                    'data' => @$actionData['data'],
                    'mode' => @$actionData['mode'],
                    'initial' => @$actionData['initial'],
                    'max' => @$actionData['max'],
                    'min' => @$actionData['min'],
                ];
            }
            default: {
                return [
                    'type' => 'message',
                    'label' => @$actionData['label'],
                    'text' => @$actionData['text']
                ];
            }
        }
    }
}
