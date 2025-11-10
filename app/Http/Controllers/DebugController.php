<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DebugController extends Controller
{
    public function testContestCreation(): JsonResponse
    {
        try {
            // Test diretto senza eventi
            $contest = \App\Models\Contest::create([
                'title' => 'Debug Contest',
                'description' => 'Test creation',
                'start_date' => now(),
                'end_date' => now()->addDays(30),
                'status' => 'active',
                'max_entries' => 100
            ]);

            return response()->json([
                'success' => true,
                'contest' => $contest,
                'message' => 'Contest created successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
}
