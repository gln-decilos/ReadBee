<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SignInController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $supabaseUrl = rtrim(env('SUPABASE_URL'), '/');
        $anonKey = env('SUPABASE_ANON_KEY');
        $serviceRoleKey = env('SUPABASE_SERVICE_ROLE_KEY');

        $authResponse = Http::withHeaders([
            'apikey' => $anonKey,
            'Content-Type' => 'application/json',
        ])->post($supabaseUrl . '/auth/v1/token?grant_type=password', [
            'email' => $request->email,
            'password' => $request->password,
        ]);

        if (! $authResponse->successful()) {
            return back()->withInput()->withErrors([
                'email' => 'Invalid email or password.',
            ]);
        }

        $authData = $authResponse->json();
        $userId = $authData['user']['id'] ?? null;

        if (! $userId) {
            return back()->withInput()->withErrors([
                'email' => 'Unable to sign in.',
            ]);
        }

        // Get user profile with more fields
        $profileResponse = Http::withHeaders([
            'apikey' => $serviceRoleKey,
            'Authorization' => 'Bearer ' . $serviceRoleKey,
            'Content-Type' => 'application/json',
        ])->get($supabaseUrl . '/rest/v1/profiles', [
            'id' => 'eq.' . $userId,
            'select' => 'id,full_name,email,sex,phone,address,title,position,created_at,updated_at',
        ]);

        $profile = null;

        if ($profileResponse->successful()) {
            $profiles = $profileResponse->json();
            $profile = $profiles[0] ?? null;
        }

        // Get all user designations
        $designationResponse = Http::withHeaders([
            'apikey' => $serviceRoleKey,
            'Authorization' => 'Bearer ' . $serviceRoleKey,
            'Content-Type' => 'application/json',
        ])->get($supabaseUrl . '/rest/v1/user_roles', [
            'user_id' => 'eq.' . $userId,
            'select' => 'user_role_id,user_id,role_id,scope_id,district_id,municipal_id,school_id,assigned_at,roles(name,description),scopes(id,name,description,scope_type)',
            'order' => 'assigned_at.asc',
        ]);

        $designations = [];

        if ($designationResponse->successful()) {
            $rows = $designationResponse->json();

            foreach ($rows as $row) {
                $roleName = is_array($row['roles'] ?? null)
                    ? ($row['roles']['name'] ?? null)
                    : null;

                $roleDescription = is_array($row['roles'] ?? null)
                    ? ($row['roles']['description'] ?? null)
                    : null;

                $scopeName = is_array($row['scopes'] ?? null)
                    ? ($row['scopes']['name'] ?? null)
                    : null;

                $scopeDescription = is_array($row['scopes'] ?? null)
                    ? ($row['scopes']['description'] ?? null)
                    : null;

                $scopeType = is_array($row['scopes'] ?? null)
                    ? ($row['scopes']['scope_type'] ?? null)
                    : null;

                $designations[] = [
                    'user_role_id' => $row['user_role_id'] ?? null,
                    'user_id' => $row['user_id'] ?? null,
                    'role_id' => $row['role_id'] ?? null,
                    'scope_id' => $row['scope_id'] ?? null,
                    'district_id' => $row['district_id'] ?? null,
                    'municipal_id' => $row['municipal_id'] ?? null,
                    'school_id' => $row['school_id'] ?? null,
                    'assigned_at' => $row['assigned_at'] ?? null,

                    'role_name' => $roleName,
                    'role_description' => $roleDescription,
                    'scope_name' => $scopeName,
                    'scope_description' => $scopeDescription,
                    'scope_type' => $scopeType,
                ];
            }
        }

        $activeDesignation = $designations[0] ?? null;

        $activeRoleName = strtolower($activeDesignation['role_name'] ?? '');
        $activeScopeType = strtolower($activeDesignation['scope_type'] ?? '');

        session([
            'supabase_access_token' => $authData['access_token'] ?? null,
            'supabase_refresh_token' => $authData['refresh_token'] ?? null,

            'supabase_user' => [
                'id' => $userId,
                'email' => $authData['user']['email'] ?? null,
                'full_name' => $profile['full_name'] ?? null,
                'profile' => $profile,
            ],

            'user_designations' => $designations,
            'active_designation' => $activeDesignation,
        ]);

        $request->session()->regenerate();

        if (! $activeDesignation) {
            return redirect()->route('dashboard')
                ->with('success', 'Signed in successfully. No designation assigned yet.');
        }

        if ($activeRoleName === 'admin' && $activeScopeType === 'district') {
            return redirect()->route('district-admin.district-admin-dashboard')
                ->with('success', 'Signed in successfully.');
        }

        if ($activeRoleName === 'admin' && $activeScopeType === 'school') {
            return redirect()->route('school-admin.dashboard')
                ->with('success', 'Signed in successfully.');
        }

        if ($activeRoleName === 'principal') {
            return redirect()->route('principal.dashboard')
                ->with('success', 'Signed in successfully.');
        }

        if (in_array($activeRoleName, ['district supervisor', 'district_supervisor', 'supervisor'], true)) {
            return redirect()->route('district-supervisor.dashboard')
                ->with('success', 'Signed in successfully.');
        }

        if (in_array($activeRoleName, ['evaluator', 'teacher'], true)) {
            return redirect()->route('evaluator.dashboard')
                ->with('success', 'Signed in successfully.');
        }

        return redirect()->route('dashboard')
            ->with('success', 'Signed in successfully.');
    }

    public function switchDesignation(Request $request)
    {
        $request->validate([
            'user_role_id' => ['required', 'uuid'],
        ]);

        $designations = session('user_designations', []);

        $selectedDesignation = collect($designations)->firstWhere('user_role_id', $request->user_role_id);

        if (! $selectedDesignation) {
            return back()->withErrors([
                'designation' => 'Invalid designation selected.',
            ]);
        }

        session([
            'active_designation' => $selectedDesignation,
        ]);

        $roleName = strtolower($selectedDesignation['role_name'] ?? '');
        $scopeType = strtolower($selectedDesignation['scope_type'] ?? '');

        if ($roleName === 'admin' && $scopeType === 'district') {
            return redirect()->route('district-admin.district-admin-dashboard')
                ->with('success', 'Designation switched successfully.');
        }

        if ($roleName === 'admin' && $scopeType === 'school') {
            return redirect()->route('school-admin.dashboard')
                ->with('success', 'Designation switched successfully.');
        }

        if ($roleName === 'principal') {
            return redirect()->route('principal.dashboard')
                ->with('success', 'Designation switched successfully.');
        }

        if (in_array($roleName, ['district supervisor', 'district_supervisor', 'supervisor'], true)) {
            return redirect()->route('district-supervisor.dashboard')
                ->with('success', 'Designation switched successfully.');
        }

        if (in_array($roleName, ['evaluator', 'teacher'], true)) {
            return redirect()->route('evaluator.dashboard')
                ->with('success', 'Designation switched successfully.');
        }

        return redirect()->route('dashboard')
            ->with('success', 'Designation switched successfully.');
    }

    public function logout(Request $request)
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('signin')->with('success', 'Signed out successfully.');
    }
}
