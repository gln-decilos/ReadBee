<?php

namespace App\Http\Controllers\DistrictAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class DistrictAdminMunicipalityController extends Controller
{
    public function store(Request $request)
{
    $request->validate([
        'municipal_name' => 'required|string|max:255',
        'district_id' => 'required|integer',
        'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
    ]);

    $municipalName = trim($request->municipal_name);

    $checkResponse = Http::withHeaders([
        'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
        'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
        'Accept' => 'application/json',
    ])->get(env('SUPABASE_URL') . '/rest/v1/municipalities', [
        'municipal_name' => 'eq.' . $municipalName,
        'district_id' => 'eq.' . $request->district_id,
        'select' => 'municipality_id'
    ]);

    if (!$checkResponse->successful()) {
        return response()->json([
            'message' => 'Failed to validate municipality uniqueness.',
            'error' => $checkResponse->body()
        ], 500);
    }

    $existing = $checkResponse->json();

    if (!empty($existing)) {
        return response()->json([
            'message' => 'This municipality already exists in the selected district.',
            'errors' => [
                'municipal_name' => ['This municipality already exists in the selected district.']
            ]
        ], 422);
    }

    $logoBase64 = null;

    if ($request->hasFile('logo')) {
        $file = $request->file('logo');
        $mime = $file->getMimeType();
        $contents = file_get_contents($file->getRealPath());
        $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode($contents);
    }

    $response = Http::withHeaders([
        'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
        'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
        'Content-Type' => 'application/json',
        'Prefer' => 'return=representation'
    ])->post(env('SUPABASE_URL') . '/rest/v1/municipalities', [
        'municipal_name' => $municipalName,
        'district_id' => $request->district_id,
        'logo' => $logoBase64,
    ]);

    if (!$response->successful()) {
        return response()->json([
            'message' => 'Failed to create municipality.',
            'error' => $response->body()
        ], 500);
    }

    $newMunicipality = $response->json()[0] ?? null;

    return response()->json([
        'message' => 'Municipality created successfully.',
        'municipality' => $newMunicipality
    ]);
}

   public function destroy(Request $request)
{
    $request->validate([
        'ids' => 'required|array'
    ]);

    $ids = $request->ids;

    foreach ($ids as $id) {
        $schoolsResponse = Http::withHeaders([
            'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
            'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
            'Accept' => 'application/json',
        ])->get(env('SUPABASE_URL') . '/rest/v1/schools', [
            'municipality_id' => 'eq.' . $id,
            'select' => 'school_id'
        ]);

        if (!$schoolsResponse->successful()) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to validate municipality before delete.'
            ], 500);
        }

        if (!empty($schoolsResponse->json())) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete municipality because it is assigned to one or more schools.'
            ], 422);
        }
    }

    $failedDeletes = [];

    foreach ($ids as $id) {
        $response = Http::withHeaders([
            'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
            'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
            'Prefer' => 'return=minimal',
            'Accept' => 'application/json',
        ])->delete(env('SUPABASE_URL') . "/rest/v1/municipalities?municipality_id=eq.{$id}");

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
            'message' => 'Some municipalities could not be deleted.',
            'errors' => $failedDeletes
        ], 500);
    }

    return response()->json([
        'success' => true,
        'message' => 'Selected municipalities deleted successfully.',
        'deleted_ids' => $ids
    ]);
}
    public function update(Request $request, $id)
{
    $request->validate([
        'municipal_name' => 'required|string|max:255',
        'district_id' => 'required|integer',
        'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
    ]);

    $existingResponse = Http::withHeaders([
        'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
        'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
        'Accept' => 'application/json',
    ])->get(env('SUPABASE_URL') . '/rest/v1/municipalities', [
        'municipality_id' => 'eq.' . $id,
        'select' => 'logo'
    ]);

    $existingMunicipality = $existingResponse->json()[0] ?? null;
    $logoBase64 = $existingMunicipality['logo'] ?? null;

    if ($request->hasFile('logo')) {
        $file = $request->file('logo');
        $mime = $file->getMimeType();
        $contents = file_get_contents($file->getRealPath());
        $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode($contents);
    }

    $response = Http::withHeaders([
        'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
        'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
        'Content-Type' => 'application/json',
        'Prefer' => 'return=representation'
    ])->patch(env('SUPABASE_URL') . "/rest/v1/municipalities?municipality_id=eq.{$id}", [
        'municipal_name' => $request->municipal_name,
        'district_id' => $request->district_id,
        'logo' => $logoBase64,
    ]);

    if (!$response->successful()) {
        return response()->json([
            'message' => 'Failed to update municipality.',
            'errors' => $response->json()
        ], 500);
    }

    $updatedMunicipality = $response->json()[0] ?? [
        'municipality_id' => $id,
        'municipal_name' => $request->municipal_name,
        'district_id' => $request->district_id,
        'logo' => $logoBase64,
    ];

    return response()->json([
        'message' => 'Municipality updated successfully.',
        'municipality' => $updatedMunicipality
    ]);
}
}
