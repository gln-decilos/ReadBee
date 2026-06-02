<?php

use App\Services\NotificationService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('notifications:send-assessment-reminders', function () {
    $supabaseUrl = rtrim((string) env('SUPABASE_URL'), '/');
    $serviceRoleKey = env('SUPABASE_SERVICE_ROLE_KEY');

    if (! $supabaseUrl || ! $serviceRoleKey) {
        $this->error('Supabase notification credentials are missing.');
        return self::FAILURE;
    }

    $headers = [
        'apikey' => $serviceRoleKey,
        'Authorization' => 'Bearer ' . $serviceRoleKey,
        'Accept' => 'application/json',
    ];

    $notificationService = app(NotificationService::class);
    $dates = [
        now()->toDateString() => 'today',
        now()->addDay()->toDateString() => 'tomorrow',
    ];

    $created = 0;

    foreach ($dates as $date => $whenLabel) {
        $response = Http::withHeaders($headers)
            ->get($supabaseUrl . '/rest/v1/assigned_evaluators', [
                'select' => 'assignment_id,evaluator_user_id,section_id,assessment_date,assessment_status,report_status',
                'assessment_date' => 'eq.' . $date,
                'assessment_status' => 'neq.completed',
            ]);

        if (! $response->successful()) {
            $this->warn('Failed to fetch assignments for ' . $date . ': ' . $response->body());
            continue;
        }

        $assignments = collect($response->json() ?: []);

        if ($assignments->isEmpty()) {
            continue;
        }

        $sectionIds = $assignments->pluck('section_id')->filter()->unique()->values()->all();
        $sections = collect();
        $grades = collect();

        if (! empty($sectionIds)) {
            $sectionsResponse = Http::withHeaders($headers)
                ->get($supabaseUrl . '/rest/v1/class_sections', [
                    'select' => 'section_id,grade_level_id,section_name',
                    'section_id' => 'in.(' . collect($sectionIds)->map(fn ($id) => '"' . str_replace('"', '\\"', (string) $id) . '"')->implode(',') . ')',
                ]);

            if ($sectionsResponse->successful()) {
                $sections = collect($sectionsResponse->json() ?: [])->keyBy('section_id');
                $gradeIds = $sections->pluck('grade_level_id')->filter()->unique()->values()->all();

                if (! empty($gradeIds)) {
                    $gradesResponse = Http::withHeaders($headers)
                        ->get($supabaseUrl . '/rest/v1/grade_levels', [
                            'select' => 'grade_level_id,grade_number',
                            'grade_level_id' => 'in.(' . collect($gradeIds)->map(fn ($id) => '"' . str_replace('"', '\\"', (string) $id) . '"')->implode(',') . ')',
                        ]);

                    if ($gradesResponse->successful()) {
                        $grades = collect($gradesResponse->json() ?: [])->keyBy('grade_level_id');
                    }
                }
            }
        }

        foreach ($assignments as $assignment) {
            $section = $sections->get($assignment['section_id'] ?? null, []);
            $grade = $grades->get($section['grade_level_id'] ?? null, []);
            $gradeLabel = isset($grade['grade_number']) ? 'Grade ' . $grade['grade_number'] : 'Grade';
            $sectionName = $section['section_name'] ?? 'Section';
            $link = route('evaluator.assignments', [], false);

            if ($notificationService->hasNotificationToday($assignment['evaluator_user_id'] ?? '', 'assessment_reminder', $link)) {
                continue;
            }

            if ($notificationService->create(
                $assignment['evaluator_user_id'] ?? null,
                'Assessment reminder',
                'Reminder: your assessment for ' . $gradeLabel . ' - ' . $sectionName . ' is scheduled ' . $whenLabel . ' (' . date('M d, Y', strtotime($date)) . ').',
                $link,
                'assessment_reminder'
            )) {
                $created++;
            }
        }
    }

    $pendingResponse = Http::withHeaders($headers)
        ->get($supabaseUrl . '/rest/v1/assigned_evaluators', [
            'select' => 'assignment_id,evaluator_user_id,assigned_by,section_id,assessment_date,confirmation_status,assessment_status,report_status',
            'assessment_date' => 'lt.' . now()->toDateString(),
            'confirmation_status' => 'eq.confirmed',
        ]);

    if ($pendingResponse->successful()) {
        $pendingAssignments = collect($pendingResponse->json() ?: [])->filter(function ($assignment) {
            return ! in_array(strtolower((string) ($assignment['report_status'] ?? 'not_submitted')), ['submitted', 'reviewed', 'approved'], true);
        });

        if ($pendingAssignments->isNotEmpty()) {
            $sectionIds = $pendingAssignments->pluck('section_id')->filter()->unique()->values()->all();
            $sections = collect();
            $grades = collect();

            if (! empty($sectionIds)) {
                $sectionsResponse = Http::withHeaders($headers)
                    ->get($supabaseUrl . '/rest/v1/class_sections', [
                        'select' => 'section_id,grade_level_id,section_name',
                        'section_id' => 'in.(' . collect($sectionIds)->map(fn ($id) => '"' . str_replace('"', '\"', (string) $id) . '"')->implode(',') . ')',
                    ]);

                if ($sectionsResponse->successful()) {
                    $sections = collect($sectionsResponse->json() ?: [])->keyBy('section_id');
                    $gradeIds = $sections->pluck('grade_level_id')->filter()->unique()->values()->all();

                    if (! empty($gradeIds)) {
                        $gradesResponse = Http::withHeaders($headers)
                            ->get($supabaseUrl . '/rest/v1/grade_levels', [
                                'select' => 'grade_level_id,grade_number',
                                'grade_level_id' => 'in.(' . collect($gradeIds)->map(fn ($id) => '"' . str_replace('"', '\"', (string) $id) . '"')->implode(',') . ')',
                            ]);

                        if ($gradesResponse->successful()) {
                            $grades = collect($gradesResponse->json() ?: [])->keyBy('grade_level_id');
                        }
                    }
                }
            }

            foreach ($pendingAssignments as $assignment) {
                $section = $sections->get($assignment['section_id'] ?? null, []);
                $grade = $grades->get($section['grade_level_id'] ?? null, []);
                $gradeLabel = isset($grade['grade_number']) ? 'Grade ' . $grade['grade_number'] : 'Grade';
                $sectionName = $section['section_name'] ?? 'Section';
                $label = $gradeLabel . ' - ' . $sectionName;
                $evaluatorLink = route('evaluator.reports', [], false);
                $principalLink = route('principal.assign-evaluator', [], false);

                if (! $notificationService->hasNotificationToday($assignment['evaluator_user_id'] ?? '', 'pending_report_reminder', $evaluatorLink)) {
                    if ($notificationService->create(
                        $assignment['evaluator_user_id'] ?? null,
                        'Pending class report',
                        'Your assessment for ' . $label . ' was scheduled on ' . date('M d, Y', strtotime($assignment['assessment_date'] ?? now())) . '. Please submit the class report when ready.',
                        $evaluatorLink,
                        'pending_report_reminder'
                    )) {
                        $created++;
                    }
                }

                if (! $notificationService->hasNotificationToday($assignment['assigned_by'] ?? '', 'pending_evaluator_report', $principalLink)) {
                    if ($notificationService->create(
                        $assignment['assigned_by'] ?? null,
                        'Pending evaluator report',
                        'The evaluator report for ' . $label . ' is still pending after the assessment date.',
                        $principalLink,
                        'pending_evaluator_report'
                    )) {
                        $created++;
                    }
                }
            }
        }
    } else {
        $this->warn('Failed to fetch pending assignment reports: ' . $pendingResponse->body());
    }

    $this->info($created . ' notification' . ($created === 1 ? '' : 's') . ' sent.');

    return self::SUCCESS;
})->purpose('Send evaluator assessment reminders and pending report reminders');

Schedule::command('notifications:send-assessment-reminders')->dailyAt('07:00');
