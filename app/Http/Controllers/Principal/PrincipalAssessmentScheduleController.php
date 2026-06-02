<?php

namespace App\Http\Controllers\Principal;

use App\Helpers\PrincipalMenuHelper;
use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PrincipalAssessmentScheduleController extends Controller
{
    public function index(Request $request)
    {
        $schoolId = $this->principalSchoolId();

        if (! $schoolId) {
            if ($request->ajax() || $request->boolean('ajax')) {
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

        if ($selectedYearId && ! collect($schoolYears)->contains('year_id', $selectedYearId)) {
            $selectedYearId = $schoolYears[0]['year_id'] ?? null;
        }

        $quarters = $selectedYearId ? $this->fetchQuarters($selectedYearId) : [];
        $schedules = $selectedYearId ? $this->fetchSchedules($schoolId, $selectedYearId, $quarters, $schoolYears) : [];

        if ($request->ajax() || $request->boolean('ajax')) {
            return response()->json([
                'success' => true,
                'selectedYearId' => $selectedYearId,
                'quarters' => $quarters,
                'schedules' => $schedules,
            ]);
        }

        return view('pages.principal.principal-assessment-schedule', [
            'title' => 'Assessment Schedule',
            'menuGroups' => PrincipalMenuHelper::getMenuGroups(),
            'schoolYears' => $schoolYears,
            'selectedYearId' => $selectedYearId,
            'quarters' => $quarters,
            'schedules' => $schedules,
        ]);
    }

    public function store(Request $request)
    {
        $schoolId = $this->principalSchoolId();
        $userId = session('supabase_user.id');

        if (! $schoolId) {
            return response()->json([
                'message' => 'No school assigned to your principal account.',
            ], 403);
        }

        if (! $userId) {
            return response()->json([
                'message' => 'Your user session is missing. Please sign in again.',
            ], 401);
        }

        $validated = $request->validate([
            'year_id' => 'required|uuid',
            'quarter_id' => 'required|uuid',
            'assessment_date' => 'required|date',
            'status' => 'nullable|in:scheduled,ongoing,completed,cancelled',
        ]);

        if (! $this->quarterBelongsToYear($validated['quarter_id'], $validated['year_id'])) {
            return response()->json([
                'message' => 'The selected quarter does not belong to the selected school year.',
                'errors' => [
                    'quarter_id' => ['The selected quarter does not belong to the selected school year.'],
                ],
            ], 422);
        }

        $dateRangeError = $this->assessmentDateRangeError(
            $validated['assessment_date'],
            $validated['quarter_id'],
            $validated['year_id']
        );

        if ($dateRangeError) {
            return response()->json([
                'message' => $dateRangeError,
                'errors' => [
                    'assessment_date' => [$dateRangeError],
                ],
            ], 422);
        }

        if ($this->scheduleExists($schoolId, $validated['year_id'], $validated['quarter_id'], $validated['assessment_date'])) {
            return response()->json([
                'message' => 'An assessment schedule already exists for this quarter and date.',
                'errors' => [
                    'assessment_date' => ['An assessment schedule already exists for this quarter and date.'],
                ],
            ], 422);
        }

        $response = Http::withHeaders(array_merge($this->supabaseHeaders(), [
            'Content-Type' => 'application/json',
            'Prefer' => 'return=representation',
        ]))->post($this->supabaseUrl() . '/rest/v1/assessment_schedules', [
            'year_id' => $validated['year_id'],
            'quarter_id' => $validated['quarter_id'],
            'school_id' => $schoolId,
            'assessment_date' => $validated['assessment_date'],
            'status' => $validated['status'] ?? 'scheduled',
            'created_by' => $userId,
        ]);

        if (! $response->successful()) {
            report('Failed to create assessment schedule: ' . $response->body());

            return response()->json([
                'message' => 'Failed to create assessment schedule. Check Laravel logs for the Supabase error.',
            ], 500);
        }

        $schedule = $response->json()[0] ?? null;
        $quarters = $this->fetchQuarters($validated['year_id']);
        $schoolYears = $this->fetchSchoolYears();

        return response()->json([
            'message' => 'Assessment schedule created successfully.',
            'schedule' => $this->formatSchedule($schedule, $quarters, $schoolYears),
        ]);
    }

    public function update(Request $request, string $scheduleId)
    {
        $schoolId = $this->principalSchoolId();

        if (! $schoolId) {
            return response()->json([
                'message' => 'No school assigned to your principal account.',
            ], 403);
        }

        $existing = $this->fetchScheduleForSchool($scheduleId, $schoolId);

        if (! $existing) {
            return response()->json([
                'message' => 'Assessment schedule not found for your school.',
            ], 404);
        }

        $validated = $request->validate([
            'quarter_id' => 'required|uuid',
            'assessment_date' => 'required|date',
            'status' => 'required|in:scheduled,ongoing,completed,cancelled',
        ]);

        $yearId = $existing['year_id'];

        if (! $this->quarterBelongsToYear($validated['quarter_id'], $yearId)) {
            return response()->json([
                'message' => 'The selected quarter does not belong to this schedule school year.',
                'errors' => [
                    'quarter_id' => ['The selected quarter does not belong to this schedule school year.'],
                ],
            ], 422);
        }

        $dateRangeError = $this->assessmentDateRangeError(
            $validated['assessment_date'],
            $validated['quarter_id'],
            $yearId
        );

        if ($dateRangeError) {
            return response()->json([
                'message' => $dateRangeError,
                'errors' => [
                    'assessment_date' => [$dateRangeError],
                ],
            ], 422);
        }

        if ($this->scheduleExists($schoolId, $yearId, $validated['quarter_id'], $validated['assessment_date'], $scheduleId)) {
            return response()->json([
                'message' => 'Another assessment schedule already exists for this quarter and date.',
                'errors' => [
                    'assessment_date' => ['Another assessment schedule already exists for this quarter and date.'],
                ],
            ], 422);
        }

        $affectedAssignments = $this->fetchAssignmentsForSchedule($scheduleId);

        $response = Http::withHeaders(array_merge($this->supabaseHeaders(), [
            'Content-Type' => 'application/json',
            'Prefer' => 'return=representation',
        ]))->patch($this->supabaseUrl() . '/rest/v1/assessment_schedules?schedule_id=eq.' . $scheduleId . '&school_id=eq.' . $schoolId, [
            'quarter_id' => $validated['quarter_id'],
            'assessment_date' => $validated['assessment_date'],
            'status' => $validated['status'],
        ]);

        if (! $response->successful()) {
            report('Failed to update assessment schedule: ' . $response->body());

            return response()->json([
                'message' => 'Failed to update assessment schedule. Check Laravel logs for the Supabase error.',
            ], 500);
        }

        $schedule = $response->json()[0] ?? null;
        $this->syncAssignedEvaluatorScheduleDetails($scheduleId, $validated['quarter_id'], $validated['assessment_date']);
        $this->notifyScheduleChanges($existing, $schedule ?: $validated, $affectedAssignments);

        $quarters = $this->fetchQuarters($yearId);
        $schoolYears = $this->fetchSchoolYears();

        return response()->json([
            'message' => 'Assessment schedule updated successfully.',
            'schedule' => $this->formatSchedule($schedule, $quarters, $schoolYears),
        ]);
    }

    public function destroy(string $scheduleId)
    {
        $schoolId = $this->principalSchoolId();

        if (! $schoolId) {
            return response()->json([
                'message' => 'No school assigned to your principal account.',
            ], 403);
        }

        $schedule = $this->fetchScheduleForSchool($scheduleId, $schoolId);

        if (! $schedule) {
            return response()->json([
                'message' => 'Assessment schedule not found for your school.',
            ], 404);
        }

        if ($this->scheduleHasLinkedRecords($scheduleId)) {
            return response()->json([
                'message' => 'This schedule already has assessment records or evaluator assignments. Cancel it instead of deleting it.',
            ], 422);
        }

        $response = Http::withHeaders(array_merge($this->supabaseHeaders(), [
            'Prefer' => 'return=minimal',
        ]))->delete($this->supabaseUrl() . '/rest/v1/assessment_schedules?schedule_id=eq.' . $scheduleId . '&school_id=eq.' . $schoolId);

        if (! $response->successful()) {
            report('Failed to delete assessment schedule: ' . $response->body());

            return response()->json([
                'message' => 'Failed to delete assessment schedule. Check Laravel logs for the Supabase error.',
            ], 500);
        }

        return response()->json([
            'message' => 'Assessment schedule deleted successfully.',
            'schedule_id' => $scheduleId,
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
            report('Failed to fetch school years for assessment schedule: ' . $response->body());
            return [];
        }

        return collect($response->json())
            ->map(function ($year) {
                return [
                    'year_id' => $year['year_id'],
                    'start_date' => $year['start_date'] ?? null,
                    'end_date' => $year['end_date'] ?? null,
                    'created_at' => $year['created_at'] ?? null,
                    'label' => $this->schoolYearLabel($year),
                ];
            })
            ->values()
            ->all();
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
            report('Failed to fetch quarters for assessment schedule: ' . $response->body());
            return [];
        }

        return $response->json();
    }

    private function fetchSchedules(string $schoolId, string $yearId, array $quarters, array $schoolYears): array
    {
        $response = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/assessment_schedules', [
                'select' => 'schedule_id,created_at,updated_at,year_id,quarter_id,school_id,assessment_date,status,created_by',
                'school_id' => 'eq.' . $schoolId,
                'year_id' => 'eq.' . $yearId,
                'order' => 'assessment_date.asc',
            ]);

        if (! $response->successful()) {
            report('Failed to fetch assessment schedules: ' . $response->body());
            return [];
        }

        return collect($response->json())
            ->map(fn ($schedule) => $this->formatSchedule($schedule, $quarters, $schoolYears))
            ->values()
            ->all();
    }

    private function formatSchedule(?array $schedule, array $quarters, array $schoolYears): ?array
    {
        if (! $schedule) {
            return null;
        }

        $quarter = collect($quarters)->firstWhere('quarter_id', $schedule['quarter_id']);
        $year = collect($schoolYears)->firstWhere('year_id', $schedule['year_id']);

        return [
            'schedule_id' => $schedule['schedule_id'],
            'year_id' => $schedule['year_id'],
            'quarter_id' => $schedule['quarter_id'],
            'school_id' => $schedule['school_id'],
            'assessment_date' => $schedule['assessment_date'],
            'status' => $schedule['status'],
            'created_by' => $schedule['created_by'] ?? null,
            'created_at' => $schedule['created_at'] ?? null,
            'updated_at' => $schedule['updated_at'] ?? null,
            'quarter_number' => $quarter['quarter_number'] ?? null,
            'quarter_name' => $quarter['quarter_name'] ?? 'Quarter',
            'quarter_start_date' => $quarter['start_date'] ?? null,
            'quarter_end_date' => $quarter['end_date'] ?? null,
            'year_label' => $year['label'] ?? 'School Year',
            'title' => ($quarter['quarter_name'] ?? 'Assessment') . ' Assessment',
        ];
    }

    private function fetchScheduleForSchool(string $scheduleId, string $schoolId): ?array
    {
        $response = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/assessment_schedules', [
                'select' => 'schedule_id,created_at,updated_at,year_id,quarter_id,school_id,assessment_date,status,created_by',
                'schedule_id' => 'eq.' . $scheduleId,
                'school_id' => 'eq.' . $schoolId,
                'limit' => 1,
            ]);

        if (! $response->successful()) {
            report('Failed to fetch assessment schedule for school: ' . $response->body());
            return null;
        }

        return $response->json()[0] ?? null;
    }

    private function quarterBelongsToYear(string $quarterId, string $yearId): bool
    {
        $response = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/quarter', [
                'select' => 'quarter_id',
                'quarter_id' => 'eq.' . $quarterId,
                'year_id' => 'eq.' . $yearId,
                'limit' => 1,
            ]);

        if (! $response->successful()) {
            report('Failed to validate quarter year: ' . $response->body());
            return false;
        }

        return ! empty($response->json());
    }

    private function assessmentDateRangeError(string $assessmentDate, string $quarterId, string $yearId): ?string
    {
        $quarter = $this->fetchQuarterForYear($quarterId, $yearId);
        $schoolYear = $this->fetchSchoolYear($yearId);

        if (! $quarter) {
            return 'The selected quarter does not belong to the selected school year.';
        }

        $minDate = $quarter['start_date'] ?? ($schoolYear['start_date'] ?? null);
        $maxDate = $quarter['end_date'] ?? ($schoolYear['end_date'] ?? null);

        if ($minDate && $assessmentDate < $minDate) {
            return 'The assessment date must be on or after ' . date('M d, Y', strtotime($minDate)) . '.';
        }

        if ($maxDate && $assessmentDate > $maxDate) {
            return 'The assessment date must be on or before ' . date('M d, Y', strtotime($maxDate)) . '.';
        }

        return null;
    }

    private function fetchQuarterForYear(string $quarterId, string $yearId): ?array
    {
        $response = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/quarter', [
                'select' => 'quarter_id,year_id,quarter_number,quarter_name,start_date,end_date',
                'quarter_id' => 'eq.' . $quarterId,
                'year_id' => 'eq.' . $yearId,
                'limit' => 1,
            ]);

        if (! $response->successful()) {
            report('Failed to fetch quarter date range: ' . $response->body());
            return null;
        }

        return $response->json()[0] ?? null;
    }

    private function fetchSchoolYear(string $yearId): ?array
    {
        $response = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/school_year', [
                'select' => 'year_id,start_date,end_date',
                'year_id' => 'eq.' . $yearId,
                'limit' => 1,
            ]);

        if (! $response->successful()) {
            report('Failed to fetch school year date range: ' . $response->body());
            return null;
        }

        return $response->json()[0] ?? null;
    }

    private function scheduleExists(string $schoolId, string $yearId, string $quarterId, string $assessmentDate, ?string $exceptScheduleId = null): bool
    {
        $query = [
            'select' => 'schedule_id',
            'school_id' => 'eq.' . $schoolId,
            'year_id' => 'eq.' . $yearId,
            'quarter_id' => 'eq.' . $quarterId,
            'assessment_date' => 'eq.' . $assessmentDate,
            'limit' => 1,
        ];

        $response = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/assessment_schedules', $query);

        if (! $response->successful()) {
            report('Failed to validate duplicate assessment schedule: ' . $response->body());
            return true;
        }

        $existing = $response->json()[0] ?? null;

        if (! $existing) {
            return false;
        }

        if ($exceptScheduleId && (string) $existing['schedule_id'] === (string) $exceptScheduleId) {
            return false;
        }

        return true;
    }

    private function fetchAssignmentsForSchedule(string $scheduleId): array
    {
        $response = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/assigned_evaluators', [
                'select' => 'assignment_id,evaluator_user_id,section_id,schedule_id,assessment_date,quarter_id,report_status,assessment_status',
                'schedule_id' => 'eq.' . $scheduleId,
            ]);

        if (! $response->successful()) {
            report('Failed to fetch schedule assignments for notification: ' . $response->body());
            return [];
        }

        return $response->json();
    }

    private function syncAssignedEvaluatorScheduleDetails(string $scheduleId, string $quarterId, string $assessmentDate): void
    {
        $response = Http::withHeaders(array_merge($this->supabaseHeaders(), [
            'Content-Type' => 'application/json',
            'Prefer' => 'return=minimal',
        ]))->patch($this->supabaseUrl() . '/rest/v1/assigned_evaluators?schedule_id=eq.' . rawurlencode($scheduleId), [
            'quarter_id' => $quarterId,
            'assessment_date' => $assessmentDate,
            'updated_at' => now()->toISOString(),
        ]);

        if (! $response->successful()) {
            report('Failed to sync assigned evaluator schedule details: ' . $response->body());
        }
    }

    private function notifyScheduleChanges(array $oldSchedule, array $newSchedule, array $assignments): void
    {
        try {
            if (empty($assignments)) {
                return;
            }

            $oldDate = $oldSchedule['assessment_date'] ?? null;
            $newDate = $newSchedule['assessment_date'] ?? $oldDate;
            $oldStatus = strtolower((string) ($oldSchedule['status'] ?? ''));
            $newStatus = strtolower((string) ($newSchedule['status'] ?? $oldStatus));

            $dateChanged = $oldDate && $newDate && $oldDate !== $newDate;
            $cancelled = $newStatus === 'cancelled' && $oldStatus !== 'cancelled';

            if (! $dateChanged && ! $cancelled) {
                return;
            }

            $labels = $this->sectionLabelsByAssignment($assignments);
            $link = $this->notificationRoute('evaluator.assignments', '/evaluator/assignments');

            foreach ($assignments as $assignment) {
                $label = $labels[$assignment['assignment_id'] ?? ''] ?? 'your assigned section';

                if ($cancelled) {
                    $this->notifications()->create(
                        $assignment['evaluator_user_id'] ?? null,
                        'Assessment schedule cancelled',
                        'The assessment schedule for ' . $label . ' on ' . $this->dateLabel($oldDate) . ' was cancelled.',
                        $link,
                        'schedule_cancelled'
                    );

                    continue;
                }

                $this->notifications()->create(
                    $assignment['evaluator_user_id'] ?? null,
                    'Assessment date updated',
                    'The assessment date for ' . $label . ' changed from ' . $this->dateLabel($oldDate) . ' to ' . $this->dateLabel($newDate) . '.',
                    $link,
                    'schedule_updated'
                );
            }
        } catch (\Throwable $exception) {
            $this->logNotificationFailure('schedule_change', $exception);
        }
    }

    private function notificationRoute(string $routeName, string $fallback, array $parameters = []): string
    {
        try {
            if (\Illuminate\Support\Facades\Route::has($routeName)) {
                return route($routeName, $parameters, false);
            }
        } catch (\Throwable $exception) {
            $this->logNotificationFailure('notification_route', $exception);
        }

        return $fallback;
    }

    private function logNotificationFailure(string $type, \Throwable $exception): void
    {
        \Illuminate\Support\Facades\Log::warning('Notification skipped so schedule flow can continue.', [
            'type' => $type,
            'message' => $exception->getMessage(),
        ]);
    }

    private function sectionLabelsByAssignment(array $assignments): array
    {
        $sectionIds = collect($assignments)->pluck('section_id')->filter()->unique()->values()->all();

        if (empty($sectionIds)) {
            return [];
        }

        $sectionsResponse = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/class_sections', [
                'select' => 'section_id,grade_level_id,section_name',
                'section_id' => 'in.(' . $this->postgrestInList($sectionIds) . ')',
            ]);

        if (! $sectionsResponse->successful()) {
            return [];
        }

        $sections = collect($sectionsResponse->json() ?: [])->keyBy('section_id');
        $gradeIds = $sections->pluck('grade_level_id')->filter()->unique()->values()->all();
        $grades = collect();

        if (! empty($gradeIds)) {
            $gradesResponse = Http::withHeaders($this->supabaseHeaders())
                ->get($this->supabaseUrl() . '/rest/v1/grade_levels', [
                    'select' => 'grade_level_id,grade_number',
                    'grade_level_id' => 'in.(' . $this->postgrestInList($gradeIds) . ')',
                ]);

            if ($gradesResponse->successful()) {
                $grades = collect($gradesResponse->json() ?: [])->keyBy('grade_level_id');
            }
        }

        return collect($assignments)->mapWithKeys(function ($assignment) use ($sections, $grades) {
            $section = $sections->get($assignment['section_id'] ?? null, []);
            $grade = $grades->get($section['grade_level_id'] ?? null, []);
            $gradeLabel = isset($grade['grade_number']) ? 'Grade ' . $grade['grade_number'] : 'Grade';
            $sectionName = $section['section_name'] ?? 'Section';

            return [$assignment['assignment_id'] ?? '' => $gradeLabel . ' - ' . $sectionName];
        })->all();
    }

    private function notifications(): NotificationService
    {
        return app(NotificationService::class);
    }

    private function dateLabel(?string $date): string
    {
        return $date ? date('M d, Y', strtotime($date)) : 'No date';
    }

    private function postgrestInList(array $ids): string
    {
        return collect($ids)
            ->filter()
            ->map(fn ($id) => '"' . str_replace('"', '\"', (string) $id) . '"')
            ->implode(',');
    }

    private function scheduleHasLinkedRecords(string $scheduleId): bool
    {
        $recordsResponse = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/assessment_records', [
                'select' => 'assessment_record_id',
                'schedule_id' => 'eq.' . $scheduleId,
                'limit' => 1,
            ]);

        if ($recordsResponse->successful() && ! empty($recordsResponse->json())) {
            return true;
        }

        $assignmentsResponse = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/assigned_evaluators', [
                'select' => 'assignment_id',
                'schedule_id' => 'eq.' . $scheduleId,
                'limit' => 1,
            ]);

        return $assignmentsResponse->successful() && ! empty($assignmentsResponse->json());
    }

    private function schoolYearLabel(array $year): string
    {
        $start = ! empty($year['start_date']) ? date('Y', strtotime($year['start_date'])) : null;
        $end = ! empty($year['end_date']) ? date('Y', strtotime($year['end_date'])) : null;

        if ($start && $end) {
            return $start . ' - ' . $end;
        }

        return 'School Year';
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
