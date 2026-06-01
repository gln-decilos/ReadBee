<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Helpers\DistrictAdminMenuHelper;

class DistrictAdminController extends Controller
{
    public function dashboard()
    {
        $menuGroups = DistrictAdminMenuHelper::getMenuGroups();
        $activeDesignation = session('active_designation', []);
        $districtId = $activeDesignation['district_id'] ?? null;
        $districtName = $activeDesignation['district_name'] ?? $this->fetchDistrictName($districtId) ?? 'Your District';

        $dashboard = [
            'districtName' => $districtName,
            'schools' => $this->safeCount('schools', $districtId ? ['district_id' => 'eq.' . $districtId] : [], 'school_id'),
            'municipalities' => $this->safeCount('municipalities', $districtId ? ['district_id' => 'eq.' . $districtId] : [], 'municipality_id'),
            'users' => $this->safeCount('user_roles', $districtId ? ['district_id' => 'eq.' . $districtId] : [], 'user_role_id'),
            'schoolYears' => $this->safeCount('school_year', [], 'year_id'),
            'recentSchools' => $this->safeRows('schools', [
                'select' => 'school_id,name,logo,address,email,created_at,municipalities(municipal_name)',
                'order' => 'created_at.desc',
                'limit' => 5,
            ] + ($districtId ? ['district_id' => 'eq.' . $districtId] : [])),
        ];

        return view(
            'pages.district-admin.district-admin-dashboard',
            compact('menuGroups', 'dashboard')
        );
    }

    public function profile()
    {
        $menuGroups = DistrictAdminMenuHelper::getMenuGroups();

        return view(
            'pages.district-admin.district-admin-profile',
            compact('menuGroups')
        );
    }

    public function users()
{
    $menuGroups = DistrictAdminMenuHelper::getMenuGroups();

    $usersResponse = Http::withHeaders([
        'apikey' => env('SUPABASE_ANON_KEY'),
        'Authorization' => 'Bearer ' . env('SUPABASE_ANON_KEY'),
        'Accept' => 'application/json',
    ])->get(
        env('SUPABASE_URL') . '/rest/v1/profiles?select=*&order=full_name.asc'
    );

    $users = $usersResponse->successful()
        ? $usersResponse->json()
        : [];

    return view(
        'pages.district-admin.district-admin-users',
        [
            'menuGroups' => $menuGroups,
            'users' => $users,
            'page' => 1,
            'perPage' => 5,
        ]
    );
}

    public function municipalities()
{
    $menuGroups = DistrictAdminMenuHelper::getMenuGroups();

    $activeDesignation = session('active_designation', []);
    $districtId = $activeDesignation['district_id'] ?? null;
    $districtName = $activeDesignation['district_name'] ?? null;

    if (! $districtId) {
        return redirect()
            ->route('district-admin.district-admin-dashboard')
            ->with('error', 'No district assigned to your account.');
    }

    if (! $districtName) {
        $districtResponse = Http::withHeaders([
            'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
            'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
            'Accept' => 'application/json',
        ])->get(
            env('SUPABASE_URL') . '/rest/v1/districts',
            [
                'district_id' => 'eq.' . $districtId,
                'select' => 'district_id,district_name',
            ]
        );

        if ($districtResponse->successful()) {
            $district = $districtResponse->json()[0] ?? null;
            $districtName = $district['district_name'] ?? 'Your Assigned District';
        } else {
            $districtName = 'Your Assigned District';
        }
    }

    $municipalitiesResponse = Http::withHeaders([
        'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
        'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
        'Accept' => 'application/json',
    ])->get(
        env('SUPABASE_URL') . '/rest/v1/municipalities',
        [
            'select' => 'municipality_id,municipal_name,district_id,logo,districts(district_name)',
            'district_id' => 'eq.' . $districtId,
            'order' => 'municipal_name.asc',
        ]
    );

    $municipalities = $municipalitiesResponse->successful()
            ? $municipalitiesResponse->json()
            : [];

        return view('pages.district-admin.district-admin-municipality', [
            'title' => 'Municipality Management',
            'menuGroups' => $menuGroups,
            'municipalities' => $municipalities,
            'districtName' => $districtName,
            'page' => 1,
            'perPage' => 5,
        ]);
    }

    public function schools()
{
    $menuGroups = DistrictAdminMenuHelper::getMenuGroups();

    $activeDesignation = session('active_designation', []);
    $districtId = $activeDesignation['district_id'] ?? null;
    $districtName = $activeDesignation['district_name'] ?? null;

    if (! $districtId) {
        return redirect()
            ->route('district-admin.district-admin-dashboard')
            ->with('error', 'No district assigned to your account.');
    }

    if (! $districtName) {
        $districtResponse = Http::withHeaders([
            'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
            'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
            'Accept' => 'application/json',
        ])->get(
            env('SUPABASE_URL') . '/rest/v1/districts',
            [
                'district_id' => 'eq.' . $districtId,
                'select' => 'district_id,district_name',
            ]
        );

        if ($districtResponse->successful()) {
            $district = $districtResponse->json()[0] ?? null;
            $districtName = $district['district_name'] ?? 'Your Assigned District';
        } else {
            $districtName = 'Your Assigned District';
        }
    }

    $schoolsResponse = Http::withHeaders([
        'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
        'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
        'Accept' => 'application/json',
    ])->get(
        env('SUPABASE_URL') . '/rest/v1/schools',
        [
            'select' => 'school_id,name,logo,address,contact,email,district_id,municipality_id,districts(district_name),municipalities(municipal_name)',
            'district_id' => 'eq.' . $districtId,
            'order' => 'name.asc'
        ]
    );

    $schools = $schoolsResponse->successful()
        ? $schoolsResponse->json()
        : [];

    $municipalitiesResponse = Http::withHeaders([
        'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
        'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
        'Accept' => 'application/json',
    ])->get(
        env('SUPABASE_URL') . '/rest/v1/municipalities',
        [
            'select' => 'municipality_id,municipal_name,district_id',
            'district_id' => 'eq.' . $districtId,
            'order' => 'municipal_name.asc'
        ]
    );

    $municipalities = $municipalitiesResponse->successful()
        ? $municipalitiesResponse->json()
        : [];

    return view('pages.district-admin.district-admin-schools', [
        'title' => 'School Management',
        'menuGroups' => $menuGroups,
        'schools' => $schools,
        'municipalities' => $municipalities,
        'districtName' => $districtName,
        'page' => 1,
        'perPage' => 5,
    ]);
}

    public function schoolYears()
    {
        $menuGroups = DistrictAdminMenuHelper::getMenuGroups();

        $schoolYearsResponse = Http::withHeaders([
            'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
            'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
            'Accept' => 'application/json',
        ])->get(
            env('SUPABASE_URL') . '/rest/v1/school_year?select=year_id,start_date,end_date,created_at,quarter(quarter_id,quarter_number,quarter_name,start_date,end_date)&order=start_date.desc'
        );

        $schoolYears = $schoolYearsResponse->successful()
            ? $schoolYearsResponse->json()
            : [];

        return view('pages.district-admin.district-admin-school-year', [
            'title' => 'School Year Management',
            'menuGroups' => $menuGroups,
            'schoolYears' => $schoolYears,
            'page' => 1,
            'perPage' => 6,
        ]);
    }

    private function extractTotalCount($contentRange)
    {
        if ($contentRange && preg_match('/\/(\d+)$/', $contentRange, $matches)) {
            return (int) $matches[1];
        }

        return 0;
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

    private function fetchDistrictName(?string $districtId): ?string
    {
        if (! $districtId) {
            return null;
        }

        $rows = $this->safeRows('districts', [
            'select' => 'district_name',
            'district_id' => 'eq.' . $districtId,
            'limit' => 1,
        ]);

        return $rows[0]['district_name'] ?? null;
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
