<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Mail\UserCredentialsMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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

        $existingUser = $checkEmail->successful()
            ? $checkEmail->json()
            : [];

        if (! empty($existingUser)) {
            return back()->withInput()->withErrors([
                'email' => 'This email is already registered.',
            ]);
        }

        $role = $this->findAllowedSchoolUserRole($request->role_id);

        if (! $role) {
            return back()->withInput()->withErrors([
                'role_id' => 'Invalid role selected. Only Principal and Evaluator roles are allowed for school users.',
            ]);
        }

        $roleName = strtolower($role['name'] ?? '');
        $scope = $this->findSchoolScopeForRole($request->role_id, $roleName);

        if (! $scope) {
            return back()->withInput()->withErrors([
                'role_id' => 'No school scope found for this role. Please make sure the selected Principal/Evaluator role has a school scope in Supabase.',
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
            Log::error('Failed to create Supabase auth user from School Admin.', [
                'email' => $request->email,
                'status' => $authResponse->status(),
                'body' => $authResponse->body(),
            ]);

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
            Log::error('Failed to save profile from School Admin user creation.', [
                'email' => $request->email,
                'user_id' => $authUser['id'] ?? null,
                'status' => $profileResponse->status(),
                'body' => $profileResponse->body(),
            ]);

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
            Log::error('Failed to assign School Admin-created user role.', [
                'email' => $request->email,
                'user_id' => $authUser['id'] ?? null,
                'status' => $designationResponse->status(),
                'body' => $designationResponse->body(),
            ]);

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

        try {
            Mail::to($request->email)->send(
                new UserCredentialsMail(
                    $request->full_name,
                    $request->email,
                    $generatedPassword
                )
            );
        } catch (\Throwable $exception) {
            Log::error('School Admin user credentials email failed.', [
                'email' => $request->email,
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'mail_mailer' => config('mail.default'),
                'mail_from' => config('mail.from.address'),
            ]);

            return redirect()
                ->route('school-admin.users.index')
                ->with('success', 'User created successfully, but credentials email could not be sent.')
                ->with('mail_error', $exception->getMessage());
        }

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
        $rolesResponse = Http::withHeaders($this->supabaseHeaders())
            ->get(env('SUPABASE_URL') . '/rest/v1/roles', [
                'select' => 'id,name,description',
                'order' => 'name.asc',
            ]);

        if (! $rolesResponse->successful()) {
            Log::error('Failed to fetch school admin allowed roles.', [
                'status' => $rolesResponse->status(),
                'body' => $rolesResponse->body(),
            ]);

            return [];
        }

        return collect($rolesResponse->json())
            ->filter(function ($role) {
                return in_array(strtolower($role['name'] ?? ''), ['principal', 'evaluator'], true);
            })
            ->values()
            ->all();
    }

    private function supabaseHeaders(): array
    {
        return [
            'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
            'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
            'Accept' => 'application/json',
        ];
    }

    private function findAllowedSchoolUserRole(string $roleId): ?array
    {
        $response = Http::withHeaders($this->supabaseHeaders())
            ->get(env('SUPABASE_URL') . '/rest/v1/roles', [
                'id' => 'eq.' . $roleId,
                'select' => 'id,name,description',
                'limit' => 1,
            ]);

        if (! $response->successful()) {
            Log::error('Failed to find allowed school user role.', [
                'role_id' => $roleId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        $role = $response->json()[0] ?? null;
        $roleName = strtolower($role['name'] ?? '');

        return in_array($roleName, ['principal', 'evaluator'], true) ? $role : null;
    }

    private function findSchoolScopeForRole(string $roleId, string $roleName): ?array
    {
        $response = Http::withHeaders($this->supabaseHeaders())
            ->get(env('SUPABASE_URL') . '/rest/v1/scopes', [
                'scope_type' => 'eq.school',
                'select' => 'id,role_id,name,scope_type',
            ]);

        if (! $response->successful()) {
            Log::error('Failed to find school scope for role.', [
                'role_id' => $roleId,
                'role_name' => $roleName,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        $schoolScopes = collect($response->json());

        return $schoolScopes->first(function ($scope) {
                return str_contains(strtolower($scope['name'] ?? ''), 'school admin');
            })
            ?? $schoolScopes->first(function ($scope) {
                return strtolower($scope['name'] ?? '') === 'school';
            })
            ?? $schoolScopes->first();
    }
}
