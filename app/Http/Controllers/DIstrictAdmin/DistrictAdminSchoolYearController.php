<?php

namespace App\Http\Controllers\DistrictAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class DistrictAdminSchoolYearController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $checkResponse = Http::withHeaders([
            'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
            'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
            'Accept' => 'application/json',
        ])->get(env('SUPABASE_URL') . '/rest/v1/school_year', [
            'start_date' => 'eq.' . $startDate,
            'end_date' => 'eq.' . $endDate,
            'select' => 'year_id'
        ]);

        if (!$checkResponse->successful()) {
            return response()->json([
                'message' => 'Failed to validate school year uniqueness.',
                'error' => $checkResponse->body()
            ], 500);
        }

        if (!empty($checkResponse->json())) {
            return response()->json([
                'message' => 'This school year already exists.',
                'errors' => [
                    'start_date' => ['This school year already exists.']
                ]
            ], 422);
        }

        $response = Http::withHeaders([
            'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
            'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
            'Content-Type' => 'application/json',
            'Prefer' => 'return=representation'
        ])->post(env('SUPABASE_URL') . '/rest/v1/school_year', [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        if (!$response->successful()) {
            return response()->json([
                'message' => 'Failed to create school year.',
                'error' => $response->body()
            ], 500);
        }

        $newSchoolYear = $response->json()[0] ?? null;

        return response()->json([
            'message' => 'School year created successfully.',
            'schoolYear' => $newSchoolYear
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        $response = Http::withHeaders([
            'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
            'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
            'Content-Type' => 'application/json',
            'Prefer' => 'return=representation'
        ])->patch(env('SUPABASE_URL') . "/rest/v1/school_year?year_id=eq.{$id}", [
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ]);

        if (!$response->successful()) {
            return response()->json([
                'message' => 'Failed to update school year.',
                'errors' => $response->json()
            ], 500);
        }

        $updatedSchoolYear = $response->json()[0] ?? [
            'year_id' => $id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ];

        return response()->json([
            'message' => 'School year updated successfully.',
            'schoolYear' => $updatedSchoolYear
        ]);
    }

    public function destroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array'
        ]);

        $ids = $request->ids;
        $failedDeletes = [];

        foreach ($ids as $id) {
            $response = Http::withHeaders([
                'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
                'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
                'Prefer' => 'return=minimal',
                'Accept' => 'application/json',
            ])->delete(env('SUPABASE_URL') . "/rest/v1/school_year?year_id=eq.{$id}");

            if (!$response->successful()) {
                $failedDeletes[] = [
                    'id' => $id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ];
            }
        }

        if (!empty($failedDeletes)) {
            return response()->json([
                'success' => false,
                'message' => 'Some school years could not be deleted.',
                'errors' => $failedDeletes
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Selected school year(s) deleted successfully.',
            'deleted_ids' => $ids
        ]);
    }
}
