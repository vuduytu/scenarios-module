<?php

namespace Modules\Scenarios\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\Scenarios\Http\Requests\API\Admin\ScenarioMessageStoreRequest;
use Modules\Scenarios\Http\Requests\API\Admin\ScenarioMessageUpdateRequest;
use Modules\Scenarios\Http\Resources\BaseDataResource;
use Modules\Scenarios\Http\Resources\ScenarioMessageResource;
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
}
