<?php

namespace Modules\Scenarios\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\Scenarios\Http\Requests\API\Admin\ScenarioMessageStoreRequest;
use Modules\Scenarios\Http\Requests\API\Admin\ScenarioMessageUpdateRequest;
use Modules\Scenarios\Http\Resources\BaseDataResource;
use Modules\Scenarios\Http\Resources\ScenarioMessageResource;
use Modules\Scenarios\Models\ScenarioMessageModel;
use Modules\Scenarios\Services\_Exception\AppServiceException;
use Modules\Scenarios\Services\ScenarioMessageService;

class ScenarioMessageController extends \Modules\Scenarios\Http\Controllers\Controller
{
    use BaseEntryDataTrait;

    protected $entryService;
    protected $createRequestClass;
    protected $updateRequestClass;

    public function __construct(ScenarioMessageService $entryService)
    {
        $this->entryService = $entryService;
        $this->middleware('user_store_has_scenario')->only('getByScenarioId');
        $this->createRequestClass = ScenarioMessageStoreRequest::class;
        $this->updateRequestClass = ScenarioMessageUpdateRequest::class;
    }

    public function index(Request $request)
    {
        if ($request->get('scenarioId')) {
            $scenarioId = $request->get('scenarioId');
            $data = $this->entryService->getByScenarioId($scenarioId);
            $storeId = auth()->user()->store_id;
            $data->where('store_id', $storeId);
            $sorted = $data->sortBy(['nameLBD', 'asc']);
            $sorted->values()->all();
            return [
                'items' => ScenarioMessageResource::collection($sorted),
                'result' => 'OK'
            ];
        }
        if (in_array(auth(GUARD_API_ADMIN)->user()->role, [ROLE_ADMIN])) {
            $data = $this->entryService->all();
        } else {
            $storeId = auth()->user()->store_id;
            $data = $this->entryService->getByStoreId($storeId);
        }

        return [
            'items' => ScenarioMessageResource::collection($data),
            'result' => 'OK'
        ];
    }

    public function getByScenarioId(Request $request, $scenarioId)
    {
        $data = $this->entryService->getByScenarioId($scenarioId);
        return [
            'items' => ScenarioMessageResource::collection($data),
            'result' => 'OK'
        ];
    }

    public function importZipcodes(Request $request)
    {
        $arrayZipCode = [];

        if (($open = fopen($request->file->path(), "r")) !== FALSE) {

            while (($data = fgetcsv($open, 1000, ",")) !== FALSE) {
                if (isset($data[0])) {
                    $arrayZipCode[] = $data[0];
                } else {
                    throw new AppServiceException(__('messages.unknown_error'), SERVER_ERROR);
                }
            }

            fclose($open);
        }
        $filename = 'damage_report_zipcodes_'.$request->scenario_id.'_'.time().'.csv';
        $path = 'scenario-talks';
        $url = Storage::putFileAs($path, $request->file, $filename);
        $url = Storage::url($url);
        try {
            $this->entryService->importZipcodes($arrayZipCode, $request->scenario_id, $url);
            return response()->json([
                'item'=> [],
                'result' => "SUCCESS",
                'status_code' => 200
            ]);
        } catch (AppServiceException $e)
        {
            return response()->json([
                'error_msg' => $e->getMessage(),
            ], $e->getCode() ?: HTTP_VALIDATE_FAIL);
        }
    }

    public function getZipCode(Request $request)
    {
        $zipcode = $this->entryService->getZipCode($request->all());
        return [
            'zipcodes' => $zipcode,
            'result'=> 'Success'
        ];
    }

    public function deleteZipcodes(Request $request)
    {
        try {
            $this->entryService->deleteZipcodes($request->all());
            return response()->json([
                'item'=> [],
                'result' => "SUCCESS",
                'status_code' => 200
            ]);
        } catch (AppServiceException $e)
        {
            return response()->json([
                'error_msg' => $e->getMessage(),
            ], $e->getCode() ?: HTTP_VALIDATE_FAIL);
        }
    }

    public function exportZipcodesCSV(Request $request)
    {
        try {
            $url = $this->entryService->exportZipcodesCSV($request->all());
            return response()->json([
                'csv_url'=> $url,
                'result' => "SUCCESS",
                'exception' => "None",
                'status_code' => 200,
                'warning' => [],
            ]);
        } catch (AppServiceException $e)
        {
            return response()->json([
                'error_msg' => $e->getMessage(),
            ], $e->getCode() ?: HTTP_VALIDATE_FAIL);
        }
    }


    public function update($entryId, Request $request)
    {
        try {
            if (isset($this->saveRequestClass)) {
                $request = resolve($this->saveRequestClass);
            } else if (isset($this->updateRequestClass)) {
                $request = resolve($this->updateRequestClass);
            }
            if (auth()->check()) {
                $request->merge(['updated_by' => auth()->id()]);
            }
            $this->entryService->updateFromRequest($entryId, $request);

            return response()->json([]);
        } catch (AppServiceException $e)
        {
            return response()->json([
                'result' => 'ERROR',
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    public function store(Request $request)
    {
        if (isset($this->saveRequestClass)) {
            $request = resolve($this->saveRequestClass);
        } else if (isset($this->createRequestClass)) {
            $request = resolve($this->createRequestClass);
        }
        try {
            if (auth()->check()) {
                $request->merge(['created_by' => auth()->id()]);
            }
            $entry = $this->entryService->createFromRequest($request);

            if (isset($this->baseDataResource)) {
                $class = $this->baseDataResource;
                return new $class($entry);
            }
            return new BaseDataResource($entry);
        }
        catch (AppServiceException $e)
        {
            return response()->json([
                'result' => 'ERROR',
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    public function flowStartEvent(Request $request)
    {
        try {
            $scenario = $request->scenario;
            $data = explode('#', $scenario);
            $scenarioMessage = ScenarioMessageModel::where('scenario_id', $data[1])
                ->where('dataId', ScenarioMessageModel::TALK_FLOW_MAP[$request->name])->first();

            return response()->json([
                'error' => '',
                'event' => [
                    'data' => ScenarioMessageModel::TALK_FLOW_MAP[$request->name],
                    'message' => $scenarioMessage,
                    'type' => 'postback'
                ],
                'result' => 'SUCCESS'
            ]);
        }
        catch (\Exception $e)
        {
            return response()->json([
                'result' => 'ERROR',
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    public function simulateResponse(Request $request)
    {
        try {
            $scenario = $request->scenario;
            $data = explode('#', $scenario);
            if ($request->data && !in_array($request->lastMessageId, ['CATEGORY_DETAILS_PARK_CAROUSEL'])) {
                $scenarioMessage1 = ScenarioMessageModel::where('scenario_id', $data[1])
                    ->whereIn('dataId', ScenarioMessageModel::SIMULATE_RESPONSE_DATA[$request->lastMessageId][0])->get();
                $scenarioMessage2 = ScenarioMessageModel::where('scenario_id', $data[1])
                    ->whereIn('dataId', ScenarioMessageModel::SIMULATE_RESPONSE_DATA[$request->lastMessageId][1])->first();
            } else {
                $scenarioMessage1 = ScenarioMessageModel::where('scenario_id', $data[1])
                    ->whereIn('dataId', ScenarioMessageModel::SIMULATE_RESPONSE[$request->lastMessageId][0])->get();
                $scenarioMessage2 = ScenarioMessageModel::where('scenario_id', $data[1])
                    ->whereIn('dataId', ScenarioMessageModel::SIMULATE_RESPONSE[$request->lastMessageId][1])->first();
            }
            $response = [];
            if (count($scenarioMessage1)) {
                $response[] = $this->convertDataSimulate($request->lastMessageId, $scenarioMessage1, $scenario);
            }
            if (count($scenarioMessage2)) {
                $response[] = $scenarioMessage2;
            }

            return response()->json([
                'error' => '',
                'message' => $response,
                'result' => 'SUCCESS'
            ]);
        }
        catch (\Exception $e)
        {
            return response()->json([
                'result' => 'ERROR',
                'error_message' => $e->getMessage(),
            ]);
        }
    }
    public function convertDataSimulate($type, $data, $scenario)
    {
        if ($data) {
            $data = $data->toArray();
        }
        switch ($type) {
            case 'NORMAL_SHELTER_NOT_FOUND':
            case 'NORMAL_SHELTER_SEARCH':
                $data[] = [
                    "dataId" =>  "DUMMY_BOSAI_SHELTER_CAROUSEL",
                    "dataType" => "carouselFlex",
                    "nameLBD" => "避難所テンプレート",
                    "params" => [
                        "bubbleParam" => ["NORMAL_SHELTER_TEMPLATE"]
                    ],
                    "scenario" => $scenario,
                ];
                break;
            case 'BOSAI_SHELTER_SEARCH_CONFIRM_1_1':
            case 'BOSAI_SHELTER_SEARCH_CONFIRM_1_2':
            case 'BOSAI_SHELTER_SEARCH_CONFIRM_1_3':
            case 'BOSAI_SHELTER_SEARCH_CONFIRM_1_4':
            case 'BOSAI_SHELTER_SEARCH_CONFIRM_2_1':
            case 'BOSAI_SHELTER_SEARCH_CONFIRM_2_2':
            case 'BOSAI_SHELTER_SEARCH_CONFIRM_2_3':
            case 'BOSAI_SHELTER_SEARCH_CONFIRM_2_4':
            case 'BOSAI_SHELTER_SEARCH_CONFIRM_3_1':
            case 'BOSAI_SHELTER_SEARCH_CONFIRM_3_2':
            case 'BOSAI_SHELTER_SEARCH_CONFIRM_3_3':
            case 'BOSAI_SHELTER_SEARCH_CONFIRM_3_4':
            case 'BOSAI_SHELTER_SEARCH_CONFIRM_4_1':
            case 'BOSAI_SHELTER_SEARCH_CONFIRM_4_2':
            case 'BOSAI_SHELTER_SEARCH_CONFIRM_4_3':
            case 'BOSAI_SHELTER_SEARCH_CONFIRM_4_4':
            case 'BOSAI_SHELTER_NOT_FOUND':
            $data[] = [
                    "dataId" =>  "DUMMY_BOSAI_SHELTER_CAROUSEL",
                    "dataType" => "carouselFlex",
                    "nameLBD" => "避難所テンプレート",
                    "params" => [
                        "bubbleParam" => ["BOSAI_SHELTER_TEMPLATE"]
                    ],
                    "scenario" => $scenario,
                ];
                break;
            case 'RAIN_TYPHOON_IN_RISK_AREA':
            case 'RAIN_TYPHOON_OUTSIDE_RISK_AREA_1':
            case 'RAIN_TYPHOON_OUTSIDE_RISK_AREA_2':
            case 'RAIN_TYPHOON_SHELTER_NOT_FOUND':
                $data[] = [
                        "dataId" =>  "DUMMY_BOSAI_SHELTER_CAROUSEL",
                        "dataType" => "carouselFlex",
                        "nameLBD" => "避難所テンプレート",
                        "params" => [
                            "bubbleParam" => ["RAIN_TYPHOON_SHELTER_TEMPLATE"]
                        ],
                        "scenario" => $scenario,
                    ];
                break;
        case 'EARTHQUAKE_WHEREABOUTS_1_AREA_1':
        case 'EARTHQUAKE_WHEREABOUTS_1_AREA_2':
        case 'EARTHQUAKE_WHEREABOUTS_1_AREA_3':
        case 'EARTHQUAKE_WHEREABOUTS_1_AREA_4':
        case 'EARTHQUAKE_WHEREABOUTS_1_AREA_5':
        case 'EARTHQUAKE_WHEREABOUTS_1_AREA_6':
        case 'EARTHQUAKE_WHEREABOUTS_1_AREA_7':
        case 'EARTHQUAKE_WHEREABOUTS_2_AREA_1':
        case 'EARTHQUAKE_WHEREABOUTS_2_AREA_2':
        case 'EARTHQUAKE_WHEREABOUTS_2_AREA_3':
        case 'EARTHQUAKE_WHEREABOUTS_2_AREA_4':
        case 'EARTHQUAKE_WHEREABOUTS_2_AREA_5':
        case 'EARTHQUAKE_WHEREABOUTS_3_AREA_1':
        case 'EARTHQUAKE_WHEREABOUTS_3_AREA_2':
        case 'EARTHQUAKE_WHEREABOUTS_3_AREA_3':
        case 'EARTHQUAKE_SHELTER_NOT_FOUND':
                $data[] = [
                        "dataId" =>  "DUMMY_BOSAI_SHELTER_CAROUSEL",
                        "dataType" => "carouselFlex",
                        "nameLBD" => "避難所テンプレート",
                        "params" => [
                            "bubbleParam" => ["EARTHQUAKE_SHELTER_TEMPLATE"]
                        ],
                        "scenario" => $scenario,
                    ];
                break;
        }
        return $data;
    }
}
