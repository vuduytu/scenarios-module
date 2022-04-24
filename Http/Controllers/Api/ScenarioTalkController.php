<?php

namespace Modules\Scenarios\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\Scenarios\Http\Requests\StoreScenarioTalkRequest;
use Modules\Scenarios\Http\Resources\ScenarioTalkCollection;
use Modules\Scenarios\Services\_Exception\AppServiceException;
use Modules\Scenarios\Services\ScenarioTalkService;

class ScenarioTalkController extends \Modules\Scenarios\Http\Controllers\Controller
{
    use BaseEntryDataTrait;
    protected $entryService;

    public function __construct(ScenarioTalkService $entryService)
    {
        $this->entryService = $entryService;
        $this->baseDataCollection = ScenarioTalkCollection::class;
    }

    public function destroy($entryId, Request $request)
    {
        try {
            $this->entryService->deleteTalk($entryId, $request->ids);
            return response()->json([]);
        } catch (AppServiceException $e)
        {
            return response()->json([
                'error_msg' => $e->getMessage(),
            ], $e->getCode() ?: HTTP_VALIDATE_FAIL);
        }
    }

    public function addBosaiEarthquakeFlow(Request $request)
    {
        try {
            $this->entryService->addBosaiFlow($request->all());
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
    public function addBosaiRainTyphoonFlow(Request $request)
    {
        try {
            $this->entryService->addBosaiFlow($request->all(), 'scenario_rain_typhoon.json');
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
    public function addBosaiSearchFlow(Request $request)
    {
        try {
            $this->entryService->addBosaiFlow($request->all(), 'scenario_search.json');
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
    public function addBosaiFlow(Request $request)
    {
        try {
            $this->entryService->addBosaiFlow($request->all(), 'scenario.json');
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
    public function addDamageReport(Request $request)
    {
        try {
            $this->entryService->addBosaiFlow($request->all(), 'add_damage_scenario.json');
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

    public function importTrashSpreadsheet(Request $request)
    {
        $arrayTrashData = [];
        try {
            if (($open = fopen($request->file->path(), "r")) !== FALSE) {

                while (($data = fgetcsv($open, 1000, ",")) !== FALSE) {
                    $arrayTrashData[] = $data;
                }

                fclose($open);
            }
        } catch (\Exception $exception) {
            return response()->json([
                'result' => "FAILED",
                'error_msg' => "提供されたCSVファイルのデコード中にエラーが発生しました。サポートされているエンコード形式は、SHIFT-JISおよびUTF-8です。",
            ]);
        }

        $filename = 'csv_trash_'.$request->scenario_id.'_'.time().'.csv';
        $path = 'scenario-talks';
        $path = Storage::putFileAs($path, $request->file, $filename);
        $path = Storage::url($path);
        try {
            $data = $this->entryService->addTrashData($arrayTrashData, $request->scenario_id, $path);
            if ($data['success']) {
                return response()->json([
                    'item'=> [],
                    'result' => "SUCCESS",
                    'status_code' => 200
                ]);
            } else {
                return response()->json([
                    'item'=> [],
                    'result' => "FAILED",
                    'error_msg' => $data['messages']
                ]);
            }

        } catch (AppServiceException $e)
        {
            return response()->json([
                'result' => "FAILED",
                'error_msg' => __('messages.unknown_error')
            ]);
        }
    }

    public function updateTalkName(Request $request)
    {
        try {
            $this->entryService->updateTalkName($request->all());
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
}
