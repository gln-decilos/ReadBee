<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Helpers\SchoolAdminMenuHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SchoolAdminClassController extends Controller
{
    public function index(Request $request)
    {
        $menuGroups = SchoolAdminMenuHelper::getMenuGroups();
        $activeDesignation = session('active_designation', []);
        $schoolId = $activeDesignation['school_id'] ?? null;

        if (! $schoolId) {
            if ($request->boolean('ajax')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No school assigned to your account.',
                ], 422);
            }

            return redirect()
                ->route('school-admin.dashboard')
                ->with('error', 'No school assigned to your account.');
        }

        $schoolYears = $this->fetchSchoolYears();
        $selectedYearId = $request->get('year_id') ?: ($schoolYears[0]['year_id'] ?? null);

        $gradeLevels = $this->fetchGradeLevels($schoolId);
        $teachers = $this->fetchTeachers($schoolId);
        $sections = $selectedYearId
            ? $this->fetchSections($schoolId, $selectedYearId)
            : [];

        $groupedSections = collect($gradeLevels)->map(function ($grade) use ($sections) {
            $gradeSections = collect($sections)
                ->where('grade_level_id', $grade['grade_level_id'])
                ->values()
                ->all();

            return [
                'grade_level_id' => $grade['grade_level_id'],
                'grade_number' => $grade['grade_number'],
                'sections' => $gradeSections,
            ];
        })->values()->all();

        if ($request->boolean('ajax')) {
            return response()->json([
                'success' => true,
                'schoolYears' => $schoolYears,
                'selectedYearId' => $selectedYearId,
                'grades' => $groupedSections,
                'teachers' => $teachers,
            ]);
        }

        return view('pages.school-admin.school-admin-classes', [
            'menuGroups' => $menuGroups,
            'schoolYears' => $schoolYears,
            'selectedYearId' => $selectedYearId,
            'grades' => $groupedSections,
            'teachers' => $teachers,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'year_id' => 'required|uuid',
            'grade_level_id' => 'required|uuid',
            'section_name' => 'required|string|max:255',
            'adviser_user_id' => 'nullable|uuid',
        ]);

        $activeDesignation = session('active_designation', []);
        $schoolId = $activeDesignation['school_id'] ?? null;

        if (! $schoolId) {
            return response()->json([
                'success' => false,
                'message' => 'No school assigned to your account.',
            ], 422);
        }

        $sectionName = trim($request->section_name);

        $duplicateCheck = Http::withHeaders($this->supabaseHeaders())
            ->get(env('SUPABASE_URL') . '/rest/v1/class_sections', [
                'school_id' => 'eq.' . $schoolId,
                'year_id' => 'eq.' . $request->year_id,
                'grade_level_id' => 'eq.' . $request->grade_level_id,
                'section_name' => 'eq.' . $sectionName,
                'select' => 'section_id',
                'limit' => 1,
            ]);

        if ($duplicateCheck->successful() && ! empty($duplicateCheck->json())) {
            return response()->json([
                'success' => false,
                'message' => 'Section already exists for this grade and school year.',
            ], 422);
        }

        $sectionResponse = Http::withHeaders(array_merge($this->supabaseHeaders(), [
            'Prefer' => 'return=representation',
            'Content-Type' => 'application/json',
        ]))->post(env('SUPABASE_URL') . '/rest/v1/class_sections', [
            'school_id' => $schoolId,
            'year_id' => $request->year_id,
            'grade_level_id' => $request->grade_level_id,
            'section_name' => $sectionName,
            'status' => 'active',
        ]);

        if (! $sectionResponse->successful()) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create section.',
            ], 422);
        }

        $section = $sectionResponse->json()[0] ?? null;

        if (! $section) {
            return response()->json([
                'success' => false,
                'message' => 'Section was created but could not be loaded.',
            ], 422);
        }

        $adviserProfile = null;

        if ($request->filled('adviser_user_id')) {
            $assigned = $this->replaceActiveAdviser($section['section_id'], $request->adviser_user_id);

            if ($assigned) {
                $adviserProfile = $this->fetchProfileByUserId($request->adviser_user_id);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Section created successfully.',
            'section' => [
                'section_id' => $section['section_id'],
                'grade_level_id' => $section['grade_level_id'],
                'section_name' => $section['section_name'],
                'status' => $section['status'],
                'created_at' => $section['created_at'],
                'adviser_user_id' => $request->adviser_user_id ?: null,
                'adviser_name' => $adviserProfile['full_name'] ?? null,
                'adviser_email' => $adviserProfile['email'] ?? null,
            ],
        ]);
    }

    public function archive(Request $request, string $sectionId)
    {
        $request->validate([
            'year_id' => 'required|uuid',
        ]);

        $sectionResponse = Http::withHeaders(array_merge($this->supabaseHeaders(), [
            'Prefer' => 'return=representation',
            'Content-Type' => 'application/json',
        ]))->patch(
            env('SUPABASE_URL') . '/rest/v1/class_sections?section_id=eq.' . $sectionId,
            ['status' => 'archived']
        );

        if (! $sectionResponse->successful()) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to archive section.',
            ], 422);
        }

        Http::withHeaders(array_merge($this->supabaseHeaders(), [
            'Prefer' => 'return=minimal',
            'Content-Type' => 'application/json',
        ]))->patch(
            env('SUPABASE_URL') . '/rest/v1/section_advisers?section_id=eq.' . $sectionId . '&is_active=eq.true',
            [
                'is_active' => false,
                'unassigned_at' => now()->toIso8601String(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Section archived successfully.',
            'section_id' => $sectionId,
        ]);
    }

    public function destroy(string $sectionId)
    {
        Http::withHeaders(array_merge($this->supabaseHeaders(), [
            'Prefer' => 'return=minimal',
            'Content-Type' => 'application/json',
        ]))->delete(
            env('SUPABASE_URL') . '/rest/v1/section_advisers?section_id=eq.' . $sectionId
        );

        $response = Http::withHeaders(array_merge($this->supabaseHeaders(), [
            'Prefer' => 'return=minimal',
            'Content-Type' => 'application/json',
        ]))->delete(
            env('SUPABASE_URL') . '/rest/v1/class_sections?section_id=eq.' . $sectionId
        );

        if (! $response->successful()) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete section.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Section deleted successfully.',
            'section_id' => $sectionId,
        ]);
    }

    public function assignAdviser(Request $request, string $sectionId)
    {
        $request->validate([
            'adviser_user_id' => 'nullable|uuid',
        ]);

        Http::withHeaders(array_merge($this->supabaseHeaders(), [
            'Prefer' => 'return=minimal',
            'Content-Type' => 'application/json',
        ]))->patch(
            env('SUPABASE_URL') . '/rest/v1/section_advisers?section_id=eq.' . $sectionId . '&is_active=eq.true',
            [
                'is_active' => false,
                'unassigned_at' => now()->toIso8601String(),
            ]
        );

        $profile = null;

        if ($request->filled('adviser_user_id')) {
            $assignResult = $this->replaceActiveAdviser($sectionId, $request->adviser_user_id);

            if (! $assignResult) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to assign adviser.',
                ], 422);
            }

            $profile = $this->fetchProfileByUserId($request->adviser_user_id);
        }

        return response()->json([
            'success' => true,
            'message' => 'Adviser updated successfully.',
            'section_id' => $sectionId,
            'adviser_user_id' => $request->adviser_user_id ?: null,
            'adviser_name' => $profile['full_name'] ?? null,
            'adviser_email' => $profile['email'] ?? null,
        ]);
    }

    private function fetchSchoolYears(): array
    {
        $response = Http::withHeaders($this->supabaseHeaders())
            ->get(env('SUPABASE_URL') . '/rest/v1/school_year', [
                'select' => 'year_id,start_date,end_date',
                'order' => 'start_date.desc',
            ]);

        return $response->successful() ? $response->json() : [];
    }

    private function fetchGradeLevels(string $schoolId): array
    {
        $response = Http::withHeaders($this->supabaseHeaders())
            ->get(env('SUPABASE_URL') . '/rest/v1/grade_levels', [
                'school_id' => 'eq.' . $schoolId,
                'is_active' => 'eq.true',
                'select' => 'grade_level_id,grade_number',
                'order' => 'grade_number.asc',
            ]);

        return $response->successful() ? $response->json() : [];
    }

    private function fetchSections(string $schoolId, string $yearId): array
    {
        $sectionsResponse = Http::withHeaders($this->supabaseHeaders())
            ->get(env('SUPABASE_URL') . '/rest/v1/class_sections', [
                'school_id' => 'eq.' . $schoolId,
                'year_id' => 'eq.' . $yearId,
                'select' => 'section_id,grade_level_id,section_name,status,created_at',
                'order' => 'section_name.asc',
            ]);

        $sections = $sectionsResponse->successful() ? $sectionsResponse->json() : [];

        if (empty($sections)) {
            return [];
        }

        $sectionIds = collect($sections)->pluck('section_id')->filter()->values()->all();
        $quotedSectionIds = implode(',', array_map(fn ($id) => '"' . $id . '"', $sectionIds));

        $activeAdvisersResponse = Http::withHeaders($this->supabaseHeaders())
            ->get(env('SUPABASE_URL') . '/rest/v1/section_advisers', [
                'section_id' => 'in.(' . $quotedSectionIds . ')',
                'is_active' => 'eq.true',
                'select' => 'section_id,adviser_user_id',
            ]);

        $activeAdvisers = $activeAdvisersResponse->successful() ? $activeAdvisersResponse->json() : [];
        $adviserIds = collect($activeAdvisers)->pluck('adviser_user_id')->filter()->unique()->values()->all();

        $profilesById = collect();

        if (! empty($adviserIds)) {
            $quotedAdviserIds = implode(',', array_map(fn ($id) => '"' . $id . '"', $adviserIds));

            $profilesResponse = Http::withHeaders($this->supabaseHeaders())
                ->get(env('SUPABASE_URL') . '/rest/v1/profiles', [
                    'id' => 'in.(' . $quotedAdviserIds . ')',
                    'select' => 'id,full_name,email',
                ]);

            if ($profilesResponse->successful()) {
                $profilesById = collect($profilesResponse->json())->keyBy('id');
            }
        }

        $activeAdvisersBySection = collect($activeAdvisers)->keyBy('section_id');

        return collect($sections)->map(function ($section) use ($activeAdvisersBySection, $profilesById) {
            $assignment = $activeAdvisersBySection->get($section['section_id']);
            $profile = $assignment ? $profilesById->get($assignment['adviser_user_id']) : null;

            return [
                'section_id' => $section['section_id'],
                'grade_level_id' => $section['grade_level_id'],
                'section_name' => $section['section_name'],
                'status' => $section['status'],
                'created_at' => $section['created_at'],
                'adviser_user_id' => $assignment['adviser_user_id'] ?? null,
                'adviser_name' => $profile['full_name'] ?? null,
                'adviser_email' => $profile['email'] ?? null,
            ];
        })->values()->all();
    }

    private function fetchTeachers(string $schoolId): array
    {
        $rolesResponse = Http::withHeaders($this->supabaseHeaders())
            ->get(env('SUPABASE_URL') . '/rest/v1/roles', [
                'name' => 'eq.Teacher',
                'select' => 'id,name',
                'limit' => 1,
            ]);

        $teacherRole = $rolesResponse->successful() ? ($rolesResponse->json()[0] ?? null) : null;

        if (! $teacherRole) {
            return [];
        }

        $userRolesResponse = Http::withHeaders($this->supabaseHeaders())
            ->get(env('SUPABASE_URL') . '/rest/v1/user_roles', [
                'school_id' => 'eq.' . $schoolId,
                'role_id' => 'eq.' . $teacherRole['id'],
                'select' => 'user_id',
            ]);

        $teacherUserIds = $userRolesResponse->successful()
            ? collect($userRolesResponse->json())->pluck('user_id')->filter()->unique()->values()->all()
            : [];

        if (empty($teacherUserIds)) {
            return [];
        }

        $quotedIds = implode(',', array_map(fn ($id) => '"' . $id . '"', $teacherUserIds));

        $profilesResponse = Http::withHeaders($this->supabaseHeaders())
            ->get(env('SUPABASE_URL') . '/rest/v1/profiles', [
                'id' => 'in.(' . $quotedIds . ')',
                'select' => 'id,full_name,email',
                'order' => 'full_name.asc',
            ]);

        return $profilesResponse->successful() ? $profilesResponse->json() : [];
    }

    private function fetchProfileByUserId(string $userId): ?array
    {
        $response = Http::withHeaders($this->supabaseHeaders())
            ->get(env('SUPABASE_URL') . '/rest/v1/profiles', [
                'id' => 'eq.' . $userId,
                'select' => 'id,full_name,email',
                'limit' => 1,
            ]);

        return $response->successful() ? ($response->json()[0] ?? null) : null;
    }

    private function replaceActiveAdviser(string $sectionId, string $adviserUserId): bool
    {
        $response = Http::withHeaders(array_merge($this->supabaseHeaders(), [
            'Prefer' => 'return=representation',
            'Content-Type' => 'application/json',
        ]))->post(env('SUPABASE_URL') . '/rest/v1/section_advisers', [
            'section_id' => $sectionId,
            'adviser_user_id' => $adviserUserId,
            'is_active' => true,
        ]);

        return $response->successful();
    }

    private function supabaseHeaders(): array
    {
        return [
            'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
            'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
            'Accept' => 'application/json',
        ];
    }
}
