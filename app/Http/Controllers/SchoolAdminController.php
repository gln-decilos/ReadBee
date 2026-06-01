<?php

namespace App\Http\Controllers;

use App\Helpers\SchoolAdminMenuHelper;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class SchoolAdminController extends Controller
{
    public function dashboard()
    {
        $menuGroups = SchoolAdminMenuHelper::getMenuGroups();
        $activeDesignation = session('active_designation', []);
        $schoolId = $activeDesignation['school_id'] ?? null;
        $schoolIdentity = $this->fetchSchoolIdentity($schoolId);
        $schoolName = $activeDesignation['school_name'] ?? $schoolIdentity['name'] ?? 'Your School';
        $schoolLogo = $activeDesignation['school_logo'] ?? $activeDesignation['logo'] ?? $schoolIdentity['logo'] ?? null;

        $schoolFilter = $schoolId ? ['school_id' => 'eq.' . $schoolId] : [];

        $dashboard = [
            'schoolName' => $schoolName,
            'schoolLogo' => $schoolLogo,
            'users' => $this->safeCount('user_roles', $schoolFilter, 'user_role_id'),
            'classes' => $this->safeCount('class_sections', array_merge($schoolFilter, ['status' => 'neq.archived']), 'section_id'),
            'gradeLevels' => $this->safeCount('grade_levels', $schoolFilter, 'grade_level_id'),
            'students' => $this->safeCount('pupils', array_merge($schoolFilter, ['status' => 'eq.enrolled']), 'pupil_id'),
            'recentSections' => $this->safeRows('class_sections', array_merge([
                'select' => 'section_id,section_name,adviser_name,status,created_at,grade_levels(grade_number),school_year(start_date,end_date)',
                'order' => 'created_at.desc',
                'limit' => 5,
            ], $schoolFilter)),
        ];

        return view('pages.school-admin.school-admin-dashboard', compact('menuGroups', 'dashboard'));
    }

    public function profile()
    {
        $menuGroups = SchoolAdminMenuHelper::getMenuGroups();

        return view('pages.school-admin.school-admin-profile', compact('menuGroups'));
    }

    public function users()
    {
        $menuGroups = SchoolAdminMenuHelper::getMenuGroups();

        $activeDesignation = session('active_designation', []);
        $schoolId = $activeDesignation['school_id'] ?? null;

        if (! $schoolId) {
            return redirect()
                ->route('school-admin.dashboard')
                ->with('error', 'No school assigned to your account.');
        }

        $userRolesResponse = Http::withHeaders([
            'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
            'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
            'Accept' => 'application/json',
        ])->get(
            env('SUPABASE_URL') . '/rest/v1/user_roles',
            [
                'school_id' => 'eq.' . $schoolId,
                'select' => 'user_role_id,user_id,assigned_at,school_id,role_id,scope_id,roles(id,name,description),scopes(id,name,scope_type),schools(name)',
                'order' => 'assigned_at.asc',
            ]
        );

        if (! $userRolesResponse->successful()) {
            return view('pages.school-admin.school-admin-users', [
                'menuGroups' => $menuGroups,
                'users' => [],
                'page' => 1,
                'perPage' => 10,
            ])->with('error', 'Failed to load users.');
        }

        $userRoles = $userRolesResponse->json() ?? [];

        $userIds = collect($userRoles)
            ->pluck('user_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $profilesById = collect();

        if (! empty($userIds)) {
            $quotedIds = implode(',', array_map(fn ($id) => '"' . $id . '"', $userIds));

            $profilesResponse = Http::withHeaders([
                'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
                'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
                'Accept' => 'application/json',
            ])->get(
                env('SUPABASE_URL') . '/rest/v1/profiles',
                [
                    'id' => 'in.(' . $quotedIds . ')',
                    'select' => 'id,full_name,email',
                ]
            );

            if ($profilesResponse->successful()) {
                $profilesById = collect($profilesResponse->json())->keyBy('id');
            }
        }

        $users = collect($userRoles)->map(function ($row) use ($profilesById) {
            $profile = $profilesById->get($row['user_id']);

            return [
                'user_role_id' => $row['user_role_id'] ?? null,
                'id' => $row['user_id'] ?? null,
                'full_name' => $profile['full_name'] ?? 'Unknown User',
                'email' => $profile['email'] ?? null,
                'role' => $row['roles']['name'] ?? null,
                'role_id' => $row['roles']['id'] ?? null,
                'scope' => $row['scopes']['name'] ?? null,
                'school_name' => $row['schools']['name'] ?? null,
                'assigned_at' => $row['assigned_at'] ?? null,
            ];
        })->values()->all();

        return view('pages.school-admin.school-admin-users', [
            'menuGroups' => $menuGroups,
            'users' => $users,
            'page' => 1,
            'perPage' => 10,
        ]);
    }
    private function supabaseHeaders(): array
    {
        return [
            'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
            'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
            'Accept' => 'application/json',
        ];
    }

    private function supabaseUrl(): string
    {
        return rtrim((string) env('SUPABASE_URL'), '/');
    }

    private function safeCount(string $table, array $filters = [], string $select = '*'): int
    {
        try {
            $response = Http::withHeaders(array_merge($this->supabaseHeaders(), [
                'Prefer' => 'count=exact',
                'Range-Unit' => 'items',
                'Range' => '0-0',
            ]))->get($this->supabaseUrl() . '/rest/v1/' . $table, array_merge([
                'select' => $select,
                'limit' => 1,
            ], $filters));

            if (! $response->successful()) {
                report('Failed to count ' . $table . ': ' . $response->body());
                return 0;
            }

            $contentRange = $response->header('Content-Range');

            if ($contentRange && preg_match('/\/(\d+|\*)$/', $contentRange, $matches) && $matches[1] !== '*') {
                return (int) $matches[1];
            }

            return count($response->json() ?? []);
        } catch (\Throwable $exception) {
            report($exception);
            return 0;
        }
    }

    private function fetchSchoolIdentity(?string $schoolId): array
    {
        if (! $schoolId) {
            return [];
        }

        $rows = $this->safeRows('schools', [
            'select' => 'name,logo',
            'school_id' => 'eq.' . $schoolId,
            'limit' => 1,
        ]);

        return $rows[0] ?? [];
    }

    private function safeRows(string $table, array $query = []): array
    {
        try {
            $response = Http::withHeaders($this->supabaseHeaders())->get($this->supabaseUrl() . '/rest/v1/' . $table, $query);

            return $response->successful() ? ($response->json() ?? []) : [];
        } catch (\Throwable $exception) {
            report($exception);
            return [];
        }
    }

}
