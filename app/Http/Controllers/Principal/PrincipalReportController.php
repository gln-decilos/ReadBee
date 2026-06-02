<?php

namespace App\Http\Controllers\Principal;

use App\Helpers\PrincipalMenuHelper;
use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PrincipalReportController extends Controller
{
    private const LANGUAGES = ['english', 'filipino'];

    public function index(Request $request)
    {
        $schoolId = $this->principalSchoolId();

        if (! $schoolId) {
            return redirect()->route('signin')
                ->with('error', 'Please sign in as a principal to view submitted reports.');
        }

        $schoolYears = $this->fetchReportSchoolYears($schoolId);
        $selectedYearId = $request->query('year_id') ?: ($schoolYears[0]['year_id'] ?? null);
        $reportGroups = $selectedYearId ? $this->buildReportGroups($schoolId, $selectedYearId) : [];

        return view('pages.principal.principal-reports', [
            'title' => 'Submitted Reports',
            'menuGroups' => PrincipalMenuHelper::getMenuGroups(),
            'schoolYears' => $schoolYears,
            'selectedYearId' => $selectedYearId,
            'reportGroups' => $reportGroups,
        ]);
    }

    public function show(string $gradeLevelId, string $yearId, string $quarterId, string $language)
    {
        $schoolId = $this->principalSchoolId();
        $language = $this->normalizeLanguage($language);

        if (! $schoolId) {
            return redirect()->route('signin')
                ->with('error', 'Please sign in as a principal to view consolidated reports.');
        }

        if (! $language) {
            abort(404);
        }

        $report = $this->buildConsolidatedReport($schoolId, $gradeLevelId, $yearId, $quarterId, $language);

        if (! $report) {
            return redirect()->route('principal.reports')
                ->with('error', 'No submitted evaluator class reports were found for the selected grade level, quarter, and language.');
        }

        return view('components.principal.reports.consolidated-report-print', [
            'title' => $report['print_title'],
            'report' => $report,
        ]);
    }

    public function showClassReport(string $classReportId)
    {
        $schoolId = $this->principalSchoolId();

        if (! $schoolId) {
            return redirect()->route('signin')
                ->with('error', 'Please sign in as a principal to view submitted class reports.');
        }

        $report = $this->buildClassReportPreview($schoolId, $classReportId);

        if (! $report) {
            return redirect()->route('principal.reports')
                ->with('error', 'The selected class report was not found or does not belong to your school.');
        }

        return view('components.principal.reports.class-report-print', [
            'title' => $report['print_title'],
            'report' => $report,
        ]);
    }

    public function submit(Request $request, string $gradeLevelId, string $yearId, string $quarterId, string $language)
    {
        $schoolId = $this->principalSchoolId();
        $principalId = $this->currentPrincipalId();
        $language = $this->normalizeLanguage($language);

        if (! $schoolId || ! $principalId) {
            return redirect()->route('signin')
                ->with('error', 'Please sign in as a principal to submit consolidated reports.');
        }

        if (! $language) {
            abort(404);
        }

        $report = $this->buildConsolidatedReport($schoolId, $gradeLevelId, $yearId, $quarterId, $language);

        if (! $report) {
            return redirect()->route('principal.reports')
                ->with('error', 'No submitted evaluator class reports were found for the selected report.');
        }

        if (! $report['is_ready']) {
            return redirect()->route('principal.reports.show', [
                'gradeLevelId' => $gradeLevelId,
                'yearId' => $yearId,
                'quarterId' => $quarterId,
                'language' => $language,
            ])->with('error', 'This consolidated report cannot be submitted yet because there are no submitted section reports.');
        }

        if (! ($report['is_complete'] ?? false)) {
            $missingSections = collect($report['missing_section_labels'] ?? [])->implode(', ');

            return redirect()->route('principal.reports.show', [
                'gradeLevelId' => $gradeLevelId,
                'yearId' => $yearId,
                'quarterId' => $quarterId,
                'language' => $language,
            ])->with('error', 'This consolidated report is not yet complete. The following section(s) still need to submit: ' . ($missingSections ?: 'one or more sections') . '.');
        }

        if (in_array($report['existing_report_status'], ['submitted', 'reviewed', 'approved'], true)) {
            return redirect()->route('principal.reports.show', [
                'gradeLevelId' => $gradeLevelId,
                'yearId' => $yearId,
                'quarterId' => $quarterId,
                'language' => $language,
            ])->with('info', 'This consolidated report was already submitted to the district supervisor.');
        }

        $schoolReportId = $this->saveSchoolReport($report, $principalId);

        if (! $schoolReportId) {
            return back()->with('error', 'Unable to save the consolidated report. Please try again.');
        }

        $this->syncSchoolReportSections($schoolReportId, $report);
        $this->notifySchoolReportSubmitted($report, $schoolReportId);

        return redirect()->route('principal.reports.show', [
            'gradeLevelId' => $gradeLevelId,
            'yearId' => $yearId,
            'quarterId' => $quarterId,
            'language' => $language,
        ])->with('success', ucfirst($language) . ' consolidated report was submitted to the district supervisor.');
    }

    private function notifySchoolReportSubmitted(array $report, string $schoolReportId): void
    {
        $districtReviewerIds = $this->notifications()->districtReviewerUserIdsForSchool($report['school_id'] ?? null);
        $schoolAdminIds = $this->notifications()->schoolAdminUserIds($report['school_id'] ?? null);
        $message = 'A consolidated ' . ucfirst($report['language'] ?? 'school') . ' report for ' . ($report['grade_label'] ?? 'Grade') . ', ' . ($report['quarter_label'] ?? 'Quarter') . ' was submitted.';

        $this->notifications()->createForUsers(
            $districtReviewerIds,
            'School report submitted',
            $message,
            route('district-supervisor.reports.school-report', ['schoolReportId' => $schoolReportId], false),
            'school_report_submitted'
        );

        $this->notifications()->createForUsers(
            $schoolAdminIds,
            'School report submitted',
            $message,
            route('school-admin.dashboard', [], false),
            'school_report_submitted'
        );
    }

    private function notifications(): NotificationService
    {
        return app(NotificationService::class);
    }

    private function buildReportGroups(string $schoolId, string $yearId): array
    {
        $classReports = $this->fetchSubmittedClassReports($schoolId, ['year_id' => $yearId]);

        if (empty($classReports)) {
            return [];
        }

        $gradeIds = collect($classReports)->pluck('grade_level_id')->filter()->unique()->values()->all();
        $sectionIds = collect($classReports)->pluck('section_id')->filter()->unique()->values()->all();
        $quarterIds = collect($classReports)->pluck('quarter_id')->filter()->unique()->values()->all();

        $grades = collect($this->fetchRowsByIds('grade_levels', 'grade_level_id', $gradeIds, 'grade_level_id,grade_number,school_id,is_active'))->keyBy('grade_level_id');
        $sections = collect($this->fetchRowsByIds('class_sections', 'section_id', $sectionIds, 'section_id,school_id,grade_level_id,year_id,section_name,status,adviser_name'))->keyBy('section_id');
        $allSections = collect($this->fetchSchoolSectionsForYear($schoolId, $yearId, $gradeIds))->keyBy('section_id');
        $quarters = collect($this->fetchRowsByIds('quarter', 'quarter_id', $quarterIds, 'quarter_id,year_id,quarter_number,quarter_name,start_date,end_date'))->keyBy('quarter_id');
        $schoolYear = $this->fetchSingleRowById('school_year', 'year_id', $yearId, 'year_id,start_date,end_date,created_at');
        $existingSchoolReports = $this->fetchExistingSchoolReports($schoolId, $yearId);

        $latestPerSectionLanguage = collect($classReports)
            ->sortByDesc(fn ($report) => $report['updated_at'] ?? $report['submitted_at'] ?? $report['created_at'] ?? '')
            ->unique(fn ($report) => ($report['section_id'] ?? '') . '|' . ($report['grade_level_id'] ?? '') . '|' . ($report['quarter_id'] ?? '') . '|' . $this->normalizeLanguage($report['language'] ?? null))
            ->values();

        return $latestPerSectionLanguage
            ->groupBy(fn ($report) => ($report['grade_level_id'] ?? '') . '|' . ($report['quarter_id'] ?? ''))
            ->map(function ($reports, $key) use ($grades, $sections, $allSections, $quarters, $schoolYear, $existingSchoolReports) {
                [$gradeLevelId, $quarterId] = explode('|', $key) + [null, null];
                $grade = $grades->get($gradeLevelId, []);
                $quarter = $quarters->get($quarterId, []);
                $gradeNumber = $grade['grade_number'] ?? null;
                $expectedSections = $allSections
                    ->where('grade_level_id', $gradeLevelId)
                    ->sortBy('section_name')
                    ->values();

                if ($expectedSections->isEmpty()) {
                    $expectedSections = $reports
                        ->map(fn ($report) => $sections->get($report['section_id'] ?? null))
                        ->filter()
                        ->unique('section_id')
                        ->sortBy('section_name')
                        ->values();
                }

                $expectedSectionLabels = $expectedSections
                    ->pluck('section_name')
                    ->filter()
                    ->values()
                    ->all();
                $expectedSectionCount = count($expectedSectionLabels);

                $languageSummaries = [];

                foreach (self::LANGUAGES as $language) {
                    $languageReports = $reports
                        ->filter(fn ($report) => $this->normalizeLanguage($report['language'] ?? null) === $language)
                        ->values();

                    if ($languageReports->isEmpty()) {
                        continue;
                    }

                    $existing = collect($existingSchoolReports)->first(function ($schoolReport) use ($gradeLevelId, $quarterId, $language) {
                        return (string) ($schoolReport['grade_level_id'] ?? '') === (string) $gradeLevelId
                            && (string) ($schoolReport['quarter_id'] ?? '') === (string) $quarterId
                            && $this->normalizeLanguage($schoolReport['language'] ?? null) === $language;
                    });

                    $submittedSectionIds = $languageReports
                        ->pluck('section_id')
                        ->filter()
                        ->unique()
                        ->values()
                        ->all();
                    $sectionLabels = $languageReports
                        ->map(fn ($report) => $sections->get($report['section_id'] ?? null)['section_name'] ?? 'Section')
                        ->unique()
                        ->sort()
                        ->values()
                        ->all();
                    $missingSectionLabels = $expectedSections
                        ->reject(fn ($section) => in_array($section['section_id'] ?? null, $submittedSectionIds, true))
                        ->pluck('section_name')
                        ->filter()
                        ->values()
                        ->all();

                    $pupilCount = $this->countClassReportPupils($languageReports->pluck('class_report_id')->filter()->values()->all());
                    $status = strtolower((string) ($existing['report_status'] ?? 'draft'));
                    $isSubmitted = in_array($status, ['submitted', 'reviewed', 'approved'], true);
                    $isComplete = $languageReports->isNotEmpty()
                        && $expectedSectionCount > 0
                        && count($missingSectionLabels) === 0;

                    $languageSummaries[$language] = [
                        'label' => ucfirst($language),
                        'expected_sections_count' => $expectedSectionCount,
                        'submitted_sections_count' => count($sectionLabels),
                        'missing_sections_count' => count($missingSectionLabels),
                        'section_labels' => $sectionLabels,
                        'missing_section_labels' => $missingSectionLabels,
                        'class_reports' => $languageReports
                            ->map(function ($report) use ($sections) {
                                return [
                                    'class_report_id' => $report['class_report_id'] ?? null,
                                    'section_id' => $report['section_id'] ?? null,
                                    'section_name' => $sections->get($report['section_id'] ?? null)['section_name'] ?? 'Section',
                                    'submitted_at' => $report['submitted_at'] ?? null,
                                    'report_status' => $report['report_status'] ?? 'submitted',
                                ];
                            })
                            ->sortBy('section_name')
                            ->values()
                            ->all(),
                        'total_pupils' => $pupilCount,
                        'report_status' => $status ?: 'draft',
                        'is_ready' => $languageReports->isNotEmpty(),
                        'is_complete' => $isComplete,
                        'is_submitted' => $isSubmitted,
                        'submitted_at' => $existing['submitted_at'] ?? null,
                        'latest_class_report_at' => $languageReports->max('submitted_at') ?: $languageReports->max('updated_at'),
                    ];
                }

                if (empty($languageSummaries)) {
                    return null;
                }

                return [
                    'grade_level_id' => $gradeLevelId,
                    'quarter_id' => $quarterId,
                    'grade_label' => $gradeNumber ? 'Grade ' . $gradeNumber : 'Grade',
                    'quarter_label' => $this->quarterLabel($quarter),
                    'school_year_label' => $this->schoolYearLabel($schoolYear),
                    'languages' => $languageSummaries,
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

    private function buildClassReportPreview(string $schoolId, string $classReportId): ?array
    {
        $classReport = $this->fetchClassReportById($schoolId, $classReportId);

        if (! $classReport) {
            return null;
        }

        $language = $this->normalizeLanguage($classReport['language'] ?? null);

        if (! $language) {
            return null;
        }

        $section = $this->fetchSingleRowById('class_sections', 'section_id', $classReport['section_id'] ?? null, 'section_id,school_id,year_id,grade_level_id,section_name,status,adviser_name');
        $grade = $this->fetchSingleRowById('grade_levels', 'grade_level_id', $classReport['grade_level_id'] ?? null, 'grade_level_id,school_id,grade_number,is_active');
        $school = $this->fetchSingleRowById('schools', 'school_id', $schoolId, 'school_id,name,address,contact,email,district_id,municipality_id');
        $district = $this->fetchSingleRowById('districts', 'district_id', $school['district_id'] ?? null, 'district_id,district_name,province,office_address,contact,email');
        $quarter = $this->fetchSingleRowById('quarter', 'quarter_id', $classReport['quarter_id'] ?? null, 'quarter_id,year_id,quarter_number,quarter_name,start_date,end_date');
        $schoolYear = $this->fetchSingleRowById('school_year', 'year_id', $classReport['year_id'] ?? null, 'year_id,start_date,end_date,created_at');
        $submitter = $this->fetchSingleRowById('profiles', 'id', $classReport['submitted_by'] ?? null, 'id,full_name,title,position,email');

        $classReportPupils = $this->fetchClassReportPupils([$classReportId]);
        $pupilIds = collect($classReportPupils)->pluck('pupil_id')->filter()->unique()->values()->all();
        $pupils = collect($this->fetchRowsByIds('pupils', 'pupil_id', $pupilIds, 'pupil_id,full_name,sex,school_id,section_id,grade_level_id,status'))->keyBy('pupil_id');
        $rows = $this->buildClassReportPreviewRows($classReportPupils, $pupils);
        $summary = $this->buildClassReportPreviewSummary($rows);

        $gradeNumber = $grade['grade_number'] ?? null;
        $sectionName = $section['section_name'] ?? 'Section';
        $languageLabel = ucfirst($language);
        $districtName = $district['district_name'] ?? 'NASUGBU WEST SUB-OFFICE';

        return [
            'class_report_id' => $classReportId,
            'school_id' => $schoolId,
            'section_id' => $classReport['section_id'] ?? null,
            'grade_level_id' => $classReport['grade_level_id'] ?? null,
            'year_id' => $classReport['year_id'] ?? null,
            'quarter_id' => $classReport['quarter_id'] ?? null,
            'language' => $language,
            'language_label' => $languageLabel,
            'report_language_title' => strtoupper($languageLabel),
            'print_title' => 'Submitted Class Report in ' . $languageLabel,
            'school_name' => $school['name'] ?? 'School',
            'school_address' => $school['address'] ?? ($district['office_address'] ?? ''),
            'school_contact' => $school['contact'] ?? ($district['contact'] ?? ''),
            'school_email' => $school['email'] ?? ($district['email'] ?? ''),
            'district_name' => strtoupper($districtName),
            'division_label' => 'Schools Division of ' . ($district['province'] ?? 'Batangas Province'),
            'region_label' => 'Region IV-CALABARZON',
            'grade_number' => $gradeNumber,
            'grade_label' => $gradeNumber ? 'Grade ' . $gradeNumber : 'Grade',
            'section_name' => $sectionName,
            'grade_section_report_label' => strtoupper(($gradeNumber ? 'GRADE ' . $gradeNumber : 'GRADE') . ' - ' . $sectionName),
            'quarter_label' => $this->quarterLabel($quarter),
            'quarter_report_label' => strtoupper($this->quarterReportLabel($quarter)),
            'school_year_label' => $this->schoolYearLabel($schoolYear),
            'school_year_report_label' => 'S.Y ' . $this->schoolYearCompactLabel($schoolYear),
            'evaluator_name' => $submitter['full_name'] ?? 'Evaluator',
            'submitted_at' => $classReport['submitted_at'] ?? null,
            'report_status' => $classReport['report_status'] ?? 'submitted',
            'rows' => $rows,
            'summary' => $summary,
        ];
    }

    private function buildClassReportPreviewRows(array $classReportPupils, $pupils): array
    {
        return collect($classReportPupils)
            ->map(function ($row) use ($pupils) {
                $pupil = $pupils->get($row['pupil_id'] ?? null, []);
                $readingScore = $this->decodeJson($row['reading_score'] ?? null);
                $comprehensionScore = $this->decodeJson($row['comprehension_score'] ?? null);
                $passageCategory = $this->passageCategory(
                    $row['reading_level'] ?? null,
                    $this->summaryValue($this->jsonValue($readingScore, 'miscueOverallSummary'), 'Reading Speed')
                );
                $comprehensionCategory = $this->comprehensionCategory(
                    $this->summaryValue($this->jsonValue($comprehensionScore, 'comprehensionSummary'), 'Comprehension Score')
                );

                return [
                    'pupil_id' => $row['pupil_id'] ?? null,
                    'pupil_name' => $pupil['full_name'] ?? 'Unnamed pupil',
                    'sex' => strtoupper((string) ($pupil['sex'] ?? '')),
                    'assessment_record_id' => $row['assessment_record_id'] ?? null,
                    'is_assessed' => ! empty($row['assessment_record_id']),
                    'passage_category' => $passageCategory,
                    'comprehension_category' => $comprehensionCategory,
                    'reading_level' => $row['reading_level'] ?? null,
                    'reading_score' => $readingScore,
                    'comprehension_score' => $comprehensionScore,
                ];
            })
            ->sortBy('pupil_name')
            ->values()
            ->all();
    }

    private function buildClassReportPreviewSummary(array $rows): array
    {
        $summary = [
            'total' => count($rows),
            'male' => collect($rows)->where('sex', 'M')->count(),
            'female' => collect($rows)->where('sex', 'F')->count(),
            'assessed' => collect($rows)->where('is_assessed', true)->count(),
            'missing' => collect($rows)->where('is_assessed', false)->count(),
            'passage' => [],
            'comprehension' => [],
        ];

        foreach (['Non-Reader', 'Struggling', 'Slow', 'Average', 'Fast'] as $category) {
            $summary['passage'][$category] = collect($rows)->where('passage_category', $category)->count();
        }

        foreach (['Independent', 'Instructional', 'Frustrated'] as $category) {
            $summary['comprehension'][$category] = collect($rows)->where('comprehension_category', $category)->count();
        }

        return $summary;
    }

    private function buildConsolidatedReport(string $schoolId, string $gradeLevelId, string $yearId, string $quarterId, string $language): ?array
    {
        $classReports = $this->fetchSubmittedClassReports($schoolId, [
            'grade_level_id' => $gradeLevelId,
            'year_id' => $yearId,
            'quarter_id' => $quarterId,
            'language' => $language,
        ]);

        $classReports = collect($classReports)
            ->sortByDesc(fn ($report) => $report['updated_at'] ?? $report['submitted_at'] ?? $report['created_at'] ?? '')
            ->unique(fn ($report) => $report['section_id'] ?? '')
            ->values()
            ->all();

        if (empty($classReports)) {
            return null;
        }

        $expectedSections = collect($this->fetchSchoolSectionsForGrade($schoolId, $gradeLevelId, $yearId))
            ->sortBy('section_name')
            ->values();
        $classReportIds = collect($classReports)->pluck('class_report_id')->filter()->values()->all();
        $sectionIds = collect($classReports)
            ->pluck('section_id')
            ->merge($expectedSections->pluck('section_id'))
            ->filter()
            ->unique()
            ->values()
            ->all();
        $submitterIds = collect($classReports)->pluck('submitted_by')->filter()->unique()->values()->all();

        $school = $this->fetchSingleRowById('schools', 'school_id', $schoolId, 'school_id,name,address,contact,email,district_id,municipality_id');
        $district = $this->fetchSingleRowById('districts', 'district_id', $school['district_id'] ?? null, 'district_id,district_name,province,office_address,contact,email');
        $grade = $this->fetchSingleRowById('grade_levels', 'grade_level_id', $gradeLevelId, 'grade_level_id,school_id,grade_number,is_active');
        $quarter = $this->fetchSingleRowById('quarter', 'quarter_id', $quarterId, 'quarter_id,year_id,quarter_number,quarter_name,start_date,end_date');
        $schoolYear = $this->fetchSingleRowById('school_year', 'year_id', $yearId, 'year_id,start_date,end_date,created_at');
        $principalProfile = $this->fetchSingleRowById('profiles', 'id', $this->currentPrincipalId(), 'id,full_name,title,position,email');

        $sections = collect($this->fetchRowsByIds('class_sections', 'section_id', $sectionIds, 'section_id,school_id,grade_level_id,year_id,section_name,status,adviser_name'))->keyBy('section_id');
        $submitters = collect($this->fetchRowsByIds('profiles', 'id', $submitterIds, 'id,full_name,title,position,email'))->keyBy('id');
        $classReportPupils = $this->fetchClassReportPupils($classReportIds);
        $pupilIds = collect($classReportPupils)->pluck('pupil_id')->filter()->unique()->values()->all();
        $pupils = collect($this->fetchRowsByIds('pupils', 'pupil_id', $pupilIds, 'pupil_id,full_name,sex,school_id,section_id,grade_level_id,status'))->keyBy('pupil_id');

        $classReportsBySection = collect($classReports)->keyBy('section_id');
        $renderSections = $expectedSections->isNotEmpty()
            ? $expectedSections
            : collect($classReports)->map(fn ($classReport) => $sections->get($classReport['section_id'] ?? null))->filter()->values();
        $extraSubmittedSections = collect($classReports)
            ->reject(fn ($classReport) => $renderSections->pluck('section_id')->contains($classReport['section_id'] ?? null))
            ->map(fn ($classReport) => $sections->get($classReport['section_id'] ?? null))
            ->filter()
            ->values();
        $renderSections = $renderSections->merge($extraSubmittedSections)->unique('section_id')->sortBy('section_name')->values();

        $sectionRows = $renderSections
            ->map(function ($section) use ($classReportsBySection, $submitters, $classReportPupils, $pupils) {
                $classReport = $classReportsBySection->get($section['section_id'] ?? null);

                if (! $classReport) {
                    return [
                        'class_report_id' => null,
                        'section_id' => $section['section_id'] ?? null,
                        'section_name' => $section['section_name'] ?? 'Section',
                        'submitted_by' => null,
                        'submitted_at' => null,
                        'is_submitted' => false,
                        'summary' => $this->emptySummary(),
                    ];
                }

                $reportPupils = collect($classReportPupils)
                    ->where('class_report_id', $classReport['class_report_id'] ?? null)
                    ->values()
                    ->all();

                $summary = $this->buildSectionSummary($reportPupils, $pupils);
                $submitter = $submitters->get($classReport['submitted_by'] ?? null, []);

                return [
                    'class_report_id' => $classReport['class_report_id'] ?? null,
                    'section_id' => $classReport['section_id'] ?? null,
                    'section_name' => $section['section_name'] ?? 'Section',
                    'submitted_by' => $submitter['full_name'] ?? 'Evaluator',
                    'submitted_at' => $classReport['submitted_at'] ?? null,
                    'is_submitted' => true,
                    'summary' => $summary,
                ];
            })
            ->sortBy('section_name')
            ->values()
            ->all();

        $missingSectionLabels = collect($sectionRows)
            ->filter(fn ($row) => ! ($row['is_submitted'] ?? false))
            ->pluck('section_name')
            ->filter()
            ->values()
            ->all();
        $submittedSectionCount = collect($sectionRows)->filter(fn ($row) => $row['is_submitted'] ?? false)->count();
        $expectedSectionCount = count($sectionRows);


        $totals = $this->buildTotalSummary($sectionRows);
        $existing = $this->fetchExistingSchoolReport($schoolId, $gradeLevelId, $yearId, $quarterId, $language);

        $gradeNumber = $grade['grade_number'] ?? null;
        $districtName = $district['district_name'] ?? 'DISTRICT I';
        $divisionProvince = trim(str_replace(' Province', '', (string) ($district['province'] ?? 'Batangas')));
        $divisionProvince = $divisionProvince !== '' ? $divisionProvince : 'Batangas';
        $languageLabel = ucfirst($language);

        return [
            'school_id' => $schoolId,
            'grade_level_id' => $gradeLevelId,
            'year_id' => $yearId,
            'quarter_id' => $quarterId,
            'language' => $language,
            'language_label' => $languageLabel,
            'report_language_title' => strtoupper($languageLabel),
            'print_title' => 'Consolidated Oral Reading and Comprehension Assessment Result in ' . $languageLabel,
            'school_name' => $school['name'] ?? 'School',
            'school_address' => $school['address'] ?? ($district['office_address'] ?? ''),
            'school_contact' => $school['contact'] ?? ($district['contact'] ?? ''),
            'school_email' => $school['email'] ?? ($district['email'] ?? ''),
            'district_name' => strtoupper($districtName),
            'division_label' => 'Schools Division of ' . $divisionProvince,
            'region_label' => 'Region IV-CALABARZON',
            'principal_name' => $principalProfile['full_name'] ?? 'Principal',
            'principal_title' => $principalProfile['title'] ?? ($principalProfile['position'] ?? null),
            'grade_number' => $gradeNumber,
            'grade_label' => $gradeNumber ? 'Grade ' . $gradeNumber : 'Grade',
            'grade_report_label' => strtoupper($gradeNumber ? 'GRADE ' . $gradeNumber : 'GRADE'),
            'quarter_label' => $this->quarterLabel($quarter),
            'quarter_report_label' => strtoupper($this->quarterReportLabel($quarter)),
            'school_year_label' => $this->schoolYearLabel($schoolYear),
            'school_year_report_label' => 'S.Y ' . $this->schoolYearCompactLabel($schoolYear),
            'section_rows' => $sectionRows,
            'summary' => $totals,
            'expected_sections_count' => $expectedSectionCount,
            'submitted_sections_count' => $submittedSectionCount,
            'missing_sections_count' => count($missingSectionLabels),
            'missing_section_labels' => $missingSectionLabels,
            'is_ready' => $submittedSectionCount > 0,
            'is_complete' => $expectedSectionCount > 0 && count($missingSectionLabels) === 0,
            'existing_report_id' => $existing['school_report_id'] ?? null,
            'existing_report_status' => strtolower((string) ($existing['report_status'] ?? 'draft')),
            'existing_submitted_at' => $existing['submitted_at'] ?? null,
        ];
    }

    private function buildSectionSummary(array $reportPupils, $pupils): array
    {
        $summary = $this->emptySummary();

        foreach ($reportPupils as $row) {
            $pupil = $pupils->get($row['pupil_id'] ?? null, []);
            $sex = strtoupper((string) ($pupil['sex'] ?? ''));

            if ($sex === 'M') {
                $summary['male']++;
            } elseif ($sex === 'F') {
                $summary['female']++;
            }

            $passageCategory = $this->passageCategory(
                $row['reading_level'] ?? null,
                $this->summaryValue($this->jsonValue($this->decodeJson($row['reading_score'] ?? null), 'miscueOverallSummary'), 'Reading Speed')
            );

            if ($passageCategory && array_key_exists($passageCategory, $summary['passage'])) {
                $summary['passage'][$passageCategory]++;
            }

            $comprehensionCategory = $this->comprehensionCategory(
                $this->summaryValue($this->jsonValue($this->decodeJson($row['comprehension_score'] ?? null), 'comprehensionSummary'), 'Comprehension Score')
            );

            if ($comprehensionCategory && array_key_exists($comprehensionCategory, $summary['comprehension'])) {
                $summary['comprehension'][$comprehensionCategory]++;
            }
        }

        $summary['total'] = count($reportPupils);

        return $summary;
    }

    private function buildTotalSummary(array $sectionRows): array
    {
        $total = $this->emptySummary();

        foreach ($sectionRows as $row) {
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

    private function saveSchoolReport(array $report, string $principalId): ?string
    {
        $payload = [
            'school_id' => $report['school_id'],
            'grade_level_id' => $report['grade_level_id'],
            'year_id' => $report['year_id'],
            'quarter_id' => $report['quarter_id'],
            'language' => $report['language'],
            'submitted_by' => $principalId,
            'report_status' => 'submitted',
            'submitted_at' => now()->toISOString(),
            'updated_at' => now()->toISOString(),
            'remarks' => 'Consolidated school report submitted by principal.',
        ];

        if ($report['existing_report_id']) {
            $response = Http::withHeaders($this->supabaseWriteHeaders())
                ->patch(
                    $this->supabaseUrl() . '/rest/v1/school_reports?school_report_id=eq.' . rawurlencode($report['existing_report_id']),
                    $payload
                );
        } else {
            $payload['created_by'] = $principalId;
            $payload['created_at'] = now()->toISOString();

            $response = Http::withHeaders($this->supabaseWriteHeaders())
                ->post($this->supabaseUrl() . '/rest/v1/school_reports', $payload);
        }

        if (! $response->successful()) {
            report('Failed to save principal consolidated report: ' . $response->body());
            return null;
        }

        $saved = $response->json();
        $savedReport = is_array($saved) && isset($saved[0]) ? $saved[0] : $saved;

        return $savedReport['school_report_id'] ?? $report['existing_report_id'] ?? null;
    }

    private function syncSchoolReportSections(string $schoolReportId, array $report): void
    {
        Http::withHeaders($this->supabaseHeaders())
            ->delete($this->supabaseUrl() . '/rest/v1/school_report_sections?school_report_id=eq.' . rawurlencode($schoolReportId));

        $rows = collect($report['section_rows'])
            ->map(function ($row) use ($schoolReportId) {
                $summary = $row['summary'] ?? $this->emptySummary();

                return [
                    'school_report_id' => $schoolReportId,
                    'section_id' => $row['section_id'],
                    'class_report_id' => $row['class_report_id'],
                    'total_pupils' => (int) ($summary['total'] ?? 0),
                    'total_assessed' => (int) ($summary['total'] ?? 0),
                    'independent_count' => (int) ($summary['comprehension']['Independent'] ?? 0),
                    'instructional_count' => (int) ($summary['comprehension']['Instructional'] ?? 0),
                    'frustration_count' => (int) ($summary['comprehension']['Frustrated'] ?? 0),
                    'non_reader_count' => (int) ($summary['passage']['Non-Reader'] ?? 0),
                    'remarks' => json_encode([
                        'section_name' => $row['section_name'] ?? null,
                        'male' => (int) ($summary['male'] ?? 0),
                        'female' => (int) ($summary['female'] ?? 0),
                        'passage' => $summary['passage'] ?? [],
                        'comprehension' => $summary['comprehension'] ?? [],
                    ]),
                ];
            })
            ->filter(fn ($row) => ! empty($row['section_id']))
            ->values()
            ->all();

        if (empty($rows)) {
            return;
        }

        $response = Http::withHeaders($this->supabaseWriteHeaders())
            ->post($this->supabaseUrl() . '/rest/v1/school_report_sections', $rows);

        if (! $response->successful()) {
            report('Failed to save consolidated report section rows: ' . $response->body());
        }
    }

    private function fetchSchoolSectionsForYear(string $schoolId, string $yearId, array $gradeIds = []): array
    {
        $query = [
            'select' => 'section_id,school_id,grade_level_id,year_id,section_name,status,adviser_name',
            'school_id' => 'eq.' . $schoolId,
            'year_id' => 'eq.' . $yearId,
            'status' => 'neq.archived',
            'order' => 'section_name.asc',
        ];

        $gradeIds = collect($gradeIds)->filter()->unique()->values()->all();
        if (! empty($gradeIds)) {
            $query['grade_level_id'] = 'in.(' . $this->postgrestInList($gradeIds) . ')';
        }

        $response = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/class_sections', $query);

        if (! $response->successful()) {
            report('Failed to fetch school sections for principal reports: ' . $response->body());
            return [];
        }

        return $response->json();
    }

    private function fetchSchoolSectionsForGrade(string $schoolId, string $gradeLevelId, string $yearId): array
    {
        return $this->fetchSchoolSectionsForYear($schoolId, $yearId, [$gradeLevelId]);
    }

    private function fetchClassReportById(string $schoolId, string $classReportId): ?array
    {
        $response = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/class_reports', [
                'select' => 'class_report_id,created_at,updated_at,submitted_at,school_id,section_id,grade_level_id,year_id,quarter_id,language,submitted_by,report_status,remarks',
                'class_report_id' => 'eq.' . $classReportId,
                'school_id' => 'eq.' . $schoolId,
                'report_status' => 'in.("submitted","reviewed","approved")',
                'limit' => 1,
            ]);

        if (! $response->successful()) {
            report('Failed to fetch submitted class report for principal preview: ' . $response->body());
            return null;
        }

        return $response->json()[0] ?? null;
    }

    private function fetchSubmittedClassReports(string $schoolId, array $filters = []): array
    {
        $query = [
            'select' => 'class_report_id,created_at,updated_at,submitted_at,school_id,section_id,grade_level_id,year_id,quarter_id,language,submitted_by,report_status,remarks',
            'school_id' => 'eq.' . $schoolId,
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
            ->get($this->supabaseUrl() . '/rest/v1/class_reports', $query);

        if (! $response->successful()) {
            report('Failed to fetch submitted class reports for principal: ' . $response->body());
            return [];
        }

        return $response->json();
    }

    private function fetchReportSchoolYears(string $schoolId): array
    {
        $classReports = $this->fetchSubmittedClassReports($schoolId);
        $yearIds = collect($classReports)->pluck('year_id')->filter()->unique()->values()->all();

        if (empty($yearIds)) {
            return [];
        }

        return collect($this->fetchRowsByIds('school_year', 'year_id', $yearIds, 'year_id,start_date,end_date,created_at'))
            ->map(fn ($year) => array_merge($year, ['label' => $this->schoolYearLabel($year)]))
            ->sortByDesc('label')
            ->values()
            ->all();
    }

    private function fetchClassReportPupils(array $classReportIds): array
    {
        $classReportIds = collect($classReportIds)->filter()->unique()->values()->all();

        if (empty($classReportIds)) {
            return [];
        }

        $response = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/class_report_pupils', [
                'select' => 'class_report_pupil_id,class_report_id,pupil_id,assessment_record_id,reading_level,reading_score,comprehension_score,remarks,created_at',
                'class_report_id' => 'in.(' . $this->postgrestInList($classReportIds) . ')',
            ]);

        if (! $response->successful()) {
            report('Failed to fetch class report pupils for principal consolidation: ' . $response->body());
            return [];
        }

        return $response->json();
    }

    private function countClassReportPupils(array $classReportIds): int
    {
        return count($this->fetchClassReportPupils($classReportIds));
    }

    private function fetchExistingSchoolReports(string $schoolId, string $yearId): array
    {
        $response = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/school_reports', [
                'select' => 'school_report_id,created_at,updated_at,submitted_at,school_id,grade_level_id,year_id,quarter_id,language,created_by,submitted_by,report_status,remarks',
                'school_id' => 'eq.' . $schoolId,
                'year_id' => 'eq.' . $yearId,
                'order' => 'updated_at.desc',
            ]);

        if (! $response->successful()) {
            report('Failed to fetch existing school reports: ' . $response->body());
            return [];
        }

        return $response->json();
    }

    private function fetchExistingSchoolReport(string $schoolId, string $gradeLevelId, string $yearId, string $quarterId, string $language): ?array
    {
        $response = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/school_reports', [
                'select' => 'school_report_id,created_at,updated_at,submitted_at,school_id,grade_level_id,year_id,quarter_id,language,created_by,submitted_by,report_status,remarks',
                'school_id' => 'eq.' . $schoolId,
                'grade_level_id' => 'eq.' . $gradeLevelId,
                'year_id' => 'eq.' . $yearId,
                'quarter_id' => 'eq.' . $quarterId,
                'language' => 'eq.' . $language,
                'order' => 'updated_at.desc',
                'limit' => 1,
            ]);

        if (! $response->successful()) {
            report('Failed to fetch existing school report: ' . $response->body());
            return null;
        }

        return $response->json()[0] ?? null;
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
            report("Failed to fetch {$table} row for principal report: " . $response->body());
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
            report("Failed to fetch {$table} rows for principal report: " . $response->body());
            return [];
        }

        return $response->json();
    }

    private function passageCategory(?string $primary, ?string $fallback = null): ?string
    {
        $text = strtolower(trim((string) ($primary ?: $fallback)));

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

    private function principalSchoolId(): ?string
    {
        $activeDesignation = session('active_designation', []);

        if (
            strtolower($activeDesignation['role_name'] ?? '') === 'principal'
            && ! empty($activeDesignation['school_id'])
        ) {
            return $activeDesignation['school_id'];
        }

        $principalDesignation = collect(session('user_designations', []))
            ->first(function ($designation) {
                return strtolower($designation['role_name'] ?? '') === 'principal'
                    && ! empty($designation['school_id']);
            });

        return $principalDesignation['school_id'] ?? null;
    }

    private function currentPrincipalId(): ?string
    {
        return session('supabase_user.id');
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

    private function supabaseWriteHeaders(): array
    {
        return array_merge($this->supabaseHeaders(), [
            'Content-Type' => 'application/json',
            'Prefer' => 'return=representation',
        ]);
    }
}
