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

            'quarters' => 'required|array|min:1',
            'quarters.*.quarter_number' => 'required|integer|min:1|max:4',
            'quarters.*.quarter_name' => 'required|string|max:255',
            'quarters.*.start_date' => 'nullable|date',
            'quarters.*.end_date' => 'nullable|date',
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

        $schoolYearResponse = Http::withHeaders([
            'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
            'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
            'Content-Type' => 'application/json',
            'Prefer' => 'return=representation'
        ])->post(env('SUPABASE_URL') . '/rest/v1/school_year', [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        if (!$schoolYearResponse->successful()) {
            return response()->json([
                'message' => 'Failed to create school year.',
                'error' => $schoolYearResponse->body()
            ], 500);
        }

        $newSchoolYear = $schoolYearResponse->json()[0] ?? null;

        if (!$newSchoolYear || empty($newSchoolYear['year_id'])) {
            return response()->json([
                'message' => 'School year created but year_id was not returned.'
            ], 500);
        }

        $quarterPayload = collect($request->quarters)->map(function ($quarter) use ($newSchoolYear) {
            return [
                'year_id' => $newSchoolYear['year_id'],
                'quarter_number' => $quarter['quarter_number'],
                'quarter_name' => $quarter['quarter_name'],
                'start_date' => $quarter['start_date'] ?: null,
                'end_date' => $quarter['end_date'] ?: null,
            ];
        })->values()->all();

        $quarterResponse = Http::withHeaders([
            'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
            'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
            'Content-Type' => 'application/json',
            'Prefer' => 'return=representation'
        ])->post(env('SUPABASE_URL') . '/rest/v1/quarter', $quarterPayload);

        if (!$quarterResponse->successful()) {
            Http::withHeaders([
                'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
                'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
                'Prefer' => 'return=minimal',
                'Accept' => 'application/json',
            ])->delete(env('SUPABASE_URL') . "/rest/v1/school_year?year_id=eq.{$newSchoolYear['year_id']}");

            return response()->json([
                'message' => 'School year was created but failed to create quarters.',
                'error' => $quarterResponse->body()
            ], 500);
        }

        $newSchoolYear['quarter'] = $quarterResponse->json();

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

            'quarters' => 'required|array|min:1',
            'quarters.*.quarter_id' => 'nullable|uuid',
            'quarters.*.quarter_number' => 'required|integer|min:1',
            'quarters.*.quarter_name' => 'required|string|max:255',
            'quarters.*.start_date' => 'nullable|date',
            'quarters.*.end_date' => 'nullable|date',
        ]);

        $schoolYearResponse = Http::withHeaders([
            'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
            'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
            'Content-Type' => 'application/json',
            'Prefer' => 'return=representation'
        ])->patch(env('SUPABASE_URL') . "/rest/v1/school_year?year_id=eq.{$id}", [
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ]);

        if (!$schoolYearResponse->successful()) {
            return response()->json([
                'message' => 'Failed to update school year.',
                'errors' => $schoolYearResponse->json()
            ], 500);
        }

        foreach ($request->quarters as $quarter) {
            $payload = [
                'quarter_number' => $quarter['quarter_number'],
                'quarter_name' => $quarter['quarter_name'],
                'start_date' => $quarter['start_date'] ?: null,
                'end_date' => $quarter['end_date'] ?: null,
            ];

            if (!empty($quarter['quarter_id'])) {
                $quarterUpdateResponse = Http::withHeaders([
                    'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
                    'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
                    'Content-Type' => 'application/json',
                    'Prefer' => 'return=representation'
                ])->patch(
                    env('SUPABASE_URL') . "/rest/v1/quarter?quarter_id=eq.{$quarter['quarter_id']}",
                    $payload
                );

                if (!$quarterUpdateResponse->successful()) {
                    return response()->json([
                        'message' => 'Failed to update one or more quarters.',
                        'error' => $quarterUpdateResponse->body()
                    ], 500);
                }
            } else {
                $quarterCreateResponse = Http::withHeaders([
                    'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
                    'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
                    'Content-Type' => 'application/json',
                    'Prefer' => 'return=representation'
                ])->post(env('SUPABASE_URL') . '/rest/v1/quarter', [
                    'year_id' => $id,
                    ...$payload
                ]);

                if (!$quarterCreateResponse->successful()) {
                    return response()->json([
                        'message' => 'Failed to create one or more missing quarters.',
                        'error' => $quarterCreateResponse->body()
                    ], 500);
                }
            }
        }

        $freshResponse = Http::withHeaders([
            'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
            'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
            'Accept' => 'application/json',
        ])->get(
            env('SUPABASE_URL') . "/rest/v1/school_year?year_id=eq.{$id}&select=year_id,start_date,end_date,created_at,quarter(quarter_id,quarter_number,quarter_name,start_date,end_date)"
        );

        $updatedSchoolYear = $freshResponse->successful()
            ? ($freshResponse->json()[0] ?? null)
            : null;

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
