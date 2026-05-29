<?php

namespace App\Http\Controllers\DistrictSupervisor;

use App\Helpers\DistrictSupervisorMenuHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class DistrictSupervisorDashboardController extends Controller
{
    public function index(Request $request)
    {
        $menuGroups = DistrictSupervisorMenuHelper::getMenuGroups();
        $scope = $this->districtSupervisorScope();

        if (empty($scope['district_id']) && empty($scope['municipality_id'])) {
            if ($request->expectsJson() || $request->query('ajax')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your district supervisor account is not connected to a district or municipality. Please sign in again or contact the administrator.',
                ], 401);
            }

            return redirect()->route('signin')
                ->with('error', 'Please sign in as a district supervisor to view the dashboard.');
        }

        $dashboardData = $this->buildDashboardData($scope, $request);

        if ($request->expectsJson() || $request->query('ajax')) {
            return response()->json([
                'success' => true,
                'dashboardData' => $dashboardData,
            ]);
        }

        return view('pages.district-supervisor.district-supervisor-dashboard', [
            'title' => 'District Supervisor Dashboard',
            'menuGroups' => $menuGroups,
            'dashboardData' => $dashboardData,
        ]);
    }

    private function buildDashboardData(array $scope, Request $request): array
    {
        $allAssignments = $this->fetchDistrictScopeAssignments($scope);
        $lookup = $this->buildLookup($allAssignments);
        $filters = $this->normalizeFilters($request, $allAssignments, $lookup);
        $options = $this->buildFilterOptions($allAssignments, $lookup, $filters);
        $assignments = $this->filterAssignments($allAssignments, $lookup, $filters);
        $metrics = $this->buildMetrics($assignments, $lookup, $filters);

        return [
            'filters' => $filters,
            'options' => $options,
            'filterCatalog' => $this->buildFilterCatalog($allAssignments, $lookup),
            'activeLabels' => $this->activeFilterLabels($filters, $lookup),
            'summary' => $metrics['summary'],
            'cards' => $metrics['cards'],
            'chartData' => $metrics['chartData'],
            'attentionLists' => $metrics['attentionLists'],
            'updatedAt' => now()->format('M d, Y h:i A'),
        ];
    }


    /**
     * Build the district supervisor dashboard scope from the school's own sections and quarters.
     *
     * Important: this intentionally does not depend on assigned_evaluators. A district supervisor
     * should still see the selected school year, quarter, grade level, and section with
     * zero/no-data values even when no evaluator has been assigned yet.
     */
    /**
     * Build the district supervisor dashboard scope from the schools, sections, and quarters
     * under the supervisor's district/municipality. This does not depend on assigned_evaluators,
     * so a municipality, school, grade, or section still appears with zero/no-data values even
     * when no evaluator has been assigned yet.
     */
    private function fetchDistrictScopeAssignments(array $scope): array
    {
        $schoolsQuery = [
            'select' => 'school_id,name,address,contact,email,district_id,municipality_id',
            'order' => 'name.asc',
        ];

        if (! empty($scope['municipality_id'])) {
            $schoolsQuery['municipality_id'] = 'eq.' . $scope['municipality_id'];
        } elseif (! empty($scope['district_id'])) {
            $schoolsQuery['district_id'] = 'eq.' . $scope['district_id'];
        }

        $schoolsResponse = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/schools', $schoolsQuery);

        if (! $schoolsResponse->successful()) {
            report('Failed to fetch district supervisor dashboard schools: ' . $schoolsResponse->body());
            return [];
        }

        $schools = collect($schoolsResponse->json())
            ->filter(fn ($school) => ! empty($school['school_id']))
            ->keyBy('school_id');

        if ($schools->isEmpty()) {
            return [];
        }

        $schoolIds = $schools->keys()->values()->all();

        $sectionsResponse = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/class_sections', [
                'select' => 'section_id,school_id,year_id,grade_level_id,section_name,status,created_at,updated_at',
                'school_id' => 'in.(' . $this->postgrestInList($schoolIds) . ')',
                'status' => 'neq.archived',
                'order' => 'section_name.asc',
            ]);

        if (! $sectionsResponse->successful()) {
            report('Failed to fetch district supervisor dashboard sections: ' . $sectionsResponse->body());
            return [];
        }

        $sections = collect($sectionsResponse->json())
            ->filter(fn ($section) => ! empty($section['section_id']) && ! empty($section['year_id']) && ! empty($section['school_id']))
            ->values();

        if ($sections->isEmpty()) {
            return [];
        }

        $yearIds = $sections->pluck('year_id')->filter()->unique()->values()->all();

        $quartersResponse = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/quarter', [
                'select' => 'quarter_id,year_id,quarter_number,quarter_name,start_date,end_date',
                'year_id' => 'in.(' . $this->postgrestInList($yearIds) . ')',
                'order' => 'quarter_number.asc',
            ]);

        if (! $quartersResponse->successful()) {
            report('Failed to fetch district supervisor dashboard quarters: ' . $quartersResponse->body());
            return [];
        }

        $quartersByYear = collect($quartersResponse->json())
            ->filter(fn ($quarter) => ! empty($quarter['quarter_id']) && ! empty($quarter['year_id']))
            ->groupBy('year_id');

        return $sections
            ->flatMap(function ($section) use ($quartersByYear, $schools) {
                $quarters = $quartersByYear->get($section['year_id'], collect());
                $school = $schools->get($section['school_id'], []);

                return $quarters->map(function ($quarter) use ($section, $school) {
                    return [
                        'assignment_id' => null,
                        'schedule_id' => null,
                        'evaluator_user_id' => null,
                        'municipality_id' => $school['municipality_id'] ?? null,
                        'school_id' => $section['school_id'] ?? null,
                        'section_id' => $section['section_id'] ?? null,
                        'year_id' => $section['year_id'] ?? null,
                        'quarter_id' => $quarter['quarter_id'] ?? null,
                        'assigned_at' => null,
                        'confirmation_status' => null,
                        'assessment_status' => null,
                        'report_status' => null,
                        'assessment_date' => null,
                        'created_at' => $section['created_at'] ?? null,
                        'updated_at' => $section['updated_at'] ?? null,
                    ];
                });
            })
            ->filter(fn ($scope) => ! empty($scope['school_id']) && ! empty($scope['section_id']) && ! empty($scope['year_id']) && ! empty($scope['quarter_id']))
            ->values()
            ->all();
    }


    private function buildLookup(array $assignments): array
    {
        $sectionIds = collect($assignments)->pluck('section_id')->filter()->unique()->values()->all();
        $yearIds = collect($assignments)->pluck('year_id')->filter()->unique()->values()->all();
        $quarterIds = collect($assignments)->pluck('quarter_id')->filter()->unique()->values()->all();
        $schoolIds = collect($assignments)->pluck('school_id')->filter()->unique()->values()->all();
        $municipalityIds = collect($assignments)->pluck('municipality_id')->filter()->unique()->values()->all();

        $sections = collect($this->fetchRowsByIds(
            'class_sections',
            'section_id',
            $sectionIds,
            'section_id,school_id,year_id,grade_level_id,section_name,status,adviser_name'
        ))->keyBy('section_id')->all();

        $gradeLevelIds = collect($sections)->pluck('grade_level_id')->filter()->unique()->values()->all();

        $gradeLevels = collect($this->fetchRowsByIds(
            'grade_levels',
            'grade_level_id',
            $gradeLevelIds,
            'grade_level_id,grade_number,school_id,is_active'
        ))->keyBy('grade_level_id')->all();

        $schoolYears = collect($this->fetchRowsByIds(
            'school_year',
            'year_id',
            $yearIds,
            'year_id,start_date,end_date,created_at'
        ))->map(function ($year) {
            return array_merge($year, ['label' => $this->schoolYearLabel($year)]);
        })->keyBy('year_id')->all();

        $quarters = collect($this->fetchRowsByIds(
            'quarter',
            'quarter_id',
            $quarterIds,
            'quarter_id,year_id,quarter_number,quarter_name,start_date,end_date'
        ))->map(function ($quarter) {
            return array_merge($quarter, ['label' => $this->quarterLabel($quarter)]);
        })->keyBy('quarter_id')->all();

        $schools = collect($this->fetchRowsByIds(
            'schools',
            'school_id',
            $schoolIds,
            'school_id,name,address,contact,email,logo,district_id,municipality_id'
        ))->map(function ($school) {
            return array_merge($school, ['label' => $school['name'] ?? 'School']);
        })->keyBy('school_id')->all();

        $municipalities = collect($this->fetchRowsByIds(
            'municipalities',
            'municipality_id',
            $municipalityIds,
            'municipality_id,municipal_name,district_id,logo'
        ))->map(function ($municipality) {
            return array_merge($municipality, ['label' => $municipality['municipal_name'] ?? 'Municipality']);
        })->keyBy('municipality_id')->all();

        return [
            'sections' => $sections,
            'gradeLevels' => $gradeLevels,
            'schoolYears' => $schoolYears,
            'quarters' => $quarters,
            'schools' => $schools,
            'municipalities' => $municipalities,
        ];
    }


    private function normalizeFilters(Request $request, array $assignments, array $lookup): array
    {
        $yearOptions = collect($assignments)
            ->pluck('year_id')
            ->filter()
            ->unique()
            ->sortByDesc(fn ($yearId) => $lookup['schoolYears'][$yearId]['start_date'] ?? '')
            ->values();

        $requestedYear = $request->query('school_year_id', $request->query('year_id'));
        $selectedYear = $yearOptions->contains($requestedYear) ? $requestedYear : $yearOptions->first();

        $validQuarterIds = collect($assignments)
            ->when($selectedYear, fn ($items) => $items->where('year_id', $selectedYear))
            ->pluck('quarter_id')
            ->filter()
            ->unique()
            ->values();

        $requestedQuarter = $request->query('quarter_id', 'all');
        $selectedQuarter = $validQuarterIds->contains($requestedQuarter) ? $requestedQuarter : 'all';

        $validMunicipalityIds = collect($assignments)
            ->when($selectedYear, fn ($items) => $items->where('year_id', $selectedYear))
            ->when($selectedQuarter !== 'all', fn ($items) => $items->where('quarter_id', $selectedQuarter))
            ->pluck('municipality_id')
            ->filter()
            ->unique()
            ->values();

        $requestedMunicipality = $request->query('municipality_id', 'all');
        $selectedMunicipality = $validMunicipalityIds->contains($requestedMunicipality) ? $requestedMunicipality : 'all';

        $validSchoolIds = collect($assignments)
            ->when($selectedYear, fn ($items) => $items->where('year_id', $selectedYear))
            ->when($selectedQuarter !== 'all', fn ($items) => $items->where('quarter_id', $selectedQuarter))
            ->when($selectedMunicipality !== 'all', fn ($items) => $items->where('municipality_id', $selectedMunicipality))
            ->pluck('school_id')
            ->filter()
            ->unique()
            ->values();

        $requestedSchool = $request->query('school_id', 'all');
        $selectedSchool = $validSchoolIds->contains($requestedSchool) ? $requestedSchool : 'all';

        $validGradeIds = collect($assignments)
            ->when($selectedYear, fn ($items) => $items->where('year_id', $selectedYear))
            ->when($selectedQuarter !== 'all', fn ($items) => $items->where('quarter_id', $selectedQuarter))
            ->when($selectedMunicipality !== 'all', fn ($items) => $items->where('municipality_id', $selectedMunicipality))
            ->when($selectedSchool !== 'all', fn ($items) => $items->where('school_id', $selectedSchool))
            ->map(fn ($assignment) => $lookup['sections'][$assignment['section_id'] ?? null]['grade_level_id'] ?? null)
            ->filter()
            ->unique()
            ->values();

        $requestedGrade = $request->query('grade_level_id', 'all');
        $selectedGrade = $validGradeIds->contains($requestedGrade) ? $requestedGrade : 'all';

        $validSectionIds = collect($assignments)
            ->when($selectedYear, fn ($items) => $items->where('year_id', $selectedYear))
            ->when($selectedQuarter !== 'all', fn ($items) => $items->where('quarter_id', $selectedQuarter))
            ->when($selectedMunicipality !== 'all', fn ($items) => $items->where('municipality_id', $selectedMunicipality))
            ->when($selectedSchool !== 'all', fn ($items) => $items->where('school_id', $selectedSchool))
            ->filter(function ($assignment) use ($lookup, $selectedGrade) {
                if ($selectedGrade === 'all') {
                    return true;
                }

                return ($lookup['sections'][$assignment['section_id'] ?? null]['grade_level_id'] ?? null) === $selectedGrade;
            })
            ->pluck('section_id')
            ->filter()
            ->unique()
            ->values();

        $requestedSection = $request->query('section_id', 'all');
        $selectedSection = $validSectionIds->contains($requestedSection) ? $requestedSection : 'all';

        $requestedLanguage = strtolower((string) $request->query('language', 'all'));
        $selectedLanguage = in_array($requestedLanguage, ['all', 'english', 'filipino'], true) ? $requestedLanguage : 'all';

        return [
            'school_year_id' => $selectedYear,
            'quarter_id' => $selectedQuarter,
            'municipality_id' => $selectedMunicipality,
            'school_id' => $selectedSchool,
            'grade_level_id' => $selectedGrade,
            'section_id' => $selectedSection,
            'language' => $selectedLanguage,
        ];
    }


    private function buildFilterOptions(array $assignments, array $lookup, array $filters): array
    {
        $base = collect($assignments)
            ->when($filters['school_year_id'] ?? null, fn ($items) => $items->where('year_id', $filters['school_year_id']))
            ->when(($filters['quarter_id'] ?? 'all') !== 'all', fn ($items) => $items->where('quarter_id', $filters['quarter_id']));

        $yearOptions = collect($assignments)
            ->pluck('year_id')
            ->filter()
            ->unique()
            ->map(fn ($yearId) => $lookup['schoolYears'][$yearId] ?? null)
            ->filter()
            ->sortByDesc('start_date')
            ->values()
            ->all();

        $quarters = collect($assignments)
            ->when($filters['school_year_id'] ?? null, fn ($items) => $items->where('year_id', $filters['school_year_id']))
            ->pluck('quarter_id')
            ->filter()
            ->unique()
            ->map(fn ($quarterId) => $lookup['quarters'][$quarterId] ?? null)
            ->filter()
            ->sortBy('quarter_number')
            ->values()
            ->all();

        $municipalities = $base
            ->pluck('municipality_id')
            ->filter()
            ->unique()
            ->map(fn ($municipalityId) => $lookup['municipalities'][$municipalityId] ?? null)
            ->filter()
            ->sortBy('municipal_name')
            ->values()
            ->all();

        $schools = $base
            ->when(($filters['municipality_id'] ?? 'all') !== 'all', fn ($items) => $items->where('municipality_id', $filters['municipality_id']))
            ->pluck('school_id')
            ->filter()
            ->unique()
            ->map(fn ($schoolId) => $lookup['schools'][$schoolId] ?? null)
            ->filter()
            ->sortBy('name')
            ->values()
            ->all();

        $gradeLevels = $base
            ->when(($filters['municipality_id'] ?? 'all') !== 'all', fn ($items) => $items->where('municipality_id', $filters['municipality_id']))
            ->when(($filters['school_id'] ?? 'all') !== 'all', fn ($items) => $items->where('school_id', $filters['school_id']))
            ->map(fn ($assignment) => $lookup['sections'][$assignment['section_id'] ?? null]['grade_level_id'] ?? null)
            ->filter()
            ->unique()
            ->map(fn ($gradeId) => $lookup['gradeLevels'][$gradeId] ?? null)
            ->filter()
            ->sortBy('grade_number')
            ->values()
            ->all();

        $sections = $base
            ->when(($filters['municipality_id'] ?? 'all') !== 'all', fn ($items) => $items->where('municipality_id', $filters['municipality_id']))
            ->when(($filters['school_id'] ?? 'all') !== 'all', fn ($items) => $items->where('school_id', $filters['school_id']))
            ->filter(function ($assignment) use ($lookup, $filters) {
                if (($filters['grade_level_id'] ?? 'all') === 'all') {
                    return true;
                }

                return ($lookup['sections'][$assignment['section_id'] ?? null]['grade_level_id'] ?? null) === $filters['grade_level_id'];
            })
            ->pluck('section_id')
            ->filter()
            ->unique()
            ->map(function ($sectionId) use ($lookup) {
                $section = $lookup['sections'][$sectionId] ?? null;
                if (! $section) {
                    return null;
                }

                $grade = $lookup['gradeLevels'][$section['grade_level_id'] ?? null] ?? [];
                $school = $lookup['schools'][$section['school_id'] ?? null] ?? [];

                return array_merge($section, [
                    'grade_number' => $grade['grade_number'] ?? null,
                    'school_name' => $school['name'] ?? null,
                    'label' => (isset($grade['grade_number']) ? 'Grade ' . $grade['grade_number'] . ' - ' : '') . ($section['section_name'] ?? 'Section'),
                ]);
            })
            ->filter()
            ->sortBy([
                ['grade_number', 'asc'],
                ['section_name', 'asc'],
            ])
            ->values()
            ->all();

        return [
            'schoolYears' => $yearOptions,
            'quarters' => $quarters,
            'municipalities' => $municipalities,
            'schools' => $schools,
            'gradeLevels' => $gradeLevels,
            'sections' => $sections,
            'languages' => [
                ['value' => 'all', 'label' => 'All Languages'],
                ['value' => 'english', 'label' => 'English'],
                ['value' => 'filipino', 'label' => 'Filipino'],
            ],
        ];
    }


    private function buildFilterCatalog(array $assignments, array $lookup): array
    {
        $catalogAssignments = collect($assignments)
            ->map(function ($assignment) use ($lookup) {
                $section = $lookup['sections'][$assignment['section_id'] ?? null] ?? [];

                return [
                    'assignment_id' => $assignment['assignment_id'] ?? null,
                    'year_id' => $assignment['year_id'] ?? null,
                    'quarter_id' => $assignment['quarter_id'] ?? null,
                    'municipality_id' => $assignment['municipality_id'] ?? null,
                    'school_id' => $assignment['school_id'] ?? null,
                    'grade_level_id' => $section['grade_level_id'] ?? null,
                    'section_id' => $assignment['section_id'] ?? null,
                ];
            })
            ->filter(fn ($assignment) => ! empty($assignment['year_id']))
            ->values()
            ->all();

        $schoolYears = collect($lookup['schoolYears'] ?? [])
            ->sortByDesc('start_date')
            ->values()
            ->all();

        $quarters = collect($lookup['quarters'] ?? [])
            ->sortBy([
                ['year_id', 'asc'],
                ['quarter_number', 'asc'],
            ])
            ->values()
            ->all();

        $municipalities = collect($lookup['municipalities'] ?? [])
            ->sortBy('municipal_name')
            ->values()
            ->all();

        $schools = collect($lookup['schools'] ?? [])
            ->sortBy('name')
            ->values()
            ->all();

        $gradeLevels = collect($lookup['gradeLevels'] ?? [])
            ->sortBy('grade_number')
            ->values()
            ->all();

        $sections = collect($lookup['sections'] ?? [])
            ->map(function ($section) use ($lookup) {
                $grade = $lookup['gradeLevels'][$section['grade_level_id'] ?? null] ?? [];
                $school = $lookup['schools'][$section['school_id'] ?? null] ?? [];

                return array_merge($section, [
                    'grade_number' => $grade['grade_number'] ?? null,
                    'school_name' => $school['name'] ?? null,
                    'label' => (isset($grade['grade_number']) ? 'Grade ' . $grade['grade_number'] . ' - ' : '') . ($section['section_name'] ?? 'Section'),
                ]);
            })
            ->sortBy([
                ['grade_number', 'asc'],
                ['section_name', 'asc'],
            ])
            ->values()
            ->all();

        return [
            'assignments' => $catalogAssignments,
            'schoolYears' => $schoolYears,
            'quarters' => $quarters,
            'municipalities' => $municipalities,
            'schools' => $schools,
            'gradeLevels' => $gradeLevels,
            'sections' => $sections,
            'languages' => [
                ['value' => 'all', 'label' => 'All Languages'],
                ['value' => 'english', 'label' => 'English'],
                ['value' => 'filipino', 'label' => 'Filipino'],
            ],
        ];
    }


    private function filterAssignments(array $assignments, array $lookup, array $filters): array
    {
        return collect($assignments)
            ->when($filters['school_year_id'] ?? null, fn ($items) => $items->where('year_id', $filters['school_year_id']))
            ->when(($filters['quarter_id'] ?? 'all') !== 'all', fn ($items) => $items->where('quarter_id', $filters['quarter_id']))
            ->when(($filters['municipality_id'] ?? 'all') !== 'all', fn ($items) => $items->where('municipality_id', $filters['municipality_id']))
            ->when(($filters['school_id'] ?? 'all') !== 'all', fn ($items) => $items->where('school_id', $filters['school_id']))
            ->filter(function ($assignment) use ($lookup, $filters) {
                $section = $lookup['sections'][$assignment['section_id'] ?? null] ?? [];

                if (($filters['grade_level_id'] ?? 'all') !== 'all' && ($section['grade_level_id'] ?? null) !== $filters['grade_level_id']) {
                    return false;
                }

                if (($filters['section_id'] ?? 'all') !== 'all' && ($assignment['section_id'] ?? null) !== $filters['section_id']) {
                    return false;
                }

                return true;
            })
            ->values()
            ->all();
    }


    private function buildMetrics(array $assignments, array $lookup, array $filters): array
    {
        $empty = $this->emptyMetrics();

        if (empty($assignments)) {
            return $empty;
        }

        $sectionIds = collect($assignments)->pluck('section_id')->filter()->unique()->values()->all();
        $yearIds = collect($assignments)->pluck('year_id')->filter()->unique()->values()->all();
        $quarterIds = collect($assignments)->pluck('quarter_id')->filter()->unique()->values()->all();

        $pupils = $this->fetchAssignedPupils($sectionIds);
        $pupils = collect($pupils)
            ->filter(function ($pupil) use ($lookup, $filters) {
                $section = $lookup['sections'][$pupil['section_id'] ?? null] ?? [];

                if (($filters['grade_level_id'] ?? 'all') !== 'all' && ($section['grade_level_id'] ?? null) !== $filters['grade_level_id']) {
                    return false;
                }

                if (($filters['section_id'] ?? 'all') !== 'all' && ($pupil['section_id'] ?? null) !== $filters['section_id']) {
                    return false;
                }

                return true;
            })
            ->unique('pupil_id')
            ->values();

        $pupilIds = $pupils->pluck('pupil_id')->filter()->unique()->values()->all();

        if (empty($pupilIds)) {
            return $empty;
        }

        $records = $this->fetchAssessmentRecords($pupilIds, $yearIds, $quarterIds);
        $materialIds = collect($records)->pluck('material_id')->filter()->unique()->values()->all();
        $materials = collect($this->fetchRowsByIds('reading_materials', 'material_id', $materialIds, 'material_id,title,language,word_count,grade_level_id'))
            ->keyBy('material_id')
            ->all();

        $formattedRecords = collect($records)
            ->map(fn ($record) => $this->formatRecord($record, $materials[$record['material_id'] ?? null] ?? []))
            ->filter(fn ($record) => $this->recordMatchesAssignments($record, $assignments, $pupils))
            ->values();

        $pupilsById = $pupils->keyBy('pupil_id');
        $selectedRecords = $this->latestRecordsForLanguage($formattedRecords, $filters['language'] ?? 'all')
            ->map(function ($record) use ($pupilsById, $lookup) {
                $pupil = $pupilsById->get($record['pupil_id'], []);
                $section = $lookup['sections'][$pupil['section_id'] ?? null] ?? [];
                $grade = $lookup['gradeLevels'][$section['grade_level_id'] ?? null] ?? [];
                $quarter = $lookup['quarters'][$record['quarter_id'] ?? null] ?? [];

                return array_merge($record, [
                    'pupil_name' => $pupil['full_name'] ?? 'Unnamed pupil',
                    'pupil_sex' => $this->normalizeSex($pupil['sex'] ?? null),
                    'section_name' => $section['section_name'] ?? 'Section',
                    'school_name' => $lookup['schools'][$section['school_id'] ?? null]['name'] ?? 'School',
                    'municipality_name' => $lookup['municipalities'][$lookup['schools'][$section['school_id'] ?? null]['municipality_id'] ?? null]['municipal_name'] ?? 'Municipality',
                    'grade_number' => $grade['grade_number'] ?? null,
                    'grade_section' => (isset($grade['grade_number']) ? 'Grade ' . $grade['grade_number'] . ' - ' : '') . ($section['section_name'] ?? 'Section'),
                    'quarter_number' => (int) ($quarter['quarter_number'] ?? 0),
                ]);
            })
            ->values();

        $languageRecords = $this->latestLanguageRecordsByPupil($formattedRecords);
        $summary = $this->summaryMetrics($pupils, $selectedRecords, $languageRecords, $filters['language'] ?? 'all');

        return [
            'summary' => $summary,
            'cards' => $this->speedCards($selectedRecords),
            'chartData' => $this->chartData($selectedRecords, $pupils, $languageRecords),
            'attentionLists' => $this->attentionLists($selectedRecords),
        ];
    }

    private function emptyMetrics(): array
    {
        return [
            'summary' => [
                'assignments_count' => 0,
                'total_pupils' => 0,
                'assessed_count' => 0,
                'need_support_count' => 0,
                'overall_percent' => 0,
                'english_percent' => 0,
                'filipino_percent' => 0,
            ],
            'cards' => $this->speedCards(collect()),
            'chartData' => [
                'readingLevel' => ['all' => [0, 0, 0], 'male' => [0, 0, 0], 'female' => [0, 0, 0]],
                'comprehensionLevel' => ['all' => [0, 0, 0], 'male' => [0, 0, 0], 'female' => [0, 0, 0]],
                'readingRate' => ['all' => [0, 0, 0, 0], 'male' => [0, 0, 0, 0], 'female' => [0, 0, 0, 0]],
                'comprehensionRate' => ['all' => [0, 0, 0, 0], 'male' => [0, 0, 0, 0], 'female' => [0, 0, 0, 0]],
                'filipinoCompletion' => ['all' => [0, 100], 'male' => [0, 100], 'female' => [0, 100]],
                'englishCompletion' => ['all' => [0, 100], 'male' => [0, 100], 'female' => [0, 100]],
                'miscueDistribution' => [0, 0, 0, 0, 0, 0, 0],
                'speedMale' => [0, 0, 0, 0, 0],
                'speedFemale' => [0, 0, 0, 0, 0],
                'comprehensionStatus' => [0, 0],
            ],
            'attentionLists' => ['oral' => [], 'comprehension' => []],
        ];
    }

    private function summaryMetrics($pupils, $selectedRecords, array $languageRecords, string $language): array
    {
        $totalPupils = $pupils->count();
        $englishAssessed = collect($languageRecords['english'] ?? [])->count();
        $filipinoAssessed = collect($languageRecords['filipino'] ?? [])->count();
        $assessed = $language === 'english'
            ? $englishAssessed
            : ($language === 'filipino' ? $filipinoAssessed : $selectedRecords->pluck('pupil_id')->unique()->count());

        $required = $language === 'all' ? max($totalPupils * 2, 0) : $totalPupils;
        $completed = $language === 'all' ? $englishAssessed + $filipinoAssessed : $assessed;

        $needSupport = $selectedRecords
            ->filter(fn ($record) => $this->needsSupport($record))
            ->pluck('pupil_id')
            ->unique()
            ->count();

        return [
            'assignments_count' => $selectedRecords->pluck('assignment_id')->filter()->unique()->count(),
            'total_pupils' => $totalPupils,
            'assessed_count' => $assessed,
            'need_support_count' => $needSupport,
            'overall_percent' => $required > 0 ? round(($completed / $required) * 100) : 0,
            'english_percent' => $totalPupils > 0 ? round(($englishAssessed / $totalPupils) * 100) : 0,
            'filipino_percent' => $totalPupils > 0 ? round(($filipinoAssessed / $totalPupils) * 100) : 0,
        ];
    }

    private function speedCards($records): array
    {
        $labels = [
            'fast' => ['label' => 'Fast Readers', 'helper' => 'Reads smoothly and accurately.', 'icon' => 'speed'],
            'average' => ['label' => 'Average Readers', 'helper' => 'Reads at the expected pace.', 'icon' => 'book'],
            'slow' => ['label' => 'Slow Readers', 'helper' => 'Needs fluency support.', 'icon' => 'time'],
            'struggling' => ['label' => 'Struggling Readers', 'helper' => 'Needs close monitoring.', 'icon' => 'alert'],
            'non-reader' => ['label' => 'Non-Readers', 'helper' => 'Needs urgent intervention.', 'icon' => 'target'],
        ];

        $total = max($records->count(), 1);
        $counts = $records->countBy(fn ($record) => $this->readingSpeedKey($record['reading_speed'] ?? null, $record['reading_level'] ?? null));

        return collect($labels)->map(function ($item, $key) use ($counts, $total) {
            $value = (int) ($counts[$key] ?? 0);

            return [
                'label' => $item['label'],
                'value' => $value,
                'percent' => round(($value / $total) * 100) . '%',
                'helper' => $item['helper'],
                'icon' => $item['icon'],
            ];
        })->values()->all();
    }

    private function chartData($records, $pupils, array $languageRecords): array
    {
        return [
            'readingLevel' => [
                'all' => $this->levelCounts($records, 'reading_level'),
                'male' => $this->levelCounts($records->where('pupil_sex', 'male'), 'reading_level'),
                'female' => $this->levelCounts($records->where('pupil_sex', 'female'), 'reading_level'),
            ],
            'comprehensionLevel' => [
                'all' => $this->levelCounts($records, 'comprehension_level'),
                'male' => $this->levelCounts($records->where('pupil_sex', 'male'), 'comprehension_level'),
                'female' => $this->levelCounts($records->where('pupil_sex', 'female'), 'comprehension_level'),
            ],
            'readingRate' => [
                'all' => $this->quarterRates($records, 'reading'),
                'male' => $this->quarterRates($records->where('pupil_sex', 'male'), 'reading'),
                'female' => $this->quarterRates($records->where('pupil_sex', 'female'), 'reading'),
            ],
            'comprehensionRate' => [
                'all' => $this->quarterRates($records, 'comprehension'),
                'male' => $this->quarterRates($records->where('pupil_sex', 'male'), 'comprehension'),
                'female' => $this->quarterRates($records->where('pupil_sex', 'female'), 'comprehension'),
            ],
            'filipinoCompletion' => [
                'all' => $this->completionSeries($pupils, $languageRecords['filipino'] ?? [], 'all'),
                'male' => $this->completionSeries($pupils, $languageRecords['filipino'] ?? [], 'male'),
                'female' => $this->completionSeries($pupils, $languageRecords['filipino'] ?? [], 'female'),
            ],
            'englishCompletion' => [
                'all' => $this->completionSeries($pupils, $languageRecords['english'] ?? [], 'all'),
                'male' => $this->completionSeries($pupils, $languageRecords['english'] ?? [], 'male'),
                'female' => $this->completionSeries($pupils, $languageRecords['english'] ?? [], 'female'),
            ],
            'miscueDistribution' => $this->miscueDistribution($records),
            'speedMale' => $this->speedDistribution($records->where('pupil_sex', 'male')),
            'speedFemale' => $this->speedDistribution($records->where('pupil_sex', 'female')),
            'comprehensionStatus' => [
                $records->filter(fn ($record) => ! empty($record['has_comprehension_score']) || ! empty($record['comprehension_level']))->count(),
                $records->filter(fn ($record) => empty($record['has_comprehension_score']) && empty($record['comprehension_level']))->count(),
            ],
        ];
    }

    private function levelCounts($records, string $field): array
    {
        $counts = $records->countBy(fn ($record) => $this->levelKey($record[$field] ?? null));

        return [
            (int) ($counts['independent'] ?? 0),
            (int) ($counts['instructional'] ?? 0),
            (int) ($counts['frustration'] ?? 0),
        ];
    }

    private function quarterRates($records, string $type): array
    {
        return collect([1, 2, 3, 4])->map(function ($quarter) use ($records, $type) {
            $quarterRecords = $records->where('quarter_number', $quarter);
            $total = $quarterRecords->count();

            if ($total === 0) {
                return 0;
            }

            $passed = $quarterRecords->filter(function ($record) use ($type) {
                if ($type === 'reading') {
                    $speedKey = $this->readingSpeedKey($record['reading_speed'] ?? null, $record['reading_level'] ?? null);
                    return in_array($speedKey, ['fast', 'average'], true);
                }

                return in_array($this->levelKey($record['comprehension_level'] ?? null), ['independent', 'instructional'], true);
            })->count();

            return round(($passed / $total) * 100);
        })->all();
    }

    private function completionSeries($pupils, array $languageRecords, string $sex): array
    {
        $filteredPupils = $pupils->filter(function ($pupil) use ($sex) {
            if ($sex === 'all') {
                return true;
            }

            return $this->normalizeSex($pupil['sex'] ?? null) === $sex;
        });

        $total = $filteredPupils->count();

        if ($total === 0) {
            return [0, 100];
        }

        $pupilIds = $filteredPupils->pluck('pupil_id')->filter()->unique()->values();
        $assessed = collect($languageRecords)->filter(fn ($record) => $pupilIds->contains($record['pupil_id'] ?? null))->count();
        $percent = round(($assessed / $total) * 100);

        return [$percent, max(100 - $percent, 0)];
    }

    private function miscueDistribution($records): array
    {
        $categories = ['Mispronunciation', 'Omission', 'Substitution', 'Insertion', 'Transposition', 'Reversal', 'Repetition'];
        $summary = array_fill_keys($categories, 0);

        $records->each(function ($record) use (&$summary) {
            foreach (($record['miscue_summary'] ?? []) as $row) {
                $type = $this->canonicalMiscueType($row['type'] ?? '');

                if ($type && array_key_exists($type, $summary)) {
                    $summary[$type] += (int) ($row['count'] ?? 0);
                }
            }
        });

        return array_values($summary);
    }

    private function speedDistribution($records): array
    {
        $counts = $records->countBy(fn ($record) => $this->readingSpeedKey($record['reading_speed'] ?? null, $record['reading_level'] ?? null));

        return [
            (int) ($counts['fast'] ?? 0),
            (int) ($counts['average'] ?? 0),
            (int) ($counts['slow'] ?? 0),
            (int) ($counts['struggling'] ?? 0),
            (int) ($counts['non-reader'] ?? 0),
        ];
    }

    private function attentionLists($records): array
    {
        $oral = $records
            ->filter(fn ($record) => in_array($this->readingSpeedKey($record['reading_speed'] ?? null, $record['reading_level'] ?? null), ['slow', 'struggling', 'non-reader'], true))
            ->sortBy(fn ($record) => ['non-reader' => 1, 'struggling' => 2, 'slow' => 3][$this->readingSpeedKey($record['reading_speed'] ?? null, $record['reading_level'] ?? null)] ?? 9)
            ->unique('pupil_id')
            ->take(8)
            ->map(fn ($record) => [
                'name' => $record['pupil_name'] ?? 'Unnamed pupil',
                'grade' => trim(($record['school_name'] ?? 'School') . ' • ' . ($record['grade_section'] ?? 'Section')),
                'sex' => $record['pupil_sex'] ?? 'all',
                'level' => $record['reading_speed'] ?: ($record['reading_level'] ?? 'Needs support'),
            ])
            ->values()
            ->all();

        $comprehension = $records
            ->filter(fn ($record) => in_array($this->levelKey($record['comprehension_level'] ?? null), ['frustration', 'instructional'], true))
            ->sortBy(fn ($record) => ['frustration' => 1, 'instructional' => 2][$this->levelKey($record['comprehension_level'] ?? null)] ?? 9)
            ->unique('pupil_id')
            ->take(8)
            ->map(fn ($record) => [
                'name' => $record['pupil_name'] ?? 'Unnamed pupil',
                'grade' => trim(($record['school_name'] ?? 'School') . ' • ' . ($record['grade_section'] ?? 'Section')),
                'sex' => $record['pupil_sex'] ?? 'all',
                'level' => $record['comprehension_level'] ?: 'Needs support',
            ])
            ->values()
            ->all();

        return [
            'oral' => $oral,
            'comprehension' => $comprehension,
        ];
    }

    private function latestRecordsForLanguage($records, string $language)
    {
        return $records
            ->when($language !== 'all', fn ($items) => $items->where('language_key', $language))
            ->groupBy(fn ($record) => ($record['pupil_id'] ?? '') . ':' . ($record['language_key'] ?? 'unknown'))
            ->map(fn ($group) => collect($group)->sortByDesc(fn ($record) => $record['updated_at'] ?? $record['created_at'] ?? '')->first())
            ->values();
    }

    private function latestLanguageRecordsByPupil($records): array
    {
        return [
            'english' => $this->latestRecordsForLanguage($records, 'english')->values()->all(),
            'filipino' => $this->latestRecordsForLanguage($records, 'filipino')->values()->all(),
        ];
    }

    private function recordMatchesAssignments(array $record, array $assignments, $pupils): bool
    {
        $pupil = $pupils->firstWhere('pupil_id', $record['pupil_id'] ?? null);

        if (! $pupil) {
            return false;
        }

        foreach ($assignments as $assignment) {
            if (! empty($record['assignment_id']) && (string) $record['assignment_id'] === (string) ($assignment['assignment_id'] ?? '')) {
                return true;
            }

            if ((string) ($record['year_id'] ?? '') !== (string) ($assignment['year_id'] ?? '')) {
                continue;
            }

            if ((string) ($record['quarter_id'] ?? '') !== (string) ($assignment['quarter_id'] ?? '')) {
                continue;
            }

            if ((string) ($pupil['section_id'] ?? '') !== (string) ($assignment['section_id'] ?? '')) {
                continue;
            }

            if (! empty($record['schedule_id']) && ! empty($assignment['schedule_id']) && (string) $record['schedule_id'] !== (string) $assignment['schedule_id']) {
                continue;
            }

            return true;
        }

        return false;
    }

    private function fetchAssignedPupils(array $sectionIds): array
    {
        $sectionIds = collect($sectionIds)->filter()->unique()->values()->all();

        if (empty($sectionIds)) {
            return [];
        }

        $response = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/pupils', [
                'select' => 'pupil_id,created_at,updated_at,lrn,full_name,sex,age,school_id,section_id,grade_level_id,status',
                'section_id' => 'in.(' . $this->postgrestInList($sectionIds) . ')',
                'status' => 'eq.enrolled',
                'order' => 'full_name.asc',
            ]);

        if (! $response->successful()) {
            report('Failed to fetch district supervisor dashboard pupils: ' . $response->body());
            return [];
        }

        return $response->json();
    }

    private function fetchAssessmentRecords(array $pupilIds, array $yearIds, array $quarterIds): array
    {
        $pupilIds = collect($pupilIds)->filter()->unique()->values()->all();
        $yearIds = collect($yearIds)->filter()->unique()->values()->all();
        $quarterIds = collect($quarterIds)->filter()->unique()->values()->all();

        if (empty($pupilIds) || empty($yearIds) || empty($quarterIds)) {
            return [];
        }

        $response = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/assessment_records', [
                'select' => 'assessment_record_id,created_at,updated_at,pupil_id,evaluator_user_id,material_id,schedule_id,year_id,quarter_id,assessment_method,assessment_type,reading_score,comprehension_score,miscue_content,total_score,reading_level,status,assignment_id',
                'pupil_id' => 'in.(' . $this->postgrestInList($pupilIds) . ')',
                'year_id' => 'in.(' . $this->postgrestInList($yearIds) . ')',
                'quarter_id' => 'in.(' . $this->postgrestInList($quarterIds) . ')',
                'status' => 'neq.draft',
                'order' => 'updated_at.desc',
            ]);

        if (! $response->successful()) {
            report('Failed to fetch district supervisor dashboard assessment records: ' . $response->body());
            return [];
        }

        return $response->json();
    }

    private function formatRecord(array $record, array $material): array
    {
        $readingScore = $record['reading_score'] ?? null;
        $comprehensionScore = $record['comprehension_score'] ?? null;
        $readingSummary = $this->extractReadingSummary($readingScore, $record['reading_level'] ?? null);
        $comprehensionSummary = $this->extractComprehensionSummary($comprehensionScore);

        return [
            'assessment_record_id' => $record['assessment_record_id'] ?? null,
            'created_at' => $record['created_at'] ?? null,
            'updated_at' => $record['updated_at'] ?? null,
            'pupil_id' => $record['pupil_id'] ?? null,
            'evaluator_user_id' => $record['evaluator_user_id'] ?? null,
            'material_id' => $record['material_id'] ?? null,
            'assignment_id' => $record['assignment_id'] ?? null,
            'schedule_id' => $record['schedule_id'] ?? null,
            'year_id' => $record['year_id'] ?? null,
            'quarter_id' => $record['quarter_id'] ?? null,
            'language_key' => $this->normalizeLanguage($material['language'] ?? null),
            'language' => $this->displayLanguage($material['language'] ?? null),
            'reading_level' => $readingSummary['reading_level'],
            'reading_speed' => $readingSummary['reading_speed'],
            'word_per_minute' => $readingSummary['word_per_minute'],
            'total_miscues' => $readingSummary['total_miscues'],
            'comprehension_level' => $comprehensionSummary['comprehension_level'],
            'comprehension_rate' => $comprehensionSummary['comprehension_rate'],
            'has_comprehension_score' => ! empty($record['comprehension_score']),
            'miscue_summary' => $this->formatSummaryRows($this->jsonValue($readingScore, 'miscueSummary')),
        ];
    }

    private function extractReadingSummary($readingScore, ?string $fallbackLevel): array
    {
        $summary = $this->jsonValue($readingScore, 'miscueOverallSummary');

        return [
            'reading_level' => $fallbackLevel ?: $this->summaryValue($summary, 'Reading Level'),
            'reading_speed' => $this->summaryValue($summary, 'Reading Speed'),
            'word_per_minute' => $this->summaryValue($summary, 'Word per Minute'),
            'total_miscues' => $this->summaryValue($summary, 'Total Miscues'),
        ];
    }

    private function extractComprehensionSummary($comprehensionScore): array
    {
        $summary = $this->jsonValue($comprehensionScore, 'comprehensionSummary');

        return [
            'comprehension_level' => $this->summaryValue($summary, 'Comprehension Score'),
            'comprehension_rate' => $this->summaryValue($summary, 'Comprehension Rate'),
        ];
    }

    private function formatSummaryRows(array $summary): array
    {
        return collect($summary)
            ->map(fn ($item) => [
                'type' => trim((string) ($item['type'] ?? '')),
                'count' => (int) ($item['count'] ?? 0),
            ])
            ->filter(fn ($item) => $item['type'] !== '')
            ->values()
            ->all();
    }

    private function jsonValue($value, string $key): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : [];
        }

        if (! is_array($value)) {
            return [];
        }

        return is_array($value[$key] ?? null) ? $value[$key] : [];
    }

    private function summaryValue(array $summary, string $type)
    {
        $row = collect($summary)->first(function ($item) use ($type) {
            return strtolower(trim((string) ($item['type'] ?? ''))) === strtolower($type);
        });

        return $row['count'] ?? null;
    }

    private function levelKey($value): string
    {
        $value = strtolower(trim((string) $value));

        if (str_contains($value, 'independent')) {
            return 'independent';
        }

        if (str_contains($value, 'instructional')) {
            return 'instructional';
        }

        if (str_contains($value, 'frustrat') || str_contains($value, 'frustration')) {
            return 'frustration';
        }

        return 'unknown';
    }

    private function readingSpeedKey($speed, $level = null): string
    {
        $value = strtolower(trim((string) ($speed ?: $level)));

        if (str_contains($value, 'non')) {
            return 'non-reader';
        }

        if (str_contains($value, 'struggling')) {
            return 'struggling';
        }

        if (str_contains($value, 'slow')) {
            return 'slow';
        }

        if (str_contains($value, 'average')) {
            return 'average';
        }

        if (str_contains($value, 'fast')) {
            return 'fast';
        }

        return 'unknown';
    }

    private function needsSupport(array $record): bool
    {
        return in_array($this->readingSpeedKey($record['reading_speed'] ?? null, $record['reading_level'] ?? null), ['slow', 'struggling', 'non-reader'], true)
            || in_array($this->levelKey($record['reading_level'] ?? null), ['frustration'], true)
            || in_array($this->levelKey($record['comprehension_level'] ?? null), ['frustration', 'instructional'], true);
    }

    private function canonicalMiscueType(string $type): ?string
    {
        $value = strtolower(trim($type));

        return match (true) {
            str_contains($value, 'mispron') => 'Mispronunciation',
            str_contains($value, 'omission') => 'Omission',
            str_contains($value, 'substitution') => 'Substitution',
            str_contains($value, 'insertion') => 'Insertion',
            str_contains($value, 'transposition') => 'Transposition',
            str_contains($value, 'reversal') => 'Reversal',
            str_contains($value, 'repetition') => 'Repetition',
            default => null,
        };
    }

    private function normalizeSex($sex): string
    {
        $sex = strtolower(trim((string) $sex));

        if (in_array($sex, ['m', 'male'], true)) {
            return 'male';
        }

        if (in_array($sex, ['f', 'female'], true)) {
            return 'female';
        }

        return 'unknown';
    }

    private function normalizeLanguage(?string $language): ?string
    {
        $language = strtolower(trim((string) $language));

        if ($language === 'tagalog') {
            $language = 'filipino';
        }

        return in_array($language, ['english', 'filipino'], true) ? $language : null;
    }

    private function displayLanguage(?string $language): string
    {
        $language = $this->normalizeLanguage($language);

        return $language ? ucfirst($language) : 'Unknown Language';
    }

    private function activeFilterLabels(array $filters, array $lookup): array
    {
        $section = $lookup['sections'][$filters['section_id'] ?? null] ?? [];
        $grade = $lookup['gradeLevels'][$filters['grade_level_id'] ?? null] ?? [];
        $municipality = $lookup['municipalities'][$filters['municipality_id'] ?? null] ?? [];
        $school = $lookup['schools'][$filters['school_id'] ?? null] ?? [];

        return [
            'schoolYear' => $lookup['schoolYears'][$filters['school_year_id'] ?? null]['label'] ?? 'No school year',
            'quarter' => ($filters['quarter_id'] ?? 'all') === 'all' ? 'All Quarters' : ($lookup['quarters'][$filters['quarter_id']]['label'] ?? 'Quarter'),
            'municipality' => ($filters['municipality_id'] ?? 'all') === 'all' ? 'All Municipalities' : ($municipality['municipal_name'] ?? 'Municipality'),
            'school' => ($filters['school_id'] ?? 'all') === 'all' ? 'All Schools' : ($school['name'] ?? 'School'),
            'gradeLevel' => ($filters['grade_level_id'] ?? 'all') === 'all' ? 'All Grades' : (isset($grade['grade_number']) ? 'Grade ' . $grade['grade_number'] : 'Grade'),
            'section' => ($filters['section_id'] ?? 'all') === 'all' ? 'All Sections' : ($section['section_name'] ?? 'Section'),
            'language' => ucfirst($filters['language'] ?? 'all') === 'All' ? 'All Languages' : ucfirst($filters['language']),
        ];
    }


    private function fetchRowsByIds(string $table, string $idField, array $ids, string $select): array
    {
        $ids = collect($ids)->filter()->unique()->values()->all();

        if (empty($ids)) {
            return [];
        }

        $response = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/' . $table, [
                'select' => $select,
                $idField => 'in.(' . $this->postgrestInList($ids) . ')',
            ]);

        if (! $response->successful()) {
            report("Failed to fetch {$table} for district supervisor dashboard: " . $response->body());
            return [];
        }

        return $response->json();
    }

    private function districtSupervisorScope(): array
    {
        $matchesRole = function ($designation) {
            $role = strtolower(trim((string) ($designation['role_name'] ?? '')));

            return in_array($role, ['district supervisor', 'district_supervisor', 'district-supervisor'], true);
        };

        $activeDesignation = session('active_designation', []);

        if ($matchesRole($activeDesignation)) {
            return [
                'district_id' => $activeDesignation['district_id'] ?? null,
                'municipality_id' => $activeDesignation['municipal_id'] ?? ($activeDesignation['municipality_id'] ?? null),
            ];
        }

        $designation = collect(session('user_designations', []))->first(fn ($item) => $matchesRole($item));

        return [
            'district_id' => $designation['district_id'] ?? null,
            'municipality_id' => $designation['municipal_id'] ?? ($designation['municipality_id'] ?? null),
        ];
    }


    private function schoolYearLabel(array $year): string
    {
        $start = ! empty($year['start_date']) ? date('Y', strtotime($year['start_date'])) : null;
        $end = ! empty($year['end_date']) ? date('Y', strtotime($year['end_date'])) : null;

        return $start && $end ? $start . ' - ' . $end : 'School Year';
    }

    private function quarterLabel(array $quarter): string
    {
        if (empty($quarter)) {
            return 'Quarter';
        }

        $name = $quarter['quarter_name'] ?? 'Quarter';
        $number = $quarter['quarter_number'] ?? null;

        return $number ? 'Q' . $number . ' - ' . $name : $name;
    }

    private function postgrestInList(array $ids): string
    {
        return collect($ids)
            ->filter()
            ->map(fn ($id) => '"' . str_replace('"', '\\"', (string) $id) . '"')
            ->implode(',');
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
