<?php

namespace App\Http\Controllers\Evaluator;

use App\Helpers\EvaluatorMenuHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class EvaluatorProgressMonitoringController extends Controller
{
    public function index(Request $request)
    {
        $menuGroups = EvaluatorMenuHelper::getMenuGroups();
        $evaluatorId = $this->currentEvaluatorId();

        if (! $evaluatorId) {
            if ($request->expectsJson() || $request->query('ajax')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your user session is missing. Please sign in again.',
                ], 401);
            }

            return redirect()->route('signin')
                ->with('error', 'Please sign in as an evaluator to monitor progress.');
        }

        $schoolYears = $this->fetchSchoolYears($evaluatorId);
        $selectedYearId = $request->query('year_id') ?: ($schoolYears[0]['year_id'] ?? null);
        $assignments = $selectedYearId ? $this->buildProgressAssignments($evaluatorId, $selectedYearId, $schoolYears) : [];
        $summary = $this->buildSummary($assignments);

        if ($request->expectsJson() || $request->query('ajax')) {
            return response()->json([
                'success' => true,
                'schoolYears' => $schoolYears,
                'selectedYearId' => $selectedYearId,
                'summary' => $summary,
                'assignments' => $assignments,
            ]);
        }

        return view('pages.evaluator.evaluator-progress-monitoring', [
            'title' => 'Progress Monitoring',
            'menuGroups' => $menuGroups,
            'schoolYears' => $schoolYears,
            'selectedYearId' => $selectedYearId,
            'summary' => $summary,
            'assignments' => $assignments,
        ]);
    }

    private function buildProgressAssignments(string $evaluatorId, string $yearId, array $schoolYears): array
    {
        $assignments = $this->fetchConfirmedAssignments($evaluatorId, $yearId);

        if (empty($assignments)) {
            return [];
        }

        $sectionIds = collect($assignments)->pluck('section_id')->filter()->unique()->values()->all();
        $quarterIds = collect($assignments)->pluck('quarter_id')->filter()->unique()->values()->all();
        $scheduleIds = collect($assignments)->pluck('schedule_id')->filter()->unique()->values()->all();

        $sections = collect($this->fetchRowsByIds('class_sections', 'section_id', $sectionIds, 'section_id,school_id,year_id,grade_level_id,section_name,status,adviser_name'))
            ->keyBy('section_id')
            ->all();

        $gradeLevelIds = collect($sections)->pluck('grade_level_id')->filter()->unique()->values()->all();
        $schoolIds = collect($sections)->pluck('school_id')->filter()->unique()->values()->all();

        $gradeLevels = collect($this->fetchRowsByIds('grade_levels', 'grade_level_id', $gradeLevelIds, 'grade_level_id,grade_number,school_id,is_active'))
            ->keyBy('grade_level_id')
            ->all();

        $quarters = collect($this->fetchRowsByIds('quarter', 'quarter_id', $quarterIds, 'quarter_id,year_id,quarter_number,quarter_name,start_date,end_date'))
            ->keyBy('quarter_id')
            ->all();

        $schools = collect($this->fetchRowsByIds('schools', 'school_id', $schoolIds, 'school_id,name,address,logo'))
            ->keyBy('school_id')
            ->all();

        $pupils = $this->fetchAssignedPupils($sectionIds);
        $pupilsBySection = collect($pupils)->groupBy('section_id');
        $pupilIds = collect($pupils)->pluck('pupil_id')->filter()->unique()->values()->all();

        $records = $this->fetchAssessmentRecords($evaluatorId, $yearId, $pupilIds, $quarterIds, $scheduleIds);
        $materialIds = collect($records)->pluck('material_id')->filter()->unique()->values()->all();
        $materials = collect($this->fetchRowsByIds('reading_materials', 'material_id', $materialIds, 'material_id,title,language,word_count,grade_level_id'))
            ->keyBy('material_id')
            ->all();

        $records = collect($records)
            ->map(fn ($record) => $this->formatRecord($record, $materials[$record['material_id'] ?? null] ?? []))
            ->values()
            ->all();

        $schoolYearsById = collect($schoolYears)->keyBy('year_id');

        return collect($assignments)
            ->map(function ($assignment) use ($sections, $gradeLevels, $quarters, $schools, $pupilsBySection, $records, $schoolYearsById) {
                $section = $sections[$assignment['section_id'] ?? null] ?? [];
                $grade = $gradeLevels[$section['grade_level_id'] ?? null] ?? [];
                $quarter = $quarters[$assignment['quarter_id'] ?? null] ?? [];
                $school = $schools[$section['school_id'] ?? null] ?? [];
                $schoolYear = $schoolYearsById->get($assignment['year_id'] ?? null, []);
                $sectionPupils = collect($pupilsBySection->get($assignment['section_id'] ?? null, []))
                    ->where('status', 'enrolled')
                    ->values();

                $assignmentRecords = collect($records)
                    ->filter(fn ($record) => $this->recordBelongsToAssignment($record, $assignment, $sectionPupils->pluck('pupil_id')->all()))
                    ->values();

                $pupilRows = $sectionPupils
                    ->map(function ($pupil) use ($assignmentRecords, $section, $grade) {
                        $pupilRecords = $assignmentRecords
                            ->where('pupil_id', $pupil['pupil_id'])
                            ->values();

                        $englishRecord = $this->latestLanguageRecord($pupilRecords, 'english');
                        $filipinoRecord = $this->latestLanguageRecord($pupilRecords, 'filipino');

                        return [
                            'pupil_id' => $pupil['pupil_id'],
                            'lrn' => $pupil['lrn'] ?? '',
                            'full_name' => $pupil['full_name'] ?? 'Unnamed pupil',
                            'sex' => $pupil['sex'] ?? null,
                            'age' => $pupil['age'] ?? null,
                            'status' => $pupil['status'] ?? 'enrolled',
                            'section_id' => $pupil['section_id'] ?? null,
                            'section_name' => $section['section_name'] ?? 'Section',
                            'grade_level_id' => $pupil['grade_level_id'] ?? ($grade['grade_level_id'] ?? null),
                            'grade_number' => $grade['grade_number'] ?? null,
                            'english' => $this->languageStatus($englishRecord),
                            'filipino' => $this->languageStatus($filipinoRecord),
                            'has_any_record' => (bool) ($englishRecord || $filipinoRecord),
                            'is_complete' => (bool) ($englishRecord && $filipinoRecord),
                        ];
                    })
                    ->sortBy('full_name')
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
                    'assignment_id' => $assignment['assignment_id'] ?? null,
                    'schedule_id' => $assignment['schedule_id'] ?? null,
                    'section_id' => $assignment['section_id'] ?? null,
                    'year_id' => $assignment['year_id'] ?? null,
                    'quarter_id' => $assignment['quarter_id'] ?? null,
                    'assessment_date' => $assignment['assessment_date'] ?? null,
                    'assessment_status' => $assignment['assessment_status'] ?? 'not_started',
                    'assessment_status_label' => $this->statusLabel($assignment['assessment_status'] ?? 'not_started'),
                    'report_status' => $assignment['report_status'] ?? 'not_submitted',
                    'report_status_label' => $this->statusLabel($assignment['report_status'] ?? 'not_submitted'),
                    'school_name' => $school['name'] ?? 'School',
                    'school_year_label' => $schoolYear['label'] ?? $this->schoolYearLabel($schoolYear),
                    'quarter_label' => $this->quarterLabel($quarter),
                    'grade_level_id' => $section['grade_level_id'] ?? null,
                    'grade_number' => $grade['grade_number'] ?? null,
                    'grade_label' => isset($grade['grade_number']) ? 'Grade ' . $grade['grade_number'] : 'Grade',
                    'section_name' => $section['section_name'] ?? 'Section',
                    'adviser_name' => $section['adviser_name'] ?? null,
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
                ['grade_number', 'asc'],
                ['section_name', 'asc'],
                ['assessment_date', 'asc'],
            ])
            ->values()
            ->all();
    }

    private function buildSummary(array $assignments): array
    {
        $totalPupils = collect($assignments)->sum('total_pupils');
        $englishAssessed = collect($assignments)->sum('english_assessed');
        $filipinoAssessed = collect($assignments)->sum('filipino_assessed');
        $bothLanguages = collect($assignments)->sum('both_languages_count');
        $totalRequired = max($totalPupils * 2, 0);
        $totalCompleted = $englishAssessed + $filipinoAssessed;

        return [
            'assignments_count' => count($assignments),
            'total_pupils' => $totalPupils,
            'english_assessed' => $englishAssessed,
            'filipino_assessed' => $filipinoAssessed,
            'both_languages_count' => $bothLanguages,
            'total_required' => $totalRequired,
            'total_completed' => $totalCompleted,
            'overall_percent' => $totalRequired > 0 ? round(($totalCompleted / $totalRequired) * 100, 1) : 0,
        ];
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
            report('Failed to fetch evaluator confirmed assignments for progress monitoring: ' . $response->body());
            return [];
        }

        return $response->json();
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
            report('Failed to fetch evaluator assigned pupils for progress monitoring: ' . $response->body());
            return [];
        }

        return $response->json();
    }

    private function fetchAssessmentRecords(string $evaluatorId, string $yearId, array $pupilIds, array $quarterIds, array $scheduleIds): array
    {
        $pupilIds = collect($pupilIds)->filter()->unique()->values()->all();
        $quarterIds = collect($quarterIds)->filter()->unique()->values()->all();

        if (empty($pupilIds) || empty($quarterIds)) {
            return [];
        }

        $query = [
            'select' => 'assessment_record_id,created_at,updated_at,pupil_id,evaluator_user_id,material_id,schedule_id,year_id,quarter_id,assessment_method,assessment_type,reading_score,comprehension_score,miscue_content,total_score,reading_level,status,assignment_id',
            'evaluator_user_id' => 'eq.' . $evaluatorId,
            'year_id' => 'eq.' . $yearId,
            'pupil_id' => 'in.(' . $this->postgrestInList($pupilIds) . ')',
            'quarter_id' => 'in.(' . $this->postgrestInList($quarterIds) . ')',
            'status' => 'neq.draft',
            'order' => 'updated_at.desc',
        ];

        if (! empty($scheduleIds)) {
            // Keep records flexible: some older records may not have schedule_id, so exact assignment matching is handled later.
        }

        $response = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/assessment_records', $query);

        if (! $response->successful()) {
            report('Failed to fetch assessment records for evaluator progress monitoring: ' . $response->body());
            return [];
        }

        return $response->json();
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

    private function formatRecord(array $record, array $material): array
    {
        $languageKey = $this->normalizeLanguage($material['language'] ?? null);
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
            'language' => $this->displayLanguage($material['language'] ?? null),
            'language_key' => $languageKey,
            'schedule_id' => $record['schedule_id'] ?? null,
            'year_id' => $record['year_id'] ?? null,
            'quarter_id' => $record['quarter_id'] ?? null,
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
            report('Failed to fetch school years for evaluator progress monitoring: ' . $assignmentResponse->body());
            return [];
        }

        $yearIds = collect($assignmentResponse->json())
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
            report('Failed to fetch school year rows for evaluator progress monitoring: ' . $response->body());
            return [];
        }

        return collect($response->json())
            ->map(function ($year) {
                return array_merge($year, ['label' => $this->schoolYearLabel($year)]);
            })
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
            report("Failed to fetch {$table} for evaluator progress monitoring: " . $response->body());
            return [];
        }

        return $response->json();
    }

    private function currentEvaluatorId(): ?string
    {
        return session('supabase_user.id');
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

    private function statusLabel(string $status): string
    {
        return collect(explode('_', $status))
            ->filter()
            ->map(fn ($part) => ucfirst($part))
            ->implode(' ');
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
