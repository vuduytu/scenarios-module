<?php

namespace Modules\Scenarios\Http\Controllers\Api;

use Modules\Scenarios\Http\Resources\BaseDataCollection;
use Modules\Scenarios\Http\Resources\BaseDataResource;
use Modules\Scenarios\Http\Resources\BaseDataSelectCollection;
use Modules\Scenarios\Services\_Exception\AppServiceException;
use Illuminate\Http\Request;

trait BaseEntryDataTrait
{
    // baseDataCollection => class name list data
    // baseDataSelectCollection => class name list data select
    // searchRequestClass => class name to validate INDEX request.
    // showRequestClass => class name to validate SHOW request.
    // saveRequestClass => class name to validate STORE and UPDATE request.
    // storeRequestClass => class name to validate STORE request.
    // updateRequestClass => class name to validate UPDATE request.
    // destroyRequestClass => class name to validate DESTROY request.

    public function index(Request $request)
    {
        if (isset($this->searchRequestClass)) {
            $request = resolve($this->searchRequestClass);
        }
        $entries = $this->entryService->search();
        if (isset($this->baseDataCollection)) {
            $class = $this->baseDataCollection;
            return new $class($entries);
        }
        return new BaseDataCollection($entries);
    }

    public function listSelect(Request $request)
    {
        if (isset($this->searchRequestClass)) {
            $request = resolve($this->searchRequestClass);
        }
        $entries = $this->entryService->listSelect();
        if (isset($this->baseDataSelectCollection)) {
            $class = $this->baseDataSelectCollection;
            return new $class($entries);
        }
        return new BaseDataSelectCollection($entries);
    }

    public function destroy($entryId, Request $request)
    {
        try {
            if (isset($this->destroyRequestClass)) {
                $request = resolve($this->destroyRequestClass);
            }
            $this->entryService->delete($entryId);
            return response()->json([]);
        } catch (AppServiceException $e)
        {
            return response()->json([
                'result' => 'ERROR',
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    public function show($id)
    {
        if (isset($this->showRequestClass)) {
            $request = resolve($this->showRequestClass);
        }
        try {
            $entry = $this->entryService->find($id);
            if (isset($this->baseDataResource)) {
                $class = $this->baseDataResource;
                return new $class($entry);
            }
            return response()->json([
                'data' => new BaseDataResource($entry)
            ]);
        }
        catch (AppServiceException $e)
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
}
