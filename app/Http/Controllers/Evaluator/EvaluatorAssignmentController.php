<?php

namespace App\Http\Controllers\Evaluator;

use App\Helpers\EvaluatorMenuHelper;
use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class EvaluatorAssignmentController extends Controller
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
                ->with('error', 'Please sign in as an evaluator to view your assignments.');
        }

        $schoolYears = $this->fetchSchoolYears();
        $selectedYearId = $request->query('year_id') ?: ($schoolYears[0]['year_id'] ?? null);
        $assignments = $selectedYearId ? $this->fetchEvaluatorAssignments($evaluatorId, $selectedYearId, $schoolYears) : [];

        if ($request->expectsJson() || $request->query('ajax')) {
            return response()->json([
                'success' => true,
                'selectedYearId' => $selectedYearId,
                'schoolYears' => $schoolYears,
                'assignments' => $assignments,
            ]);
        }

        return view('pages.evaluator.evaluator-assignments', [
            'title' => 'My Assignments',
            'menuGroups' => $menuGroups,
            'schoolYears' => $schoolYears,
            'selectedYearId' => $selectedYearId,
            'assignments' => $assignments,
        ]);
    }

    public function confirm(Request $request, string $assignmentId)
    {
        $evaluatorId = $this->currentEvaluatorId();

        if (! $evaluatorId) {
            return response()->json([
                'message' => 'Your user session is missing. Please sign in again.',
            ], 401);
        }

        $assignment = $this->findEvaluatorAssignment($assignmentId, $evaluatorId);

        if (! $assignment) {
            return response()->json([
                'message' => 'Assignment not found, or it is not assigned to your evaluator account.',
            ], 404);
        }

        if (($assignment['confirmation_status'] ?? null) === 'confirmed') {
            return response()->json([
                'message' => 'This assignment is already confirmed.',
                'assignment' => $this->hydrateAssignment($assignment),
            ]);
        }

        $response = Http::withHeaders(array_merge($this->supabaseHeaders(), [
            'Content-Type' => 'application/json',
            'Prefer' => 'return=representation',
        ]))->patch($this->supabaseUrl() . '/rest/v1/assigned_evaluators?assignment_id=eq.' . $assignmentId . '&evaluator_user_id=eq.' . $evaluatorId, [
            'confirmation_status' => 'confirmed',
        ]);

        if (! $response->successful()) {
            report('Failed to confirm evaluator assignment from evaluator side: ' . $response->body());

            return response()->json([
                'message' => 'Failed to confirm the assignment. Please try again or contact your principal.',
            ], 500);
        }

        $updated = $response->json()[0] ?? array_merge($assignment, ['confirmation_status' => 'confirmed']);
        $this->notifyPrincipalAssignmentConfirmed($updated);

        return response()->json([
            'message' => 'Assignment confirmed successfully.',
            'assignment' => $this->hydrateAssignment($updated),
        ]);
    }

    private function notifications(): NotificationService
    {
        return app(NotificationService::class);
    }

    private function notifyPrincipalAssignmentConfirmed(array $assignment): void
    {
        try {
            $this->notifications()->create(
                $assignment['assigned_by'] ?? null,
                'Evaluator confirmed assignment',
                'An evaluator has confirmed an assessment assignment.',
                $this->notificationRoute('principal.assign-evaluator', '/principal/assign-evaluator'),
                'assignment_confirmed'
            );
        } catch (\Throwable $exception) {
            $this->logNotificationFailure('assignment_confirmed', $exception);
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
        \Illuminate\Support\Facades\Log::warning('Notification skipped so evaluator assignment flow can continue.', [
            'type' => $type,
            'message' => $exception->getMessage(),
        ]);
    }

    private function fetchEvaluatorAssignments(string $evaluatorId, string $yearId, array $schoolYears): array
    {
        $response = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/assigned_evaluators', [
                'select' => 'assignment_id,schedule_id,evaluator_user_id,section_id,year_id,quarter_id,assigned_by,assigned_at,confirmation_status,assessment_status,report_status,assessment_date,created_at,updated_at',
                'evaluator_user_id' => 'eq.' . $evaluatorId,
                'year_id' => 'eq.' . $yearId,
                'order' => 'assessment_date.asc',
            ]);

        if (! $response->successful()) {
            report('Failed to fetch evaluator assignments: ' . $response->body());
            return [];
        }

        return $this->hydrateAssignments($response->json(), $schoolYears);
    }

    private function findEvaluatorAssignment(string $assignmentId, string $evaluatorId): ?array
    {
        $response = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/assigned_evaluators', [
                'select' => 'assignment_id,schedule_id,evaluator_user_id,section_id,year_id,quarter_id,assigned_by,assigned_at,confirmation_status,assessment_status,report_status,assessment_date,created_at,updated_at',
                'assignment_id' => 'eq.' . $assignmentId,
                'evaluator_user_id' => 'eq.' . $evaluatorId,
                'limit' => 1,
            ]);

        if (! $response->successful()) {
            report('Failed to find evaluator assignment: ' . $response->body());
            return null;
        }

        return $response->json()[0] ?? null;
    }

    private function hydrateAssignment(array $assignment): array
    {
        return $this->hydrateAssignments([$assignment], $this->fetchSchoolYears())[0] ?? $this->formatAssignment($assignment, [], [], [], [], [], []);
    }

    private function hydrateAssignments(array $assignments, array $schoolYears): array
    {
        if (empty($assignments)) {
            return [];
        }

        $scheduleIds = collect($assignments)->pluck('schedule_id')->filter()->unique()->values()->all();
        $sectionIds = collect($assignments)->pluck('section_id')->filter()->unique()->values()->all();
        $quarterIds = collect($assignments)->pluck('quarter_id')->filter()->unique()->values()->all();
        $principalIds = collect($assignments)->pluck('assigned_by')->filter()->unique()->values()->all();

        $schedules = collect($this->fetchRowsByIds('assessment_schedules', 'schedule_id', $scheduleIds, 'schedule_id,year_id,quarter_id,school_id,assessment_date,status,created_by,created_at,updated_at'))
            ->keyBy('schedule_id')
            ->all();

        $sections = collect($this->fetchRowsByIds('class_sections', 'section_id', $sectionIds, 'section_id,school_id,year_id,grade_level_id,section_name,adviser_name,status,created_at,updated_at'))
            ->keyBy('section_id')
            ->all();

        $gradeLevelIds = collect($sections)->pluck('grade_level_id')->filter()->unique()->values()->all();
        $gradeLevels = collect($this->fetchRowsByIds('grade_levels', 'grade_level_id', $gradeLevelIds, 'grade_level_id,grade_number,school_id,is_active'))
            ->keyBy('grade_level_id')
            ->all();

        $quarters = collect($this->fetchRowsByIds('quarter', 'quarter_id', $quarterIds, 'quarter_id,year_id,quarter_number,quarter_name,start_date,end_date'))
            ->keyBy('quarter_id')
            ->all();

        $profiles = collect($this->fetchRowsByIds('profiles', 'id', $principalIds, 'id,full_name,email,title,position'))
            ->keyBy('id')
            ->all();

        $schoolIds = collect($schedules)->pluck('school_id')
            ->merge(collect($sections)->pluck('school_id'))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $schools = collect($this->fetchRowsByIds('schools', 'school_id', $schoolIds, 'school_id,name,district_id,municipality_id'))
            ->keyBy('school_id')
            ->all();

        return collect($assignments)
            ->map(fn ($assignment) => $this->formatAssignment($assignment, $schedules, $sections, $gradeLevels, $quarters, $schoolYears, $profiles, $schools))
            ->values()
            ->all();
    }

    private function formatAssignment(array $assignment, array $schedules, array $sections, array $gradeLevels, array $quarters, array $schoolYears, array $profiles, array $schools = []): array
    {
        $schedule = $schedules[$assignment['schedule_id'] ?? null] ?? [];
        $section = $sections[$assignment['section_id'] ?? null] ?? [];
        $grade = $gradeLevels[$section['grade_level_id'] ?? null] ?? [];
        $quarter = $quarters[$assignment['quarter_id'] ?? null] ?? [];
        $schoolYear = collect($schoolYears)->firstWhere('year_id', $assignment['year_id'] ?? null) ?: [];
        $principal = $profiles[$assignment['assigned_by'] ?? null] ?? [];
        $school = $schools[$schedule['school_id'] ?? ($section['school_id'] ?? null)] ?? [];
        $assessmentDate = $assignment['assessment_date'] ?? ($schedule['assessment_date'] ?? null);

        return [
            'assignment_id' => $assignment['assignment_id'] ?? null,
            'schedule_id' => $assignment['schedule_id'] ?? null,
            'evaluator_user_id' => $assignment['evaluator_user_id'] ?? null,
            'section_id' => $assignment['section_id'] ?? null,
            'year_id' => $assignment['year_id'] ?? null,
            'quarter_id' => $assignment['quarter_id'] ?? null,
            'assigned_by' => $assignment['assigned_by'] ?? null,
            'assigned_at' => $assignment['assigned_at'] ?? null,
            'confirmation_status' => $assignment['confirmation_status'] ?? 'pending',
            'assessment_status' => $assignment['assessment_status'] ?? 'not_started',
            'report_status' => $assignment['report_status'] ?? 'not_submitted',
            'assessment_date' => $assessmentDate,
            'created_at' => $assignment['created_at'] ?? null,
            'updated_at' => $assignment['updated_at'] ?? null,
            'school_year_label' => $schoolYear['label'] ?? $this->schoolYearLabel($schoolYear),
            'quarter_label' => $this->quarterLabel($quarter),
            'schedule_label' => trim($this->quarterLabel($quarter) . ' - ' . $this->dateLabel($assessmentDate)),
            'school_name' => $school['name'] ?? 'School',
            'section_name' => $section['section_name'] ?? 'Section',
            'grade_level_id' => $section['grade_level_id'] ?? null,
            'grade_label' => isset($grade['grade_number']) ? 'Grade ' . $grade['grade_number'] : 'Grade',
            'adviser_name' => $section['adviser_name'] ?? null,
            'assigned_by_name' => $principal['full_name'] ?? 'School Principal',
            'assigned_by_email' => $principal['email'] ?? null,
        ];
    }

    private function fetchSchoolYears(): array
    {
        $response = Http::withHeaders($this->supabaseHeaders())
            ->get($this->supabaseUrl() . '/rest/v1/school_year', [
                'select' => 'year_id,start_date,end_date,created_at',
                'order' => 'start_date.desc',
            ]);

        if (! $response->successful()) {
            report('Failed to fetch school years for evaluator: ' . $response->body());
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
            report("Failed to fetch {$table} for evaluator assignments: " . $response->body());
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
