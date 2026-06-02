<?php

namespace App\Http\Controllers\DistrictSupervisor;

use App\Helpers\DistrictSupervisorMenuHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class DistrictSupervisorReportController extends Controller
{
    private const LANGUAGES = ['english', 'filipino'];

    public function index(Request $request)
    {
        $scope = $this->districtSupervisorScope();

        if (empty($scope['district_id']) && empty($scope['municipality_id'])) {
            return redirect()->route('signin')
                ->with('error', 'Please sign in as a district supervisor to view submitted reports.');
        }

        $schools = $this->fetchScopedSchools($scope);
        $schoolIds = collect($schools)->pluck('school_id')->filter()->values()->all();

        $schoolYears = $this->fetchReportSchoolYears($schoolIds);
        $selectedYearId = $request->query('year_id') ?: ($schoolYears[0]['year_id'] ?? null);

        $reportGroups = $selectedYearId ? $this->buildReportGroups($schoolIds, $selectedYearId) : [];
        $submittedReports = $selectedYearId ? $this->buildSubmittedSchoolReportsList($schoolIds, $selectedYearId) : [];

        return view('pages.district-supervisor.district-supervisor-reports', [
            'title' => 'District Supervisor Reports',
            'menuGroups' => DistrictSupervisorMenuHelper::getMenuGroups(),
            'schoolYears' => $schoolYears,
            'selectedYearId' => $selectedYearId,
            'reportGroups' => $reportGroups,
            'submittedReports' => $submittedReports,
        ]);
    }

    public function showSchoolReport(string $schoolReportId)
    {
        $scope = $this->districtSupervisorScope();
        $schools = $this->fetchScopedSchools($scope);
        $schoolIds = collect($schools)->pluck('school_id')->filter()->values()->all();

        $report = $this->buildSchoolReportPreview($schoolReportId, $schoolIds);

        if (! $report) {
            return redirect()->route('district-supervisor.reports')
                ->with('error', 'The selected school report was not found or does not belong to your district/municipality.');
        }

        return view('components.district-supervisor.reports.school-report-print', [
            'title' => $report['print_title'],
            'report' => $report,
        ]);
    }

    public function show(string $gradeLevelId, string $yearId, string $quarterId, string $language)
    {
        $scope = $this->districtSupervisorScope();
        $language = $this->normalizeLanguage($language);

        if (! $language) {
            abort(404);
        }

        $schools = $this->fetchScopedSchools($scope);
        $schoolIds = collect($schools)->pluck('school_id')->filter()->values()->all();
        $report = $this->buildDistrictConsolidatedReport($schoolIds, $gradeLevelId, $yearId, $quarterId, $language);

        if (! $report) {
            return redirect()->route('district-supervisor.reports')
                ->with('error', 'No submitted principal school reports were found for the selected grade level, quarter, and language.');
        }

        return view('components.district-supervisor.reports.consolidated-report-print', [
            'title' => $report['print_title'],
            'report' => $report,
        ]);
    }

    private function buildReportGroups(array $schoolIds, string $yearId): array
    {
        $schoolReports = $this->fetchSubmittedSchoolReports($schoolIds, ['year_id' => $yearId]);

        if (empty($schoolReports)) {
            return [];
        }

        $gradeIds = collect($schoolReports)->pluck('grade_level_id')->filter()->unique()->values()->all();
        $quarterIds = collect($schoolReports)->pluck('quarter_id')->filter()->unique()->values()->all();
        $schoolReportIds = collect($schoolReports)->pluck('school_report_id')->filter()->values()->all();
        $schoolIdsFromReports = collect($schoolReports)->pluck('school_id')->filter()->unique()->values()->all();
        $submitterIds = collect($schoolReports)->pluck('submitted_by')->filter()->unique()->values()->all();

        $grades = collect($this->fetchRowsByIds('grade_levels', 'grade_level_id', $gradeIds, 'grade_level_id,grade_number,school_id,is_active'))->keyBy('grade_level_id');
        $quarters = collect($this->fetchRowsByIds('quarter', 'quarter_id', $quarterIds, 'quarter_id,year_id,quarter_number,quarter_name,start_date,end_date'))->keyBy('quarter_id');
        $schoolYear = $this->fetchSingleRowById('school_year', 'year_id', $yearId, 'year_id,start_date,end_date,created_at');
        $schools = collect($this->fetchRowsByIds('schools', 'school_id', $schoolIdsFromReports, 'school_id,name,logo,address,contact,email,district_id,municipality_id'))->keyBy('school_id');
        $submitters = collect($this->fetchRowsByIds('profiles', 'id', $submitterIds, 'id,full_name,title,position,email'))->keyBy('id');
        $sectionRows = collect($this->fetchSchoolReportSections($schoolReportIds))->groupBy('school_report_id');

        $latestPerSchoolLanguage = collect($schoolReports)
            ->sortByDesc(fn ($report) => $report['updated_at'] ?? $report['submitted_at'] ?? $report['created_at'] ?? '')
            ->unique(fn ($report) => ($report['school_id'] ?? '') . '|' . ($report['grade_level_id'] ?? '') . '|' . ($report['quarter_id'] ?? '') . '|' . $this->normalizeLanguage($report['language'] ?? null))
            ->values();

        return $latestPerSchoolLanguage
            ->groupBy(fn ($report) => ($report['grade_level_id'] ?? '') . '|' . ($report['quarter_id'] ?? ''))
            ->map(function ($reports, $key) use ($grades, $quarters, $schoolYear, $schools, $submitters, $sectionRows) {
                [$gradeLevelId, $quarterId] = explode('|', $key) + [null, null];
                $grade = $grades->get($gradeLevelId, []);
                $quarter = $quarters->get($quarterId, []);
                $gradeNumber = $grade['grade_number'] ?? null;
                $languages = [];

                foreach (self::LANGUAGES as $language) {
                    $languageReports = $reports
                        ->filter(fn ($report) => $this->normalizeLanguage($report['language'] ?? null) === $language)
                        ->values();

                    if ($languageReports->isEmpty()) {
                        continue;
                    }

                    $schoolNames = $languageReports
                        ->map(fn ($report) => $schools->get($report['school_id'] ?? null)['name'] ?? 'School')
                        ->unique()
                        ->sort()
                        ->values()
                        ->all();

                    $schoolReportCards = $languageReports
                        ->map(function ($report) use ($schools, $submitters, $sectionRows) {
                            $schoolReportId = $report['school_report_id'] ?? null;
                            $sections = collect($sectionRows->get($schoolReportId, []));
                            $school = $schools->get($report['school_id'] ?? null, []);
                            $submitter = $submitters->get($report['submitted_by'] ?? null, []);

                            return [
                                'school_report_id' => $schoolReportId,
                                'school_name' => $school['name'] ?? 'School',
                                'submitted_by' => $submitter['full_name'] ?? 'Principal',
                                'submitted_at' => $report['submitted_at'] ?? null,
                                'section_count' => $sections->count(),
                                'total_pupils' => $sections->sum(fn ($row) => (int) ($row['total_assessed'] ?? $row['total_pupils'] ?? 0)),
                                'status' => strtolower((string) ($report['report_status'] ?? 'submitted')),
                            ];
                        })
                        ->sortBy('school_name')
                        ->values()
                        ->all();

                    $totalPupils = collect($schoolReportCards)->sum(fn ($report) => (int) ($report['total_pupils'] ?? 0));
                    $submittedAt = $languageReports->max('submitted_at') ?: $languageReports->max('updated_at');

                    $languages[$language] = [
                        'label' => ucfirst($language),
                        'submitted_schools_count' => count($schoolNames),
                        'school_labels' => $schoolNames,
                        'school_reports' => $schoolReportCards,
                        'total_pupils' => $totalPupils,
                        'is_ready' => $languageReports->isNotEmpty(),
                        'latest_submitted_at' => $submittedAt,
                    ];
                }

                if (empty($languages)) {
                    return null;
                }

                return [
                    'grade_level_id' => $gradeLevelId,
                    'quarter_id' => $quarterId,
                    'grade_label' => $gradeNumber ? 'Grade ' . $gradeNumber : 'Grade',
                    'quarter_label' => $this->quarterLabel($quarter),
                    'school_year_label' => $this->schoolYearLabel($schoolYear),
                    'languages' => $languages,
                ];
            })
            ->filter()
            ->sortBy([
                ['grade_label', 'asc'],
                ['quarter_label', 'asc'],
            ])
            ->values()
            ->all();
    }

    private function buildSubmittedSchoolReportsList(array $schoolIds, string $yearId): array
    {
        $schoolReports = $this->fetchSubmittedSchoolReports($schoolIds, ['year_id' => $yearId]);

        if (empty($schoolReports)) {
            return [];
        }

        $gradeIds = collect($schoolReports)->pluck('grade_level_id')->filter()->unique()->values()->all();
        $quarterIds = collect($schoolReports)->pluck('quarter_id')->filter()->unique()->values()->all();
        $reportSchoolIds = collect($schoolReports)->pluck('school_id')->filter()->unique()->values()->all();
        $submitterIds = collect($schoolReports)->pluck('submitted_by')->filter()->unique()->values()->all();
        $schoolReportIds = collect($schoolReports)->pluck('school_report_id')->filter()->values()->all();

        $grades = collect($this->fetchRowsByIds('grade_levels', 'grade_level_id', $gradeIds, 'grade_level_id,grade_number,school_id,is_active'))->keyBy('grade_level_id');
        $quarters = collect($this->fetchRowsByIds('quarter', 'quarter_id', $quarterIds, 'quarter_id,year_id,quarter_number,quarter_name,start_date,end_date'))->keyBy('quarter_id');
        $schools = collect($this->fetchRowsByIds('schools', 'school_id', $reportSchoolIds, 'school_id,name,logo,address,contact,email,district_id,municipality_id'))->keyBy('school_id');
        $submitters = collect($this->fetchRowsByIds('profiles', 'id', $submitterIds, 'id,full_name,title,position,email'))->keyBy('id');
        $sections = collect($this->fetchSchoolReportSections($schoolReportIds))->groupBy('school_report_id');

        return collect($schoolReports)
            ->sortByDesc(fn ($report) => $report['submitted_at'] ?? $report['updated_at'] ?? $report['created_at'] ?? '')
            ->map(function ($report) use ($grades, $quarters, $schools, $submitters, $sections) {
                $grade = $grades->get($report['grade_level_id'] ?? null, []);
                $quarter = $quarters->get($report['quarter_id'] ?? null, []);
                $school = $schools->get($report['school_id'] ?? null, []);
                $submitter = $submitters->get($report['submitted_by'] ?? null, []);
                $sectionRows = collect($sections->get($report['school_report_id'] ?? null, []));

                return [
                    'school_report_id' => $report['school_report_id'] ?? null,
                    'school_name' => $school['name'] ?? 'School',
                    'grade_label' => isset($grade['grade_number']) ? 'Grade ' . $grade['grade_number'] : 'Grade',
                    'quarter_label' => $this->quarterLabel($quarter),
                    'language_label' => ucfirst($this->normalizeLanguage($report['language'] ?? null) ?? 'Language'),
                    'submitted_by' => $submitter['full_name'] ?? 'Principal',
                    'submitted_at' => $report['submitted_at'] ?? null,
                    'section_count' => $sectionRows->count(),
                    'total_pupils' => $sectionRows->sum(fn ($row) => (int) ($row['total_assessed'] ?? $row['total_pupils'] ?? 0)),
                    'status' => strtolower((string) ($report['report_status'] ?? 'submitted')),
                ];
            })
            ->values()
            ->all();
    }

    private function buildSchoolReportPreview(string $schoolReportId, array $allowedSchoolIds): ?array
    {
        $report = $this->fetchSingleRowById('school_reports', 'school_report_id', $schoolReportId, 'school_report_id,created_at,updated_at,submitted_at,school_id,grade_level_id,year_id,quarter_id,language,created_by,submitted_by,report_status,remarks');

        if (! $report || ! in_array($report['school_id'] ?? null, $allowedSchoolIds, true)) {
            return null;
        }

        $school = $this->fetchSingleRowById('schools', 'school_id', $report['school_id'] ?? null, 'school_id,name,logo,address,contact,email,district_id,municipality_id');
        $district = $this->fetchSingleRowById('districts', 'district_id', $school['district_id'] ?? null, 'district_id,district_name,province,office_address,contact,email,logo');
        $municipality = $this->fetchSingleRowById('municipalities', 'municipality_id', $school['municipality_id'] ?? null, 'municipality_id,municipal_name,logo,district_id');
        $grade = $this->fetchSingleRowById('grade_levels', 'grade_level_id', $report['grade_level_id'] ?? null, 'grade_level_id,grade_number,school_id,is_active');
        $quarter = $this->fetchSingleRowById('quarter', 'quarter_id', $report['quarter_id'] ?? null, 'quarter_id,year_id,quarter_number,quarter_name,start_date,end_date');
        $schoolYear = $this->fetchSingleRowById('school_year', 'year_id', $report['year_id'] ?? null, 'year_id,start_date,end_date,created_at');
        $principal = $this->fetchSingleRowById('profiles', 'id', $report['submitted_by'] ?? null, 'id,full_name,title,position,email');
        $sectionRowsRaw = $this->fetchSchoolReportSections([$schoolReportId]);

        $sectionRows = collect($sectionRowsRaw)
            ->map(fn ($row) => $this->formatSectionReportRow($row))
            ->sortBy('section_name')
            ->values()
            ->all();

        $summary = $this->buildTotalSummary($sectionRows);
        $language = $this->normalizeLanguage($report['language'] ?? null) ?? 'english';

        return [
            'school_report_id' => $schoolReportId,
            'print_title' => 'Principal Submitted Consolidated Report in ' . ucfirst($language),
            'report_language_title' => strtoupper(ucfirst($language)),
            'language_label' => ucfirst($language),
            'school_name' => $school['name'] ?? 'School',
            'school_address' => $school['address'] ?? ($district['office_address'] ?? ''),
            'school_contact' => $school['contact'] ?? ($district['contact'] ?? ''),
            'school_email' => $school['email'] ?? ($district['email'] ?? ''),
            'district_name' => strtoupper($district['district_name'] ?? 'DISTRICT'),
            'municipality_name' => $municipality['municipal_name'] ?? '',
            'division_label' => 'Schools Division of ' . trim(str_replace(' Province', '', (string) ($district['province'] ?? 'Batangas'))),
            'region_label' => 'Region IV-CALABARZON',
            'principal_name' => $principal['full_name'] ?? 'Principal',
            'grade_label' => isset($grade['grade_number']) ? 'Grade ' . $grade['grade_number'] : 'Grade',
            'grade_report_label' => strtoupper(isset($grade['grade_number']) ? 'GRADE ' . $grade['grade_number'] : 'GRADE'),
            'quarter_label' => $this->quarterLabel($quarter),
            'quarter_report_label' => strtoupper($this->quarterReportLabel($quarter)),
            'school_year_label' => $this->schoolYearLabel($schoolYear),
            'school_year_report_label' => 'S.Y ' . $this->schoolYearCompactLabel($schoolYear),
            'submitted_at' => $report['submitted_at'] ?? null,
            'section_rows' => $sectionRows,
            'summary' => $summary,
        ];
    }

    private function buildDistrictConsolidatedReport(array $schoolIds, string $gradeLevelId, string $yearId, string $quarterId, string $language): ?array
    {
        $schoolReports = $this->fetchSubmittedSchoolReports($schoolIds, [
            'grade_level_id' => $gradeLevelId,
            'year_id' => $yearId,
            'quarter_id' => $quarterId,
        ]);

        if (empty($schoolReports)) {
            return null;
        }

        $schoolReports = collect($schoolReports)
            ->sortByDesc(fn ($report) => $report['updated_at'] ?? $report['submitted_at'] ?? $report['created_at'] ?? '')
            ->unique(fn ($report) => ($report['school_id'] ?? '') . '|' . ($report['grade_level_id'] ?? '') . '|' . ($report['quarter_id'] ?? '') . '|' . $this->normalizeLanguage($report['language'] ?? null))
            ->values()
            ->all();

        $reportSchoolIds = collect($schoolReports)->pluck('school_id')->filter()->unique()->values()->all();
        $schoolReportIds = collect($schoolReports)->pluck('school_report_id')->filter()->values()->all();

        $schools = collect($this->fetchRowsByIds('schools', 'school_id', $reportSchoolIds, 'school_id,name,logo,address,contact,email,district_id,municipality_id'))->keyBy('school_id');
        $firstSchool = $schools->first() ?: [];
        $district = $this->fetchSingleRowById('districts', 'district_id', $firstSchool['district_id'] ?? null, 'district_id,district_name,province,office_address,contact,email,logo');
        $grade = $this->fetchSingleRowById('grade_levels', 'grade_level_id', $gradeLevelId, 'grade_level_id,grade_number,school_id,is_active');
        $quarter = $this->fetchSingleRowById('quarter', 'quarter_id', $quarterId, 'quarter_id,year_id,quarter_number,quarter_name,start_date,end_date');
        $schoolYear = $this->fetchSingleRowById('school_year', 'year_id', $yearId, 'year_id,start_date,end_date,created_at');
        $supervisor = $this->fetchSingleRowById('profiles', 'id', $this->currentUserId(), 'id,full_name,title,position,email');
        $sections = collect($this->fetchSchoolReportSections($schoolReportIds))->groupBy('school_report_id');

        $schoolRows = collect($schoolReports)
            ->map(function ($report) use ($schools, $sections) {
                $sectionRows = collect($sections->get($report['school_report_id'] ?? null, []))
                    ->map(fn ($row) => $this->formatSectionReportRow($row))
                    ->values()
                    ->all();

                return [
                    'school_report_id' => $report['school_report_id'] ?? null,
                            'school_name' => $schools->get($report['school_id'] ?? null)['name'] ?? 'School',
                    'submitted_at' => $report['submitted_at'] ?? null,
                    'summary' => $this->buildTotalSummary($sectionRows),
                ];
            })
            ->sortBy('school_name')
            ->values()
            ->all();

        $totals = $this->buildTotalSummary($schoolRows);
        $languageLabel = ucfirst($language);

        return [
            'print_title' => 'District Consolidated Oral Reading and Comprehension Assessment Result in ' . $languageLabel,
            'report_language_title' => strtoupper($languageLabel),
            'language_label' => $languageLabel,
            'district_name' => strtoupper($district['district_name'] ?? 'DISTRICT'),
            'division_label' => 'Schools Division of ' . trim(str_replace(' Province', '', (string) ($district['province'] ?? 'Batangas'))),
            'region_label' => 'Region IV-CALABARZON',
            'office_address' => $district['office_address'] ?? '',
            'contact' => $district['contact'] ?? '',
            'email' => $district['email'] ?? '',
            'supervisor_name' => $supervisor['full_name'] ?? 'District Supervisor',
            'supervisor_title' => $supervisor['title'] ?? ($supervisor['position'] ?? null),
            'grade_label' => isset($grade['grade_number']) ? 'Grade ' . $grade['grade_number'] : 'Grade',
            'grade_report_label' => strtoupper(isset($grade['grade_number']) ? 'GRADE ' . $grade['grade_number'] : 'GRADE'),
            'quarter_label' => $this->quarterLabel($quarter),
            'quarter_report_label' => strtoupper($this->quarterReportLabel($quarter)),
            'school_year_label' => $this->schoolYearLabel($schoolYear),
            'school_year_report_label' => 'S.Y ' . $this->schoolYearCompactLabel($schoolYear),
            'school_rows' => $schoolRows,
            'summary' => $totals,
        ];
    }

    private function formatSectionReportRow(array $row): array
    {
        $remarks = $this->decodeJson($row['remarks'] ?? null);
        $summary = $this->emptySummary();

        $summary['total'] = (int) ($row['total_assessed'] ?? $row['total_pupils'] ?? 0);
        $summary['male'] = (int) ($remarks['male'] ?? 0);
        $summary['female'] = (int) ($remarks['female'] ?? 0);
        $summary['passage']['Non-Reader'] = (int) ($row['non_reader_count'] ?? ($remarks['passage']['Non-Reader'] ?? 0));

        foreach (['Struggling', 'Slow', 'Average', 'Fast'] as $category) {
            $summary['passage'][$category] = (int) ($remarks['passage'][$category] ?? 0);
        }

        $summary['comprehension']['Independent'] = (int) ($row['independent_count'] ?? ($remarks['comprehension']['Independent'] ?? 0));
        $summary['comprehension']['Instructional'] = (int) ($row['instructional_count'] ?? ($remarks['comprehension']['Instructional'] ?? 0));
        $summary['comprehension']['Frustrated'] = (int) ($row['frustration_count'] ?? ($remarks['comprehension']['Frustrated'] ?? 0));

        return [
            'section_id' => $row['section_id'] ?? null,
            'section_name' => $remarks['section_name'] ?? 'Section',
            'summary' => $summary,
        ];
    }

    private function buildTotalSummary(array $rows): array
    {
        $total = $this->emptySummary();

        foreach ($rows as $row) {
            $summary = $row['summary'] ?? $this->emptySummary();
            $total['total'] += (int) ($summary['total'] ?? 0);
            $total['male'] += (int) ($summary['male'] ?? 0);
            $total['female'] += (int) ($summary['female'] ?? 0);

            foreach (array_keys($total['passage']) as $category) {
                $total['passage'][$category] += (int) ($summary['passage'][$category] ?? 0);
            }

            foreach (array_keys($total['comprehension']) as $category) {
                $total['comprehension'][$category] += (int) ($summary['comprehension'][$category] ?? 0);
            }
        }

        return $total;
    }

    private function emptySummary(): array
    {
        return [
            'total' => 0,
            'male' => 0,
            'female' => 0,
            'passage' => [
                'Non-Reader' => 0,
                'Struggling' => 0,
                'Slow' => 0,
                'Average' => 0,
                'Fast' => 0,
            ],
            'comprehension' => [
                'Independent' => 0,
                'Instructional' => 0,
                'Frustrated' => 0,
            ],
        ];
    }

    private function fetchSubmittedSchoolReports(array $schoolIds, array $filters = []): array
    {
        $schoolIds = collect($schoolIds)->filter()->unique()->values()->all();

        if (empty($schoolIds)) {
            return [];
        }

        $query = [
            'select' => 'school_report_id,created_at,updated_at,submitted_at,school_id,grade_level_id,year_id,quarter_id,language,created_by,submitted_by,report_status,remarks',
            'school_id' => 'in.(' . $this->postgrestInList($schoolIds) . ')',
            'report_status' => 'in.("submitted","reviewed","approved")',
            'order' => 'updated_at.desc',
        ];

        foreach (['grade_level_id', 'year_id', 'quarter_id'] as $field) {
            if (! empty($filters[$field])) {
                $query[$field] = 'eq.' . $filters[$field];
            }
        }

        if (! empty($filters['language'])) {
            $query['language'] = 'eq.' . $filters['language'];
        }

        $response = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/school_reports', $query);

        if (! $response->successful()) {
            report('Failed to fetch district supervisor submitted school reports: ' . $response->body());
            return [];
        }

        return $response->json();
    }

    private function fetchReportSchoolYears(array $schoolIds): array
    {
        $schoolReports = $this->fetchSubmittedSchoolReports($schoolIds);
        $yearIds = collect($schoolReports)->pluck('year_id')->filter()->unique()->values()->all();

        if (empty($yearIds)) {
            return [];
        }

        return collect($this->fetchRowsByIds('school_year', 'year_id', $yearIds, 'year_id,start_date,end_date,created_at'))
            ->map(fn ($year) => array_merge($year, ['label' => $this->schoolYearLabel($year)]))
            ->sortByDesc('label')
            ->values()
            ->all();
    }

    private function fetchScopedSchools(array $scope): array
    {
        $query = [
            'select' => 'school_id,name,logo,address,contact,email,district_id,municipality_id',
            'order' => 'name.asc',
        ];

        if (! empty($scope['municipality_id'])) {
            $query['municipality_id'] = 'eq.' . $scope['municipality_id'];
        } elseif (! empty($scope['district_id'])) {
            $query['district_id'] = 'eq.' . $scope['district_id'];
        } else {
            return [];
        }

        $response = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/schools', $query);

        if (! $response->successful()) {
            report('Failed to fetch district supervisor report schools: ' . $response->body());
            return [];
        }

        return $response->json();
    }

    private function fetchSchoolReportSections(array $schoolReportIds): array
    {
        $schoolReportIds = collect($schoolReportIds)->filter()->unique()->values()->all();

        if (empty($schoolReportIds)) {
            return [];
        }

        $response = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/school_report_sections', [
                'select' => 'school_report_section_id,created_at,school_report_id,section_id,class_report_id,total_pupils,total_assessed,independent_count,instructional_count,frustration_count,non_reader_count,remarks',
                'school_report_id' => 'in.(' . $this->postgrestInList($schoolReportIds) . ')',
            ]);

        if (! $response->successful()) {
            report('Failed to fetch school report sections for district supervisor: ' . $response->body());
            return [];
        }

        return $response->json();
    }

    private function fetchSingleRowById(string $table, string $idField, $id, string $select): ?array
    {
        if (! $id && $id !== 0) {
            return null;
        }

        $response = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/' . $table, [
                'select' => $select,
                $idField => 'eq.' . $id,
                'limit' => 1,
            ]);

        if (! $response->successful()) {
            report("Failed to fetch {$table} row for district supervisor report: " . $response->body());
            return null;
        }

        return $response->json()[0] ?? null;
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
            report("Failed to fetch {$table} rows for district supervisor report: " . $response->body());
            return [];
        }

        return $response->json();
    }

    private function decodeJson($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function normalizeLanguage(?string $language): ?string
    {
        $value = strtolower(trim((string) $language));

        return in_array($value, self::LANGUAGES, true) ? $value : null;
    }

    private function schoolYearLabel(?array $year): string
    {
        $start = ! empty($year['start_date']) ? date('Y', strtotime($year['start_date'])) : null;
        $end = ! empty($year['end_date']) ? date('Y', strtotime($year['end_date'])) : null;

        return $start && $end ? $start . ' - ' . $end : 'School Year';
    }

    private function schoolYearCompactLabel(?array $year): string
    {
        $start = ! empty($year['start_date']) ? date('Y', strtotime($year['start_date'])) : null;
        $end = ! empty($year['end_date']) ? date('Y', strtotime($year['end_date'])) : null;

        return $start && $end ? $start . '-' . $end : 'School Year';
    }

    private function quarterLabel(?array $quarter): string
    {
        if (empty($quarter)) {
            return 'Quarter';
        }

        $name = $quarter['quarter_name'] ?? 'Quarter';
        $number = $quarter['quarter_number'] ?? null;

        return $number ? 'Q' . $number . ' - ' . $name : $name;
    }

    private function quarterReportLabel(?array $quarter): string
    {
        if (empty($quarter)) {
            return 'Quarter';
        }

        $name = strtoupper((string) ($quarter['quarter_name'] ?? 'Quarter'));

        if (str_contains($name, 'FIRST')) {
            return 'First Quarter';
        }

        if (str_contains($name, 'SECOND')) {
            return 'Second Quarter';
        }

        if (str_contains($name, 'THIRD')) {
            return 'Third Quarter';
        }

        if (str_contains($name, 'FOURTH')) {
            return 'Fourth Quarter';
        }

        $number = (int) ($quarter['quarter_number'] ?? 0);

        return match ($number) {
            1 => 'First Quarter',
            2 => 'Second Quarter',
            3 => 'Third Quarter',
            4 => 'Fourth Quarter',
            default => $quarter['quarter_name'] ?? 'Quarter',
        };
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

    private function currentUserId(): ?string
    {
        return session('supabase_user.id') ?? session('auth_user.id') ?? session('user.id') ?? auth()->id();
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
