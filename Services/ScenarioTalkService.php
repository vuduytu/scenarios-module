<?php

namespace Modules\Scenarios\Services;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Scenarios\Models\ScenarioMessageModel;
use Modules\Scenarios\Models\ScenarioModel;
use Modules\Scenarios\Models\ScenarioTalkModel;
use Modules\Scenarios\Models\ScenarioTextMappingModel;
use Modules\Scenarios\Models\ScenarioUserMessageModel;
use Modules\Scenarios\Repositories\ScenarioTalk\IScenarioTalkRepo;
use Modules\Scenarios\Services\_Exception\AppServiceException;
use Modules\Scenarios\Services\_Trait\EntryServiceTrait;

class ScenarioTalkService
{
    use EntryServiceTrait {
        createFromArray as createFromArrayTrait;
        updateFromRequest as updateFromRequestTrait;
    }
    protected $mainRepository;

    const DATA_ID_FILE_NAME = [
        'scenario_earthquake.json' => 'BOSAI_EARTHQUAKE_TALK',
        'scenario_rain_typhoon.json' => 'BOSAI_RAIN_TYPHOON_TALK',
        'scenario_search.json' => 'BOSAI_FLOW_SEARCH_TALK',
        'scenario.json' => 'BOSAI_FLOW_TALK',
        'add_damage_scenario.json' => 'DAMAGE_REPORT_TALK',
    ];

    public function __construct(IScenarioTalkRepo $entryRepo)
    {
        $this->mainRepository = $entryRepo;
    }

    public function createFromRequest(Request $request)
    {
        DB::beginTransaction();
        try {
            $scenario = ScenarioModel::find($request->scenario_id);
            $talkCheckDataName = ScenarioTalkModel::where('scenario_id', $scenario->id)->where('displayName', $request->talkName)->first();
            if ($talkCheckDataName) {
                throw new AppServiceException('トーク名を既存のトークと同じにすることはできません。', SERVER_ERROR);
            }
            $talkCheckDataMessage = ScenarioTalkModel::where('scenario_id', $scenario->id)->where('startMessage', $request->startMessage)->first();
            if ($talkCheckDataMessage) {
                throw new AppServiceException('トーク名を既存のユーザーテキストマッピングと同じにすることはできません。', SERVER_ERROR);
            }
            $dataTextMap = ScenarioTextMappingModel::where('scenario_id', $request->scenario_id)->first();
            $newDataTextMapping = $dataTextMap->textMapping;
            $newDataTextMapping[$request->startMessage] = $request->idTextMapping;
            $dataTextMap->update(['textMapping' => $newDataTextMapping]);
            $userMessage = ScenarioUserMessageModel::create([
                'store_id' => $scenario->store_id,
                'scenario_id' => $scenario->id,
                'params' => ['text' => $request->startMessage],
                'type' => 'text',
            ]);
            $talk = $this->mainRepository->create([
                'dataType' => 'talk',
                'store_id' => $scenario->store_id,
                'scenario_id' => $scenario->id,
                'displayName' => $request->talkName,
                'startMessage' => $request->startMessage,
                'numberOfMessage' => 1,
                'params' => [
                    'displayNumber' => $this->mainRepository->where('scenario_id', $request->scenario_id)->count() + 1,
                    'editor' => 'lbd-web',
                    'name' => $request->talkName,
                    'selectedRichMenuId' => null,
                    'updateDate' => time(),
                    'webAppList' => [],
                    'messages' => [
                        [
                            'messageId' => $userMessage->id,
                            'sender' =>  "USER",
                            'time'=> time(),
                        ],
                        [
                            'messageId' => $request->idTextMapping,
                            'sender' =>  "BOT",
                            'time'=> time(),
                        ]
                    ]
                ],
            ]);
            $message = $request->messages;
            unset($message['scenario']);
            $message['scenario_id'] = $scenario->id;
            $message['store_id'] = $scenario->store_id;
            $message['scenario_talk_id'] = $talk->id;
            ScenarioMessageModel::create($message);
            DB::commit();
            return $talk;
        } catch (\Exception $exception) {
            DB::rollBack();
            throw new AppServiceException(__('messages.unknown_error'), SERVER_ERROR);
        }
    }

    public function deleteTalk($entryId, $talkIds)
    {
        DB::beginTransaction();
        try {
            $scenario = ScenarioModel::find($entryId);
            foreach ($talkIds as $talkId) {
                $talk = ScenarioTalkModel::find($talkId);
                if (!$talk) {
                    $talk = $this->mainRepository->where('dataId', $talkId)->where('scenario_id', $entryId)->first();
                }
                if ($talkId === 'DAMAGE_REPORT_TALK') {
                    $scenario->update(['specialTalks' => []]);
                }
                if ($talk) {
                    $messages = $talk->params['messages'];
                    foreach ($messages as $message) {
                        if ($message['sender'] === 'USER') {
                            $userMessage = ScenarioUserMessageModel::find($message['messageId']);
                            if ($userMessage) {
                                $userMessage->delete();
                            }
                        }
                        if ($message['sender'] === 'BOT') {
                            $dataTextMap = ScenarioTextMappingModel::where('scenario_id', $entryId)->first();
                            $newDataTextMapping = $dataTextMap->textMapping;
                            $arrayFlip = array_flip($newDataTextMapping);
                            unset($arrayFlip[$message['messageId']]);
                            $dataTextMap->update(['textMapping' => array_flip($arrayFlip)]);
                        }
                    }
                    ScenarioMessageModel::where('scenario_talk_id', $talk->id)->delete();
                    $this->mainRepository->where('id', $talk->id)->delete();
                }
            }
            DB::commit();
            return $entryId;
        } catch (\Exception $exception)
        {
            DB::rollBack();
            throw new AppServiceException(__('messages.unknown_error'), SERVER_ERROR);
        }
    }

    public function addBosaiFlow($dataRequest, $fileName = 'scenario_earthquake.json')
    {
        DB::beginTransaction();
        try {
            $scenario = ScenarioModel::find($dataRequest['version']);
            $dataTalkId = self::DATA_ID_FILE_NAME[$fileName];
            if ($dataTalkId === 'DAMAGE_REPORT_TALK') {
                $scenario->update(['specialTalks' => ['damageReport' => true]]);
            }
            $checkTalk = $this->mainRepository->where('dataId', $dataTalkId)->where('scenario_id', $scenario->id)->first();
            if ($checkTalk) {
                return [];
            }
            $path = app()->basePath() . '/Modules/Scenarios/Bosai/'.$fileName;
            $data = json_decode(file_get_contents($path), true);
            $params = $data['talk'];
            $params['displayNumber'] = $this->mainRepository->where('scenario_id', $dataRequest['version'])->count() + 1;
            $talk = $this->mainRepository->create([
                'dataType' => 'talk',
                'store_id' => $scenario->store_id,
                'scenario_id' => $scenario->id,
                'displayName' => $data['talk']['name'],
                'startMessage' => '',
                'dataId' => self::DATA_ID_FILE_NAME[$fileName],
                'params' => $params,
                'numberOfMessage' => count($data['scenario']),
            ]);
            $messages = $data['scenario'];
            foreach ($messages as $message) {
                $message['scenario_id'] = $scenario->id;
                $message['store_id'] = $scenario->store_id;
                $message['scenario_talk_id'] = $talk->id;
                ScenarioMessageModel::create($message);
            }
            DB::commit();
            return [];
        } catch (\Exception $exception) {
            DB::rollBack();
            throw new AppServiceException(__('messages.unknown_error'), SERVER_ERROR);
        }
    }

    public function addTrashData($data, $entryId, $path)
    {
        if (count($data) == 0) {
            return [
                'success' => false,
                'messages' => __('messages.unknown_error')
            ];
        }
        unset($data[0]);
        DB::beginTransaction();
        try {
            $scenario = ScenarioModel::find($entryId);
            $checkTalk = $this->mainRepository->where('dataId', 'TRASH_SEPARATION_TALK')->where('scenario_id', $entryId)->first();
            if ($checkTalk) {
                $dataCheck = $this->formatCsv($data, $checkTalk, $path);
                if (!$dataCheck['success']) {
                    DB::rollBack();
                    return $dataCheck;
                }
                DB::commit();
                return ['success' => true];
            } else {
                $talk = $this->mainRepository->create([
                    'dataType' => 'talk',
                    'store_id' => $scenario->store_id,
                    'scenario_id' => $scenario->id,
                    'displayName' => 'ゴミ分別',
                    'dataId' => 'TRASH_SEPARATION_TALK',
                ]);
                $dataCheck = $this->formatCsv($data, $talk, $path);
                if (!$dataCheck['success']) {
                    DB::rollBack();
                    return $dataCheck;
                }
                DB::commit();
                return ['success' => true];
            }
            return ['success' => true];
        } catch (\Exception $exception) {
            DB::rollBack();
            return [
                'success' => false,
                'messages' => __('messages.unknown_error')
            ];
        }
    }

    private function formatCsv($data, $talk, $path)
    {
        $textMapping = [];
        $messageList = [];
        $messages = [];
        $compositMessageOveride = [];
        # Last saved user text mapping.
        $saveUserInput = null;
        # Last saved messages list.
        $savedMessages = [];
        foreach ($data as $datum) {
            $userInput = $datum[0];
            $textMessage = $datum[1];
            if($userInput) {
                if ($saveUserInput && count($savedMessages) > 0) {
                    if (array_key_exists($userInput, $textMapping)) {
                        return [
                            'success' => false,
                            'messages' => "Error: CSVファイルの処理中にエラーが発生しました：ユーザーテキストマッピングが重複しています：「".$userInput."」"
                        ];
                    }
                    if(count($savedMessages) == 1) {
                        if (array_key_exists($savedMessages[0], $messageList)) {
                            $textMapping[$saveUserInput] = $messageList[$savedMessages[0]];
                        } else {
                            $newMessage = [];
                            $newMessage['scenario_id'] = $talk->scenario_id;
                            $newMessage['store_id'] = $talk->store_id;
                            $newMessage['scenario_talk_id'] = $talk->id;
                            $newMessage['dataType'] = 'text';
                            $newMessage['params'] = [
                                'specialScenarioTalk' => 'ゴミ分別',
                                'text' => $savedMessages[0],
                            ];
                            $newMessageObject = ScenarioMessageModel::create($newMessage);
                            $compositMessageParams[] = $newMessageObject->id;
                            $newMessageObject->dataId = $newMessageObject->id;
                            $newMessageObject->save();
                            $textMapping[$saveUserInput] = $newMessageObject->id;
                            $messageList[$savedMessages[0]] = $newMessageObject->id;
                            $messages[] = [
                                'messageId' => $newMessageObject->id,
                                'sender' => 'BOT',
                                'time' => time()
                            ];
                        }
                    } else {
                        $compositMessageParams = [];
                        foreach ($savedMessages as $dataMessage) {
                            if (array_key_exists($dataMessage, $messageList)) {
                                $compositMessageParams[] = $messageList[$dataMessage];
                            } else {
                                $newMessage = [];
                                $newMessage['scenario_id'] = $talk->scenario_id;
                                $newMessage['store_id'] = $talk->store_id;
                                $newMessage['scenario_talk_id'] = $talk->id;
                                $newMessage['dataType'] = 'text';
                                $newMessage['params'] = [
                                    'specialScenarioTalk' => 'ゴミ分別',
                                    'text' => $dataMessage,
                                ];
                                $newMessageObject = ScenarioMessageModel::create($newMessage);
                                $compositMessageParams[] = $newMessageObject->id;
                                $newMessageObject->dataId = $newMessageObject->id;
                                $newMessageObject->save();
                                $messageList[$dataMessage] = $newMessageObject->id;
                                $messages[] = [
                                    'messageId' => $newMessageObject->id,
                                    'sender' => 'BOT',
                                    'time' => time()
                                ];
                            }
                        }
                        $key = implode(',',$compositMessageParams);
                        if (array_key_exists($key, $compositMessageOveride)) {
                            $textMapping[$saveUserInput] = $compositMessageOveride[$key];
                        } else {
                            $compositMessage = [];
                            $compositMessage['scenario_id'] = $talk->scenario_id;
                            $compositMessage['store_id'] = $talk->store_id;
                            $compositMessage['scenario_talk_id'] = $talk->id;
                            $compositMessage['dataType'] = 'compositeMessage';
                            $compositMessage['params'] = [
                                'specialScenarioTalk' => 'ゴミ分別',
                            ];
                            $compositMessage['messages'] = $compositMessageParams;
                            $compositMessageObject = ScenarioMessageModel::create($compositMessage);
                            $compositMessageObject->dataId = $compositMessageObject->id;
                            $compositMessageObject->save();
                            $messages[] = [
                                'messageId' => $compositMessageObject->id,
                                'sender' => 'BOT',
                                'time' => time()
                            ];
                            $textMapping[$saveUserInput] = $compositMessageObject->id;
                            $compositMessageOveride[$key] = $compositMessageObject->id;
                        }
                    }
                }
                $saveUserInput = $userInput;
                $savedMessages = [];
            }
            $savedMessages[] = $textMessage;
        }
        if ($saveUserInput && count($savedMessages) > 0) {
            if (array_key_exists($userInput, $textMapping)) {
                return [
                    'success' => false,
                    'messages' => "Error: CSVファイルの処理中にエラーが発生しました：ユーザーテキストマッピングが重複しています：「".$userInput."」"
                ];
            }
            if(count($savedMessages) == 1) {
                if (array_key_exists($savedMessages[0], $messageList)) {
                    $textMapping[$saveUserInput] = $messageList[$savedMessages[0]];
                } else {
                    $newMessage = [];
                    $newMessage['scenario_id'] = $talk->scenario_id;
                    $newMessage['store_id'] = $talk->store_id;
                    $newMessage['scenario_talk_id'] = $talk->id;
                    $newMessage['dataType'] = 'text';
                    $newMessage['params'] = [
                        'specialScenarioTalk' => 'ゴミ分別',
                        'text' => $savedMessages[0],
                    ];
                    $newMessageObject = ScenarioMessageModel::create($newMessage);
                    $compositMessageParams[] = $newMessageObject->id;
                    $newMessageObject->dataId = $newMessageObject->id;
                    $newMessageObject->save();
                    $textMapping[$saveUserInput] = $newMessageObject->id;
                    $messageList[$savedMessages[0]] = $newMessageObject->id;
                    $messages[] = [
                        'messageId' => $newMessageObject->id,
                        'sender' => 'BOT',
                        'time' => time()
                    ];
                }
            } else {
                $compositMessageParams = [];
                foreach ($savedMessages as $dataMessage) {
                    if (array_key_exists($dataMessage, $messageList)) {
                        $compositMessageParams[] = $messageList[$dataMessage];
                    } else {
                        $newMessage = [];
                        $newMessage['scenario_id'] = $talk->scenario_id;
                        $newMessage['store_id'] = $talk->store_id;
                        $newMessage['scenario_talk_id'] = $talk->id;
                        $newMessage['dataType'] = 'text';
                        $newMessage['params'] = [
                            'specialScenarioTalk' => 'ゴミ分別',
                            'text' => $dataMessage,
                        ];
                        $newMessageObject = ScenarioMessageModel::create($newMessage);
                        $compositMessageParams[] = $newMessageObject->id;
                        $newMessageObject->dataId = $newMessageObject->id;
                        $newMessageObject->save();
                        $messageList[$dataMessage] = $newMessageObject->id;
                        $messages[] = [
                            'messageId' => $newMessageObject->id,
                            'sender' => 'BOT',
                            'time' => time()
                        ];
                    }
                }
                $key = implode(',',$compositMessageParams);
                if (array_key_exists($key, $compositMessageOveride)) {
                    $textMapping[$saveUserInput] = $compositMessageOveride[$key];
                } else {
                    $compositMessage = [];
                    $compositMessage['scenario_id'] = $talk->scenario_id;
                    $compositMessage['store_id'] = $talk->store_id;
                    $compositMessage['scenario_talk_id'] = $talk->id;
                    $compositMessage['dataType'] = 'compositeMessage';
                    $compositMessage['params'] = [
                        'specialScenarioTalk' => 'ゴミ分別',
                    ];
                    $compositMessage['messages'] = $compositMessageParams;
                    $compositMessageObject = ScenarioMessageModel::create($compositMessage);
                    $compositMessageObject->dataId = $compositMessageObject->id;
                    $compositMessageObject->save();
                    $messages[] = [
                        'messageId' => $compositMessageObject->id,
                        'sender' => 'BOT',
                        'time' => time()
                    ];
                    $textMapping[$saveUserInput] = $compositMessageObject->id;
                    $compositMessageOveride[$key] = $compositMessageObject->id;
                }
            }
        }
        $talk->params = [
            'displayNumber' => 1,
            'messages' => $messages,
            'name' => 'ゴミ分別',
            'selectedRichMenuId' => null,
            'webAppList' => [],
            'path' => $path,
        ];
        $talk->startMessage = array_keys($textMapping)[0];
        $talk->numberOfMessage = count($messages);
        $talk->save();
        $dataTextMap = ScenarioTextMappingModel::where('scenario_id', $talk->scenario_id)->first();
        $newDataTextMapping = $dataTextMap->textMapping ?? [];
        $newDataTextMapping = array_merge($newDataTextMapping, $textMapping);
        $dataTextMap->update(['textMapping' => $newDataTextMapping]);
        return ['success' => true];
    }

    public function updateTalkName($data)
    {
        $talkCheckDataName = ScenarioTalkModel::where('scenario_id', $data['versionId'])
            ->where('displayName', $data['newTalkName'])
            ->where('id', '<>', $data['talkDataId'])
            ->first();
        if ($talkCheckDataName) {
            throw new AppServiceException('トーク名を既存のトークと同じにすることはできません。', SERVER_ERROR);
        }
        $talk = ScenarioTalkModel::where('scenario_id', $data['versionId'])->where(function($query) use($data) {
            return $query->where('id', $data['talkDataId'])->orWhere('dataId', $data['talkDataId']);
        })->first();
        $params = $talk->params;
        $params['name'] = $data['newTalkName'];
        $talk->displayName = $data['newTalkName'];
        $talk->params = $params;
        $talk->save();
//        $this->mainRepository->update(['displayName' => $data['newTalkName'], 'params' => $params], $data['talkDataId']);
    }
}
