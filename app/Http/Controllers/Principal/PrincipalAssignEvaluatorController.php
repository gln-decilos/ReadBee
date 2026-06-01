<?php

namespace App\Http\Controllers\Principal;

use App\Helpers\PrincipalMenuHelper;
use App\Http\Controllers\Controller;
use App\Mail\EvaluatorAssignmentMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class PrincipalAssignEvaluatorController extends Controller
{
    public function index(Request $request)
    {
        $menuGroups = PrincipalMenuHelper::getMenuGroups();
        $schoolId = $this->principalSchoolId();

        if (! $schoolId) {
            if ($request->expectsJson() || $request->query('ajax')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No school assigned to your principal account.',
                ], 403);
            }

            return redirect()
                ->route('principal.dashboard')
                ->with('error', 'No school assigned to your principal account.');
        }

        $schoolYears = $this->fetchSchoolYears();
        $selectedYearId = $request->query('year_id') ?: ($schoolYears[0]['year_id'] ?? null);

        $quarters = $selectedYearId ? $this->fetchQuarters($selectedYearId) : [];
        $grades = $selectedYearId ? $this->fetchGradesWithSections($schoolId, $selectedYearId) : [];
        $schedules = $selectedYearId ? $this->fetchSchedules($schoolId, $selectedYearId, $quarters) : [];
        $evaluators = $this->fetchEvaluators($schoolId);
        $assignments = $selectedYearId ? $this->fetchAssignments($schoolId, $selectedYearId, $grades, $schedules, $quarters, $schoolYears, $evaluators) : [];

        if ($request->expectsJson() || $request->query('ajax')) {
            return response()->json([
                'success' => true,
                'selectedYearId' => $selectedYearId,
                'quarters' => $quarters,
                'grades' => $grades,
                'schedules' => $schedules,
                'evaluators' => $evaluators,
                'assignments' => $assignments,
            ]);
        }

        return view('pages.principal.principal-assign-evaluator', [
            'title' => 'Assign Evaluator',
            'menuGroups' => $menuGroups,
            'schoolYears' => $schoolYears,
            'selectedYearId' => $selectedYearId,
            'quarters' => $quarters,
            'grades' => $grades,
            'schedules' => $schedules,
            'evaluators' => $evaluators,
            'assignments' => $assignments,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'year_id' => 'required|uuid',
            'grade_level_id' => 'required|uuid',
            'section_id' => 'required|uuid',
            'schedule_id' => 'required|uuid',
            'evaluator_user_id' => 'required|uuid',
        ]);

        $schoolId = $this->principalSchoolId();
        $assignedBy = session('supabase_user.id');

        if (! $schoolId) {
            return response()->json([
                'message' => 'No school assigned to your principal account.',
            ], 403);
        }

        if (! $assignedBy) {
            return response()->json([
                'message' => 'Your user session is missing. Please sign in again.',
            ], 401);
        }

        $schedule = $this->findSchedule($validated['schedule_id'], $schoolId, $validated['year_id']);

        if (! $schedule) {
            return response()->json([
                'message' => 'The selected assessment schedule was not found for your school year.',
                'errors' => [
                    'schedule_id' => ['The selected assessment schedule was not found for your school year.'],
                ],
            ], 422);
        }

        if (($schedule['status'] ?? '') === 'cancelled') {
            return response()->json([
                'message' => 'Cancelled schedules cannot be assigned to evaluators.',
                'errors' => [
                    'schedule_id' => ['Cancelled schedules cannot be assigned to evaluators.'],
                ],
            ], 422);
        }

        $section = $this->findSection(
            $validated['section_id'],
            $schoolId,
            $validated['year_id'],
            $validated['grade_level_id']
        );

        if (! $section) {
            return response()->json([
                'message' => 'The selected section does not belong to the selected grade and school year.',
                'errors' => [
                    'section_id' => ['The selected section does not belong to the selected grade and school year.'],
                ],
            ], 422);
        }

        $evaluator = $this->findEvaluator($validated['evaluator_user_id'], $schoolId);

        if (! $evaluator) {
            return response()->json([
                'message' => 'The selected evaluator is not assigned to your school.',
                'errors' => [
                    'evaluator_user_id' => ['The selected evaluator is not assigned to your school.'],
                ],
            ], 422);
        }

        if ($this->assignmentExists($validated['schedule_id'], $validated['section_id'])) {
            return response()->json([
                'message' => 'This section already has an evaluator for the selected schedule.',
                'errors' => [
                    'section_id' => ['This section already has an evaluator for the selected schedule.'],
                ],
            ], 422);
        }

        $payload = [
            'schedule_id' => $schedule['schedule_id'],
            'evaluator_user_id' => $evaluator['user_id'],
            'section_id' => $section['section_id'],
            'year_id' => $schedule['year_id'],
            'quarter_id' => $schedule['quarter_id'],
            'assigned_by' => $assignedBy,
            'assessment_date' => $schedule['assessment_date'],
            'confirmation_status' => 'pending',
            'assessment_status' => 'not_started',
            'report_status' => 'not_submitted',
        ];

        $assignment = $this->createSupabaseRow('assigned_evaluators', $payload);

        if (! $assignment) {
            return response()->json([
                'message' => 'Failed to assign evaluator. Check Laravel logs for the Supabase error.',
            ], 500);
        }

        $quarters = $this->fetchQuarters($schedule['year_id']);
        $schoolYears = $this->fetchSchoolYears();
        $formatted = $this->formatAssignment(
            $assignment,
            [$schedule['schedule_id'] => $this->formatSchedule($schedule, $quarters)],
            [$section['section_id'] => $this->formatSection($section)],
            [$evaluator['user_id'] => $evaluator],
            $quarters,
            $schoolYears
        );

        session()->forget('mail_error_debug');
        $mailSent = $this->sendAssignmentEmail($formatted);

        return response()->json([
            'message' => $mailSent
                ? 'Evaluator assigned successfully. Confirmation email was sent.'
                : 'Evaluator assigned successfully, but the confirmation email could not be sent. Check your mail configuration.',
            'assignment' => $formatted,
            'mail_sent' => $mailSent,
            'mail_error' => session('mail_error_debug'),
            'debug' => [
                'app_url' => config('app.url'),
                'mail_mailer' => config('mail.default'),
                'mail_host' => config('mail.mailers.smtp.host'),
                'mail_port' => config('mail.mailers.smtp.port'),
                'mail_username' => config('mail.mailers.smtp.username'),
                'mail_from' => config('mail.from.address'),
            ],
        ]);
    }


    public function bulkStore(Request $request)
    {
        $validated = $request->validate([
            'year_id' => 'required|uuid',
            'schedule_id' => 'required|uuid',
            'assignments' => 'required|array|min:1',
            'assignments.*.grade_level_id' => 'required|uuid',
            'assignments.*.section_id' => 'required|uuid',
            'assignments.*.evaluator_user_id' => 'required|uuid',
        ]);

        $schoolId = $this->principalSchoolId();
        $assignedBy = session('supabase_user.id');

        if (! $schoolId) {
            return response()->json([
                'message' => 'No school assigned to your principal account.',
            ], 403);
        }

        if (! $assignedBy) {
            return response()->json([
                'message' => 'Your user session is missing. Please sign in again.',
            ], 401);
        }

        $schedule = $this->findSchedule($validated['schedule_id'], $schoolId, $validated['year_id']);

        if (! $schedule) {
            return response()->json([
                'message' => 'The selected assessment schedule was not found for your school year.',
                'errors' => [
                    'schedule_id' => ['The selected assessment schedule was not found for your school year.'],
                ],
            ], 422);
        }

        if (($schedule['status'] ?? '') === 'cancelled') {
            return response()->json([
                'message' => 'Cancelled schedules cannot be assigned to evaluators.',
                'errors' => [
                    'schedule_id' => ['Cancelled schedules cannot be assigned to evaluators.'],
                ],
            ], 422);
        }

        $quarters = $this->fetchQuarters($schedule['year_id']);
        $schoolYears = $this->fetchSchoolYears();
        $evaluators = collect($this->fetchEvaluators($schoolId))->keyBy('user_id');
        $scheduleLookup = [$schedule['schedule_id'] => $this->formatSchedule($schedule, $quarters)];
        $created = [];
        $skipped = [];
        $mailSentCount = 0;
        $seenSectionKeys = [];

        foreach ($validated['assignments'] as $index => $row) {
            $rowNumber = $index + 1;
            $sectionKey = $schedule['schedule_id'] . '|' . $row['section_id'];

            if (isset($seenSectionKeys[$sectionKey])) {
                $skipped[] = [
                    'row' => $rowNumber,
                    'reason' => 'Duplicate section in this bulk submission.',
                ];
                continue;
            }

            $seenSectionKeys[$sectionKey] = true;

            $section = $this->findSection(
                $row['section_id'],
                $schoolId,
                $validated['year_id'],
                $row['grade_level_id']
            );

            if (! $section) {
                $skipped[] = [
                    'row' => $rowNumber,
                    'reason' => 'The selected section does not belong to the selected grade and school year.',
                ];
                continue;
            }

            $evaluator = $evaluators->get($row['evaluator_user_id']);

            if (! $evaluator) {
                $skipped[] = [
                    'row' => $rowNumber,
                    'reason' => 'The selected evaluator is not assigned to your school.',
                ];
                continue;
            }

            if ($this->assignmentExists($schedule['schedule_id'], $section['section_id'])) {
                $skipped[] = [
                    'row' => $rowNumber,
                    'reason' => 'This section already has an evaluator for the selected schedule.',
                    'section_name' => $section['section_name'] ?? null,
                ];
                continue;
            }

            $payload = [
                'schedule_id' => $schedule['schedule_id'],
                'evaluator_user_id' => $evaluator['user_id'],
                'section_id' => $section['section_id'],
                'year_id' => $schedule['year_id'],
                'quarter_id' => $schedule['quarter_id'],
                'assigned_by' => $assignedBy,
                'assessment_date' => $schedule['assessment_date'],
                'confirmation_status' => 'pending',
                'assessment_status' => 'not_started',
                'report_status' => 'not_submitted',
            ];

            $assignment = $this->createSupabaseRow('assigned_evaluators', $payload);

            if (! $assignment) {
                $skipped[] = [
                    'row' => $rowNumber,
                    'reason' => 'Failed to save this assignment. Check Laravel logs for the Supabase error.',
                    'section_name' => $section['section_name'] ?? null,
                ];
                continue;
            }

            $formatted = $this->formatAssignment(
                $assignment,
                $scheduleLookup,
                [$section['section_id'] => $this->formatSection($section)],
                [$evaluator['user_id'] => $evaluator],
                $quarters,
                $schoolYears
            );

            if ($this->sendAssignmentEmail($formatted)) {
                $mailSentCount++;
            }

            $created[] = $formatted;
        }

        if (empty($created)) {
            return response()->json([
                'message' => 'No evaluator assignments were created. Review the skipped rows and try again.',
                'assignments' => [],
                'skipped' => $skipped,
                'mail_sent_count' => $mailSentCount,
            ], 422);
        }

        $message = count($created) . ' evaluator assignment' . (count($created) === 1 ? '' : 's') . ' created successfully.';

        if (! empty($skipped)) {
            $message .= ' ' . count($skipped) . ' row' . (count($skipped) === 1 ? '' : 's') . ' skipped.';
        }

        return response()->json([
            'message' => $message,
            'assignments' => $created,
            'skipped' => $skipped,
            'mail_sent_count' => $mailSentCount,
        ]);
    }

    public function resend(string $assignmentId)
    {
        $schoolId = $this->principalSchoolId();

        if (! $schoolId) {
            return response()->json([
                'message' => 'No school assigned to your principal account.',
            ], 403);
        }

        $assignment = $this->findAssignmentForSchool($assignmentId, $schoolId);

        if (! $assignment) {
            return response()->json([
                'message' => 'Evaluator assignment not found for your school.',
            ], 404);
        }

        if (($assignment['confirmation_status'] ?? null) === 'confirmed') {
            return response()->json([
                'message' => 'This evaluator has already confirmed the assignment. No follow-up is needed.',
            ], 422);
        }

        $formatted = $this->hydrateSingleAssignment($assignment, $schoolId);
        $mailSent = $this->sendAssignmentEmail($formatted);

        if (! $mailSent) {
            return response()->json([
                'message' => 'The follow-up email could not be sent. Check your mail configuration.',
            ], 500);
        }

        return response()->json([
            'message' => 'Follow-up email sent successfully.',
            'assignment' => $formatted,
        ]);
    }

    public function destroy(string $assignmentId)
    {
        $schoolId = $this->principalSchoolId();

        if (! $schoolId) {
            return response()->json([
                'message' => 'No school assigned to your principal account.',
            ], 403);
        }

        $assignment = $this->findAssignmentForSchool($assignmentId, $schoolId);

        if (! $assignment) {
            return response()->json([
                'message' => 'Evaluator assignment not found for your school.',
            ], 404);
        }

        if ($this->assignmentHasAssessmentRecords($assignmentId)) {
            return response()->json([
                'message' => 'This assignment cannot be deleted because assessment records already reference it.',
            ], 422);
        }

        $response = Http::withHeaders($this->supabaseHeaders())
            ->delete($this->supabaseUrl() . '/rest/v1/assigned_evaluators?assignment_id=eq.' . $assignmentId);

        if (! $response->successful()) {
            report('Failed to delete evaluator assignment: ' . $response->body());

            return response()->json([
                'message' => 'Failed to delete evaluator assignment. Check Laravel logs for the Supabase error.',
            ], 500);
        }

        return response()->json([
            'message' => 'Evaluator assignment deleted successfully.',
            'assignment_id' => $assignmentId,
        ]);
    }

    public function confirm(Request $request, string $assignmentId)
    {
        return $this->confirmAssignment($assignmentId);
    }

    private function confirmAssignment(string $assignmentId)
    {
        $response = Http::withHeaders(array_merge($this->supabaseHeaders(), [
            'Content-Type' => 'application/json',
            'Prefer' => 'return=representation',
        ]))->patch($this->supabaseUrl() . '/rest/v1/assigned_evaluators?assignment_id=eq.' . $assignmentId, [
            'confirmation_status' => 'confirmed',
        ]);

        if (! $response->successful()) {
            report('Failed to confirm evaluator assignment: ' . $response->body());

            return view('pages.principal.evaluator-assignment-response', [
                'status' => 'error',
                'title' => 'Confirmation Failed',
                'message' => 'The assignment confirmation could not be recorded. Please contact your school principal.',
            ]);
        }

        return view('pages.principal.evaluator-assignment-response', [
            'status' => 'confirmed',
            'title' => 'Assignment Confirmed',
            'message' => 'Thank you. Your evaluator assignment has been confirmed.',
        ]);
    }

    private function fetchSchoolYears(): array
    {
        $response = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/school_year', [
                'select' => 'year_id,start_date,end_date,created_at',
                'order' => 'start_date.desc',
            ]);

        if (! $response->successful()) {
            report('Failed to fetch school years: ' . $response->body());
            return [];
        }

        return collect($response->json())->map(function ($year) {
            return [
                'year_id' => $year['year_id'],
                'start_date' => $year['start_date'] ?? null,
                'end_date' => $year['end_date'] ?? null,
                'created_at' => $year['created_at'] ?? null,
                'label' => $this->schoolYearLabel($year),
            ];
        })->values()->all();
    }

    private function fetchQuarters(string $yearId): array
    {
        $response = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/quarter', [
                'select' => 'quarter_id,year_id,quarter_number,quarter_name,start_date,end_date',
                'year_id' => 'eq.' . $yearId,
                'order' => 'quarter_number.asc',
            ]);

        if (! $response->successful()) {
            report('Failed to fetch quarters: ' . $response->body());
            return [];
        }

        return collect($response->json())->map(function ($quarter) {
            return [
                'quarter_id' => $quarter['quarter_id'],
                'year_id' => $quarter['year_id'],
                'quarter_number' => $quarter['quarter_number'] ?? null,
                'quarter_name' => $quarter['quarter_name'] ?? 'Quarter',
                'start_date' => $quarter['start_date'] ?? null,
                'end_date' => $quarter['end_date'] ?? null,
                'label' => $this->quarterLabel($quarter),
            ];
        })->values()->all();
    }

    private function fetchGradesWithSections(string $schoolId, string $yearId): array
    {
        $gradesResponse = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/grade_levels', [
                'select' => 'grade_level_id,grade_number,school_id,is_active',
                'school_id' => 'eq.' . $schoolId,
                'is_active' => 'eq.true',
                'order' => 'grade_number.asc',
            ]);

        if (! $gradesResponse->successful()) {
            report('Failed to fetch principal grades: ' . $gradesResponse->body());
            return [];
        }

        $sectionsResponse = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/class_sections', [
                'select' => 'section_id,school_id,year_id,grade_level_id,section_name,adviser_name,status,created_at,updated_at',
                'school_id' => 'eq.' . $schoolId,
                'year_id' => 'eq.' . $yearId,
                'status' => 'neq.archived',
                'order' => 'section_name.asc',
            ]);

        if (! $sectionsResponse->successful()) {
            report('Failed to fetch principal sections: ' . $sectionsResponse->body());
            return [];
        }

        $sectionsByGrade = collect($sectionsResponse->json())
            ->map(fn ($section) => $this->formatSection($section))
            ->groupBy('grade_level_id');

        return collect($gradesResponse->json())->map(function ($grade) use ($sectionsByGrade) {
            $sections = $sectionsByGrade->get($grade['grade_level_id'], collect())->values()->all();

            return [
                'grade_level_id' => $grade['grade_level_id'],
                'grade_number' => $grade['grade_number'],
                'school_id' => $grade['school_id'],
                'section_count' => count($sections),
                'sections' => $sections,
            ];
        })->values()->all();
    }

    private function fetchSchedules(string $schoolId, string $yearId, array $quarters): array
    {
        $response = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/assessment_schedules', [
                'select' => 'schedule_id,year_id,quarter_id,school_id,assessment_date,status,created_by,created_at,updated_at',
                'school_id' => 'eq.' . $schoolId,
                'year_id' => 'eq.' . $yearId,
                'status' => 'neq.cancelled',
                'order' => 'assessment_date.asc',
            ]);

        if (! $response->successful()) {
            report('Failed to fetch assessment schedules: ' . $response->body());
            return [];
        }

        return collect($response->json())
            ->map(fn ($schedule) => $this->formatSchedule($schedule, $quarters))
            ->values()
            ->all();
    }

    private function fetchEvaluators(string $schoolId): array
    {
        $rolesResponse = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/user_roles', [
                'select' => 'user_role_id,user_id,school_id,roles(name,description),scopes(name,scope_type)',
                'school_id' => 'eq.' . $schoolId,
                'order' => 'assigned_at.asc',
            ]);

        if (! $rolesResponse->successful()) {
            report('Failed to fetch school user roles for evaluators: ' . $rolesResponse->body());
            return [];
        }

        $eligibleRoles = collect($rolesResponse->json())->filter(function ($row) {
            $roleName = strtolower($row['roles']['name'] ?? '');

            return str_contains($roleName, 'teacher')
                || str_contains($roleName, 'evaluator');
        })->values();

        $userIds = $eligibleRoles->pluck('user_id')->filter()->unique()->values()->all();
        $profiles = collect($this->fetchProfilesByIds($userIds))->keyBy('id');

        return $eligibleRoles->map(function ($row) use ($profiles) {
            $profile = $profiles->get($row['user_id'], []);
            $roleName = $row['roles']['name'] ?? 'Evaluator';
            $fullName = $profile['full_name'] ?? 'Unnamed Evaluator';
            $email = $profile['email'] ?? null;

            return [
                'user_id' => $row['user_id'],
                'full_name' => $fullName,
                'email' => $email,
                'role_name' => $roleName,
                'label' => $email ? $fullName . ' (' . $email . ')' : $fullName,
            ];
        })->unique('user_id')->sortBy('full_name')->values()->all();
    }

    private function fetchAssignments(string $schoolId, string $yearId, array $grades, array $schedules, array $quarters, array $schoolYears, array $evaluators): array
    {
        $sectionLookup = collect($grades)
            ->flatMap(fn ($grade) => $grade['sections'] ?? [])
            ->keyBy('section_id')
            ->all();

        $scheduleLookup = collect($schedules)->keyBy('schedule_id')->all();
        $evaluatorLookup = collect($evaluators)->keyBy('user_id')->all();

        $response = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/assigned_evaluators', [
                'select' => 'assignment_id,schedule_id,evaluator_user_id,section_id,year_id,quarter_id,assigned_by,assigned_at,confirmation_status,assessment_status,report_status,assessment_date,created_at,updated_at',
                'year_id' => 'eq.' . $yearId,
                'order' => 'assigned_at.desc',
            ]);

        if (! $response->successful()) {
            report('Failed to fetch evaluator assignments: ' . $response->body());
            return [];
        }

        return collect($response->json())->filter(function ($assignment) use ($sectionLookup, $scheduleLookup, $schoolId) {
            $section = $sectionLookup[$assignment['section_id']] ?? null;
            $schedule = $scheduleLookup[$assignment['schedule_id']] ?? null;

            return $section && $schedule && ($schedule['school_id'] ?? null) === $schoolId;
        })->map(function ($assignment) use ($scheduleLookup, $sectionLookup, $evaluatorLookup, $quarters, $schoolYears) {
            return $this->formatAssignment($assignment, $scheduleLookup, $sectionLookup, $evaluatorLookup, $quarters, $schoolYears);
        })->values()->all();
    }

    private function hydrateSingleAssignment(array $assignment, string $schoolId): array
    {
        $schoolYears = $this->fetchSchoolYears();
        $quarters = $this->fetchQuarters($assignment['year_id']);
        $schedule = $this->findSchedule($assignment['schedule_id'], $schoolId, $assignment['year_id']);
        $section = $this->findSection($assignment['section_id'], $schoolId, $assignment['year_id'], null);
        $evaluator = $this->findEvaluator($assignment['evaluator_user_id'], $schoolId);

        return $this->formatAssignment(
            $assignment,
            $schedule ? [$schedule['schedule_id'] => $this->formatSchedule($schedule, $quarters)] : [],
            $section ? [$section['section_id'] => $this->formatSection($section)] : [],
            $evaluator ? [$evaluator['user_id'] => $evaluator] : [],
            $quarters,
            $schoolYears
        );
    }

    private function formatAssignment(array $assignment, array $scheduleLookup, array $sectionLookup, array $evaluatorLookup, array $quarters, array $schoolYears): array
    {
        $schedule = $scheduleLookup[$assignment['schedule_id']] ?? [];
        $section = $sectionLookup[$assignment['section_id']] ?? [];
        $evaluator = $evaluatorLookup[$assignment['evaluator_user_id']] ?? [];
        $quarter = collect($quarters)->firstWhere('quarter_id', $assignment['quarter_id']) ?: [];
        $schoolYear = collect($schoolYears)->firstWhere('year_id', $assignment['year_id']) ?: [];

        return [
            'assignment_id' => $assignment['assignment_id'],
            'schedule_id' => $assignment['schedule_id'],
            'evaluator_user_id' => $assignment['evaluator_user_id'],
            'section_id' => $assignment['section_id'],
            'year_id' => $assignment['year_id'],
            'quarter_id' => $assignment['quarter_id'],
            'assigned_by' => $assignment['assigned_by'] ?? null,
            'assigned_at' => $assignment['assigned_at'] ?? null,
            'confirmation_status' => $assignment['confirmation_status'] ?? 'pending',
            'assessment_status' => $assignment['assessment_status'] ?? 'not_started',
            'report_status' => $assignment['report_status'] ?? 'not_submitted',
            'assessment_date' => $assignment['assessment_date'] ?? ($schedule['assessment_date'] ?? null),
            'created_at' => $assignment['created_at'] ?? null,
            'updated_at' => $assignment['updated_at'] ?? null,
            'school_year_label' => $schoolYear['label'] ?? $this->schoolYearLabel($schoolYear),
            'quarter_label' => $quarter['label'] ?? $this->quarterLabel($quarter),
            'schedule_label' => $schedule['label'] ?? $this->dateLabel($assignment['assessment_date'] ?? null),
            'section_name' => $section['section_name'] ?? 'Unknown Section',
            'grade_level_id' => $section['grade_level_id'] ?? null,
            'grade_label' => $section['grade_number'] ?? null ? 'Grade ' . $section['grade_number'] : 'Grade',
            'adviser_name' => $section['adviser_name'] ?? null,
            'evaluator_name' => $evaluator['full_name'] ?? 'Unknown Evaluator',
            'evaluator_email' => $evaluator['email'] ?? null,
            'evaluator_role' => $evaluator['role_name'] ?? 'Evaluator',
        ];
    }

    private function formatSection(array $section): array
    {
        $gradeNumber = $this->gradeNumberById($section['grade_level_id'] ?? null);

        return [
            'section_id' => $section['section_id'],
            'school_id' => $section['school_id'] ?? null,
            'year_id' => $section['year_id'] ?? null,
            'grade_level_id' => $section['grade_level_id'] ?? null,
            'grade_number' => $gradeNumber,
            'section_name' => $section['section_name'] ?? 'Section',
            'adviser_name' => $section['adviser_name'] ?? null,
            'status' => $section['status'] ?? 'active',
        ];
    }

    private array $gradeNumberCache = [];

    private function gradeNumberById(?string $gradeLevelId): ?int
    {
        if (! $gradeLevelId) {
            return null;
        }

        if (array_key_exists($gradeLevelId, $this->gradeNumberCache)) {
            return $this->gradeNumberCache[$gradeLevelId];
        }

        $response = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/grade_levels', [
                'select' => 'grade_level_id,grade_number',
                'grade_level_id' => 'eq.' . $gradeLevelId,
                'limit' => 1,
            ]);

        if (! $response->successful()) {
            $this->gradeNumberCache[$gradeLevelId] = null;
            return null;
        }

        $this->gradeNumberCache[$gradeLevelId] = $response->json()[0]['grade_number'] ?? null;
        return $this->gradeNumberCache[$gradeLevelId];
    }

    private function formatSchedule(array $schedule, array $quarters): array
    {
        $quarter = collect($quarters)->firstWhere('quarter_id', $schedule['quarter_id']) ?: [];

        return [
            'schedule_id' => $schedule['schedule_id'],
            'year_id' => $schedule['year_id'],
            'quarter_id' => $schedule['quarter_id'],
            'school_id' => $schedule['school_id'],
            'assessment_date' => $schedule['assessment_date'],
            'status' => $schedule['status'] ?? 'scheduled',
            'quarter_label' => $quarter['label'] ?? $this->quarterLabel($quarter),
            'label' => trim(($quarter['label'] ?? $this->quarterLabel($quarter)) . ' - ' . $this->dateLabel($schedule['assessment_date'] ?? null)),
        ];
    }

    private function findSchedule(string $scheduleId, string $schoolId, string $yearId): ?array
    {
        $response = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/assessment_schedules', [
                'select' => 'schedule_id,year_id,quarter_id,school_id,assessment_date,status,created_by,created_at,updated_at',
                'schedule_id' => 'eq.' . $scheduleId,
                'school_id' => 'eq.' . $schoolId,
                'year_id' => 'eq.' . $yearId,
                'limit' => 1,
            ]);

        if (! $response->successful()) {
            report('Failed to find schedule: ' . $response->body());
            return null;
        }

        return $response->json()[0] ?? null;
    }

    private function findSection(string $sectionId, string $schoolId, string $yearId, ?string $gradeLevelId): ?array
    {
        $query = [
            'select' => 'section_id,school_id,year_id,grade_level_id,section_name,adviser_name,status,created_at,updated_at',
            'section_id' => 'eq.' . $sectionId,
            'school_id' => 'eq.' . $schoolId,
            'year_id' => 'eq.' . $yearId,
            'status' => 'neq.archived',
            'limit' => 1,
        ];

        if ($gradeLevelId) {
            $query['grade_level_id'] = 'eq.' . $gradeLevelId;
        }

        $response = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/class_sections', $query);

        if (! $response->successful()) {
            report('Failed to find section: ' . $response->body());
            return null;
        }

        return $response->json()[0] ?? null;
    }

    private function findEvaluator(string $userId, string $schoolId): ?array
    {
        return collect($this->fetchEvaluators($schoolId))->firstWhere('user_id', $userId);
    }

    private function findAssignmentForSchool(string $assignmentId, string $schoolId): ?array
    {
        $response = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/assigned_evaluators', [
                'select' => 'assignment_id,schedule_id,evaluator_user_id,section_id,year_id,quarter_id,assigned_by,assigned_at,confirmation_status,assessment_status,report_status,assessment_date,created_at,updated_at',
                'assignment_id' => 'eq.' . $assignmentId,
                'limit' => 1,
            ]);

        if (! $response->successful()) {
            report('Failed to find evaluator assignment: ' . $response->body());
            return null;
        }

        $assignment = $response->json()[0] ?? null;

        if (! $assignment) {
            return null;
        }

        $schedule = $this->findSchedule($assignment['schedule_id'], $schoolId, $assignment['year_id']);

        return $schedule ? $assignment : null;
    }

    private function assignmentExists(string $scheduleId, string $sectionId): bool
    {
        $response = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/assigned_evaluators', [
                'select' => 'assignment_id',
                'schedule_id' => 'eq.' . $scheduleId,
                'section_id' => 'eq.' . $sectionId,
                'limit' => 1,
            ]);

        if (! $response->successful()) {
            report('Failed to validate duplicate evaluator assignment: ' . $response->body());
            return false;
        }

        return ! empty($response->json());
    }

    private function assignmentHasAssessmentRecords(string $assignmentId): bool
    {
        $response = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/assessment_records', [
                'select' => 'assessment_record_id',
                'assignment_id' => 'eq.' . $assignmentId,
                'limit' => 1,
            ]);

        if (! $response->successful()) {
            report('Failed to check assignment assessment records: ' . $response->body());
            return true;
        }

        return ! empty($response->json());
    }

    private function createSupabaseRow(string $table, array $payload): ?array
    {
        $response = Http::withHeaders(array_merge($this->supabaseHeaders(), [
            'Content-Type' => 'application/json',
            'Prefer' => 'return=representation',
        ]))->post($this->supabaseUrl() . '/rest/v1/' . $table, $payload);

        if (! $response->successful()) {
            report("Failed to create {$table}: " . $response->body());
            return null;
        }

        return $response->json()[0] ?? null;
    }

    private function fetchProfilesByIds(array $userIds): array
    {
        if (empty($userIds)) {
            return [];
        }

        $response = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/profiles', [
                'select' => 'id,full_name,email,title,position',
                'id' => 'in.(' . $this->postgrestInList($userIds) . ')',
            ]);

        if (! $response->successful()) {
            report('Failed to fetch profiles by ids: ' . $response->body());
            return [];
        }

        return $response->json();
    }

    private function sendAssignmentEmail(array $assignment): bool
    {
        $email = $assignment['evaluator_email'] ?? null;

        if (! $email) {
            session()->flash('mail_error_debug', 'Evaluator email is missing.');

            Log::error('Evaluator email missing.', [
                'assignment' => $assignment,
            ]);

            return false;
        }

        $principalName = session('supabase_user.full_name')
            ?? session('supabase_user.email')
            ?? 'School Principal';

        try {
            URL::forceRootUrl(rtrim(config('app.url', 'https://readbee.onrender.com'), '/'));
            URL::forceScheme('https');

            $confirmUrl = URL::temporarySignedRoute(
                'principal.assign-evaluator.confirm',
                now()->addDays(7),
                [
                    'assignmentId' => $assignment['assignment_id'],
                ]
            );

            Log::info('Sending evaluator assignment email...', [
                'to' => $email,
                'confirm_url' => $confirmUrl,
            ]);

            Mail::to($email)->send(
                new EvaluatorAssignmentMail(
                    $assignment['evaluator_name'] ?? 'Evaluator',
                    $assignment['school_year_label'] ?? 'School Year',
                    $assignment['quarter_label'] ?? 'Quarter',
                    $this->dateLabel($assignment['assessment_date'] ?? null),
                    $assignment['grade_label'] ?? 'Grade',
                    $assignment['section_name'] ?? 'Section',
                    $principalName,
                    $confirmUrl
                )
            );

            Log::info('Evaluator assignment email sent successfully.', [
                'to' => $email,
            ]);

            return true;
        } catch (\Throwable $exception) {
            session()->flash('mail_error_debug', $exception->getMessage());

            Log::error('Evaluator assignment email failed.', [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return false;
        }
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

    private function dateLabel(?string $date): string
    {
        return $date ? date('M d, Y', strtotime($date)) : 'No date';
    }

    private function postgrestInList(array $ids): string
    {
        return collect($ids)
            ->filter()
            ->map(fn ($id) => '"' . str_replace('"', '\\"', (string) $id) . '"')
            ->implode(',');
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
