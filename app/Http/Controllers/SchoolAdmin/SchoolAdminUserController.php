<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Mail\UserCredentialsMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SchoolAdminUserController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email',
            'role_id' => 'required|uuid',
        ]);

        $activeDesignation = session('active_designation', []);
        $schoolId = $activeDesignation['school_id'] ?? null;

        if (! $schoolId) {
            return back()->with('error', 'No school assigned to your account.');
        }

        $checkEmail = Http::withHeaders([
            'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
            'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
            'Content-Type' => 'application/json',
        ])->get(env('SUPABASE_URL') . '/rest/v1/profiles', [
            'email' => 'eq.' . $request->email,
            'select' => 'id,email',
        ]);

        $existingUser = $checkEmail->json();

        if (! empty($existingUser)) {
            return back()->withInput()->withErrors([
                'email' => 'This email is already registered.',
            ]);
        }

        $roleResponse = Http::withHeaders([
            'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
            'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
            'Accept' => 'application/json',
        ])->get(env('SUPABASE_URL') . '/rest/v1/roles', [
            'id' => 'eq.' . $request->role_id,
            'select' => 'id,name',
        ]);

        $role = $roleResponse->successful() ? ($roleResponse->json()[0] ?? null) : null;

        if (! $role || ! in_array(strtolower($role['name']), ['principal', 'teacher'])) {
            return back()->withInput()->withErrors([
                'role_id' => 'Invalid role selected.',
            ]);
        }

        $scopeResponse = Http::withHeaders([
            'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
            'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
            'Accept' => 'application/json',
        ])->get(env('SUPABASE_URL') . '/rest/v1/scopes', [
            'role_id' => 'eq.' . $request->role_id,
            'scope_type' => 'eq.school',
            'select' => 'id,name,scope_type',
            'limit' => 1,
        ]);

        $scope = $scopeResponse->successful() ? ($scopeResponse->json()[0] ?? null) : null;

        if (! $scope) {
            return back()->withInput()->withErrors([
                'role_id' => 'No school scope found for this role.',
            ]);
        }

        $generatedPassword = Str::random(10);

        $authResponse = Http::withHeaders([
            'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
            'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
            'Content-Type' => 'application/json',
        ])->post(env('SUPABASE_URL') . '/auth/v1/admin/users', [
            'email' => $request->email,
            'password' => $generatedPassword,
            'email_confirm' => true,
        ]);

        if (! $authResponse->successful()) {
            return back()->with('error', 'Failed to create user.');
        }

        $authUser = $authResponse->json();

        $profileResponse = Http::withHeaders([
            'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
            'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
            'Content-Type' => 'application/json',
            'Prefer' => 'return=minimal',
        ])->post(env('SUPABASE_URL') . '/rest/v1/profiles', [
            'id' => $authUser['id'],
            'full_name' => $request->full_name,
            'email' => $request->email,
        ]);

        if (! $profileResponse->successful()) {
            Http::withHeaders([
                'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
                'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
            ])->delete(env('SUPABASE_URL') . '/auth/v1/admin/users/' . $authUser['id']);

            return back()->with('error', 'Failed to save profile.');
        }

        $designationResponse = Http::withHeaders([
            'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
            'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
            'Content-Type' => 'application/json',
            'Prefer' => 'return=representation',
        ])->post(env('SUPABASE_URL') . '/rest/v1/user_roles', [
            'user_id' => $authUser['id'],
            'role_id' => $request->role_id,
            'scope_id' => $scope['id'],
            'school_id' => $schoolId,
            'district_id' => $activeDesignation['district_id'] ?? null,
            'municipal_id' => $activeDesignation['municipal_id'] ?? null,
        ]);

        if (! $designationResponse->successful()) {
            Http::withHeaders([
                'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
                'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
            ])->delete(env('SUPABASE_URL') . '/rest/v1/profiles?id=eq.' . $authUser['id']);

            Http::withHeaders([
                'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
                'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
            ])->delete(env('SUPABASE_URL') . '/auth/v1/admin/users/' . $authUser['id']);

            return back()->with('error', 'Failed to assign user role.');
        }

        Mail::to($request->email)->send(
            new UserCredentialsMail(
                $request->full_name,
                $request->email,
                $generatedPassword
            )
        );

        return redirect()
            ->route('school-admin.users.index')
            ->with('success', 'User created successfully. Credentials have been emailed.');
    }

    public function destroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
        ]);

        foreach ($request->ids as $id) {
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
        ]);
    }

    public function roles()
    {
        return Http::withHeaders([
            'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
            'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
            'Accept' => 'application/json',
        ])->get(
            env('SUPABASE_URL') . '/rest/v1/roles',
            [
                'select' => 'id,name,description',
                'name' => 'in.(Principal,Teacher)',
                'order' => 'name.asc',
            ]
        )->json();
    }
}
