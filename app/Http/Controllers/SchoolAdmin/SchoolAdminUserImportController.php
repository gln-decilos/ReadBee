<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Helpers\SchoolAdminMenuHelper;
use App\Mail\UserCredentialsMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SchoolAdminUserImportController extends Controller
{
    public function index()
    {
        $menuGroups = SchoolAdminMenuHelper::getMenuGroups();

        return view('pages.school-admin.school-admin-users-import', compact('menuGroups'));
    }

    public function downloadTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="school-user-import-template.csv"',
        ];

        $content = "full_name,email,role\n";
        $content .= "Juan Dela Cruz,juan@example.com,Teacher\n";
        $content .= "Maria Santos,maria@example.com,Principal\n";

        return response($content, 200, $headers);
    }

    public function preview(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt',
        ]);

        $menuGroups = SchoolAdminMenuHelper::getMenuGroups();
        $activeDesignation = session('active_designation', []);
        $schoolId = $activeDesignation['school_id'] ?? null;

        if (! $schoolId) {
            return redirect()
                ->route('school-admin.users.index')
                ->with('error', 'No school assigned to your account.');
        }

        $rows = $this->parseCsv($request->file('csv_file'));
        $validatedRows = $this->validateImportRows($rows, $schoolId);

        session([
            'school_user_import_rows' => $validatedRows,
        ]);

        return view('pages.school-admin.school-admin-users-import-review', [
            'menuGroups' => $menuGroups,
            'rows' => $validatedRows,
        ]);
    }

    public function validateRows(Request $request)
    {
        $menuGroups = SchoolAdminMenuHelper::getMenuGroups();
        $activeDesignation = session('active_designation', []);
        $schoolId = $activeDesignation['school_id'] ?? null;

        if (! $schoolId) {
            return redirect()
                ->route('school-admin.users.index')
                ->with('error', 'No school assigned to your account.');
        }

        $rows = $request->input('rows', []);
        $normalizedRows = [];

        foreach ($rows as $index => $row) {
            $normalizedRows[] = [
                'row_number' => $row['row_number'] ?? ($index + 2),
                'full_name' => trim($row['full_name'] ?? ''),
                'email' => trim($row['email'] ?? ''),
                'role' => trim($row['role'] ?? ''),
            ];
        }

        $validatedRows = $this->validateImportRows($normalizedRows, $schoolId);

        session([
            'school_user_import_rows' => $validatedRows,
        ]);

        return view('pages.school-admin.school-admin-users-import-review', [
            'menuGroups' => $menuGroups,
            'rows' => $validatedRows,
        ]);
    }

    public function commit(Request $request)
    {
        $rows = session('school_user_import_rows', []);
        $activeDesignation = session('active_designation', []);
        $schoolId = $activeDesignation['school_id'] ?? null;

        if (! $schoolId) {
            return redirect()
                ->route('school-admin.users.index')
                ->with('error', 'No school assigned to your account.');
        }

        if (empty($rows)) {
            return redirect()
                ->route('school-admin.users.import.index')
                ->with('error', 'No import data found.');
        }

        $invalidRows = collect($rows)->filter(fn ($row) => ($row['status'] ?? 'invalid') !== 'valid');

        if ($invalidRows->isNotEmpty()) {
            return redirect()
                ->route('school-admin.users.import.index')
                ->with('error', 'Please fix validation errors before creating accounts.');
        }

        $roleMap = $this->resolveRoleMap();
        $scopeMap = $this->resolveScopeMap();

        $createdCount = 0;
        $failedRows = [];

        foreach ($rows as $row) {
            $roleName = strtolower(trim($row['role']));
            $role = $roleMap[$roleName] ?? null;
            $scope = $scopeMap[$role['id'] ?? ''] ?? null;

            if (! $role || ! $scope) {
                $failedRows[] = 'Row ' . $row['row_number'] . ': Missing role or scope mapping.';
                continue;
            }

            $password = Str::random(10);

            $authResponse = Http::withHeaders([
                'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
                'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
                'Content-Type' => 'application/json',
            ])->post(env('SUPABASE_URL') . '/auth/v1/admin/users', [
                'email' => $row['email'],
                'password' => $password,
                'email_confirm' => true,
            ]);

            if (! $authResponse->successful()) {
                $failedRows[] = 'Row ' . $row['row_number'] . ': Failed to create auth user.';
                continue;
            }

            $authUser = $authResponse->json();

            $profileResponse = Http::withHeaders([
                'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
                'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
                'Content-Type' => 'application/json',
                'Prefer' => 'return=minimal',
            ])->post(env('SUPABASE_URL') . '/rest/v1/profiles', [
                'id' => $authUser['id'],
                'full_name' => $row['full_name'],
                'email' => $row['email'],
            ]);

            if (! $profileResponse->successful()) {
                Http::withHeaders([
                    'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
                    'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
                ])->delete(env('SUPABASE_URL') . '/auth/v1/admin/users/' . $authUser['id']);

                $failedRows[] = 'Row ' . $row['row_number'] . ': Failed to save profile.';
                continue;
            }

            $designationResponse = Http::withHeaders([
                'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
                'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
                'Content-Type' => 'application/json',
                'Prefer' => 'return=representation',
            ])->post(env('SUPABASE_URL') . '/rest/v1/user_roles', [
                'user_id' => $authUser['id'],
                'role_id' => $role['id'],
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

                $failedRows[] = 'Row ' . $row['row_number'] . ': Failed to assign role.';
                continue;
            }

            Mail::to($row['email'])->send(
                new UserCredentialsMail(
                    $row['full_name'],
                    $row['email'],
                    $password
                )
            );

            $createdCount++;
        }

        session()->forget('school_user_import_rows');

        if (! empty($failedRows)) {
            return redirect()
                ->route('school-admin.users.index')
                ->with('success', $createdCount . ' account(s) created.')
                ->with('error', implode(' ', $failedRows));
        }

        return redirect()
            ->route('school-admin.users.index')
            ->with('success', $createdCount . ' account(s) created successfully.');
    }

    private function parseCsv($file): array
    {
        $rows = [];
        $handle = fopen($file->getRealPath(), 'r');

        if (! $handle) {
            return [];
        }

        $header = fgetcsv($handle);

        if (! $header) {
            fclose($handle);
            return [];
        }

        $header = array_map(fn ($value) => trim(strtolower($value)), $header);
        $rowNumber = 2;

        while (($data = fgetcsv($handle)) !== false) {
            if (count(array_filter($data, fn ($value) => trim((string) $value) !== '')) === 0) {
                $rowNumber++;
                continue;
            }

            $mapped = array_combine($header, array_pad($data, count($header), ''));

            $rows[] = [
                'row_number' => $rowNumber,
                'full_name' => trim($mapped['full_name'] ?? ''),
                'email' => trim($mapped['email'] ?? ''),
                'role' => trim($mapped['role'] ?? ''),
            ];

            $rowNumber++;
        }

        fclose($handle);

        return $rows;
    }

    private function validateImportRows(array $rows, string $schoolId): array
    {
        $emailsInFile = [];
        $roleMap = $this->resolveRoleMap();
        $scopeMap = $this->resolveScopeMap();

        foreach ($rows as $row) {
            $email = strtolower(trim($row['email'] ?? ''));
            if ($email !== '') {
                $emailsInFile[$email] = ($emailsInFile[$email] ?? 0) + 1;
            }
        }

        $existingEmails = [];
        $emailsToCheck = array_keys($emailsInFile);

        if (! empty($emailsToCheck)) {
            $quotedEmails = implode(',', array_map(fn ($email) => '"' . $email . '"', $emailsToCheck));

            $profilesResponse = Http::withHeaders([
                'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
                'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
                'Accept' => 'application/json',
            ])->get(env('SUPABASE_URL') . '/rest/v1/profiles', [
                'email' => 'in.(' . $quotedEmails . ')',
                'select' => 'email',
            ]);

            if ($profilesResponse->successful()) {
                $existingEmails = collect($profilesResponse->json())
                    ->pluck('email')
                    ->map(fn ($email) => strtolower(trim($email)))
                    ->all();
            }
        }

        $validated = [];

        foreach ($rows as $row) {
            $errors = [];
            $fullName = trim($row['full_name'] ?? '');
            $email = strtolower(trim($row['email'] ?? ''));
            $role = trim($row['role'] ?? '');
            $normalizedRole = strtolower($role);

            if ($fullName === '') {
                $errors['full_name'] = 'Full name is required.';
            }

            if ($email === '') {
                $errors['email'] = 'Email is required.';
            } elseif (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Invalid email format.';
            } elseif (in_array($email, $existingEmails)) {
                $errors['email'] = 'Email already exists.';
            } elseif (($emailsInFile[$email] ?? 0) > 1) {
                $errors['email'] = 'Duplicate email in file.';
            }

            if ($role === '') {
                $errors['role'] = 'Role is required.';
            } elseif (! in_array($normalizedRole, ['principal', 'teacher'])) {
                $errors['role'] = 'Role must be Principal or Teacher.';
            } else {
                $roleRow = $roleMap[$normalizedRole] ?? null;
                if (! $roleRow) {
                    $errors['role'] = 'Role not found in database.';
                } elseif (! isset($scopeMap[$roleRow['id']])) {
                    $errors['role'] = 'School scope not found for role.';
                }
            }

            $validated[] = [
                'row_number' => $row['row_number'],
                'full_name' => $fullName,
                'email' => $email,
                'role' => $role,
                'status' => empty($errors) ? 'valid' : 'invalid',
                'errors' => $errors,
            ];
        }

        return $validated;
    }

    private function resolveRoleMap(): array
    {
        $response = Http::withHeaders([
            'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
            'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
            'Accept' => 'application/json',
        ])->get(env('SUPABASE_URL') . '/rest/v1/roles', [
            'select' => 'id,name',
            'name' => 'in.(Principal,Teacher)',
        ]);

        if (! $response->successful()) {
            return [];
        }

        $map = [];
        foreach ($response->json() as $role) {
            $map[strtolower($role['name'])] = $role;
        }

        return $map;
    }

    private function resolveScopeMap(): array
    {
        $response = Http::withHeaders([
            'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
            'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
            'Accept' => 'application/json',
        ])->get(env('SUPABASE_URL') . '/rest/v1/scopes', [
            'scope_type' => 'eq.school',
            'select' => 'id,role_id,name,scope_type',
        ]);

        if (! $response->successful()) {
            return [];
        }

        $map = [];
        foreach ($response->json() as $scope) {
            $map[$scope['role_id']] = $scope;
        }

        return $map;
    }
}
