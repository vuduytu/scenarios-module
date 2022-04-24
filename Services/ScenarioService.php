<?php

namespace Modules\Scenarios\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Scenarios\Http\Requests\API\Admin\ScenarioStoreRequest;
use Modules\Scenarios\Models\Scenario;
use Modules\Scenarios\Models\ScenarioMessageModel;
use Modules\Scenarios\Models\ScenarioSettingModel;
use Modules\Scenarios\Models\ScenarioTalkModel;
use Modules\Scenarios\Models\ScenarioTextMappingModel;
use Modules\Scenarios\Models\ScenarioUserMessageModel;
use Modules\Scenarios\Repositories\Scenario\IScenarioRepo;
use Modules\Scenarios\Services\_Abstract\BaseService;
use Modules\Scenarios\Services\_Exception\AppServiceException;
use Modules\Scenarios\Services\_Trait\EntryServiceTrait;
use Modules\Scenarios\Services\Common\DataHandleService;
//use File;
use Illuminate\Support\Facades\File;

class ScenarioService extends BaseService
{
    use EntryServiceTrait {
        createFromArray as createFromArrayTrait;
        updateFromRequest as updateFromRequestTrait;
    }

    const COMPONENT_PROPERTY = [
            "box" =>  ['layout', 'flex', 'spacing', 'margin', 'action',
                    'position', 'offsetTop', 'offsetBottom', 'offsetStart',
                    'offsetEnd', 'paddingAll', 'paddingTop', 'paddingBottom',
                    'paddingStart', 'paddingEnd', 'width', 'height', 'borderWidth',
                    'backgroundColor', 'borderColor', 'cornerRadius'],
            "image" => ['url', 'flex', 'size', 'align', 'gravity', 'aspectRatio',
                    'aspectMode', 'margin', 'backgroundColor', 'action',
                    'position', 'offsetTop', 'offsetBottom', 'offsetStart', 'offsetEnd'],
            "button" => ['action', 'flex', 'margin', 'height', 'style', 'color',
                    'gravity', 'position', 'offsetTop', 'offsetBottom',
                    'offsetStart', 'offsetEnd'],
            "filler" => ['flex'],
            "separator" => ['margin', 'color'],
            "text" => ['text', 'flex', 'size', 'color', 'weight', 'align',
                    'gravity', 'margin', 'wrap', 'action', 'style', 'decoration',
                    'position', 'offsetTop', 'offsetBottom', 'offsetStart', 'offsetEnd'],
            "spacer" => ['size'],
            "icon" => ['url', 'margin', 'size', 'aspectRatio', 'position',
                    'offsetTop', 'offsetBottom', 'offsetStart', 'offsetEnd'],
            "span" => ['text', 'size', 'color', 'weight', 'style', 'decoration']
            ];

    const MESSAGE_TYPE = [
        "text",
        "buttons",
        "carousel",
        "sticker",
        "confirm",
        "imagemap",
        "image",
        "video",
        "audio",
        "file",
        "location"];

    const EMPTY_ACTION = [
        "type" => "uri"
    ];

    const EMPTY_PROP_MAP = [
        'layout' => "vertical",
        'flex' => "",
        'spacing' => "",
        'margin' => "",
        'action' => self::EMPTY_ACTION,
        'position' => "none",
        'offsetTop' => "",
        'offsetBottom' => "",
        'offsetStart' => "",
        'offsetEnd' => "",
        'paddingAll' => "",
        'paddingTop' => "",
        'paddingBottom' => "",
        'paddingStart' => "",
        'paddingEnd' => "",
        'width' => "",
        'height' => "",
        'borderWidth' => "",
        'backgroundColor' => null,
        'borderColor' => null,
        'cornerRadius' => "",
        'url' => "https =>//developers.line.biz/assets/images/services/bot-designer-icon.png",
        'size' => "none",
        'align' => null,
        'gravity' => null,
        'aspectRatio' => null,
        'aspectMode' => "none",
        'style' => "none",
        'color' => "",
        'text' => "Body",
        'weight' => "",
        'wrap' => "none",
        'decoration' => "none",
    ];

    const TEMPLATE_SCENARIO_TALK_IDS = [
        "BOSAI_FLOW_TALK",
        "BOSAI_FLOW_SEARCH_TALK",
        "DAMAGE_REPORT_TALK",
        "TRASH_SEPARATION_TALK",
        "BOSAI_RAIN_TYPHOON_TALK",
        "BOSAI_EARTHQUAKE_TALK"
    ];

    const MESSAGE_TYPE_CONVERT = [
        "image",
        "audio",
        "video",
        "imagemap",
        "buttons",
        "carousel",
        "confirm"
    ];

    const MESSAGE_WITH_URL =  ["image", "audio", "video", "imagemap", "buttons", "carousel"];

    protected $mainRepository;

    public function __construct(IScenarioRepo $entryRepo)
    {
        $this->mainRepository = $entryRepo;
    }

    public function search($data)
    {
        $setting = $this->checkSetting();
        $id = '';
        $idSearch = '';
        $typeSearch = false;
        if ($setting) {
            $mapping = json_decode($setting->envMapping, true);
            if (@$data['sortBy'] === 'production') {
                $id = @$mapping['production'];
            }
            if (@$data['sortBy'] === 'sandbox') {
                $id = @$mapping['sandbox'];
            }

            if (isset($data['filtersProduction'])) {
                $idSearch = @$mapping['production'];
                $typeSearch = $data['filtersProduction'];
            }

            if (isset($data['filtersSandbox'])) {
                $idSearch = @$mapping['sandbox'];
                $typeSearch = $data['filtersSandbox'];
            }
        }

        return DataHandleService::initFromRepository($this->mainRepository)
            ->commonListQuery()
            ->additionQuery(function ($query) use ($setting, $id, $data, $idSearch, $typeSearch) {
                $query->where('store_id', auth()->user()->store_id);
                $query->where('scenario_setting_id', @$setting->id);
                if ($idSearch) {
                    if (!$typeSearch) {
                        $query->where('id', $idSearch);
                    } else {
                        $query->where('id', '<>', $idSearch);
                    }
                }
                else {
                    // nếu ko tồn tại idSearch thì cả production và sandbox đều ko có
                    // ==> Applied search ra null còn non-apply lấy toàn bộ
                    if ($typeSearch === '0') {
                        $query->where('id', $idSearch);
                    }
                }
                $query->orderByRaw("FIELD(id , '" . $id . "') " . (isset($data['sortType']) ? $data['sortType'] : "ASC"));
            })->paginate();
    }

    public function createFromRequest(ScenarioStoreRequest $request)
    {
        $request->merge(['store_id' => auth()->user()->store_id]);
        $setting = $this->checkSetting();
        $request->merge(['scenario_setting_id' => $setting->id]);

        try {
            return $this->mainRepository->create($request->fillData());
        } catch (\Exception $e) {
            \Log::info($e->getMessage());
            throw new AppServiceException(__('messages.unknown_error'), SERVER_ERROR);
        }
    }

    public function checkSetting()
    {
        $setting = ScenarioSettingModel::where('store_id', auth()->user()->store_id)->first();
        if (!$setting) {
            ScenarioSettingModel::create([
                "name" => "GovTechProgram",
                'store_id' => auth()->user()->store_id
            ]);
            $setting = ScenarioSettingModel::where('store_id', auth()->user()->store_id)->first();
        }
        return $setting;
    }

    public function getEnvMapping()
    {
        $setting = $this->checkSetting();
        $product = Scenario::where('scenario_setting_id', $setting->id)->where('type', Scenario::PRODUCTION)->first();
        $sandbox = Scenario::where('scenario_setting_id', $setting->id)->where('type', Scenario::SANDBOX)->first();

        return [
            'production' => @$product->id,
            'sandbox' => @$sandbox->id
        ];
    }

    public function changeActive($id, $type)
    {
        $setting = $this->checkSetting();
        $envMapping = $setting->envMapping;
        $envMapping = json_decode($envMapping, true);
        if ($envMapping === null) {
            $envMapping = [
                $type => $id
            ];
        } else {
            $envMapping[$type] = $id;
        }
        $setting->envMapping = $envMapping;
        $setting->save();

        return $setting->save();
    }

    public function updateSpecialTalk($id, $value)
    {
        $scenario = $this->mainRepository->find($id);
        $scenario->specialTalks = ['damageReport' => $value];
        $scenario->save();
    }

    public function exportLBD($id)
    {
        $destinationPath = storage_path() . "/app/public/scenario-export/" . time() . "/";
        if (!is_dir($destinationPath)) {
            mkdir($destinationPath, 0777, true);
        }
        try {
            $scenario = $this->mainRepository->find($id);
            $scenarioSeeting = ScenarioSettingModel::where('store_id', $scenario->store_id)->first();
            if (!$scenarioSeeting) {
                $scenarioSeeting = ScenarioSettingModel::first();
            }
            $messages = [];
            $user_messages = [];
            $chats = [];
            $web_appls = [];
            $resources = [];
            $bubble_flexes = [];
            $carousel_flexes = [];
            $extensions = [];
            ;
            $extensionChats = [];
            $extension_mappings = [];

            #temp data for processing
            $special_message_ids = [];
            $json_file = [];
            $json_file['modelVersion'] = 1;
            $json_file['backwardCompatibleModelVersion'] = 1;
            $json_file['meta'] = [
                'name' => $scenarioSeeting->name,
                'companyName' => '',
            ];
            $json_file['releaseVersion'] = "1.3.4";
            $texMappingData = ScenarioTextMappingModel::where('scenario_id', $id)->first();
            $userMessageData = ScenarioUserMessageModel::where('scenario_id', $id)->get();
            $talks = ScenarioTalkModel::where('scenario_id', $id)->get();
            $messagesData = ScenarioMessageModel::where('scenario_id', $id)->get();
            foreach ($talks as $talk) {
                if ($talk->dataId && in_array($talk->dataId, self::TEMPLATE_SCENARIO_TALK_IDS)) {
                    $talkArray = $talk->toArray();
                    unset($talkArray['id']);
                    $extensionChats[] = $talkArray;
                } else {
                    $chats[] = $talk->params;
                }
            }
            foreach ($messagesData as $key => $messagesDatum) {
                if (!$messagesDatum->talkModel) {
                    continue;
                }
                $dataIdTalk = $messagesDatum->talkModel->dataId;
                $item = $messagesDatum->toArray();
                if (!$item['dataId']) {
                    $item['dataId'] = $item['id'];
                }
                unset($item['id']);
                unset($item['store_id']);
                unset($item['scenario_id']);
                unset($item['scenario_talk_id']);
                unset($item['created_at']);
                unset($item['updated_at']);
                unset($item['deleted_at']);
                if ($item['dataType'] === "apiCall") {
                    $extensions[] = $item;
                    $special_message_ids[] = $item['dataId'];
                }
                if (in_array($item['dataType'], self::MESSAGE_TYPE) && $item['params'] && (isset($item['params']['specialScenarioTalk']) || in_array($dataIdTalk, self::TEMPLATE_SCENARIO_TALK_IDS))) {
                    $extensions[] = $item;
                    $special_message_ids[] = $item['dataId'];
                }
                $message = [];
                if (in_array($item['dataType'], self::MESSAGE_TYPE) && $item['params'] && !$dataIdTalk) {
                    $message['id'] = $item['dataId'];
                    $message['type'] = $item['dataType'];
                    if (isset($item['nameLBD'])) {
                        $message['name'] = $item['nameLBD'];
                    }
                    if ($item['dataType'] === "buttons") {
                        $message['params'] = $this->getButtonParam($item);
                    } elseif ($item['dataType'] === "imagemap") {
                        if ($item['params']['image2']['type'] == "file") {
                            $imageUrl = $item['params']['baseUrl'];
                            if (!is_dir($destinationPath . '/resources')) {
                                mkdir($destinationPath . '/resources', 0777, true);
                            }
                            $fileImage = file_get_contents($imageUrl . '/1040');
                            file_put_contents($destinationPath . 'resources/' . $item['params']['image2']['file']['id'], $fileImage);
                            $file = $item['params']['image2']['file'];
                            $file['url'] = $item['params']['baseUrl'];
                            $resources[] = $item['params']['image2']['file'];
                        }
                        $message['params'] = $item['params'];
                    } elseif ($item['dataType'] === "image") {
                        $message['params'] = $this->getImageParam($item);
                    } elseif ($item['dataType'] === "audio") {
                        $message['params'] = $this->getAudioParam($item);
                    } elseif ($item['dataType'] === "video") {
                        $message['params'] = $this->getVideoParam($item);
                    } elseif ($item['dataType'] === "sticker") {
                        $message['params'] = $this->getStickerParam($item);
                    } else {
                        $message['params'] = $item['params'];
                    }
                }
                if (count($message)) {
                    $messages[] = $message;
                }

                # bubbleFlexes
                if ($item['dataType'] == "bubbleFlex") {
                    if ($item['params'] && (isset($item['params']['specialScenarioTalk']) || in_array($dataIdTalk, self::TEMPLATE_SCENARIO_TALK_IDS))) {
                        $extensions[] = $item;
                    } else {
                        $bubbleflex = [];
                        $bubbleflex['id'] = $item['dataId'];
                        $bubbleflex['type'] = $item['dataType'];
                        if (isset($item['nameLBD'])) {
                            $bubbleflex['name'] = $item['nameLBD'];
                        }
                        $bubbleflex['params'] = $this->getBubbleFlexParams($item['params']);
                        $bubble_flexes[] = $bubbleflex;
                    }
                }

                if ($item['dataType'] == "carouselFlex") {
                    if (array_key_exists('defaultNameNumber', $item['params']) && !$dataIdTalk) {
                        $carouseflex = [];

                        $carouseflex['id'] = $item['dataId'];
                        $carouseflex['type'] = $item['dataType'];
                        if (isset($item['nameLBD'])) {
                            $carouseflex['name'] = $item['nameLBD'];
                        }
                        $carouseflex['params'] = $item['params'];

                        $carousel_flexes[] = $carouseflex;
                    }
                }
            }
            if ($texMappingData) {
                $arrayTextMap = $texMappingData->textMapping;
                foreach ($special_message_ids as $message_id) {
                    $keys = [];
                    foreach ($arrayTextMap as $key => $value) {
                        if ($value == $message_id) {
                            $keys[] = $key;
                        }
                    }
                    foreach ($keys as $key) {
                        $extension_mappings[$key] = $message_id;
                    }
                }
                $extension_mappings['TextMappings'] = true;
                $extensionChats[] = $extension_mappings;
                $arrayTextMap['OriginalTextMappings'] = true;
                $extensionChats[] = $arrayTextMap;
            }
            if ($userMessageData->count()) {
                foreach ($userMessageData as $userMessageDatum) {
                    $user_messages[$userMessageDatum->id] = [
                        'id' => $userMessageDatum->id,
                        'params' => $userMessageDatum->params,
                        'type' => $userMessageDatum->type,
                    ];
                }
            };
            $json_file['messages'] = $messages;
            $json_file['userMessages'] = $user_messages;
            $json_file['chats'] = $chats;
            $json_file['webApps'] = $web_appls;
            $json_file['resources'] = $resources;
            $json_file['bubbleFlexes'] = $bubble_flexes;
            $json_file['carouselFlexes'] = $carousel_flexes;
            $json_file['extensions'] = $extensions;
            $json_file['extensionChats'] = $extensionChats;
            $data = json_encode($json_file);
            $file = 'model.json';
            File::put($destinationPath . $file, $data);
            $fileName = str_replace('/', '-', Str::slug($scenario->displayVersionName).time()) . '.lbd';
            $zip_file = str_replace('/', '-', Str::slug($scenario->displayVersionName).time()) . '.lbd';
            $zip = new \ZipArchive();
            $zip->open($zip_file, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

            $path = $destinationPath;
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
            $zip->addEmptyDir('resources');
            foreach ($files as $name => $file) {
                // We're skipping all subfolders
                if (!$file->isDir()) {
                    $filePath     = $file->getRealPath();

                    // extracting filename with substr/strlen
                    $relativePath = substr($filePath, strlen($path));

                    $zip->addFile($filePath, $relativePath);
                }
            }

            $zip->close();
            $path = Storage::putFileAs('LBD-Files', $zip_file, $fileName);
            $path = Storage::url($path);
            File::deleteDirectory($destinationPath);
            if (File::exists(public_path() . '/' . $fileName)) {
                unlink(public_path() . '/' . $fileName);
            }
            return $path;
        } catch (\Exception $exception) {
            File::deleteDirectory($destinationPath);
            \Log::info($exception->getMessage() . '---' . $exception->getLine() . '---exportLBD');
            return false;
        }
    }

    private function getButtonParam($data)
    {
        $params = $data['params'];
        for ($i = 0; $i < $params['actionCount']; $i++) {
            if (isset($params['actions.' . $i])) {
                $actionData = $params['actions.' . $i];
                if ($actionData['type'] === 'datetimepicker') {
                    if ($actionData['mode'] === 'time') {
                        $actionData['initial'] = "2020-01-01T" + $actionData['initial'];
                        $actionData['max'] = "2020-01-01T" + $actionData['max'];
                        $actionData['min'] = "2020-01-01T" + $actionData['min'];
                        $params['actions.' . $i] = $actionData;
                    }
                    if ($actionData['mode'] === 'time') {
                        $actionData['initial'] = $actionData['initial'] + "T00:00";
                        $actionData['max'] = $actionData['max']  + "T00:00";
                        $actionData['min'] = $actionData['min']  + "T00:00";
                        $params['actions.' . $i] = $actionData;
                    }
                }
            }
        }
        return $params;
    }

    private function getImageParam($data)
    {
        $params = [];
        $original_content_url = [];
        $preview_image_url = [];

        # Type = url
        $original_content_url['type'] = "url";
        $original_content_url['url'] = $data['params']['originalContentUrl'];
        $original_content_url['file'] = null;

        $preview_image_url['type'] = "url";
        $preview_image_url['url'] = $data['params']['previewImageUrl'];
        $preview_image_url['file'] = null;

        $params['animated'] = false;
        $params['originalContentUrl'] = $original_content_url;
        $params['previewImageUrl'] = $preview_image_url;
        return $params;
    }

    private function getAudioParam($data)
    {
        $params = [];
        $original_content_url = [];
        $duration = [];

        # url
        $original_content_url['type'] = 'url';
        $original_content_url['file'] = null;
        $original_content_url['url'] = '';

        if (array_key_exists('originalContentUrl', $data['params'])) {
            $original_content_url['url'] = $data['params']['originalContentUrl'];
        }

        $temp_duration = null;
        if (array_key_exists('duration', $data['params'])) {
            $temp_duration = $data['params']['duration'] / 1000;
        }
        $duration['value'] = $temp_duration;

        if (array_key_exists('duration', $data['originalLBD'])) {
            $duration['unit'] = isset($data['originalLBD']['duration']['unit']) ? $data['originalLBD']['duration']['unit'] : "SECOND";
        } else {
            $duration['unit'] = "SECOND";
        }

        $params['originalContentUrl'] = $original_content_url;
        $params['duration'] = $duration;

        return $params;
    }

    private function getVideoParam($data)
    {
        $param = [];

        $original_content_url = [];
        $preview_image_url = [];

        $original_content_url['type'] = "url";
        $original_content_url['file'] = null;
        $original_content_url['url'] = $data['params']['originalContentUrl'];

        $preview_image_url['type'] = "url";
        $preview_image_url['file'] = null;
        $preview_image_url['url'] = $data['params']['previewImageUrl'];

        $param['originalContentUrl'] = $original_content_url;
        $param['previewImageUrl'] = $preview_image_url;

        return $param;
    }

    private function getStickerParam($data)
    {
        $sticker = [];
        $sticker['sticker'] = [];
        $sticker['sticker']['packageId'] = $data['params']['sticker']['packageId'];
        $sticker['sticker']['stickerId'] = $data['params']['sticker']['stickerId'];
        return $sticker;
    }

    private function generateActionComponent($data)
    {
        $actionData = $data;
        if ($actionData['type'] === 'datetimepicker') {
            if ($actionData['mode'] === 'time') {
                $actionData['initial'] = "2020-01-01T" + $actionData['initial'];
                $actionData['max'] = "2020-01-01T" + $actionData['max'];
                $actionData['min'] = "2020-01-01T" + $actionData['min'];
            }
            if ($actionData['mode'] === 'time') {
                $actionData['initial'] = $actionData['initial'] + "T00:00";
                $actionData['max'] = $actionData['max'] + "T00:00";
                $actionData['min'] = $actionData['min'] + "T00:00";
            }
        }
        return $actionData;
    }

    private function getBubbleFlexParams($data)
    {
        $converted_data = [];
        $converted_data["type"] = "bubble";
        if (array_key_exists('direction', $data)) {
            $converted_data["direction"] = $data['direction'];
        }
        $converted_data['size'] = isset($data['bubbleSize']) ? $data['bubbleSize'] : "none";
        $converted_data['action'] = isset($data['action']) ? $this->generateActionComponent($data['action']) : self::EMPTY_ACTION;
        //Generate Styles
        if (array_key_exists('styles', $data)) {
            $style_data = $data['styles'];
            #header styles
            if (array_key_exists('header', $data)) {
                $style_header = $style_data['header'];
                if (array_key_exists('backgroundColor', $style_header)) {
                    $converted_data["backgroundColor.0"] = $style_header["backgroundColor"];
                    $converted_data["separatorColor.0"] = $style_header["backgroundColor"];
                }
                if (array_key_exists('separator', $style_header)) {
                    $converted_data["separator.0"] = isset($style_header["separator"]) ? "true" : "false";
                } else {
                    $converted_data["separator.0"] = "false";
                }
            } else {
                $converted_data["separator.0"] = "none";
            }

            #hero styles
            if (array_key_exists('hero', $data)) {
                $style_hero = $style_data['hero'];
                if (array_key_exists('backgroundColor', $style_hero)) {
                    $converted_data["backgroundColor.1"] = $style_hero["backgroundColor"];
                    $converted_data["separatorColor.1"] = $style_hero["backgroundColor"];
                }
                if (array_key_exists('separator', $style_hero)) {
                    $converted_data["separator.1"] = isset($style_hero["separator"]) ? "true" : "false";
                } else {
                    $converted_data["separator.1"] = "false";
                }
            } else {
                $converted_data["separator.1"] = "none";
            }

            #body styles
            if (array_key_exists('body', $data)) {
                $style_body = $style_data['hero'];
                if (array_key_exists('backgroundColor', $style_body)) {
                    $converted_data["backgroundColor.2"] = $style_body["backgroundColor"];
                    $converted_data["separatorColor.2"] = $style_body["backgroundColor"];
                }
                if (array_key_exists('separator', $style_body)) {
                    $converted_data["separator.2"] = isset($style_body["separator"]) ? "true" : "false";
                } else {
                    $converted_data["separator.2"] = "false";
                }
            } else {
                $converted_data["separator.2"] = "none";
            }

            #footer styles
            if (array_key_exists('footer', $data)) {
                $style_footer = $style_data['hero'];
                if (array_key_exists('backgroundColor', $style_footer)) {
                    $converted_data["backgroundColor.3"] = $style_footer["backgroundColor"];
                    $converted_data["separatorColor.3"] = $style_footer["backgroundColor"];
                }
                if (array_key_exists('separator', $style_footer)) {
                    $converted_data["separator.3"] = isset($style_footer["separator"]) ? "true" : "false";
                } else {
                    $converted_data["separator.3"] = "false";
                }
            } else {
                $converted_data["separator.3"] = "none";
            }
        } else {
            $converted_data["separator.0"] = "none";
            $converted_data["separator.1"] = "none";
            $converted_data["separator.2"] = "none";
            $converted_data["separator.3"] = "none";
        }

        #Header component
        if (array_key_exists("header", $data)) {
            $converted_data["header"] = $this->generatorComponent($data["header"]);
        } else {
            $converted_data["header"] = [
                "type" => "box",
                "disable" => true,
                "componentProps" => $this->generateComponentProp(["type" => "box"]),
                "contents" => [$this->generatorComponent(["type" => "filler"])],
            ];
        }

        #Hero component
        if (array_key_exists("hero", $data)) {
            $converted_data["hero"] = $this->generatorComponent($data["hero"]);
        } else {
            $converted_data["hero"] = [
                "type" => "image",
                "disable" => true,
                "componentProps" => $this->generateComponentProp(["type" => "image"]),
            ];
        }

        #Body component
        if (array_key_exists("body", $data)) {
            $converted_data["body"] = $this->generatorComponent($data["body"]);
        } else {
            $converted_data["body"] = [
                "type" => "box",
                "disable" => true,
                "componentProps" => $this->generateComponentProp(["type" => "box"]),
                "contents" => [$this->generatorComponent(["type" => "filler"])],
            ];
        }

        #Footer component
        if (array_key_exists("footer", $data)) {
            $converted_data["footer"] = $this->generatorComponent($data["footer"]);
        } else {
            $converted_data["footer"] = [
                "type" => "box",
                "disable" => true,
                "componentProps" => $this->generateComponentProp(["type" => "box"]),
                "contents" => [$this->generatorComponent(["type" => "filler"])],
            ];
        }

        return $converted_data;
    }

    private function generatorComponent($data)
    {
        $converted_data = [
            "type" => isset($data['type']) ? $data['type'] : "box",
            "disable" => isset($data['disable']) ? $data['disable'] : false,
            "componentProps" => $this->generateComponentProp($data)
        ];

        #For elements that can have children
        if (in_array($converted_data['type'], ["box", "text"])) {
            $converted_contents = [];
            if (array_key_exists('contents', $data) && is_array($data['contents'])) {
                foreach ($data['contents'] as $item) {
                    $converted_contents[] = $this->generatorComponent($item);
                }
            }
            if ($converted_data['type'] === 'box' && count($converted_contents) == 0) {
                $converted_contents[] = $this->generatorComponent(['type' => 'filler']);
            }
            $converted_data['contents'] = $converted_contents;
        }
        return $converted_data;
    }

    private function generateComponentProp($data)
    {
        $converted_props = [];

        $properties = isset($data['type']) && isset(self::COMPONENT_PROPERTY[$data['type']]) ? self::COMPONENT_PROPERTY[$data['type']] : [];
        foreach ($properties as $prop) {
            if ($prop === "action" && array_key_exists($prop, $data)) {
                $converted_props[] = $this->generateActionComponent($data[$prop]);
            } else {
                $converted_props[] = array_key_exists($prop, $data) ? $data[$prop] : self::EMPTY_PROP_MAP[$prop];
            }
        }
        return $converted_props;
    }

    public function importLBD($data)
    {
        $scenarioSetting = ScenarioSettingModel::find($data['scenario']);
        if (!$scenarioSetting) {
            return [
                'success' => false,
                'message' => __('messages.unknown_error'),
            ];
        }
        $storeId = $scenarioSetting->store_id;
        $checkVersion = $this->mainRepository->where('store_id', $storeId)->where('displayVersionName', $data['version'])->first();
        if ($checkVersion) {
            return [
                'success' => false,
                'message' => '同名のバージョンが存在しています。'
            ];
        }
        DB::beginTransaction();
        $destinationPath = storage_path() . "/app/public/scenario" . time() . "/";
        mkdir($destinationPath, 0777, true);
        try {
            $scenario = $this->mainRepository->create([
                'store_id' => $storeId,
                'displayVersionName' => $data['version'],
                'scenario_setting_id' => $scenarioSetting->id,
            ]);
            $zip = new \ZipArchive();
            $res = $zip->open($data['file']->path());
            if ($res === true) {
                $zip->extractTo($destinationPath);
                $zip->close();
                $modelPath = $destinationPath . 'model.json';
                $dataModel = json_decode(file_get_contents($modelPath), true);
                $messages = isset($dataModel['messages']) ? $dataModel['messages'] : [];
                $bubbles = isset($dataModel['bubbleFlexes']) ? $dataModel['bubbleFlexes'] : [];
                $carousels = isset($dataModel['carouselFlexes']) ? $dataModel['carouselFlexes'] : [];
                $user_messages = isset($dataModel['userMessages']) ? $dataModel['userMessages'] : [];
                $chats = isset($dataModel['chats']) ? $dataModel['chats'] : [];
                $extensions = isset($dataModel['extensions']) ? $dataModel['extensions'] : [];
                $extensionChats = isset($dataModel['extensionChats']) ? $dataModel['extensionChats'] : [];
                #Run validation for https only.
                foreach ($messages as $msg) {
                    if (in_array($msg['type'], self::MESSAGE_WITH_URL)) {
                        $params = isset($msg['params']) ? $msg['params'] : [];
                        if ($msg['type'] === 'carousel') {
                            if (isset($params['useThumbnailImage'])) {
                                for ($i = 0; $i < $params['columnCount']; $i++) {
                                    $value = $params['thumbnail.' . $i];
                                    if ($value) {
                                        if (is_string($value) && strpos($value, 'https://') === false) {
                                            return [
                                                'success' => false,
                                                'message' => 'は無効です。全てリンクはHTTPSでなければなりません。'
                                            ];
                                        }
                                        if (isset($value['type']) && $value['type'] == 'url' && strpos($value['url'], 'https://') === false) {
                                            return [
                                                'success' => false,
                                                'message' => 'は無効です。全てリンクはHTTPSでなければなりません。'
                                            ];
                                        }
                                    }
                                }
                            }
                        } else {
                            $this->checkNoHttpsInParam('params', $params);
                        }
                    }
                }
                #Run validation for web applications.
                if (isset($data['webApps']) && count($data['webApps'])) {
                    return [
                        'success' => false,
                        'message' => 'ファイルは無効です。Webアプリを対応しません。ファイルのWebアプリが消しても一回インポートしてください。'
                    ];
                }
                # Talk
                # Delete existing talk in ChatbotScenarioData table before import
                $userMessageIds = [];
                if (count($user_messages)) {
                    foreach ($user_messages as $user_message) {
                        $dataInsert = [];
                        $dataInsert['params'] = $user_message['params'];
                        $dataInsert['store_id'] = $storeId;
                        $dataInsert['scenario_id'] = $scenario->id;
                        $uData = ScenarioUserMessageModel::create($dataInsert);
                        $userMessageIds[$user_message['id']] = $uData->id;
                    }
                }
                $dataTalk = [];
                $texMapping = [];
                foreach ($extensionChats as $extensionChat) {
                    if (array_key_exists('TextMappings', $extensionChat)) {
                        continue;
                    } elseif (array_key_exists('OriginalTextMappings', $extensionChat)) {
                        unset($extensionChat['OriginalTextMappings']);
                        $texMapping = $extensionChat;
                        ScenarioTextMappingModel::create([
                            'store_id' => $storeId,
                            'scenario_id' => $scenario->id,
                            'dataId' => 'textMapping',
                            'dataType' => 'textMapping',
                            'textMapping' => $extensionChat
                        ]);
                    } else {
                        unset($extensionChat['scenario']);
                        $extensionChat['store_id'] = $storeId;
                        $extensionChat['scenario_id'] = $scenario->id;
                        $extensionChat['displayName'] = $extensionChat['params']['name'];
                        $talk = ScenarioTalkModel::create($extensionChat);
                        $dataTalk[$talk->id] = $extensionChat['params']['messages'];
                    }
                }
                foreach ($chats as $chat) {
                    $messagesChat = $chat['messages'];
                    $key = array_search("USER", array_column($messagesChat, 'sender'));
                    if ($key !== false) {
                        $idUMess = $messagesChat[$key]['messageId'];
                        $messagesChat[$key]['messageId'] = array_key_exists($idUMess, $userMessageIds) ? $userMessageIds[$idUMess] : $idUMess;
                    }
                    $startMess = '';
                    $keyBot = array_search("BOT", array_column($messagesChat, 'sender'));
                    if ($keyBot !== false) {
                        $idUMess = $messagesChat[$keyBot]['messageId'];
                        if (in_array($idUMess, array_values($texMapping))) {
                            $startMess = array_search($idUMess, $texMapping);
                        }
                    }
                    $chat['messages'] = $messagesChat;
                    $dataChat = [];
                    $dataChat['params'] = $chat;
                    $dataChat['store_id'] = $storeId;
                    $dataChat['scenario_id'] = $scenario->id;
                    $dataChat['dataType'] = 'talk';
                    $dataChat['displayName'] = $chat['name'];
                    $dataChat['startMessage'] = $startMess;
                    $talk = ScenarioTalkModel::create($dataChat);
                    $dataTalk[$talk->id] = $messagesChat;
                }
                # Loop to save messages
                foreach ($messages as $msg) {
                    if ($msg) {
                        $idTalk = '';
                        foreach ($dataTalk as $key => $messagesChat) {
                            $keyTalk = array_search($msg['id'], array_column($messagesChat, 'messageId'));
                            if ($keyTalk !== false) {
                                $idTalk = $key;
                                break;
                            }
                        }
                        if (!$idTalk) {
                            ;
                            return [
                                'success' => false,
                                'message' => __('messages.unknown_error')
                            ];
                        }
                        $item_to_save = [
                            'store_id' => $storeId,
                            'scenario_id' => $scenario->id,
                            'dataId' => $msg['id'],
                            'scenario_talk_id' => $idTalk,
                        ];
                        if (in_array($msg['type'], self::MESSAGE_TYPE_CONVERT)) {
                            $original_params = isset($msg['params']) ? $msg['params'] : [];
                            $item_to_save['dataType'] = $msg['type'];
                            $converted_params = $this->convertParams($msg['params'], $msg['type'], $destinationPath);
                            $item_to_save["params"] = $converted_params;
                            $item_to_save["originalLBD"] = $original_params;
                        } else {
                            $item_to_save["params"] = $msg['params'];
                            $item_to_save['dataType'] = $msg['type'];
                        }

                        if (array_key_exists('name', $msg)) {
                            $item_to_save['nameLBD'] = $msg['name'];
                        }
                        ScenarioMessageModel::create($item_to_save);
                    }
                }
                # extension - composite message, damaga report
                foreach ($extensions as $item_to_save) {
                    $idTalk = '';
                    foreach ($dataTalk as $key => $messagesChat) {
                        $keyTalk = array_search($item_to_save['dataId'], array_column($messagesChat, 'messageId'));
                        if ($keyTalk !== false) {
                            $idTalk = $key;
                            break;
                        }
                        if ($item_to_save['messages'] && count($item_to_save['messages'])) {
                            $keyTalkdata = array_search($item_to_save['messages'][0], array_column($messagesChat, 'messageId'));
                            if ($keyTalkdata !== false) {
                                $idTalk = $key;
                                break;
                            }
                        }
                    }
                    if (!$idTalk) {
                        return [
                            'success' => false,
                            'message' => __('messages.unknown_error')
                        ];
                    }
                    $item_to_save['store_id'] = $storeId;
                    $item_to_save['scenario_id'] = $scenario->id;
                    $item_to_save['scenario_talk_id'] = $idTalk;
                    ScenarioMessageModel::create($item_to_save);
                }
                #carousel
                foreach ($carousels as $crs) {
                    if ($crs) {
                        $idTalk = '';
                        foreach ($dataTalk as $key => $messagesChat) {
                            $keyTalk = array_search($crs['id'], array_column($messagesChat, 'messageId'));
                            if ($keyTalk !== false) {
                                $idTalk = $key;
                                break;
                            }
                        }
                        if (!$idTalk) {
                            return [
                                'success' => false,
                                'message' => __('messages.unknown_error')
                            ];
                        }
                        $crs['store_id'] = $storeId;
                        $crs['dataId'] = $crs['id'];
                        $crs['scenario_id'] = $scenario->id;
                        $crs['scenario_talk_id'] = $idTalk;
                        $crs['nameLBD'] = $crs['name'];
                        $crs["dataType"] = "carouselFlex";
                        ScenarioMessageModel::create($crs);
                    }
                }
                #read bubbleFlexes; convert and save
                foreach ($bubbles as $bbs) {
                    if ($bbs) {
                        $idTalk = '';
                        foreach ($dataTalk as $key => $messagesChat) {
                            $keyTalk = array_search($bbs['id'], array_column($messagesChat, 'messageId'));
                            if ($keyTalk !== false) {
                                $idTalk = $key;
                                break;
                            }
                        }

                        if (!$idTalk) {
                            return [
                                'success' => false,
                                'message' => __('messages.unknown_error')
                            ];
                        }
                        $original_params = $bbs['params'];
                        $converted_params = $this->convertBubleParams($bbs['params']);
                        $bbs['store_id'] = $storeId;
                        $bbs['dataId'] = $bbs['id'];
                        $bbs['scenario_id'] = $scenario->id;
                        $bbs['scenario_talk_id'] = $idTalk;
                        $bbs['nameLBD'] = $bbs['name'];
                        $bbs['dataType'] = $bbs['type'];
                        $bbs['originalLBD'] = $original_params;
                        $bbs['params'] = $converted_params;
                        ScenarioMessageModel::create($bbs);
                    }
                }
                DB::commit();
                File::deleteDirectory($destinationPath);
                return [
                    'success' => true,
                    'data' => $scenario
                ];
            } else {
                File::deleteDirectory($destinationPath);
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => __('messages.unknown_error')
                ];
            }
        } catch (\Exception $exception) {
            File::deleteDirectory($destinationPath);
            DB::rollBack();
            return [
                'success' => false,
                'message' => __('messages.unknown_error')
            ];
        }
    }

    public function checkNoHttpsInParam($main_key, $main_value)
    {
        return true;
        if (is_string($main_key) && $main_key === 'url') {
            if (strpos($main_value, 'https://') === false) {
                throw new AppServiceException('は無効です。全てリンクはHTTPSでなければなりません。', SERVER_ERROR);
            }
        } else {
            foreach ($main_value as $key => $value) {
                $this->checkNoHttpsInParam($key, $value);
            }
        }
    }

    private function convertParams($params, $type, $path = null)
    {
        switch ($type) {
            case 'image': {
                return $this->convertImageParams($params, $type);
            }
            case 'audio': {
                return $this->convertAudioParams($params, $type);
            }
            case 'video': {
                return $this->convertVideoParams($params, $type);
            }
            case 'imagemap': {
                return $this->convertImageMapParams($params, $path);
            }
            case 'buttons': {
                return $this->convertButtonParams($params, $type);
            }
            case 'carousel': {
                return $this->convertCarouselParams($params, $type);
            }
            case 'confirm': {
                return $this->convertConfirmParams($params, $type);
            }
            default: {
                return $params;
            }
        }
    }

    private function convertImageParams($data, $type)
    {
        $converted_data = $data;
        $converted_data['originalImageLocal'] = false;
        $converted_data['previewImageLocal'] = false;

        if ($data['originalContentUrl']['type'] === 'file') {
            $temp_file = $data['originalContentUrl']['file'];
            $file_url = Storage::putFileAs('/image', $temp_file, 'demo.png');
            $converted_data['originalImageLocal'] = false;
            $converted_data['originalContentUrl'] = $file_url;
        } else {
            $file_url = $data['originalContentUrl']['url'];
            $converted_data['originalContentUrl'] = $file_url;
        }
        if ($data['originalContentUrl']['type'] === 'file') {
        } else {
            $file_url = $data['previewImageUrl']['url'];
            $converted_data['previewImageUrl'] = $file_url;
        }
        return $converted_data;
    }

    private function convertAudioParams($data, $type)
    {
        $convertData = $data;
        if (array_key_exists('url', $data['originalContentUrl'])) {
            $convertData['originalContentUrl'] = $data['originalContentUrl']['url'];
            $convertData['audioFileLocal'] = false;
        } else {
            $convertData['originalContentUrl'] = $data['originalContentUrl'];
            $convertData['audioFileLocal'] = false;
        }

        if (array_key_exists('unit', $data['duration'])) {
            if ($data['duration']['unit'] === "SECOND") {
                $tempDuaration = $data['duration']['value'];
                if ($tempDuaration) {
                    $convertData['duration'] = $tempDuaration * 1000;
                } else {
                    $convertData['duration'] = null;
                }
            }
        } else {
            if (array_key_exists('value', $data['duration'])) {
                $convertData['duration'] = $data['duration']['value'];
            } else {
                $convertData['duration'] = null;
            }
        }
        return $convertData;
    }

    private function convertVideoParams($data, $type)
    {
        $converted_data = $data;
        if (array_key_exists('url', $data['originalContentUrl'])) {
            $convertData['originalContentUrl'] = $data['originalContentUrl']['url'];
            $convertData['originalContentLocal'] = false;
        } else {
            $convertData['originalContentUrl'] = $data['originalContentUrl'];
            $convertData['originalContentLocal'] = false;
        }
        if (array_key_exists('url', $data['previewImageUrl'])) {
            $convertData['previewImageUrl'] = $data['previewImageUrl']['url'];
            $convertData['previewImageLocal'] = false;
        } else {
            $convertData['originalContentUrl'] = $data['originalContentUrl'];
            $convertData['previewImageLocal'] = false;
        }

        return $converted_data;
    }

    private function convertDateTimePickerData($actionData)
    {
        if ($actionData['mode'] == "date") {
            $actionData['initial'] = explode("T", $actionData['initial'])[0];
            $actionData['max'] = explode("T", $actionData['max'])[0];
            $actionData['min'] = explode("T", $actionData['min'])[0];
        }
        if ($actionData['mode'] == "time") {
            $actionData['initial'] = strpos($actionData['initial'], 'T') !== false ? explode("T", $actionData['initial'])[1] : $actionData['initial'];
            $actionData['max'] = strpos($actionData['max'], 'T') !== false ? explode("T", $actionData['max'])[1] : $actionData['max'];
            $actionData['min'] = strpos($actionData['min'], 'T') !== false ? explode("T", $actionData['min'])[1] : $actionData['min'];
        }
        return $actionData;
    }

    private function convertButtonParams($data, $type)
    {
        $convertData = $data;
        for ($i = 0; $i < $convertData['actionCount']; $i++) {
            $action_data = $convertData['actions.' . $i];
            if ($action_data['type'] == "datetimepicker") {
                $action_data = $this->convertDateTimePickerData($action_data);
            }
            $convertData['actions.' . $i] = $action_data;
        }
        return $data;
    }

    private function convertConfirmParams($data, $type)
    {
        $converted_data = $data;
        $left_data = $converted_data['actionLeft'];
        if ($left_data['type'] == "datetimepicker") {
            $left_data = $this->convertDateTimePickerData($left_data);
        }
        $right_data = $converted_data['actionRight'];
        if ($right_data['type'] == "datetimepicker") {
            $right_data = $this->convertDateTimePickerData($right_data);
        }
        $converted_data['actionLeft'] = $left_data;
        $converted_data['actionRight'] = $right_data;
        return $converted_data;
    }

    public function convertCarouselParams($data, $type)
    {
        $converted_data = $data;
        $column_count = $converted_data['columnCount'];
        $action_count = $converted_data['actionCount'];
        for ($i = 0; $i < $column_count; $i++) {
            for ($j = 0; $j < $action_count; $j++) {
                $actionData = $converted_data['action.' . $i . '.' . $j];
                if ($actionData['type'] == 'datetimepicker') {
                    $actionData = $this->convertDateTimePickerData($actionData);
                }
//                if ($actionData['type'] == 'webapp') {
//                    $actionData = $this->convertWebappData($actionData);
//                }

                $converted_data['action.' . $i . '.' . $j] = $actionData;
            }
        }
        return $converted_data;
    }

    private function convertImageMapParams($data, $path)
    {
        $converted_data = $data;
        if ($data['image2']['type'] == 'url') {
            $tempUrl = $data['image2']['url'];
            if ($tempUrl) {
                $split = explode('/', $tempUrl);
                $size_allowed = ['1040', '700', '460', '300', '240'];
                if (isset($split[1]) && in_array($split[1], $size_allowed)) {
                    $converted_data['baseUrl'] = $split[0];
                } else {
                    $converted_data['baseUrl'] = $tempUrl;
                }
                $converted_data['imageLocalFile'] = false;
            }
        } else {
            $converted_data['baseUrl'] = $this->uploadS3ImageMap($data['image2']['file'], $path);
            $converted_data['imageLocalFile'] = true;
        }
        return $converted_data;
    }

    private function uploadS3ImageMap($data, $path)
    {
        try {
            $arraySize = ["240", "300", "460", "700", "1040"];

            foreach ($arraySize as $size) {
                $imageName = $data['id'] . '/' . $size;
                Storage::disk('s3')->putFileAs(config('filesystems.disks.s3.path'), $path . 'resources/' . $data['id'], $imageName);
            }
            $urlFinal = Storage::disk('s3')->url(config('filesystems.disks.s3.path') . '/' . $data['id']);
            return $urlFinal;
        } catch (\Exception $exception) {
            throw new AppServiceException(__('messages.upload_file_error'), 400);
        }
    }

    private function convertBubleParams($data)
    {
        $converted_data = [];
        $converted_data["type"] = "bubble";
        if (array_key_exists('direction', $data)) {
            $converted_data["direction"] = $data['direction'];
        }
        if (array_key_exists('bubbleSize', $data)) {
            $converted_data["bubbleSize"] = $data['bubbleSize'];
        }
        if (array_key_exists('action', $data)) {
            if (!($data['action']['type'] == 'uri' and !array_key_exists('uri', $data['action']))) {
                $converted_data["action"] = $this->generateActionComponent($data['action']);
            } else {
//                $converted_data["action"] = $data['action'];
            }
        }

        if (!$data['header']['disable']) {
            $converted_data['header'] = $this->generateBoxComponent($data['header']);
        }

        if (!$data['hero']['disable']) {
            $converted_data['hero'] = $this->generateImageComponent($data['hero']);
        }

        if (!$data['body']['disable']) {
            $converted_data['body'] = $this->generateBoxComponent($data['body']);
        }

        if (!$data['footer']['disable']) {
            $converted_data['footer'] = $this->generateBoxComponent($data['footer']);
        }
        $converted_data['styles'] = $this->generateStyleBubble($data);
        return $converted_data;
    }

    private function generateStyleBubble($data)
    {
        $converted_data = ["header" => [], "hero" => [], "body" => [], "footer" => []];
        #header styles
        if (array_key_exists("backgroundColor.0", $data)) {
            $converted_data["header"]["backgroundColor"] = $data["backgroundColor.0"];
        }
        if (array_key_exists("separator.0", $data)) {
            $converted_data["header"]["separator"] = $data["separator.0"] == "true";
        }
        if (array_key_exists("separatorColor.0", $data)) {
            $converted_data["header"]["backgroundColor"] = $data["backgroundColor.0"];
        }
        #hero styles
        $converted_data["hero"]["separator"] = $data["separator.1"] == "true";
        if (array_key_exists("backgroundColor.1", $data)) {
            $converted_data["hero"]["backgroundColor"] = $data["backgroundColor.1"];
        }
        if (array_key_exists("separatorColor.1", $data)) {
            $converted_data["hero"]["separatorColor"] = $data["separatorColor.1"];
        }
        #body styles
        $converted_data["body"]["separator"] = $data["separator.2"] == "true";
        if (array_key_exists("backgroundColor.2", $data)) {
            $converted_data["body"]["backgroundColor"] = $data["backgroundColor.2"];
        }
        if (array_key_exists("separatorColor.2", $data)) {
            $converted_data["body"]["separatorColor"] = $data["separatorColor.2"];
        }
        #footer styles
        $converted_data["footer"]["separator"] = $data["separator.3"] == "true";
        if (array_key_exists("backgroundColor.3", $data)) {
            $converted_data["footer"]["backgroundColor"] = $data["backgroundColor.3"];
        }
        if (array_key_exists("separatorColor.3", $data)) {
            $converted_data["footer"]["separatorColor"] = $data["separatorColor.3"];
        }
        return $converted_data;
    }
    private function generateBoxComponent($data)
    {
        $converted_data = [];
        $converted_data['type'] = $data['type'];
        $converted_data['disable'] = $data['disable'];
        $component_properties = ['layout', 'flex', 'spacing', 'margin', 'action',
            'position', 'offsetTop', 'offsetBottom', 'offsetStart',
            'offsetEnd', 'paddingAll', 'paddingTop', 'paddingBottom',
            'paddingStart', 'paddingEnd', 'width', 'height', 'borderWidth',
            'backgroundColor', 'borderColor', 'cornerRadius'];
        $component_properties_values = $data['componentProps'];
        foreach ($component_properties_values as $key => $value) {
            if ($component_properties[$key] == 'action') {
                if (!($value['type'] == 'uri' and !array_key_exists('uri', $value))) {
                    $converted_data[$component_properties[$key]] = $this->generateActionComponent($value);
                }
            } elseif ($component_properties[$key] == 'flex' && $value) {
                $converted_data[$component_properties[$key]] = (int) $value;
            } else {
                if ($value && $value != 'none') {
                    $converted_data[$component_properties[$key]] = $value;
                }
            }
        }
        if (count($data['contents'])) {
            $converted_data['contents'] = [];
            $header_contents = $data['contents'];
            foreach ($header_contents as $content) {
                if (array_key_exists('componentProps', $content) && count($content['componentProps']) == 0) {
                    unset($content['componentProps']);
                }
                $converted_data['contents'][] = $this->generateComponent($content['type'], $content);
            }
        }
        return $converted_data;
    }
    private function generateComponent($type, $data)
    {
        switch ($type) {
            case 'button': {
                return $this->generateButtonComponent($data);
            }
            case 'filler': {
                return $this->generateFilerComponent($data);
            }
            case 'image': {
                return $this->generateImageComponent($data);
            }
            case 'separator': {
                return $this->generateSperatorComponent($data);
            }
            case 'text': {
                return $this->generateTextComponent($data);
            }
            case 'spacer': {
                return $this->generateSpacerComponent($data);
            }
            case 'icon': {
                return $this->generateIconComponent($data);
            }
            case 'box': {
                return $this->generateBoxComponent($data);
            }
            case 'span': {
                return $this->generateSpanComponent($data);
            }
            default: {
                return $data;
            }
        }
    }
    private function generateButtonComponent($data)
    {
        $converted_data = [];
        $converted_data['type'] = $data['type'];
        $converted_data['disable'] = $data['disable'];
        $component_properties = ['action', 'flex', 'margin', 'height', 'style', 'color',
            'gravity', 'position', 'offsetTop', 'offsetBottom',
            'offsetStart', 'offsetEnd'];
        $component_properties_values = $data['componentProps'];
        foreach ($component_properties_values as $key => $value) {
            if ($component_properties[$key] == 'action') {
                if (!($value['type'] == 'uri' and !array_key_exists('uri', $value))) {
                    $converted_data[$component_properties[$key]] = $this->generateActionComponent($value);
                }
            } elseif ($component_properties[$key] == 'flex' && $value) {
                $converted_data[$component_properties[$key]] = (int) $value;
            } else {
                if ($value && $value != 'none') {
                    $converted_data[$component_properties[$key]] = $value;
                }
            }
        }
        return $converted_data;
    }
    private function generateFilerComponent($data)
    {
        $converted_data = [];
        $converted_data['type'] = $data['type'];
        $converted_data['disable'] = $data['disable'];
        $component_properties = ['flex'];
        $component_properties_values = $data['componentProps'];
        foreach ($component_properties_values as $key => $value) {
            if ($component_properties[$key] == 'flex' && $value) {
                $converted_data[$component_properties[$key]] = (int) $value;
            } else {
                if ($value && $value != 'none') {
                    $converted_data[$component_properties[$key]] = $value;
                }
            }
        }
        return $converted_data;
    }
    private function generateSpanComponent($data)
    {
        $converted_data = [];
        $converted_data['type'] = $data['type'];
        $converted_data['disable'] = $data['disable'];
        $component_properties = ['text', 'size', 'color', 'weight', 'style', 'decoration'];
        $component_properties_values = $data['componentProps'];
        foreach ($component_properties_values as $key => $value) {
            if ($value && $value != 'none') {
                $converted_data[$component_properties[$key]] = $value;
            }
        }
        return $converted_data;
    }
    private function generateSperatorComponent($data)
    {
        $converted_data = [];
        $converted_data['type'] = $data['type'];
        $converted_data['disable'] = $data['disable'];
        $component_properties = ['margin', 'color'];
        $component_properties_values = $data['componentProps'];
        foreach ($component_properties_values as $key => $value) {
            if ($value && $value != 'none') {
                $converted_data[$component_properties[$key]] = $value;
            }
        }
        return $converted_data;
    }
    private function generateSpacerComponent($data)
    {
        $converted_data = [];
        $converted_data['type'] = $data['type'];
        $converted_data['disable'] = $data['disable'];
        $component_properties = ['size'];
        $component_properties_values = $data['componentProps'];
        foreach ($component_properties_values as $key => $value) {
            if ($value && $value != 'none') {
                $converted_data[$component_properties[$key]] = $value;
            }
        }
        return $converted_data;
    }
    private function generateIconComponent($data)
    {
        $converted_data = [];
        $converted_data['type'] = $data['type'];
        $converted_data['disable'] = $data['disable'];
        $component_properties = ['url', 'margin', 'size', 'aspectRatio', 'position',
            'offsetTop', 'offsetBottom', 'offsetStart', 'offsetEnd'];
        $component_properties_values = $data['componentProps'];
        foreach ($component_properties_values as $key => $value) {
            if ($value && $value != 'none') {
                $converted_data[$component_properties[$key]] = $value;
            }
        }
        return $converted_data;
    }
    private function generateImageComponent($data)
    {
        $converted_data = [];
        $converted_data['type'] = $data['type'];
        $converted_data['disable'] = $data['disable'];
        $component_properties = ['url', 'flex', 'size', 'align', 'gravity', 'aspectRatio',
            'aspectMode', 'margin', 'backgroundColor', 'action',
            'position', 'offsetTop', 'offsetBottom', 'offsetStart', 'offsetEnd'];
        $component_properties_values = $data['componentProps'];
        foreach ($component_properties_values as $key => $value) {
            if ($component_properties[$key] == 'action') {
                if (!($value['type'] == 'uri' and !array_key_exists('uri', $value))) {
                    $converted_data[$component_properties[$key]] = $this->generateActionComponent($value);
                }
            } elseif ($component_properties[$key] == 'flex' && $value) {
                $converted_data[$component_properties[$key]] = (int) $value;
            } else {
                if ($value && $value != 'none') {
                    $converted_data[$component_properties[$key]] = $value;
                }
            }
        }
        return $converted_data;
    }
    private function generateTextComponent($data)
    {
        $converted_data = [];
        $converted_data['type'] = $data['type'];
        $converted_data['disable'] = $data['disable'];
        $component_properties = ['text', 'flex', 'size', 'color', 'weight', 'align',
            'gravity', 'margin', 'wrap', 'action', 'style', 'decoration',
            'position', 'offsetTop', 'offsetBottom', 'offsetStart', 'offsetEnd'];
        $component_properties_values = $data['componentProps'];
        foreach ($component_properties_values as $key => $value) {
            if ($component_properties[$key] == 'action') {
                if (!($value['type'] == 'uri' and !array_key_exists('uri', $value))) {
                    $converted_data[$component_properties[$key]] = $this->generateActionComponent($value);
                }
            } elseif ($component_properties[$key] == 'flex' && $value) {
                $converted_data[$component_properties[$key]] = (int) $value;
            } else {
                if ($value && $value != 'none') {
                    $converted_data[$component_properties[$key]] = $value;
                }
            }
        }
        if (count($data['contents'])) {
            $converted_data['contents'] = [];
            $header_contents = $data['contents'];
            foreach ($header_contents as $content) {
                $converted_data['contents'][] = $this->generateComponent($content['type'], $content);
            }
        }
        return $converted_data;
    }
}
