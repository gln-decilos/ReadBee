<?php

namespace App\Http\Controllers\DistrictAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class DistrictAdminMunicipalityController extends Controller
{
    public function store(Request $request)
{
    $activeDesignation = session('active_designation');
    $districtId = $activeDesignation['district_id'] ?? null;

    if (! $districtId) {
        return response()->json([
            'message' => 'No district assigned to your account.'
        ], 403);
    }

    $request->validate([
        'municipal_name' => 'required|string|max:255',
        'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
    ]);

    $municipalName = trim($request->municipal_name);

    $checkResponse = Http::withHeaders([
        'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
        'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
        'Accept' => 'application/json',
    ])->get(env('SUPABASE_URL') . '/rest/v1/municipalities', [
        'municipal_name' => 'eq.' . $municipalName,
        'district_id' => 'eq.' . $districtId,
        'select' => 'municipality_id'
    ]);

    if (! $checkResponse->successful()) {
        return response()->json([
            'message' => 'Failed to validate municipality uniqueness.',
            'error' => $checkResponse->body()
        ], 500);
    }

    $existing = $checkResponse->json();

    if (!empty($existing)) {
        return response()->json([
            'message' => 'This municipality already exists in your district.',
            'errors' => [
                'municipal_name' => ['This municipality already exists in your district.']
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
        'district_id' => $districtId,
        'logo' => $logoBase64,
    ]);

    if (! $response->successful()) {
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
    $activeDesignation = session('active_designation');
    $districtId = $activeDesignation['district_id'] ?? null;

    if (! $districtId) {
        return response()->json([
            'success' => false,
            'message' => 'No district assigned to your account.'
        ], 403);
    }

    $request->validate([
        'ids' => 'required|array'
    ]);

    $ids = $request->ids;

    foreach ($ids as $id) {
        $municipalityCheck = Http::withHeaders([
            'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
            'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
            'Accept' => 'application/json',
        ])->get(env('SUPABASE_URL') . '/rest/v1/municipalities', [
            'municipality_id' => 'eq.' . $id,
            'district_id' => 'eq.' . $districtId,
            'select' => 'municipality_id'
        ]);

        if (empty($municipalityCheck->json())) {
            return response()->json([
                'success' => false,
                'message' => 'One or more municipalities do not belong to your district.'
            ], 403);
        }

        $schoolsResponse = Http::withHeaders([
            'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
            'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
            'Accept' => 'application/json',
        ])->get(env('SUPABASE_URL') . '/rest/v1/schools', [
            'municipality_id' => 'eq.' . $id,
            'select' => 'school_id'
        ]);

        if (!empty($schoolsResponse->json())) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete municipality because it is assigned to one or more schools.'
            ], 422);
        }
    }

    foreach ($ids as $id) {
        Http::withHeaders([
            'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
            'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
            'Prefer' => 'return=minimal',
            'Accept' => 'application/json',
        ])->delete(
            env('SUPABASE_URL') . "/rest/v1/municipalities?municipality_id=eq.{$id}&district_id=eq.{$districtId}"
        );
    }

    return response()->json([
        'success' => true,
        'message' => 'Selected municipalities deleted successfully.',
        'deleted_ids' => $ids
    ]);
}
    public function update(Request $request, $id)
{
    $activeDesignation = session('active_designation');
    $districtId = $activeDesignation['district_id'] ?? null;

    if (! $districtId) {
        return response()->json([
            'message' => 'No district assigned to your account.'
        ], 403);
    }

    $request->validate([
        'municipal_name' => 'required|string|max:255',
        'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
    ]);

    $existingResponse = Http::withHeaders([
        'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
        'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
        'Accept' => 'application/json',
    ])->get(env('SUPABASE_URL') . '/rest/v1/municipalities', [
        'municipality_id' => 'eq.' . $id,
        'district_id' => 'eq.' . $districtId,
        'select' => 'municipality_id,logo'
    ]);

    $existingMunicipality = $existingResponse->json()[0] ?? null;

    if (! $existingMunicipality) {
        return response()->json([
            'message' => 'Municipality not found in your district.'
        ], 404);
    }

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
    ])->patch(env('SUPABASE_URL') . "/rest/v1/municipalities?municipality_id=eq.{$id}&district_id=eq.{$districtId}", [
        'municipal_name' => $request->municipal_name,
        'district_id' => $districtId,
        'logo' => $logoBase64,
    ]);

    if (! $response->successful()) {
        return response()->json([
            'message' => 'Failed to update municipality.',
            'errors' => $response->json()
        ], 500);
    }

    $updatedMunicipality = $response->json()[0] ?? [
        'municipality_id' => $id,
        'municipal_name' => $request->municipal_name,
        'district_id' => $districtId,
        'logo' => $logoBase64,
    ];

    return response()->json([
        'message' => 'Municipality updated successfully.',
        'municipality' => $updatedMunicipality
    ]);
}
}
