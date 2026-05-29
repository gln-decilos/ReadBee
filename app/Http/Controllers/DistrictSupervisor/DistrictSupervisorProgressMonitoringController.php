<?php

namespace App\Http\Controllers\DistrictSupervisor;

use App\Helpers\DistrictSupervisorMenuHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class DistrictSupervisorProgressMonitoringController extends Controller
{
    public function index(Request $request)
    {
        $menuGroups = DistrictSupervisorMenuHelper::getMenuGroups();
        $scope = $this->districtSupervisorScope();

        if (empty($scope['district_id']) && empty($scope['municipality_id'])) {
            if ($request->expectsJson() || $request->query('ajax')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your district supervisor account is not connected to a district or municipality.',
                ], 403);
            }

            return redirect()
                ->route('district-supervisor.dashboard')
                ->with('error', 'Your district supervisor account is not connected to a district or municipality.');
        }

        $schoolYears = $this->fetchSchoolYears($scope);
        $selectedYearId = $request->query('year_id') ?: ($schoolYears[0]['year_id'] ?? null);
        $municipalities = $selectedYearId ? $this->buildMunicipalityProgress($scope, $selectedYearId, $schoolYears) : [];
        $summary = $this->buildSummary($municipalities);

        if ($request->expectsJson() || $request->query('ajax')) {
            return response()->json([
                'success' => true,
                'schoolYears' => $schoolYears,
                'selectedYearId' => $selectedYearId,
                'summary' => $summary,
                'municipalities' => $municipalities,
            ]);
        }

        return view('pages.district-supervisor.district-supervisor-progress-monitoring', [
            'title' => 'District Supervisor Progress Monitoring',
            'menuGroups' => $menuGroups,
            'schoolYears' => $schoolYears,
            'selectedYearId' => $selectedYearId,
            'summary' => $summary,
            'municipalities' => $municipalities,
        ]);
    }

    private function buildMunicipalityProgress(array $scope, string $yearId, array $schoolYears): array
    {
        $schools = collect($this->fetchSchools($scope))
            ->filter(fn ($school) => ! empty($school['school_id']))
            ->values();

        if ($schools->isEmpty()) {
            return [];
        }

        $schoolIds = $schools->pluck('school_id')->filter()->unique()->values()->all();
        $municipalityIds = $schools->pluck('municipality_id')->filter()->unique()->values()->all();

        $municipalities = collect($this->fetchRowsByIds(
            'municipalities',
            'municipality_id',
            $municipalityIds,
            'municipality_id,municipal_name,district_id,logo'
        ))->keyBy('municipality_id');

        $sections = collect($this->fetchSections($schoolIds, $yearId));
        $sectionIds = $sections->pluck('section_id')->filter()->unique()->values()->all();
        $gradeLevelIds = $sections->pluck('grade_level_id')->filter()->unique()->values()->all();

        $gradeLevels = collect($this->fetchRowsByIds(
            'grade_levels',
            'grade_level_id',
            $gradeLevelIds,
            'grade_level_id,school_id,grade_number,is_active'
        ))->keyBy('grade_level_id');

        $pupils = collect($this->fetchPupils($schoolIds, $sectionIds));
        $pupilsBySection = $pupils->groupBy('section_id');
        $pupilIds = $pupils->pluck('pupil_id')->filter()->unique()->values()->all();

        $records = $this->fetchAssessmentRecords($yearId, $pupilIds);
        $materialIds = collect($records)->pluck('material_id')->filter()->unique()->values()->all();
        $quarterIds = collect($records)->pluck('quarter_id')->filter()->unique()->values()->all();

        $materials = collect($this->fetchRowsByIds(
            'reading_materials',
            'material_id',
            $materialIds,
            'material_id,title,language,word_count,grade_level_id'
        ))->keyBy('material_id');

        $quarters = collect($this->fetchRowsByIds(
            'quarter',
            'quarter_id',
            $quarterIds,
            'quarter_id,year_id,quarter_number,quarter_name,start_date,end_date'
        ))->keyBy('quarter_id');

        $records = collect($records)
            ->map(fn ($record) => $this->formatRecord(
                $record,
                $materials[$record['material_id'] ?? null] ?? [],
                $quarters[$record['quarter_id'] ?? null] ?? []
            ))
            ->values();

        $recordsByPupil = $records->groupBy('pupil_id');
        $schoolYearsById = collect($schoolYears)->keyBy('year_id');
        $schoolYear = $schoolYearsById->get($yearId, []);
        $schoolsById = $schools->keyBy('school_id');

        $sectionRows = $sections
            ->map(function ($section) use ($gradeLevels, $pupilsBySection, $recordsByPupil, $schoolYear, $schoolsById, $municipalities) {
                $school = $schoolsById[$section['school_id'] ?? null] ?? [];
                $municipality = $municipalities[$school['municipality_id'] ?? null] ?? [];
                $grade = $gradeLevels[$section['grade_level_id'] ?? null] ?? [];
                $sectionPupils = collect($pupilsBySection->get($section['section_id'] ?? null, []))
                    ->where('status', 'enrolled')
                    ->sortBy('full_name')
                    ->values();

                $pupilRows = $sectionPupils
                    ->map(function ($pupil) use ($recordsByPupil, $section, $grade, $school, $municipality) {
                        $pupilRecords = collect($recordsByPupil->get($pupil['pupil_id'] ?? null, []));
                        $englishRecord = $this->latestLanguageRecord($pupilRecords, 'english');
                        $filipinoRecord = $this->latestLanguageRecord($pupilRecords, 'filipino');

                        return [
                            'pupil_id' => $pupil['pupil_id'],
                            'lrn' => $pupil['lrn'] ?? '',
                            'full_name' => $pupil['full_name'] ?? 'Unnamed pupil',
                            'sex' => $pupil['sex'] ?? null,
                            'sex_label' => $this->sexLabel($pupil['sex'] ?? null),
                            'age' => $pupil['age'] ?? null,
                            'status' => $pupil['status'] ?? 'enrolled',
                            'municipality_id' => $municipality['municipality_id'] ?? ($school['municipality_id'] ?? null),
                            'municipality_name' => $municipality['municipal_name'] ?? 'Municipality',
                            'municipality_logo' => $municipality['logo'] ?? null,
                            'school_id' => $pupil['school_id'] ?? ($school['school_id'] ?? null),
                            'school_name' => $school['name'] ?? 'School',
                            'school_logo' => $school['logo'] ?? null,
                            'section_id' => $pupil['section_id'] ?? null,
                            'section_name' => $section['section_name'] ?? 'Section',
                            'grade_level_id' => $pupil['grade_level_id'] ?? ($grade['grade_level_id'] ?? null),
                            'grade_number' => $grade['grade_number'] ?? null,
                            'grade_label' => isset($grade['grade_number']) ? 'Grade ' . $grade['grade_number'] : 'Grade',
                            'english' => $this->languageStatus($englishRecord),
                            'filipino' => $this->languageStatus($filipinoRecord),
                            'has_any_record' => (bool) ($englishRecord || $filipinoRecord),
                            'is_complete' => (bool) ($englishRecord && $filipinoRecord),
                        ];
                    })
                    ->values()
                    ->all();

                $totalPupils = count($pupilRows);
                $englishAssessed = collect($pupilRows)->where('english.assessed', true)->count();
                $filipinoAssessed = collect($pupilRows)->where('filipino.assessed', true)->count();
                $bothLanguages = collect($pupilRows)->where('is_complete', true)->count();
                $withAnyRecord = collect($pupilRows)->where('has_any_record', true)->count();
                $totalRequired = $totalPupils * 2;
                $totalCompleted = $englishAssessed + $filipinoAssessed;
                $overallPercent = $totalRequired > 0 ? round(($totalCompleted / $totalRequired) * 100, 1) : 0;

                return [
                    'section_id' => $section['section_id'] ?? null,
                    'municipality_id' => $municipality['municipality_id'] ?? ($school['municipality_id'] ?? null),
                    'municipality_name' => $municipality['municipal_name'] ?? 'Municipality',
                    'municipality_logo' => $municipality['logo'] ?? null,
                    'school_id' => $section['school_id'] ?? null,
                    'school_name' => $school['name'] ?? 'School',
                    'school_logo' => $school['logo'] ?? null,
                    'year_id' => $section['year_id'] ?? null,
                    'grade_level_id' => $section['grade_level_id'] ?? null,
                    'grade_number' => $grade['grade_number'] ?? null,
                    'grade_label' => isset($grade['grade_number']) ? 'Grade ' . $grade['grade_number'] : 'Grade',
                    'section_name' => $section['section_name'] ?? 'Section',
                    'adviser_name' => $section['adviser_name'] ?? null,
                    'status' => $section['status'] ?? 'active',
                    'school_year_label' => $this->schoolYearLabel($schoolYear),
                    'total_pupils' => $totalPupils,
                    'english_assessed' => $englishAssessed,
                    'filipino_assessed' => $filipinoAssessed,
                    'both_languages_count' => $bothLanguages,
                    'with_any_record_count' => $withAnyRecord,
                    'missing_any_count' => max($totalPupils - $bothLanguages, 0),
                    'total_required' => $totalRequired,
                    'total_completed' => $totalCompleted,
                    'overall_percent' => $overallPercent,
                    'english_percent' => $totalPupils > 0 ? round(($englishAssessed / $totalPupils) * 100, 1) : 0,
                    'filipino_percent' => $totalPupils > 0 ? round(($filipinoAssessed / $totalPupils) * 100, 1) : 0,
                    'pupils' => $pupilRows,
                ];
            })
            ->sortBy([
                ['school_name', 'asc'],
                ['grade_number', 'asc'],
                ['section_name', 'asc'],
            ])
            ->values();

        $sectionsBySchool = $sectionRows->groupBy('school_id');

        $schoolRows = $schools
            ->map(function ($school) use ($sectionsBySchool, $gradeLevels, $schoolYear, $municipalities) {
                $schoolSections = collect($sectionsBySchool->get($school['school_id'] ?? null, []));
                $municipality = $municipalities[$school['municipality_id'] ?? null] ?? [];

                $gradeRows = $schoolSections
                    ->groupBy('grade_level_id')
                    ->map(function ($gradeSections, $gradeLevelId) use ($gradeLevels, $schoolYear, $school, $municipality) {
                        $firstSection = $gradeSections->first();
                        $grade = $gradeLevels[$gradeLevelId] ?? [];
                        $totalPupils = $gradeSections->sum('total_pupils');
                        $englishAssessed = $gradeSections->sum('english_assessed');
                        $filipinoAssessed = $gradeSections->sum('filipino_assessed');
                        $bothLanguages = $gradeSections->sum('both_languages_count');
                        $withAnyRecord = $gradeSections->sum('with_any_record_count');
                        $totalRequired = $gradeSections->sum('total_required');
                        $totalCompleted = $gradeSections->sum('total_completed');

                        return [
                            'grade_level_id' => $gradeLevelId,
                            'municipality_id' => $municipality['municipality_id'] ?? ($school['municipality_id'] ?? null),
                            'municipality_name' => $municipality['municipal_name'] ?? 'Municipality',
                            'municipality_logo' => $municipality['logo'] ?? null,
                            'school_id' => $school['school_id'] ?? null,
                            'school_name' => $school['name'] ?? 'School',
                            'school_logo' => $school['logo'] ?? null,
                            'grade_number' => $grade['grade_number'] ?? ($firstSection['grade_number'] ?? null),
                            'grade_label' => isset($grade['grade_number'])
                                ? 'Grade ' . $grade['grade_number']
                                : ($firstSection['grade_label'] ?? 'Grade'),
                            'school_year_label' => $this->schoolYearLabel($schoolYear),
                            'sections_count' => $gradeSections->count(),
                            'total_pupils' => $totalPupils,
                            'english_assessed' => $englishAssessed,
                            'filipino_assessed' => $filipinoAssessed,
                            'both_languages_count' => $bothLanguages,
                            'with_any_record_count' => $withAnyRecord,
                            'missing_any_count' => max($totalPupils - $bothLanguages, 0),
                            'total_required' => $totalRequired,
                            'total_completed' => $totalCompleted,
                            'overall_percent' => $totalRequired > 0 ? round(($totalCompleted / $totalRequired) * 100, 1) : 0,
                            'english_percent' => $totalPupils > 0 ? round(($englishAssessed / $totalPupils) * 100, 1) : 0,
                            'filipino_percent' => $totalPupils > 0 ? round(($filipinoAssessed / $totalPupils) * 100, 1) : 0,
                            'sections' => $gradeSections->values()->all(),
                        ];
                    })
                    ->sortBy('grade_number')
                    ->values();

                return array_merge($this->aggregateNode($gradeRows), [
                    'municipality_id' => $municipality['municipality_id'] ?? ($school['municipality_id'] ?? null),
                    'municipality_name' => $municipality['municipal_name'] ?? 'Municipality',
                    'school_id' => $school['school_id'] ?? null,
                    'school_name' => $school['name'] ?? 'School',
                    'school_label' => $school['name'] ?? 'School',
                    'school_logo' => $school['logo'] ?? null,
                    'municipality_logo' => $municipality['logo'] ?? null,
                    'address' => $school['address'] ?? null,
                    'contact' => $school['contact'] ?? null,
                    'email' => $school['email'] ?? null,
                    'grade_levels_count' => $gradeRows->count(),
                    'sections_count' => $gradeRows->sum('sections_count'),
                    'school_year_label' => $this->schoolYearLabel($schoolYear),
                    'grades' => $gradeRows->values()->all(),
                ]);
            })
            ->sortBy('school_name')
            ->values();

        return $schoolRows
            ->groupBy('municipality_id')
            ->map(function ($municipalitySchools, $municipalityId) use ($municipalities) {
                $firstSchool = $municipalitySchools->first();
                $municipality = $municipalities[$municipalityId] ?? [];

                return array_merge($this->aggregateNode($municipalitySchools), [
                    'municipality_id' => $municipalityId ?: 'unknown',
                    'municipality_name' => $municipality['municipal_name'] ?? ($firstSchool['municipality_name'] ?? 'Municipality'),
                    'municipality_label' => $municipality['municipal_name'] ?? ($firstSchool['municipality_name'] ?? 'Municipality'),
                    'municipality_logo' => $municipality['logo'] ?? ($firstSchool['municipality_logo'] ?? null),
                    'schools_count' => $municipalitySchools->count(),
                    'grade_levels_count' => $municipalitySchools->sum('grade_levels_count'),
                    'sections_count' => $municipalitySchools->sum('sections_count'),
                    'schools' => $municipalitySchools->values()->all(),
                ]);
            })
            ->sortBy('municipality_name')
            ->values()
            ->all();
    }

    private function aggregateNode($nodes): array
    {
        $nodes = collect($nodes);
        $totalPupils = $nodes->sum('total_pupils');
        $englishAssessed = $nodes->sum('english_assessed');
        $filipinoAssessed = $nodes->sum('filipino_assessed');
        $bothLanguages = $nodes->sum('both_languages_count');
        $withAnyRecord = $nodes->sum('with_any_record_count');
        $totalRequired = $nodes->sum('total_required');
        $totalCompleted = $nodes->sum('total_completed');

        return [
            'total_pupils' => $totalPupils,
            'english_assessed' => $englishAssessed,
            'filipino_assessed' => $filipinoAssessed,
            'both_languages_count' => $bothLanguages,
            'with_any_record_count' => $withAnyRecord,
            'missing_any_count' => max($totalPupils - $bothLanguages, 0),
            'total_required' => $totalRequired,
            'total_completed' => $totalCompleted,
            'overall_percent' => $totalRequired > 0 ? round(($totalCompleted / $totalRequired) * 100, 1) : 0,
            'english_percent' => $totalPupils > 0 ? round(($englishAssessed / $totalPupils) * 100, 1) : 0,
            'filipino_percent' => $totalPupils > 0 ? round(($filipinoAssessed / $totalPupils) * 100, 1) : 0,
        ];
    }

    private function buildSummary(array $municipalities): array
    {
        $municipalities = collect($municipalities);
        $schoolsCount = $municipalities->sum('schools_count');
        $gradeLevelsCount = $municipalities->sum('grade_levels_count');
        $sectionsCount = $municipalities->sum('sections_count');
        $totalPupils = $municipalities->sum('total_pupils');
        $englishAssessed = $municipalities->sum('english_assessed');
        $filipinoAssessed = $municipalities->sum('filipino_assessed');
        $bothLanguages = $municipalities->sum('both_languages_count');
        $totalRequired = $municipalities->sum('total_required');
        $totalCompleted = $municipalities->sum('total_completed');

        return [
            'municipalities_count' => $municipalities->count(),
            'schools_count' => $schoolsCount,
            'grade_levels_count' => $gradeLevelsCount,
            'sections_count' => $sectionsCount,
            'total_pupils' => $totalPupils,
            'english_assessed' => $englishAssessed,
            'filipino_assessed' => $filipinoAssessed,
            'both_languages_count' => $bothLanguages,
            'total_required' => $totalRequired,
            'total_completed' => $totalCompleted,
            'overall_percent' => $totalRequired > 0 ? round(($totalCompleted / $totalRequired) * 100, 1) : 0,
        ];
    }

    private function fetchSchools(array $scope): array
    {
        $query = [
            'select' => 'school_id,name,address,contact,email,logo,district_id,municipality_id',
            'order' => 'name.asc',
        ];

        if (! empty($scope['municipality_id'])) {
            $query['municipality_id'] = 'eq.' . $scope['municipality_id'];
        } elseif (! empty($scope['district_id'])) {
            $query['district_id'] = 'eq.' . $scope['district_id'];
        }

        $response = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/schools', $query);

        if (! $response->successful()) {
            report('Failed to fetch district supervisor progress schools: ' . $response->body());
            return [];
        }

        return $response->json();
    }

    private function fetchSections(array $schoolIds, string $yearId): array
    {
        $schoolIds = collect($schoolIds)->filter()->unique()->values()->all();

        if (empty($schoolIds)) {
            return [];
        }

        $response = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/class_sections', [
                'select' => 'section_id,school_id,year_id,grade_level_id,section_name,status,adviser_name,created_at,updated_at',
                'school_id' => 'in.(' . $this->postgrestInList($schoolIds) . ')',
                'year_id' => 'eq.' . $yearId,
                'status' => 'neq.archived',
                'order' => 'section_name.asc',
            ]);

        if (! $response->successful()) {
            report('Failed to fetch district supervisor progress sections: ' . $response->body());
            return [];
        }

        return $response->json();
    }

    private function fetchPupils(array $schoolIds, array $sectionIds): array
    {
        $schoolIds = collect($schoolIds)->filter()->unique()->values()->all();
        $sectionIds = collect($sectionIds)->filter()->unique()->values()->all();

        if (empty($schoolIds) || empty($sectionIds)) {
            return [];
        }

        $response = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/pupils', [
                'select' => 'pupil_id,created_at,updated_at,lrn,full_name,sex,age,school_id,section_id,grade_level_id,status',
                'school_id' => 'in.(' . $this->postgrestInList($schoolIds) . ')',
                'section_id' => 'in.(' . $this->postgrestInList($sectionIds) . ')',
                'status' => 'eq.enrolled',
                'order' => 'full_name.asc',
            ]);

        if (! $response->successful()) {
            report('Failed to fetch district supervisor progress pupils: ' . $response->body());
            return [];
        }

        return $response->json();
    }

    private function fetchAssessmentRecords(string $yearId, array $pupilIds): array
    {
        $pupilIds = collect($pupilIds)->filter()->unique()->values()->all();

        if (empty($pupilIds)) {
            return [];
        }

        $response = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/assessment_records', [
                'select' => 'assessment_record_id,created_at,updated_at,pupil_id,evaluator_user_id,material_id,schedule_id,year_id,quarter_id,assessment_method,assessment_type,reading_score,comprehension_score,miscue_content,total_score,reading_level,status,assignment_id',
                'year_id' => 'eq.' . $yearId,
                'pupil_id' => 'in.(' . $this->postgrestInList($pupilIds) . ')',
                'status' => 'neq.draft',
                'order' => 'updated_at.desc',
            ]);

        if (! $response->successful()) {
            report('Failed to fetch district supervisor progress assessment records: ' . $response->body());
            return [];
        }

        return $response->json();
    }

    private function fetchSchoolYears(array $scope): array
    {
        $schools = collect($this->fetchSchools($scope));
        $schoolIds = $schools->pluck('school_id')->filter()->unique()->values()->all();

        if (empty($schoolIds)) {
            return [];
        }

        $sectionsResponse = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/class_sections', [
                'select' => 'year_id',
                'school_id' => 'in.(' . $this->postgrestInList($schoolIds) . ')',
                'status' => 'neq.archived',
                'order' => 'created_at.desc',
            ]);

        if (! $sectionsResponse->successful()) {
            report('Failed to fetch year ids for district supervisor progress monitoring: ' . $sectionsResponse->body());
            return [];
        }

        $yearIds = collect($sectionsResponse->json())
            ->pluck('year_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($yearIds)) {
            return [];
        }

        $response = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/school_year', [
                'select' => 'year_id,start_date,end_date,created_at',
                'year_id' => 'in.(' . $this->postgrestInList($yearIds) . ')',
                'order' => 'start_date.desc',
            ]);

        if (! $response->successful()) {
            report('Failed to fetch school year rows for district supervisor progress monitoring: ' . $response->body());
            return [];
        }

        return collect($response->json())
            ->map(fn ($year) => array_merge($year, ['label' => $this->schoolYearLabel($year)]))
            ->values()
            ->all();
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
            report("Failed to fetch {$table} for district supervisor progress monitoring: " . $response->body());
            return [];
        }

        return $response->json();
    }

    private function latestLanguageRecord($records, string $language): ?array
    {
        return collect($records)
            ->where('language_key', $language)
            ->sortByDesc(fn ($record) => $record['updated_at'] ?? $record['created_at'] ?? '')
            ->first();
    }

    private function languageStatus(?array $record): array
    {
        if (! $record) {
            return [
                'assessed' => false,
                'status' => 'Not Yet Assessed',
                'record' => null,
            ];
        }

        return [
            'assessed' => true,
            'status' => 'Assessed',
            'record' => $record,
        ];
    }

    private function formatRecord(array $record, array $material, array $quarter): array
    {
        $languageKey = $this->normalizeLanguage($material['language'] ?? null)
            ?: $this->normalizeLanguage($record['assessment_type'] ?? null);
        $readingScore = $record['reading_score'] ?? null;
        $comprehensionScore = $record['comprehension_score'] ?? null;
        $readingSummary = $this->extractReadingSummary($readingScore, $record['reading_level'] ?? null);
        $comprehensionSummary = $this->extractComprehensionSummary($comprehensionScore);
        $miscueSummary = $this->formatSummaryRows($this->jsonValue($readingScore, 'miscueSummary'));
        $readingOverallSummary = $this->formatSummaryRows($this->jsonValue($readingScore, 'miscueOverallSummary'));
        $comprehensionDetailSummary = $this->formatSummaryRows($this->jsonValue($comprehensionScore, 'comprehensionSummary'));

        return [
            'assessment_record_id' => $record['assessment_record_id'] ?? null,
            'created_at' => $record['created_at'] ?? null,
            'updated_at' => $record['updated_at'] ?? null,
            'pupil_id' => $record['pupil_id'] ?? null,
            'evaluator_user_id' => $record['evaluator_user_id'] ?? null,
            'material_id' => $record['material_id'] ?? null,
            'material_title' => $material['title'] ?? 'Reading Material',
            'language' => $this->displayLanguage($languageKey),
            'language_key' => $languageKey,
            'schedule_id' => $record['schedule_id'] ?? null,
            'year_id' => $record['year_id'] ?? null,
            'quarter_id' => $record['quarter_id'] ?? null,
            'quarter_label' => $this->quarterLabel($quarter),
            'assignment_id' => $record['assignment_id'] ?? null,
            'assessment_method' => $record['assessment_method'] ?? null,
            'assessment_type' => $record['assessment_type'] ?? null,
            'miscue_content' => $record['miscue_content'] ?? null,
            'status' => $record['status'] ?? 'recorded',
            'status_label' => $this->statusLabel($record['status'] ?? 'recorded'),
            'total_score' => isset($record['total_score']) ? (float) $record['total_score'] : null,
            'reading_level' => $readingSummary['reading_level'],
            'reading_speed' => $readingSummary['reading_speed'],
            'word_per_minute' => $readingSummary['word_per_minute'],
            'total_miscues' => $readingSummary['total_miscues'],
            'correct_words' => $readingSummary['correct_words'],
            'passage_words' => $readingSummary['passage_words'],
            'comprehension_level' => $comprehensionSummary['comprehension_level'],
            'comprehension_rate' => $comprehensionSummary['comprehension_rate'],
            'correct_answers' => $comprehensionSummary['correct_answers'],
            'wrong_answers' => $comprehensionSummary['wrong_answers'],
            'miscue_summary' => $miscueSummary,
            'reading_overall_summary' => $readingOverallSummary,
            'comprehension_summary' => $comprehensionDetailSummary,
            'has_reading_score' => ! empty($record['reading_score']),
            'has_comprehension_score' => ! empty($record['comprehension_score']),
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
            'correct_words' => $this->summaryValue($summary, 'Number of Correct Words'),
            'passage_words' => $this->summaryValue($summary, 'Number of Words in the Passage'),
        ];
    }

    private function extractComprehensionSummary($comprehensionScore): array
    {
        $summary = $this->jsonValue($comprehensionScore, 'comprehensionSummary');

        return [
            'comprehension_level' => $this->summaryValue($summary, 'Comprehension Score'),
            'comprehension_rate' => $this->summaryValue($summary, 'Comprehension Rate'),
            'correct_answers' => $this->summaryValue($summary, 'No. of Correct Answer'),
            'wrong_answers' => $this->summaryValue($summary, 'No. of Wrong Answer'),
        ];
    }

    private function formatSummaryRows(array $summary): array
    {
        return collect($summary)
            ->map(function ($item) {
                return [
                    'type' => trim((string) ($item['type'] ?? '')),
                    'count' => $item['count'] ?? 0,
                ];
            })
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

    private function sexLabel(?string $sex): string
    {
        return match (strtoupper((string) $sex)) {
            'M' => 'Male',
            'F' => 'Female',
            default => 'Not specified',
        };
    }

    private function statusLabel(string $status): string
    {
        return collect(explode('_', $status))
            ->filter()
            ->map(fn ($part) => ucfirst($part))
            ->implode(' ');
    }

    private function districtSupervisorScope(): array
    {
        $matchesRole = function ($designation) {
            $role = strtolower(trim((string) ($designation['role_name'] ?? '')));

            return in_array($role, ['district supervisor', 'district_supervisor', 'district-supervisor', 'supervisor'], true);
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
