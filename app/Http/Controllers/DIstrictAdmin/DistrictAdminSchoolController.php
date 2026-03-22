<?php

namespace App\Http\Controllers\DistrictAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class DistrictAdminSchoolController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'district_id' => 'required|integer',
            'municipality_id' => 'required|integer',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'address' => 'nullable|string|max:255',
            'contact' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
        ]);

        $schoolName = trim($request->name);

        $municipalityCheck = Http::withHeaders([
            'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
            'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
            'Accept' => 'application/json',
        ])->get(env('SUPABASE_URL') . '/rest/v1/municipalities', [
            'municipality_id' => 'eq.' . $request->municipality_id,
            'district_id' => 'eq.' . $request->district_id,
            'select' => 'municipality_id'
        ]);

        if (!$municipalityCheck->successful()) {
            return response()->json([
                'message' => 'Failed to validate municipality.'
            ], 500);
        }

        if (empty($municipalityCheck->json())) {
            return response()->json([
                'message' => 'Selected municipality does not belong to the selected district.',
                'errors' => [
                    'municipality_id' => ['Selected municipality does not belong to the selected district.']
                ]
            ], 422);
        }

        $duplicateCheck = Http::withHeaders([
            'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
            'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
            'Accept' => 'application/json',
        ])->get(env('SUPABASE_URL') . '/rest/v1/schools', [
            'name' => 'eq.' . $schoolName,
            'municipality_id' => 'eq.' . $request->municipality_id,
            'select' => 'school_id'
        ]);

        if (!$duplicateCheck->successful()) {
            return response()->json([
                'message' => 'Failed to validate school uniqueness.',
                'error' => $duplicateCheck->body()
            ], 500);
        }

        if (!empty($duplicateCheck->json())) {
            return response()->json([
                'message' => 'This school already exists in the selected municipality.',
                'errors' => [
                    'name' => ['This school already exists in the selected municipality.']
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
        ])->post(env('SUPABASE_URL') . '/rest/v1/schools', [
            'name' => $schoolName,
            'district_id' => $request->district_id,
            'municipality_id' => $request->municipality_id,
            'logo' => $logoBase64,
            'address' => $request->address,
            'contact' => $request->contact,
            'email' => $request->email,
        ]);

        if (!$response->successful()) {
            return response()->json([
                'message' => 'Failed to create school.',
                'error' => $response->body()
            ], 500);
        }

        $newSchool = $response->json()[0] ?? null;

        return response()->json([
            'message' => 'School created successfully.',
            'school' => $newSchool
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'district_id' => 'required|integer',
            'municipality_id' => 'required|integer',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'address' => 'nullable|string|max:255',
            'contact' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
        ]);

        $schoolName = trim($request->name);

        $municipalityCheck = Http::withHeaders([
            'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
            'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
            'Accept' => 'application/json',
        ])->get(env('SUPABASE_URL') . '/rest/v1/municipalities', [
            'municipality_id' => 'eq.' . $request->municipality_id,
            'district_id' => 'eq.' . $request->district_id,
            'select' => 'municipality_id'
        ]);

        if (!$municipalityCheck->successful()) {
            return response()->json([
                'message' => 'Failed to validate municipality.'
            ], 500);
        }

        if (empty($municipalityCheck->json())) {
            return response()->json([
                'message' => 'Selected municipality does not belong to the selected district.',
                'errors' => [
                    'municipality_id' => ['Selected municipality does not belong to the selected district.']
                ]
            ], 422);
        }

        $duplicateCheck = Http::withHeaders([
            'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
            'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
            'Accept' => 'application/json',
        ])->get(env('SUPABASE_URL') . '/rest/v1/schools', [
            'name' => 'eq.' . $schoolName,
            'municipality_id' => 'eq.' . $request->municipality_id,
            'select' => 'school_id'
        ]);

        if (!$duplicateCheck->successful()) {
            return response()->json([
                'message' => 'Failed to validate school uniqueness.'
            ], 500);
        }

        $existingDuplicate = collect($duplicateCheck->json())
            ->first(fn ($school) => (string) $school['school_id'] !== (string) $id);

        if ($existingDuplicate) {
            return response()->json([
                'message' => 'This school already exists in the selected municipality.',
                'errors' => [
                    'name' => ['This school already exists in the selected municipality.']
                ]
            ], 422);
        }

        $existingResponse = Http::withHeaders([
            'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
            'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
            'Accept' => 'application/json',
        ])->get(env('SUPABASE_URL') . '/rest/v1/schools', [
            'school_id' => 'eq.' . $id,
            'select' => 'logo'
        ]);

        $existingSchool = $existingResponse->json()[0] ?? null;
        $logoBase64 = $existingSchool['logo'] ?? null;

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
        ])->patch(env('SUPABASE_URL') . "/rest/v1/schools?school_id=eq.{$id}", [
            'name' => $schoolName,
            'district_id' => $request->district_id,
            'municipality_id' => $request->municipality_id,
            'logo' => $logoBase64,
            'address' => $request->address,
            'contact' => $request->contact,
            'email' => $request->email,
        ]);

        if (!$response->successful()) {
            return response()->json([
                'message' => 'Failed to update school.',
                'errors' => $response->json()
            ], 500);
        }

        $updatedSchool = $response->json()[0] ?? [
            'school_id' => $id,
            'name' => $schoolName,
            'district_id' => $request->district_id,
            'municipality_id' => $request->municipality_id,
            'logo' => $logoBase64,
            'address' => $request->address,
            'contact' => $request->contact,
            'email' => $request->email,
        ];

        return response()->json([
            'message' => 'School updated successfully.',
            'school' => $updatedSchool
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
            ])->delete(env('SUPABASE_URL') . "/rest/v1/schools?school_id=eq.{$id}");

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
                'message' => 'Some schools could not be deleted.',
                'errors' => $failedDeletes
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Selected schools deleted successfully.',
            'deleted_ids' => $ids
        ]);
    }
}
