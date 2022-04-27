<?php

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

use Illuminate\Support\Facades\Route;

//Customer
Route::group([
    'namespace' => 'Api',
    'prefix' => 'admin',
    'middleware' => 'app_jwt_auth'
], function () {
    Route::get('scenarios/getSetting', 'ScenarioController@getSetting');
    Route::get('scenarios/listAll', 'ScenarioController@listAll');
    Route::post('scenarios/deleteScenario', 'ScenarioController@deleteScenario');
    Route::post('scenarios/changeActive', 'ScenarioController@changeActive');
    Route::post('scenarios/update-special-talks', 'ScenarioController@updateSpecialTalk');
    Route::post('scenario/api/exportLBD', 'ScenarioController@exportLBD');
    Route::post('scenario/importLBD', 'ScenarioController@importLBD');
    Route::resource('scenarios', 'ScenarioController');
    Route::resource('scenario_settings', 'ScenarioSettingController');
    Route::post('scenario-talks/addBosaiEarthquakeFlow', 'ScenarioTalkController@addBosaiEarthquakeFlow');
    Route::post('scenario-talks/addBosaiRainTyphoonFlow', 'ScenarioTalkController@addBosaiRainTyphoonFlow');
    Route::post('scenario-talks/addBosaiSearchFlow', 'ScenarioTalkController@addBosaiSearchFlow');
    Route::post('scenario-talks/addBosaiFlow', 'ScenarioTalkController@addBosaiFlow');
    Route::post('scenario-talks/addDamageReport', 'ScenarioTalkController@addDamageReport');
    Route::post('scenario-talks/importTrashSpreadsheet', 'ScenarioTalkController@importTrashSpreadsheet');
    Route::post('scenario-talks/updateTalkName', 'ScenarioTalkController@updateTalkName');
    Route::resource('scenario-talks', 'ScenarioTalkController');
    Route::get('scenario-messages/scenario/{scenarioId}', 'ScenarioMessageController@getByScenarioId');
    Route::post('scenario-messages/importZipcodes', 'ScenarioMessageController@importZipcodes');
    Route::resource('scenario-messages', 'ScenarioMessageController');
    Route::post('/get-zipcode', 'ScenarioMessageController@getZipCode');
    Route::post('/deleteZipcodes', 'ScenarioMessageController@deleteZipcodes');
    Route::post('/exportZipcodesCSV', 'ScenarioMessageController@exportZipcodesCSV');
    Route::post('/scenario-textmap/save-text-mapping', 'ScenarioTextMappingController@saveTextMapping');
    Route::put('/scenario-text-mapping/{scenarioId}', 'ScenarioTextMappingController@update');
    Route::get('/scenario-textmap/data-type/{scenario_id}', 'ScenarioTextMappingController@getDataType');
    Route::get('/scenario-textmap/{scenario_id}', 'ScenarioTextMappingController@getAllTextMappingByScenarioId');
    Route::post('/scenario-messages/flowStartEvent', 'ScenarioMessageController@flowStartEvent');
    Route::post('/scenario-messages/simulateResponse', 'ScenarioMessageController@simulateResponse');
});
