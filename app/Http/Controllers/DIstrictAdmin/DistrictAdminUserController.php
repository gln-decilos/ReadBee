<?php

namespace App\Http\Controllers\DistrictAdmin;

use App\Http\Controllers\Controller;
use App\Mail\UserCredentialsMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class DistrictAdminUserController extends Controller
{
    private function supabaseHeaders(?string $prefer = null): array
    {
        $headers = [
            'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
            'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        if ($prefer) {
            $headers['Prefer'] = $prefer;
        }

        return $headers;
    }

    private function normalizeRoleName(?string $roleName): string
    {
        return str_replace([' ', '-'], '_', strtolower(trim((string) $roleName)));
    }

    private function getRoleById(string $roleId): ?array
    {
        $response = Http::withHeaders($this->supabaseHeaders())
            ->get(env('SUPABASE_URL') . '/rest/v1/roles', [
                'id' => 'eq.' . $roleId,
                'select' => 'id,name,description',
                'limit' => 1,
            ]);

        if (! $response->successful()) {
            return null;
        }

        return $response->json()[0] ?? null;
    }

    private function expectedScopeTypeForRole(?string $roleName): ?string
    {
        $normalized = $this->normalizeRoleName($roleName);

        if (in_array($normalized, ['evaluator', 'principal'], true)) {
            return 'school';
        }

        if (in_array($normalized, ['district_supervisor', 'districtsupervisor'], true)) {
            return 'district';
        }

        return null;
    }

    private function getScopeById(string $scopeId): ?array
    {
        $response = Http::withHeaders($this->supabaseHeaders())
            ->get(env('SUPABASE_URL') . '/rest/v1/scopes', [
                'id' => 'eq.' . $scopeId,
                'select' => 'id,name,description,scope_type',
                'limit' => 1,
            ]);

        if (! $response->successful()) {
            return null;
        }

        return $response->json()[0] ?? null;
    }

    private function getFirstScopeByType(string $scopeType): ?array
    {
        $response = Http::withHeaders($this->supabaseHeaders())
            ->get(env('SUPABASE_URL') . '/rest/v1/scopes', [
                'scope_type' => 'eq.' . $scopeType,
                'select' => 'id,name,description,scope_type',
                'order' => 'name.asc',
                'limit' => 1,
            ]);

        if (! $response->successful()) {
            return null;
        }

        return $response->json()[0] ?? null;
    }

    public function store(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email'
        ]);

        $checkEmail = Http::withHeaders($this->supabaseHeaders())
            ->get(env('SUPABASE_URL') . '/rest/v1/profiles', [
                'email' => 'eq.' . $request->email,
                'select' => 'id,email',
            ]);

        $existingUser = $checkEmail->successful() ? $checkEmail->json() : [];

        if (! empty($existingUser)) {
            return back()->withInput()->withErrors([
                'email' => 'This email is already registered.'
            ]);
        }

        $generatedPassword = Str::random(10);

        $authResponse = Http::withHeaders($this->supabaseHeaders())
            ->post(env('SUPABASE_URL') . '/auth/v1/admin/users', [
                'email' => $request->email,
                'password' => $generatedPassword,
                'email_confirm' => true
            ]);

        if (! $authResponse->successful()) {
            return back()->with('error', 'Failed to create user.');
        }

        $authUser = $authResponse->json();

        Http::withHeaders($this->supabaseHeaders('return=minimal'))
            ->post(env('SUPABASE_URL') . '/rest/v1/profiles', [
                'id' => $authUser['id'],
                'full_name' => $request->full_name,
                'email' => $request->email,
            ]);

        try {
            Mail::to($request->email)->send(
                new UserCredentialsMail(
                    $request->full_name,
                    $request->email,
                    $generatedPassword
                )
            );
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()
                ->route('district-admin.district-admin-users')
                ->with('success', 'User created successfully, but credentials email could not be sent.');
        }

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
            Http::withHeaders($this->supabaseHeaders())
                ->delete(env('SUPABASE_URL') . "/auth/v1/admin/users/{$id}");

            Http::withHeaders($this->supabaseHeaders())
                ->delete(env('SUPABASE_URL') . "/rest/v1/profiles?id=eq.{$id}");
        }

        return response()->json([
            'success' => true,
            'message' => 'Selected users deleted successfully.',
            'deleted_ids' => $ids
        ]);
    }

    public function getUserDesignations($userId)
    {
        $userResponse = Http::withHeaders($this->supabaseHeaders())
            ->get(env('SUPABASE_URL') . '/rest/v1/profiles', [
                'id' => 'eq.' . $userId,
                'select' => 'id,full_name,email'
            ]);

        $user = $userResponse->json()[0] ?? null;

        if (! $user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $rolesResponse = Http::withHeaders($this->supabaseHeaders())
            ->get(env('SUPABASE_URL') . '/rest/v1/user_roles', [
                'user_id' => 'eq.' . $userId,
                'select' => 'user_role_id,role_id,scope_id,assigned_at,district_id,municipal_id,school_id,roles(name,description),scopes(name,description,scope_type),districts(district_name),municipalities(municipal_name),schools(name)'
            ]);

        $userRoles = $rolesResponse->successful() ? $rolesResponse->json() : [];
        $designations = [];

        foreach ($userRoles as $role) {
            $districtName = is_array($role['districts'] ?? null)
                ? ($role['districts']['district_name'] ?? '')
                : '';

            $municipalityName = is_array($role['municipalities'] ?? null)
                ? ($role['municipalities']['municipal_name'] ?? '')
                : '';

            $schoolName = is_array($role['schools'] ?? null)
                ? ($role['schools']['name'] ?? '')
                : '';

            $scopeType = is_array($role['scopes'] ?? null)
                ? ($role['scopes']['scope_type'] ?? '')
                : '';

            $assignedTo = '-';

            if ($scopeType === 'district') {
                $assignedTo = $districtName ?: '-';
            } elseif ($scopeType === 'school') {
                $parts = array_filter([$districtName, $municipalityName, $schoolName]);
                $assignedTo = ! empty($parts) ? implode(' / ', $parts) : '-';
            }

            $designations[] = [
                'user_role_id' => $role['user_role_id'],
                'role_id' => $role['role_id'],
                'scope_id' => $role['scope_id'],
                'district_id' => $role['district_id'] ?? null,
                'municipal_id' => $role['municipal_id'] ?? null,
                'school_id' => $role['school_id'] ?? null,
                'role' => is_array($role['roles'] ?? null)
                    ? ($role['roles']['name'] ?? 'Unknown Role')
                    : 'Unknown Role',
                'role_description' => is_array($role['roles'] ?? null)
                    ? ($role['roles']['description'] ?? '')
                    : '',
                'scope' => is_array($role['scopes'] ?? null)
                    ? ($role['scopes']['name'] ?? 'Unknown Scope')
                    : 'Unknown Scope',
                'scope_description' => is_array($role['scopes'] ?? null)
                    ? ($role['scopes']['description'] ?? '')
                    : '',
                'scope_type' => $scopeType,
                'district_name' => $districtName,
                'municipal_name' => $municipalityName,
                'school_name' => $schoolName,
                'assigned_to' => $assignedTo,
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

        if (! Str::isUuid($userRoleId)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid designation identifier'
            ], 422);
        }

        $url = env('SUPABASE_URL') . "/rest/v1/user_roles?user_role_id=eq.$userRoleId";

        $response = Http::withHeaders($this->supabaseHeaders('return=minimal'))
            ->delete($url);

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

    public function scopes(Request $request)
    {
        $request->validate([
            'role_id' => 'nullable|uuid',
        ]);

        $role = $request->role_id ? $this->getRoleById($request->role_id) : null;
        $expectedScopeType = $this->expectedScopeTypeForRole($role['name'] ?? null);

        $query = [
            'select' => 'id,name,description,scope_type',
            'order' => 'name.asc',
        ];

        if ($expectedScopeType) {
            $query['scope_type'] = 'eq.' . $expectedScopeType;
        } elseif ($request->role_id) {
            $query['role_id'] = 'eq.' . $request->role_id;
        }

        $response = Http::withHeaders($this->supabaseHeaders())
            ->get(env('SUPABASE_URL') . '/rest/v1/scopes', $query);

        if (! $response->successful()) {
            return response()->json([], 200);
        }

        return response()->json($response->json() ?: []);
    }

    public function storeDesignation(Request $request)
    {
        $request->validate([
            'user_id' => 'required|uuid',
            'role_id' => 'required|uuid',
            'scope_id' => 'nullable|uuid',
            'district_id' => 'nullable|integer',
            'municipal_id' => 'nullable|integer',
            'school_id' => 'nullable|uuid',
        ]);

        $role = $this->getRoleById($request->role_id);

        if (! $role) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid role selected.',
            ], 422);
        }

        $expectedScopeType = $this->expectedScopeTypeForRole($role['name'] ?? null);
        $scope = null;

        if ($request->scope_id) {
            $scope = $this->getScopeById($request->scope_id);
        }

        if ($expectedScopeType && (! $scope || ($scope['scope_type'] ?? null) !== $expectedScopeType)) {
            $scope = $this->getFirstScopeByType($expectedScopeType);
        }

        if (! $scope) {
            return response()->json([
                'success' => false,
                'message' => 'No scope available for the selected role.',
                'role' => $role['name'] ?? null,
                'expected_scope_type' => $expectedScopeType,
            ], 422);
        }

        if (($scope['scope_type'] ?? null) === 'school' && ! $request->school_id) {
            return response()->json([
                'success' => false,
                'message' => 'Please select a school for this role.',
            ], 422);
        }

        if (($scope['scope_type'] ?? null) === 'district' && ! $request->district_id) {
            return response()->json([
                'success' => false,
                'message' => 'Please select a district for this role.',
            ], 422);
        }

        $payload = [
            'user_id' => $request->user_id,
            'role_id' => $request->role_id,
            'scope_id' => $scope['id'],
            'district_id' => $request->district_id ?: null,
            'municipal_id' => $request->municipal_id ?: null,
            'school_id' => $request->school_id ?: null,
        ];

        if (($scope['scope_type'] ?? null) === 'district') {
            $payload['municipal_id'] = null;
            $payload['school_id'] = null;
        }

        $response = Http::withHeaders($this->supabaseHeaders('return=representation'))
            ->post(env('SUPABASE_URL') . '/rest/v1/user_roles', $payload);

        if (! $response->successful()) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign designation',
                'error' => $response->body(),
                'payload' => $payload
            ], 500);
        }

        $row = $response->json()[0] ?? [];

        return response()->json([
            'success' => true,
            'designation' => [
                'user_role_id' => $row['user_role_id'] ?? null,
                'role_id' => $row['role_id'] ?? $request->role_id,
                'scope_id' => $row['scope_id'] ?? $scope['id'],
                'scope_type' => $scope['scope_type'] ?? null,
                'district_id' => $row['district_id'] ?? null,
                'municipal_id' => $row['municipal_id'] ?? null,
                'school_id' => $row['school_id'] ?? null,
                'assigned_at' => $row['assigned_at'] ?? null,
            ]
        ]);
    }
}
