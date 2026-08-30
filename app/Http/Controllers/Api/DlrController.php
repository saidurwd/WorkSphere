<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DlrSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class DlrController extends Controller
{
    public function __construct(private DlrSyncService $dlrSyncService) {}

    public function fetch(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date' => ['required', 'string', 'max:20'],
            'companycode' => ['required', 'string', 'max:20'],
            'estatecode' => ['required', 'string', 'max:20'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $validated = $validator->validated();

            $records = $this->dlrSyncService->fetchData(
                $validated['date'],
                $validated['companycode'],
                $validated['estatecode']
            );

            Session::put('dlr_records', $records);
            Session::put('dlr_filters', $validated);

            return response()->json([
                'success' => true,
                'message' => 'Data fetched successfully',
                'data' => $records,
                'count' => count($records),
            ]);
        } catch (\Throwable $e) {
            Log::error('DLR fetch error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch data: '.$e->getMessage(),
                'debug' => config('app.debug') ? $e->getTraceAsString() : null,
            ], 500);
        }
    }
}
