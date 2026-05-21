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
        $sections = $selectedYearId
            ? $this->fetchSections($schoolId, $selectedYearId)
            : [];

        $groupedSections = $this->groupSectionsByGrade($gradeLevels, $sections);

        if ($request->boolean('ajax')) {
            return response()->json([
                'success' => true,
                'schoolYears' => $schoolYears,
                'selectedYearId' => $selectedYearId,
                'grades' => $groupedSections,
            ]);
        }

        return view('pages.school-admin.school-admin-classes', [
            'menuGroups' => $menuGroups,
            'schoolYears' => $schoolYears,
            'selectedYearId' => $selectedYearId,
            'grades' => $groupedSections,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'year_id' => 'required|uuid',
            'grade_level_id' => 'required|uuid',
            'section_name' => 'required|string|max:255',
            'adviser_name' => 'nullable|string|max:255',
        ]);

        $activeDesignation = session('active_designation', []);
        $schoolId = $activeDesignation['school_id'] ?? null;

        if (! $schoolId) {
            return response()->json([
                'success' => false,
                'message' => 'No school assigned to your account.',
            ], 422);
        }

        if (! $this->gradeBelongsToSchool($schoolId, $request->grade_level_id)) {
            return response()->json([
                'success' => false,
                'message' => 'The selected grade level is not assigned to your school.',
                'errors' => [
                    'grade_level_id' => ['The selected grade level is not assigned to your school.'],
                ],
            ], 422);
        }

        $sectionName = trim($request->section_name);
        $adviserName = $request->filled('adviser_name')
            ? trim($request->adviser_name)
            : null;

        $duplicateCheck = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/class_sections', [
                'school_id' => 'eq.' . $schoolId,
                'year_id' => 'eq.' . $request->year_id,
                'grade_level_id' => 'eq.' . $request->grade_level_id,
                'section_name' => 'eq.' . $sectionName,
                'select' => 'section_id',
                'limit' => 1,
            ]);

        if (! $duplicateCheck->successful()) {
            report('Failed to check duplicate section: ' . $duplicateCheck->body());

            return response()->json([
                'success' => false,
                'message' => 'Failed to validate section name.',
            ], 500);
        }

        if (! empty($duplicateCheck->json())) {
            return response()->json([
                'success' => false,
                'message' => 'Section already exists for this grade and school year.',
                'errors' => [
                    'section_name' => ['Section already exists for this grade and school year.'],
                ],
            ], 422);
        }

        $sectionResponse = Http::withHeaders(array_merge($this->supabaseHeaders(), [
            'Prefer' => 'return=representation',
            'Content-Type' => 'application/json',
        ]))->post($this->supabaseUrl() . '/rest/v1/class_sections', [
            'school_id' => $schoolId,
            'year_id' => $request->year_id,
            'grade_level_id' => $request->grade_level_id,
            'section_name' => $sectionName,
            'adviser_name' => $adviserName,
            'status' => 'active',
        ]);

        if (! $sectionResponse->successful()) {
            report('Failed to create section: ' . $sectionResponse->body());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create section.',
            ], 500);
        }

        $section = $sectionResponse->json()[0] ?? null;

        if (! $section) {
            return response()->json([
                'success' => false,
                'message' => 'Section was created but could not be loaded.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Section created successfully.',
            'section' => $this->formatSection($section),
        ]);
    }

    public function update(Request $request, string $sectionId)
    {
        $request->validate([
            'year_id' => 'required|uuid',
            'grade_level_id' => 'required|uuid',
            'section_name' => 'required|string|max:255',
            'adviser_name' => 'nullable|string|max:255',
        ]);

        $activeDesignation = session('active_designation', []);
        $schoolId = $activeDesignation['school_id'] ?? null;

        if (! $schoolId) {
            return response()->json([
                'success' => false,
                'message' => 'No school assigned to your account.',
            ], 422);
        }

        $existingSection = $this->findSection($schoolId, $sectionId);

        if (! $existingSection) {
            return response()->json([
                'success' => false,
                'message' => 'Section not found in your school.',
            ], 404);
        }

        if (! $this->gradeBelongsToSchool($schoolId, $request->grade_level_id)) {
            return response()->json([
                'success' => false,
                'message' => 'The selected grade level is not assigned to your school.',
                'errors' => [
                    'grade_level_id' => ['The selected grade level is not assigned to your school.'],
                ],
            ], 422);
        }

        $sectionName = trim($request->section_name);
        $adviserName = $request->filled('adviser_name')
            ? trim($request->adviser_name)
            : null;

        $duplicateCheck = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/class_sections', [
                'school_id' => 'eq.' . $schoolId,
                'year_id' => 'eq.' . $request->year_id,
                'grade_level_id' => 'eq.' . $request->grade_level_id,
                'section_name' => 'eq.' . $sectionName,
                'section_id' => 'neq.' . $sectionId,
                'status' => 'neq.archived',
                'select' => 'section_id',
                'limit' => 1,
            ]);

        if (! $duplicateCheck->successful()) {
            report('Failed to validate section update duplicate: ' . $duplicateCheck->body());

            return response()->json([
                'success' => false,
                'message' => 'Failed to validate section name.',
            ], 500);
        }

        if (! empty($duplicateCheck->json())) {
            return response()->json([
                'success' => false,
                'message' => 'Another section already uses this name for the selected grade and school year.',
                'errors' => [
                    'section_name' => ['Another section already uses this name for the selected grade and school year.'],
                ],
            ], 422);
        }

        $response = Http::withHeaders(array_merge($this->supabaseHeaders(), [
            'Prefer' => 'return=representation',
            'Content-Type' => 'application/json',
        ]))->patch($this->supabaseUrl() . '/rest/v1/class_sections?section_id=eq.' . $sectionId . '&school_id=eq.' . $schoolId, [
            'year_id' => $request->year_id,
            'grade_level_id' => $request->grade_level_id,
            'section_name' => $sectionName,
            'adviser_name' => $adviserName,
            'updated_at' => now()->toIso8601String(),
        ]);

        if (! $response->successful()) {
            report('Failed to update section: ' . $response->body());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update section.',
            ], 500);
        }

        $section = $response->json()[0] ?? null;

        return response()->json([
            'success' => true,
            'message' => 'Section updated successfully.',
            'section' => $this->formatSection($section ?: array_merge($existingSection, [
                'year_id' => $request->year_id,
                'grade_level_id' => $request->grade_level_id,
                'section_name' => $sectionName,
                'adviser_name' => $adviserName,
            ])),
        ]);
    }

    public function archive(Request $request, string $sectionId)
    {
        $request->validate([
            'year_id' => 'required|uuid',
        ]);

        $activeDesignation = session('active_designation', []);
        $schoolId = $activeDesignation['school_id'] ?? null;

        if (! $schoolId) {
            return response()->json([
                'success' => false,
                'message' => 'No school assigned to your account.',
            ], 422);
        }

        $sectionResponse = Http::withHeaders(array_merge($this->supabaseHeaders(), [
            'Prefer' => 'return=representation',
            'Content-Type' => 'application/json',
        ]))->patch(
            $this->supabaseUrl() . '/rest/v1/class_sections?section_id=eq.' . $sectionId . '&school_id=eq.' . $schoolId,
            [
                'status' => 'archived',
                'updated_at' => now()->toIso8601String(),
            ]
        );

        if (! $sectionResponse->successful()) {
            report('Failed to archive section: ' . $sectionResponse->body());

            return response()->json([
                'success' => false,
                'message' => 'Failed to archive section.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Section archived successfully.',
            'section_id' => $sectionId,
        ]);
    }

    public function destroy(string $sectionId)
    {
        $activeDesignation = session('active_designation', []);
        $schoolId = $activeDesignation['school_id'] ?? null;

        if (! $schoolId) {
            return response()->json([
                'success' => false,
                'message' => 'No school assigned to your account.',
            ], 422);
        }

        $response = Http::withHeaders(array_merge($this->supabaseHeaders(), [
            'Prefer' => 'return=minimal',
            'Content-Type' => 'application/json',
        ]))->delete(
            $this->supabaseUrl() . '/rest/v1/class_sections?section_id=eq.' . $sectionId . '&school_id=eq.' . $schoolId
        );

        if (! $response->successful()) {
            report('Failed to delete section: ' . $response->body());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete section. This section may already be used by pupils or other records.',
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
            'adviser_name' => 'nullable|string|max:255',
        ]);

        $activeDesignation = session('active_designation', []);
        $schoolId = $activeDesignation['school_id'] ?? null;

        if (! $schoolId) {
            return response()->json([
                'success' => false,
                'message' => 'No school assigned to your account.',
            ], 422);
        }

        $adviserName = $request->filled('adviser_name')
            ? trim($request->adviser_name)
            : null;

        $response = Http::withHeaders(array_merge($this->supabaseHeaders(), [
            'Prefer' => 'return=representation',
            'Content-Type' => 'application/json',
        ]))->patch(
            $this->supabaseUrl() . '/rest/v1/class_sections?section_id=eq.' . $sectionId . '&school_id=eq.' . $schoolId,
            [
                'adviser_name' => $adviserName,
                'updated_at' => now()->toIso8601String(),
            ]
        );

        if (! $response->successful()) {
            report('Failed to update adviser name: ' . $response->body());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update adviser.',
            ], 422);
        }

        $section = $response->json()[0] ?? null;

        return response()->json([
            'success' => true,
            'message' => 'Adviser updated successfully.',
            'section_id' => $sectionId,
            'adviser_name' => $section['adviser_name'] ?? $adviserName,
        ]);
    }

    private function groupSectionsByGrade(array $gradeLevels, array $sections): array
    {
        return collect($gradeLevels)->map(function ($grade) use ($sections) {
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
    }

    private function fetchSchoolYears(): array
    {
        $response = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/school_year', [
                'select' => 'year_id,start_date,end_date',
                'order' => 'start_date.desc',
            ]);

        return $response->successful() ? $response->json() : [];
    }

    private function fetchGradeLevels(string $schoolId): array
    {
        $response = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/grade_levels', [
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
            ->get($this->supabaseUrl() . '/rest/v1/class_sections', [
                'school_id' => 'eq.' . $schoolId,
                'year_id' => 'eq.' . $yearId,
                'select' => 'section_id,school_id,year_id,grade_level_id,section_name,adviser_name,status,created_at,updated_at',
                'order' => 'section_name.asc',
            ]);

        if (! $sectionsResponse->successful()) {
            report('Failed to fetch sections: ' . $sectionsResponse->body());
            return [];
        }

        return collect($sectionsResponse->json())
            ->map(fn ($section) => $this->formatSection($section))
            ->values()
            ->all();
    }

    private function gradeBelongsToSchool(string $schoolId, string $gradeLevelId): bool
    {
        $response = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/grade_levels', [
                'school_id' => 'eq.' . $schoolId,
                'grade_level_id' => 'eq.' . $gradeLevelId,
                'select' => 'grade_level_id',
                'limit' => 1,
            ]);

        return $response->successful() && ! empty($response->json());
    }

    private function findSection(string $schoolId, string $sectionId): ?array
    {
        $response = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/class_sections', [
                'school_id' => 'eq.' . $schoolId,
                'section_id' => 'eq.' . $sectionId,
                'select' => 'section_id,school_id,year_id,grade_level_id,section_name,adviser_name,status,created_at,updated_at',
                'limit' => 1,
            ]);

        if (! $response->successful()) {
            report('Failed to find section: ' . $response->body());
            return null;
        }

        return $response->json()[0] ?? null;
    }

    private function formatSection(array $section): array
    {
        return [
            'section_id' => $section['section_id'],
            'school_id' => $section['school_id'] ?? null,
            'year_id' => $section['year_id'] ?? null,
            'grade_level_id' => $section['grade_level_id'],
            'section_name' => $section['section_name'],
            'adviser_name' => $section['adviser_name'] ?? null,
            'status' => $section['status'] ?? 'active',
            'created_at' => $section['created_at'] ?? null,
            'updated_at' => $section['updated_at'] ?? null,
        ];
    }

    private function supabaseUrl(): string
    {
        return rtrim(env('SUPABASE_URL'), '/');
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
