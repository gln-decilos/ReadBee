<?php

namespace App\Http\Controllers\DistrictAdmin;
use Illuminate\Support\Facades\Mail;
use App\Mail\UserCredentialsMail;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;


class DistrictAdminUserController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email'
        ]);

        // ✅ CHECK IF EMAIL ALREADY EXISTS
        $checkEmail = Http::withHeaders([
            'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
            'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
            'Content-Type' => 'application/json',
        ])->get(env('SUPABASE_URL') . '/rest/v1/profiles', [
            'email' => 'eq.' . $request->email
        ]);

        $existingUser = $checkEmail->json();

        if (!empty($existingUser)) {
            return back()->withInput()->withErrors([
                'email' => 'This email is already registered.'
            ]);
        }

        // ✅ AUTO-GENERATE PASSWORD
        $generatedPassword = Str::random(10);

        // ✅ CREATE USER IN SUPABASE AUTH
        $authResponse = Http::withHeaders([
            'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
            'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
            'Content-Type' => 'application/json',
        ])->post(env('SUPABASE_URL') . '/auth/v1/admin/users', [
            'email' => $request->email,
            'password' => $generatedPassword,
            'email_confirm' => true
        ]);

        if (!$authResponse->successful()) {
            return back()->with('error', 'Failed to create user.');
        }

        $authUser = $authResponse->json();

        // ✅ INSERT INTO PROFILES TABLE
        Http::withHeaders([
            'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
            'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
            'Content-Type' => 'application/json',
            'Prefer' => 'return=minimal'
        ])->post(env('SUPABASE_URL') . '/rest/v1/profiles', [
            'id' => $authUser['id'],
            'full_name' => $request->full_name,
            'email' => $request->email,
        ]);

        // ✅ SEND EMAIL
        Mail::to($request->email)->send(
            new UserCredentialsMail(
                $request->full_name,
                $request->email,
                $generatedPassword
            )
        );

        return redirect()
            ->route('district-admin.district-admin-users')
            ->with('success', 'User created successfully. Credentials have been emailed.');
    }


    public function destroy(Request $request)
{
    $request->validate([
        'ids' => 'required|array'
    ]);

    $ids = $request->ids;

    foreach ($ids as $id) {

        Http::withHeaders([
            'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
            'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
        ])->delete(env('SUPABASE_URL') . "/auth/v1/admin/users/{$id}");

        Http::withHeaders([
            'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
            'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
        ])->delete(env('SUPABASE_URL') . "/rest/v1/profiles?id=eq.{$id}");
    }

    return response()->json([
        'success' => true,
        'message' => 'Selected users deleted successfully.',
        'deleted_ids' => $ids
    ]);
}

    public function getUserDesignations($userId)
{
    $userResponse = Http::withHeaders([
        'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
        'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
    ])->get(env('SUPABASE_URL') . '/rest/v1/profiles', [
        'id' => 'eq.' . $userId,
        'select' => 'id,full_name,email'
    ]);

    $user = $userResponse->json()[0] ?? null;

    if (!$user) {
        return response()->json(['error' => 'User not found'], 404);
    }

    $rolesResponse = Http::withHeaders([
        'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
        'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
    ])->get(env('SUPABASE_URL') . '/rest/v1/user_roles', [
        'user_id' => 'eq.' . $userId,
        'select' => 'user_role_id,role_id,scope_id,assigned_at,district_id,municipal_id,school_id,roles(name,description),scopes(name,description)'
    ]);

    $userRoles = $rolesResponse->json();

    $designations = [];

    foreach ($userRoles as $role) {
        $designations[] = [
            'user_role_id' => $role['user_role_id'],
            'role_id' => $role['role_id'],
            'scope_id' => $role['scope_id'],
            'district_id' => $role['district_id'] ?? null,
            'municipal_id' => $role['municipal_id'] ?? null,
            'school_id' => $role['school_id'] ?? null,
            'role' => is_array($role['roles'] ?? null) ? $role['roles']['name'] : 'Unknown Role',
            'role_description' => is_array($role['roles'] ?? null) ? $role['roles']['description'] : '',
            'scope' => is_array($role['scopes'] ?? null) ? $role['scopes']['name'] : 'Unknown Scope',
            'scope_description' => is_array($role['scopes'] ?? null) ? $role['scopes']['description'] : '',
            'assigned_at' => $role['assigned_at'],
        ];
    }

    return response()->json([
        'user' => $user,
        'designations' => $designations
    ]);
}

    public function destroyDesignation(Request $request)
{
    $userRoleId = $request->query('user_role_id');

    // ✅ Validate UUID
    if (!Str::isUuid($userRoleId)) {
        return response()->json([
            'success' => false,
            'message' => 'Invalid designation identifier'
        ], 422);
    }

    $url = env('SUPABASE_URL') . "/rest/v1/user_roles?user_role_id=eq.$userRoleId";

    $response = Http::withHeaders([
        'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
        'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
        'Prefer' => 'return=minimal',
    ])->delete($url);

    if ($response->successful()) {
        return response()->json([
            'success' => true,
            'message' => 'Designation removed successfully'
        ]);
    }

    return response()->json([
        'success' => false,
        'message' => 'Failed to remove designation',
        'supabase_error' => $response->body()
    ], 500);
}



public function storeDesignation(Request $request)
{
    $request->validate([
        'user_id' => 'required|uuid',
        'role_id' => 'required|uuid',
        'scope_id' => 'required|uuid',
        'district_id' => 'nullable|integer',
        'municipal_id' => 'nullable|integer',
        'school_id' => 'nullable|uuid',
    ]);

    $payload = [
        'user_id' => $request->user_id,
        'role_id' => $request->role_id,
        'scope_id' => $request->scope_id,
        'district_id' => $request->district_id ?: null,
        'municipal_id' => $request->municipal_id ?: null,
        'school_id' => $request->school_id ?: null,
    ];

    $response = Http::withHeaders([
        'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
        'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
        'Content-Type' => 'application/json',
        'Prefer' => 'return=representation'
    ])->post(env('SUPABASE_URL') . '/rest/v1/user_roles', $payload);

    if (!$response->successful()) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to assign designation',
            'error' => $response->body(),
            'payload' => $payload
        ], 500);
    }

    $row = $response->json()[0];

    return response()->json([
        'success' => true,
        'designation' => [
            'user_role_id' => $row['user_role_id'],
            'district_id' => $row['district_id'] ?? null,
            'municipal_id' => $row['municipal_id'] ?? null,
            'school_id' => $row['school_id'] ?? null,
            'assigned_at' => $row['assigned_at'],
        ]
    ]);
}
}
