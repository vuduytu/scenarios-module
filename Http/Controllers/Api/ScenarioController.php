<?php

namespace Modules\Scenarios\Http\Controllers\Api;

use Modules\Scenarios\Http\Requests\API\Admin\ScenarioStoreRequest;
use Modules\Scenarios\Http\Requests\API\Admin\ScenarioUpdateRequest;
use Illuminate\Http\Request;
use Modules\Scenarios\Http\Controllers\Controller;
use Modules\Scenarios\Http\Resources\BaseDataCollection;
use Modules\Scenarios\Http\Resources\ScenarioSettingResource;
use Modules\Scenarios\Models\Scenario;
use Modules\Scenarios\Services\_Exception\AppServiceException;
use Modules\Scenarios\Services\ScenarioService;

class ScenarioController extends Controller
{
    use BaseEntryDataTrait;

    protected $entryService;
    protected $createRequestClass;
    protected $updateRequestClass;

    public function __construct(ScenarioService $entryService)
    {
        $this->entryService = $entryService;
        $this->createRequestClass = ScenarioStoreRequest::class;
        $this->updateRequestClass = ScenarioUpdateRequest::class;
    }

    public function index(Request $request)
    {
        $entries = $this->entryService->search($request->all());
        $data = [];
        foreach ($entries as $key => $value) {
            $value->specialTalks = is_array($value->specialTalks) ? $value->specialTalks : [];
            $data[$value->id] = $value;
        }

        return new BaseDataCollection($entries);
    }

    public function getSetting(Request $request)
    {
        $setting = $this->entryService->checkSetting();
        return response()->json([
            'result' => 'OK',
            'item' => $setting ? new ScenarioSettingResource($setting) : null
        ]);
    }

    public function changeActive(Request $request)
    {
        $this->entryService->changeActive($request->get('id'), $request->get('type'));
        $setting = $this->entryService->checkSetting();
        return response()->json([
            'result' => 'OK',
            'item' => $setting ? new ScenarioSettingResource($setting) : null
        ]);
    }

    public function deleteScenario(Request $request)
    {
        try {
            $ids = $request->get('ids');
            $ids = explode(',', $ids);
            foreach ($ids as $entryId) {
                $this->entryService->delete($entryId);
            }
            return response()->json([]);
        } catch (AppServiceException $e)
        {
            return response()->json([
                'result' => 'ERROR',
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    public function listAll(Request $request)
    {
        $entries = $this->entryService->search($request->all());
        $data = [];
        foreach ($entries as $key => $value) {
            $value->specialTalks = is_array($value->specialTalks) ? $value->specialTalks : [];
            $data[$value->id] = $value;
        }

        $setting = $this->entryService->checkSetting();
        $mapping = json_decode($setting->envMapping, true);
        $env = [
            'production' => @$mapping['production'],
            'sandbox' => @$mapping['sandbox']
        ];
        return response()->json([
            'result' => 'OK',
            'items' => [
                [
                    "activeScenarioId" => $setting->name,
                    "scenarioId"=> $setting->id,
                    "envMapping"=> $env
                ],
                [
                    "versions" => $data,
                    "scenarioId" => $setting->id,
                ]
            ]
        ]);
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
            $setting = $this->entryService->checkSetting();
            return response()->json([
                'result' => 'OK',
                'item' => $setting ? new ScenarioSettingResource($setting) : null
            ]);
        }
        catch (AppServiceException $e)
        {
            return response()->json([
                'error_msg' => $e->getMessage(),
            ], $e->getCode() ?: HTTP_VALIDATE_FAIL);
        }
    }

    public function updateSpecialTalk(Request $request)
    {
        $this->entryService->updateSpecialTalk($request->get('scenarioId'), $request->get('value'));
        $setting = $this->entryService->checkSetting();
        return response()->json([
            'result' => 'OK',
            'item' => $setting ? new ScenarioSettingResource($setting) : null
        ]);
    }


    public function update($entryId, Request $request)
    {
        try {

            $entry = Scenario::find($entryId);
            if (!$entry) {
                return response()->json([
                    'result' => 'ERROR',
                    'error_message' => 'シナリオが見つかりません。',
                ]);
            }

            if ($entry->displayVersionName !== $request->get('originalVersionName')) {
                return response()->json([
                    'result' => 'ERROR',
                    'error_message' => '予期しないエラーが発生いたしました。',
                ]);
            }

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

    public function exportLBD(Request $request)
    {;
        try {
            $path = $this->entryService->exportLBD($request->scenario);
            if (!$path) {
                return response()->json([
                    'result' => "ERROR",
                    'status_code' => 500,
                    'exception' => __('messages.unknown_error')
                ]);
            }
            return response()->json([
                'url'=> $path,
                'result' => "SUCCESS",
                'status_code' => 200,
                'exception' => ""
            ]);
        } catch (AppServiceException $e)
        {
            return response()->json([
                'result' => "ERROR",
                'status_code' => 500,
                'exception' => __('messages.unknown_error')
            ]);
        }
    }

    public function importLBD(Request $request)
    {
        try {
            $data = $this->entryService->importLBD($request->all());
            if ($data['success']) {
                $scenario = $data['data'];
                return response()->json([
                    'result' => "SUCCESS",
                    'status_code' => 200,
                    'exception' => "",
                    'scenario' => $scenario,
                ]);
            } else {
                return response()->json([
                    'result' => "ERROR",
                    'status_code' => 500,
                    'exception' => $data['message']
                ]);
            }
        } catch (AppServiceException $e)
        {
            return response()->json([
                'result' => "ERROR",
                'status_code' => 500,
                'exception' => __('messages.unknown_error')
            ]);
        }
    }
}
