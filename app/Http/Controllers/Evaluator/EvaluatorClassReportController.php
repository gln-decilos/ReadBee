<?php

namespace App\Http\Controllers\Evaluator;

use App\Helpers\EvaluatorMenuHelper;
use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class EvaluatorClassReportController extends Controller
{
    private const LANGUAGES = ['english', 'filipino'];

    public function index(Request $request)
    {
        $evaluatorId = $this->currentEvaluatorId();

        if (! $evaluatorId) {
            return redirect()->route('signin')
                ->with('error', 'Please sign in as an evaluator to generate class reports.');
        }

        $schoolYears = $this->fetchSchoolYears($evaluatorId);
        $selectedYearId = $request->query('year_id') ?: ($schoolYears[0]['year_id'] ?? null);
        $assignments = $selectedYearId ? $this->buildReportAssignments($evaluatorId, $selectedYearId) : [];

        return view('pages.evaluator.evaluator-class-reports', [
            'title' => 'Class Reports',
            'menuGroups' => EvaluatorMenuHelper::getMenuGroups(),
            'schoolYears' => $schoolYears,
            'selectedYearId' => $selectedYearId,
            'assignments' => $assignments,
        ]);
    }

    public function show(Request $request, string $assignmentId, string $language)
    {
        $evaluatorId = $this->currentEvaluatorId();
        $language = $this->normalizeLanguage($language);

        if (! $evaluatorId) {
            return redirect()->route('signin')
                ->with('error', 'Please sign in as an evaluator to generate class reports.');
        }

        if (! $language) {
            abort(404);
        }

        $report = $this->buildReportData($evaluatorId, $assignmentId, $language);

        if (! $report) {
            return redirect()->route('evaluator.reports')
                ->with('error', 'The selected assignment could not be found or is not assigned to you.');
        }

        return view('components.evaluator.reports.class-report-print', [
            'title' => $report['print_title'],
            'report' => $report,
        ]);
    }

    public function submit(Request $request, string $assignmentId, string $language)
    {
        $evaluatorId = $this->currentEvaluatorId();
        $language = $this->normalizeLanguage($language);

        if (! $evaluatorId) {
            return redirect()->route('signin')
                ->with('error', 'Please sign in as an evaluator to submit class reports.');
        }

        if (! $language) {
            abort(404);
        }

        $report = $this->buildReportData($evaluatorId, $assignmentId, $language);

        if (! $report) {
            return redirect()->route('evaluator.reports')
                ->with('error', 'The selected assignment could not be found or is not assigned to you.');
        }

        if (! $report['is_ready']) {
            return redirect()->route('evaluator.reports.show', [
                'assignmentId' => $assignmentId,
                'language' => $language,
            ])->with('error', 'This report cannot be submitted yet. Please complete all pupil assessments for ' . ucfirst($language) . '.');
        }

        if (in_array(strtolower((string) ($report['existing_report_status'] ?? 'draft')), ['submitted', 'reviewed', 'approved'], true)) {
            return redirect()->route('evaluator.reports.show', [
                'assignmentId' => $assignmentId,
                'language' => $language,
            ])->with('info', ucfirst($language) . ' report has already been submitted to the principal.');
        }

        $classReportId = $this->saveClassReport($report, $evaluatorId);

        if (! $classReportId) {
            return back()->with('error', 'Unable to save the class report. Please try again.');
        }

        $this->syncClassReportPupils($classReportId, $report);
        $isAssignmentReportComplete = $this->markAssignmentSubmittedWhenComplete($assignmentId, $report['year_id'], $report['quarter_id'], $evaluatorId);
        $this->notifyPrincipalClassReportSubmitted($report, $classReportId);

        if ($isAssignmentReportComplete) {
            $this->notifyPrincipalAssignmentReportCompleted($report);
        }

        return redirect()->route('evaluator.reports.show', [
            'assignmentId' => $assignmentId,
            'language' => $language,
        ])->with('success', ucfirst($language) . ' report was submitted to the principal.');
    }

    private function buildReportAssignments(string $evaluatorId, string $yearId): array
    {
        $assignments = $this->fetchConfirmedAssignments($evaluatorId, $yearId);

        return collect($assignments)
            ->map(function ($assignment) use ($evaluatorId) {
                $base = $this->buildAssignmentBase($evaluatorId, $assignment);

                if (! $base) {
                    return null;
                }

                $reports = $this->fetchExistingReports(
                    $base['school_id'],
                    $base['section_id'],
                    $base['grade_level_id'],
                    $base['year_id'],
                    $base['quarter_id']
                );

                $languageSummaries = [];

                foreach (self::LANGUAGES as $language) {
                    $rows = $this->buildReportRows($base['pupils'], $base['records'], $language);
                    $assessed = collect($rows)->where('is_assessed', true)->count();
                    $existingReport = collect($reports)->first(fn ($report) => $this->normalizeLanguage($report['language'] ?? null) === $language);

                    $languageSummaries[$language] = [
                        'label' => ucfirst($language),
                        'assessed_count' => $assessed,
                        'total_pupils' => count($rows),
                        'missing_count' => max(count($rows) - $assessed, 0),
                        'is_ready' => count($rows) > 0 && $assessed === count($rows),
                        'report_status' => $existingReport['report_status'] ?? 'draft',
                        'submitted_at' => $existingReport['submitted_at'] ?? null,
                    ];
                }

                return [
                    'assignment_id' => $base['assignment_id'],
                    'school_name' => $base['school_name'],
                    'grade_label' => $base['grade_label'],
                    'section_name' => $base['section_name'],
                    'quarter_label' => $base['quarter_report_label'],
                    'school_year_label' => $base['school_year_report_label'],
                    'assessment_date' => $base['assessment_date'],
                    'report_status' => $assignment['report_status'] ?? 'not_submitted',
                    'languages' => $languageSummaries,
                ];
            })
            ->filter()
            ->sortBy([
                ['grade_label', 'asc'],
                ['section_name', 'asc'],
                ['assessment_date', 'asc'],
            ])
            ->values()
            ->all();
    }

    private function buildReportData(string $evaluatorId, string $assignmentId, string $language): ?array
    {
        $assignment = $this->fetchAssignment($evaluatorId, $assignmentId);

        if (! $assignment) {
            return null;
        }

        $base = $this->buildAssignmentBase($evaluatorId, $assignment);

        if (! $base) {
            return null;
        }

        $rows = $this->buildReportRows($base['pupils'], $base['records'], $language);
        $summary = $this->buildReportSummary($rows);
        $existingReports = $this->fetchExistingReports(
            $base['school_id'],
            $base['section_id'],
            $base['grade_level_id'],
            $base['year_id'],
            $base['quarter_id']
        );
        $existingReport = collect($existingReports)->first(fn ($report) => $this->normalizeLanguage($report['language'] ?? null) === $language);

        $languageLabel = ucfirst($language);
        $printTitle = 'Oral Reading and Comprehension Assessment Result in ' . $languageLabel;

        return array_merge($base, [
            'language' => $language,
            'language_label' => $languageLabel,
            'report_language_title' => strtoupper($languageLabel),
            'print_title' => $printTitle,
            'rows' => $rows,
            'summary' => $summary,
            'is_ready' => count($rows) > 0 && $summary['missing'] === 0,
            'existing_report_id' => $existingReport['class_report_id'] ?? null,
            'existing_report_status' => $existingReport['report_status'] ?? 'draft',
            'existing_submitted_at' => $existingReport['submitted_at'] ?? null,
        ]);
    }

    private function buildAssignmentBase(string $evaluatorId, array $assignment): ?array
    {
        $section = $this->fetchSingleRowById('class_sections', 'section_id', $assignment['section_id'] ?? null, 'section_id,school_id,year_id,grade_level_id,section_name,status,adviser_name');

        if (! $section) {
            return null;
        }

        $grade = $this->fetchSingleRowById('grade_levels', 'grade_level_id', $section['grade_level_id'] ?? null, 'grade_level_id,grade_number,school_id,is_active');
        $school = $this->fetchSingleRowById('schools', 'school_id', $section['school_id'] ?? null, 'school_id,name,address,contact,email,district_id,municipality_id');
        $district = $this->fetchSingleRowById('districts', 'district_id', $school['district_id'] ?? null, 'district_id,district_name,province,office_address,contact,email');
        $quarter = $this->fetchSingleRowById('quarter', 'quarter_id', $assignment['quarter_id'] ?? null, 'quarter_id,year_id,quarter_number,quarter_name,start_date,end_date');
        $schoolYear = $this->fetchSingleRowById('school_year', 'year_id', $assignment['year_id'] ?? null, 'year_id,start_date,end_date,created_at');
        $evaluatorProfile = $this->fetchSingleRowById('profiles', 'id', $evaluatorId, 'id,full_name,title,position,email');
        $pupils = $this->fetchAssignedPupils($section['section_id']);
        $records = $this->fetchFormattedRecords($evaluatorId, $assignment, collect($pupils)->pluck('pupil_id')->filter()->values()->all());

        $gradeNumber = $grade['grade_number'] ?? null;
        $sectionName = $section['section_name'] ?? 'Section';
        $districtName = $district['district_name'] ?? 'DISTRICT I';
        $divisionProvince = trim(str_replace(' Province', '', (string) ($district['province'] ?? 'Batangas')));
        $divisionProvince = $divisionProvince !== '' ? $divisionProvince : 'Batangas';

        return [
            'assignment_id' => $assignment['assignment_id'] ?? null,
            'schedule_id' => $assignment['schedule_id'] ?? null,
            'school_id' => $section['school_id'] ?? null,
            'section_id' => $section['section_id'] ?? null,
            'grade_level_id' => $section['grade_level_id'] ?? null,
            'year_id' => $assignment['year_id'] ?? null,
            'quarter_id' => $assignment['quarter_id'] ?? null,
            'assessment_date' => $assignment['assessment_date'] ?? null,
            'school_name' => $school['name'] ?? 'School',
            'school_address' => $school['address'] ?? ($district['office_address'] ?? ''),
            'school_contact' => $school['contact'] ?? ($district['contact'] ?? ''),
            'school_email' => $school['email'] ?? ($district['email'] ?? ''),
            'district_name' => strtoupper($districtName),
            'division_label' => 'Schools Division of ' . $divisionProvince,
            'region_label' => 'Region IV-CALABARZON',
            'evaluator_name' => $evaluatorProfile['full_name'] ?? 'Evaluator',
            'evaluator_title' => $evaluatorProfile['title'] ?? ($evaluatorProfile['position'] ?? null),
            'grade_number' => $gradeNumber,
            'grade_label' => $gradeNumber ? 'Grade ' . $gradeNumber : 'Grade',
            'section_name' => $sectionName,
            'grade_section_report_label' => strtoupper(($gradeNumber ? 'GRADE ' . $gradeNumber : 'GRADE') . ' - ' . $sectionName),
            'quarter_label' => $this->quarterLabel($quarter),
            'quarter_report_label' => strtoupper($this->quarterReportLabel($quarter)),
            'school_year_label' => $this->schoolYearLabel($schoolYear),
            'school_year_report_label' => 'S.Y ' . $this->schoolYearCompactLabel($schoolYear),
            'adviser_name' => $section['adviser_name'] ?? null,
            'pupils' => $pupils,
            'records' => $records,
        ];
    }

    private function buildReportRows(array $pupils, array $records, string $language): array
    {
        return collect($pupils)
            ->map(function ($pupil) use ($records, $language) {
                $record = collect($records)
                    ->where('pupil_id', $pupil['pupil_id'] ?? null)
                    ->where('language_key', $language)
                    ->sortByDesc(fn ($item) => $item['updated_at'] ?? $item['created_at'] ?? '')
                    ->first();

                $passageCategory = $record ? $this->passageCategory($record['reading_speed'] ?? null, $record['reading_level'] ?? null) : null;
                $comprehensionCategory = $record ? $this->comprehensionCategory($record['comprehension_level'] ?? null) : null;

                return [
                    'pupil_id' => $pupil['pupil_id'] ?? null,
                    'pupil_name' => $pupil['full_name'] ?? 'Unnamed pupil',
                    'sex' => strtoupper((string) ($pupil['sex'] ?? '')),
                    'assessment_record_id' => $record['assessment_record_id'] ?? null,
                    'is_assessed' => (bool) $record,
                    'passage_category' => $passageCategory,
                    'comprehension_category' => $comprehensionCategory,
                    'reading_level' => $record['reading_level'] ?? null,
                    'reading_speed' => $record['reading_speed'] ?? null,
                    'comprehension_level' => $record['comprehension_level'] ?? null,
                    'reading_score' => $record['reading_score'] ?? null,
                    'comprehension_score' => $record['comprehension_score'] ?? null,
                ];
            })
            ->sortBy('pupil_name')
            ->values()
            ->all();
    }

    private function buildReportSummary(array $rows): array
    {
        $passageCategories = ['Non-Reader', 'Struggling', 'Slow', 'Average', 'Fast'];
        $comprehensionCategories = ['Independent', 'Instructional', 'Frustrated'];
        $summary = [
            'total' => count($rows),
            'male' => collect($rows)->where('sex', 'M')->count(),
            'female' => collect($rows)->where('sex', 'F')->count(),
            'assessed' => collect($rows)->where('is_assessed', true)->count(),
            'missing' => collect($rows)->where('is_assessed', false)->count(),
            'passage' => [],
            'comprehension' => [],
        ];

        foreach ($passageCategories as $category) {
            $summary['passage'][$category] = collect($rows)->where('passage_category', $category)->count();
        }

        foreach ($comprehensionCategories as $category) {
            $summary['comprehension'][$category] = collect($rows)->where('comprehension_category', $category)->count();
        }

        return $summary;
    }

    private function notifications(): NotificationService
    {
        return app(NotificationService::class);
    }

    private function notifyPrincipalClassReportSubmitted(array $report, string $classReportId): void
    {
        $principalIds = $this->notifications()->principalUserIdsForSchool($report['school_id'] ?? null);

        $this->notifications()->createForUsers(
            $principalIds,
            'Class report submitted',
            ($report['evaluator_name'] ?? 'An evaluator') . ' submitted the ' . ucfirst($report['language'] ?? 'class') . ' report for ' . ($report['grade_label'] ?? 'Grade') . ' - ' . ($report['section_name'] ?? 'Section') . '.',
            route('principal.reports.class-report', ['classReportId' => $classReportId], false),
            'class_report_submitted'
        );
    }

    private function notifyPrincipalAssignmentReportCompleted(array $report): void
    {
        $principalIds = $this->notifications()->principalUserIdsForSchool($report['school_id'] ?? null);

        $this->notifications()->createForUsers(
            $principalIds,
            'Evaluator reports completed',
            ($report['evaluator_name'] ?? 'An evaluator') . ' completed all required reports for ' . ($report['grade_label'] ?? 'Grade') . ' - ' . ($report['section_name'] ?? 'Section') . '.',
            route('principal.reports', ['year_id' => $report['year_id'] ?? null], false),
            'assignment_reports_completed'
        );
    }

    private function saveClassReport(array $report, string $evaluatorId): ?string
    {
        $payload = [
            'school_id' => $report['school_id'],
            'section_id' => $report['section_id'],
            'grade_level_id' => $report['grade_level_id'],
            'year_id' => $report['year_id'],
            'quarter_id' => $report['quarter_id'],
            'language' => $report['language'],
            'submitted_by' => $evaluatorId,
            'report_status' => 'submitted',
            'submitted_at' => now()->toISOString(),
            'updated_at' => now()->toISOString(),
            'remarks' => 'Submitted by evaluator.',
        ];

        if ($report['existing_report_id']) {
            $response = Http::withHeaders($this->supabaseWriteHeaders())
                ->patch(
                    $this->supabaseUrl() . '/rest/v1/class_reports?class_report_id=eq.' . rawurlencode($report['existing_report_id']),
                    $payload
                );
        } else {
            $payload['created_at'] = now()->toISOString();

            $response = Http::withHeaders($this->supabaseWriteHeaders())
                ->post($this->supabaseUrl() . '/rest/v1/class_reports', $payload);
        }

        if (! $response->successful()) {
            report('Failed to save evaluator class report: ' . $response->body());
            return null;
        }

        $saved = $response->json();
        $savedReport = is_array($saved) && isset($saved[0]) ? $saved[0] : $saved;

        return $savedReport['class_report_id'] ?? $report['existing_report_id'] ?? null;
    }

    private function syncClassReportPupils(string $classReportId, array $report): void
    {
        Http::withHeaders($this->supabaseHeaders())
            ->delete($this->supabaseUrl() . '/rest/v1/class_report_pupils?class_report_id=eq.' . rawurlencode($classReportId));

        $rows = collect($report['rows'])
            ->map(function ($row) use ($classReportId) {
                return [
                    'class_report_id' => $classReportId,
                    'pupil_id' => $row['pupil_id'],
                    'assessment_record_id' => $row['assessment_record_id'],
                    'reading_level' => $row['reading_speed'] ?: $row['reading_level'],
                    'reading_score' => $row['reading_score'] ?: null,
                    'comprehension_score' => $row['comprehension_score'] ?: null,
                    'remarks' => $row['is_assessed'] ? 'Assessed' : 'No assessment record',
                ];
            })
            ->filter(fn ($row) => ! empty($row['pupil_id']))
            ->values()
            ->all();

        if (empty($rows)) {
            return;
        }

        $response = Http::withHeaders($this->supabaseWriteHeaders())
            ->post($this->supabaseUrl() . '/rest/v1/class_report_pupils', $rows);

        if (! $response->successful()) {
            report('Failed to save evaluator class report pupil rows: ' . $response->body());
        }
    }

    private function markAssignmentSubmittedWhenComplete(string $assignmentId, string $yearId, string $quarterId, string $evaluatorId): bool
    {
        $assignment = $this->fetchAssignment($evaluatorId, $assignmentId);

        if (! $assignment) {
            return false;
        }

        $base = $this->buildAssignmentBase($evaluatorId, $assignment);

        if (! $base) {
            return false;
        }

        $reports = $this->fetchExistingReports(
            $base['school_id'],
            $base['section_id'],
            $base['grade_level_id'],
            $yearId,
            $quarterId
        );

        $submittedLanguages = collect($reports)
            ->where('report_status', 'submitted')
            ->map(fn ($report) => $this->normalizeLanguage($report['language'] ?? null))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (count(array_intersect(self::LANGUAGES, $submittedLanguages)) < 2) {
            return false;
        }

        $response = Http::withHeaders($this->supabaseWriteHeaders())
            ->patch(
                $this->supabaseUrl() . '/rest/v1/assigned_evaluators?assignment_id=eq.' . rawurlencode($assignmentId),
                [
                    'report_status' => 'submitted',
                    'updated_at' => now()->toISOString(),
                ]
            );

        if (! $response->successful()) {
            report('Failed to update evaluator assignment report status: ' . $response->body());
            return false;
        }

        return true;
    }

    private function fetchConfirmedAssignments(string $evaluatorId, string $yearId): array
    {
        $response = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/assigned_evaluators', [
                'select' => 'assignment_id,schedule_id,evaluator_user_id,section_id,year_id,quarter_id,assigned_at,confirmation_status,assessment_status,report_status,assessment_date,created_at,updated_at',
                'evaluator_user_id' => 'eq.' . $evaluatorId,
                'year_id' => 'eq.' . $yearId,
                'confirmation_status' => 'eq.confirmed',
                'order' => 'assessment_date.asc',
            ]);

        if (! $response->successful()) {
            report('Failed to fetch evaluator assignments for reports: ' . $response->body());
            return [];
        }

        return $response->json();
    }

    private function fetchAssignment(string $evaluatorId, string $assignmentId): ?array
    {
        $response = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/assigned_evaluators', [
                'select' => 'assignment_id,schedule_id,evaluator_user_id,section_id,year_id,quarter_id,assigned_at,confirmation_status,assessment_status,report_status,assessment_date,created_at,updated_at',
                'assignment_id' => 'eq.' . $assignmentId,
                'evaluator_user_id' => 'eq.' . $evaluatorId,
                'confirmation_status' => 'eq.confirmed',
                'limit' => 1,
            ]);

        if (! $response->successful()) {
            report('Failed to fetch evaluator report assignment: ' . $response->body());
            return null;
        }

        return $response->json()[0] ?? null;
    }

    private function fetchAssignedPupils(?string $sectionId): array
    {
        if (! $sectionId) {
            return [];
        }

        $response = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/pupils', [
                'select' => 'pupil_id,lrn,full_name,sex,age,school_id,section_id,grade_level_id,status',
                'section_id' => 'eq.' . $sectionId,
                'status' => 'eq.enrolled',
                'order' => 'full_name.asc',
            ]);

        if (! $response->successful()) {
            report('Failed to fetch pupils for evaluator report: ' . $response->body());
            return [];
        }

        return $response->json();
    }

    private function fetchFormattedRecords(string $evaluatorId, array $assignment, array $pupilIds): array
    {
        $pupilIds = collect($pupilIds)->filter()->unique()->values()->all();

        if (empty($pupilIds)) {
            return [];
        }

        $query = [
            'select' => 'assessment_record_id,created_at,updated_at,pupil_id,evaluator_user_id,material_id,schedule_id,year_id,quarter_id,assessment_method,assessment_type,reading_score,comprehension_score,total_score,reading_level,status,assignment_id,miscue_content',
            'evaluator_user_id' => 'eq.' . $evaluatorId,
            'year_id' => 'eq.' . ($assignment['year_id'] ?? ''),
            'quarter_id' => 'eq.' . ($assignment['quarter_id'] ?? ''),
            'pupil_id' => 'in.(' . $this->postgrestInList($pupilIds) . ')',
            'status' => 'neq.draft',
            'order' => 'updated_at.desc',
        ];

        $response = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/assessment_records', $query);

        if (! $response->successful()) {
            report('Failed to fetch assessment records for evaluator report: ' . $response->body());
            return [];
        }

        $records = collect($response->json())
            ->filter(fn ($record) => $this->recordBelongsToAssignment($record, $assignment, $pupilIds))
            ->values()
            ->all();

        $materialIds = collect($records)->pluck('material_id')->filter()->unique()->values()->all();
        $materials = collect($this->fetchRowsByIds('reading_materials', 'material_id', $materialIds, 'material_id,title,language,word_count,grade_level_id'))
            ->keyBy('material_id')
            ->all();

        return collect($records)
            ->map(fn ($record) => $this->formatRecord($record, $materials[$record['material_id'] ?? null] ?? []))
            ->values()
            ->all();
    }

    private function fetchExistingReports(?string $schoolId, ?string $sectionId, ?string $gradeLevelId, ?string $yearId, ?string $quarterId): array
    {
        if (! $schoolId || ! $sectionId || ! $gradeLevelId || ! $yearId || ! $quarterId) {
            return [];
        }

        $response = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/class_reports', [
                'select' => 'class_report_id,school_id,section_id,grade_level_id,year_id,quarter_id,language,submitted_by,report_status,submitted_at,created_at,updated_at,remarks',
                'school_id' => 'eq.' . $schoolId,
                'section_id' => 'eq.' . $sectionId,
                'grade_level_id' => 'eq.' . $gradeLevelId,
                'year_id' => 'eq.' . $yearId,
                'quarter_id' => 'eq.' . $quarterId,
                'order' => 'updated_at.desc',
            ]);

        if (! $response->successful()) {
            report('Failed to fetch existing class reports: ' . $response->body());
            return [];
        }

        return $response->json();
    }

    private function fetchSchoolYears(string $evaluatorId): array
    {
        $assignmentResponse = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/assigned_evaluators', [
                'select' => 'year_id',
                'evaluator_user_id' => 'eq.' . $evaluatorId,
                'confirmation_status' => 'eq.confirmed',
                'order' => 'assessment_date.desc',
            ]);

        if (! $assignmentResponse->successful()) {
            report('Failed to fetch school years for evaluator reports: ' . $assignmentResponse->body());
            return [];
        }

        $yearIds = collect($assignmentResponse->json())->pluck('year_id')->filter()->unique()->values()->all();

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
            report('Failed to fetch school year rows for evaluator reports: ' . $response->body());
            return [];
        }

        return collect($response->json())
            ->map(fn ($year) => array_merge($year, ['label' => $this->schoolYearLabel($year)]))
            ->values()
            ->all();
    }

    private function fetchSingleRowById(string $table, string $idField, $id, string $select): ?array
    {
        if (! $id && $id !== 0) {
            return null;
        }

        $cacheKey = 'evaluator_report_single:' . md5($table . '|' . $idField . '|' . (string) $id . '|' . $select);

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($table, $idField, $id, $select) {
            $response = Http::withHeaders($this->supabaseHeaders())
                ->get($this->supabaseUrl() . '/rest/v1/' . $table, [
                    'select' => $select,
                    $idField => 'eq.' . $id,
                    'limit' => 1,
                ]);

            if (! $response->successful()) {
                report("Failed to fetch {$table} row for evaluator report: " . $response->body());
                return null;
            }

            return $response->json()[0] ?? null;
        });
    }

    private function fetchRowsByIds(string $table, string $idField, array $ids, string $select): array
    {
        $ids = collect($ids)->filter()->unique()->values()->all();

        if (empty($ids)) {
            return [];
        }

        $cacheKey = 'evaluator_report_many:' . md5($table . '|' . $idField . '|' . implode(',', $ids) . '|' . $select);

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($table, $idField, $ids, $select) {
            $response = Http::withHeaders($this->supabaseHeaders())
                ->get($this->supabaseUrl() . '/rest/v1/' . $table, [
                    'select' => $select,
                    $idField => 'in.(' . $this->postgrestInList($ids) . ')',
                ]);

            if (! $response->successful()) {
                report("Failed to fetch {$table} rows for evaluator report: " . $response->body());
                return [];
            }

            return $response->json();
        });
    }

    private function recordBelongsToAssignment(array $record, array $assignment, array $assignmentPupilIds): bool
    {
        if (! in_array($record['pupil_id'] ?? null, $assignmentPupilIds, true)) {
            return false;
        }

        if (! empty($record['assignment_id'])) {
            return (string) $record['assignment_id'] === (string) ($assignment['assignment_id'] ?? '');
        }

        if ((string) ($record['year_id'] ?? '') !== (string) ($assignment['year_id'] ?? '')) {
            return false;
        }

        if ((string) ($record['quarter_id'] ?? '') !== (string) ($assignment['quarter_id'] ?? '')) {
            return false;
        }

        if (! empty($record['schedule_id']) && ! empty($assignment['schedule_id'])) {
            return (string) $record['schedule_id'] === (string) $assignment['schedule_id'];
        }

        return true;
    }

    private function formatRecord(array $record, array $material): array
    {
        $readingScore = $this->decodeJson($record['reading_score'] ?? null);
        $comprehensionScore = $this->decodeJson($record['comprehension_score'] ?? null);
        $readingSummary = $this->jsonValue($readingScore, 'miscueOverallSummary');
        $comprehensionSummary = $this->jsonValue($comprehensionScore, 'comprehensionSummary');

        return [
            'assessment_record_id' => $record['assessment_record_id'] ?? null,
            'created_at' => $record['created_at'] ?? null,
            'updated_at' => $record['updated_at'] ?? null,
            'pupil_id' => $record['pupil_id'] ?? null,
            'material_id' => $record['material_id'] ?? null,
            'material_title' => $material['title'] ?? 'Reading Material',
            'language_key' => $this->normalizeLanguage($material['language'] ?? null),
            'reading_score' => $readingScore,
            'comprehension_score' => $comprehensionScore,
            'reading_level' => $record['reading_level'] ?? $this->summaryValue($readingSummary, 'Reading Level'),
            'reading_speed' => $this->summaryValue($readingSummary, 'Reading Speed'),
            'comprehension_level' => $this->summaryValue($comprehensionSummary, 'Comprehension Score'),
        ];
    }

    private function passageCategory(?string $readingSpeed, ?string $readingLevel): ?string
    {
        $text = strtolower(trim((string) ($readingSpeed ?: $readingLevel)));

        if ($text === '') {
            return null;
        }

        if (str_contains($text, 'non')) {
            return 'Non-Reader';
        }

        if (str_contains($text, 'struggl')) {
            return 'Struggling';
        }

        if (str_contains($text, 'slow')) {
            return 'Slow';
        }

        if (str_contains($text, 'average')) {
            return 'Average';
        }

        if (str_contains($text, 'fast')) {
            return 'Fast';
        }

        return null;
    }

    private function comprehensionCategory(?string $level): ?string
    {
        $text = strtolower(trim((string) $level));

        if ($text === '') {
            return null;
        }

        if (str_contains($text, 'independent')) {
            return 'Independent';
        }

        if (str_contains($text, 'instruction')) {
            return 'Instructional';
        }

        if (str_contains($text, 'frustrat')) {
            return 'Frustrated';
        }

        return null;
    }

    private function decodeJson($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    private function jsonValue(array $value, string $key): array
    {
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

        return in_array($language, self::LANGUAGES, true) ? $language : null;
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

        if (! empty($quarter['quarter_name'])) {
            return $quarter['quarter_name'];
        }

        return match ((int) ($quarter['quarter_number'] ?? 0)) {
            1 => 'First Quarter',
            2 => 'Second Quarter',
            3 => 'Third Quarter',
            4 => 'Fourth Quarter',
            default => 'Quarter',
        };
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

    private function postgrestInList(array $ids): string
    {
        return collect($ids)
            ->filter()
            ->map(fn ($id) => '"' . str_replace('"', '\\"', (string) $id) . '"')
            ->implode(',');
    }

    private function currentEvaluatorId(): ?string
    {
        return session('supabase_user.id');
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

    private function supabaseWriteHeaders(): array
    {
        return array_merge($this->supabaseHeaders(), [
            'Content-Type' => 'application/json',
            'Prefer' => 'return=representation',
        ]);
    }
}
