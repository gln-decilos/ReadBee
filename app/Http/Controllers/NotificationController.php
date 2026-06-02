<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

class NotificationController extends Controller
{
    public function feed(Request $request)
    {
        $userId = session('supabase_user.id');
        $fallbackUrl = $this->defaultNotificationUrl();

        if (! $userId || ! $this->hasSupabaseCredentials()) {
            return response()->json([
                'notifications' => [],
                'unread_count' => 0,
                'fallback_url' => $fallbackUrl,
            ]);
        }

        try {
            $response = Http::timeout(4)
                ->connectTimeout(2)
                ->withHeaders($this->supabaseHeaders())
                ->get($this->supabaseUrl() . '/rest/v1/notifications', [
                    'select' => 'notification_id,user_id,title,message,link,notification_type,is_read,created_at,read_at',
                    'user_id' => 'eq.' . $userId,
                    'order' => 'created_at.desc',
                    'limit' => 10,
                ]);

            if (! $response->successful()) {
                Log::warning('Failed to load notifications.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'user_id' => $userId,
                ]);

                return response()->json([
                    'notifications' => [],
                    'unread_count' => 0,
                    'fallback_url' => $fallbackUrl,
                ]);
            }

            $rows = collect($response->json() ?: []);

            return response()->json([
                'notifications' => $rows->map(fn ($notification) => $this->formatNotification($notification))->values()->all(),
                'unread_count' => $rows->where('is_read', false)->count(),
                'fallback_url' => $fallbackUrl,
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Unable to load notifications.', [
                'message' => $exception->getMessage(),
                'user_id' => $userId,
            ]);

            return response()->json([
                'notifications' => [],
                'unread_count' => 0,
                'fallback_url' => $fallbackUrl,
            ]);
        }
    }

    public function markAsRead(Request $request, string $notificationId)
    {
        $userId = session('supabase_user.id');
        $redirectTo = $this->safeRedirect($request->input('redirect_to'));

        if (! $userId) {
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'redirect_to' => route('signin', [], false)], 401);
            }

            return redirect()->route('signin')
                ->with('error', 'Please sign in to open notifications.');
        }

        $this->updateNotificationReadStatus($notificationId, $userId);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'redirect_to' => $redirectTo]);
        }

        return redirect($redirectTo);
    }

    public function markAllAsRead(Request $request)
    {
        $userId = session('supabase_user.id');

        if (! $userId) {
            if ($request->expectsJson()) {
                return response()->json(['ok' => false], 401);
            }

            return redirect()->route('signin')
                ->with('error', 'Please sign in to open notifications.');
        }

        if (! $this->hasSupabaseCredentials()) {
            if ($request->expectsJson()) {
                return response()->json(['ok' => false]);
            }

            return back();
        }

        try {
            $response = Http::timeout(4)
                ->connectTimeout(2)
                ->withHeaders(array_merge($this->supabaseHeaders(), [
                    'Content-Type' => 'application/json',
                ]))->patch($this->supabaseUrl() . '/rest/v1/notifications?user_id=eq.' . $userId . '&is_read=eq.false', [
                    'is_read' => true,
                    'read_at' => now()->toIso8601String(),
                ]);

            if (! $response->successful()) {
                Log::warning('Failed to mark all notifications as read.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'user_id' => $userId,
                ]);
            }
        } catch (\Throwable $exception) {
            Log::warning('Unable to mark all notifications as read.', [
                'message' => $exception->getMessage(),
                'user_id' => $userId,
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return back();
    }

    private function updateNotificationReadStatus(string $notificationId, string $userId): void
    {
        if (! $this->hasSupabaseCredentials()) {
            return;
        }

        try {
            $response = Http::timeout(4)
                ->connectTimeout(2)
                ->withHeaders(array_merge($this->supabaseHeaders(), [
                    'Content-Type' => 'application/json',
                ]))->patch($this->supabaseUrl() . '/rest/v1/notifications?notification_id=eq.' . $notificationId . '&user_id=eq.' . $userId, [
                    'is_read' => true,
                    'read_at' => now()->toIso8601String(),
                ]);

            if (! $response->successful()) {
                Log::warning('Failed to mark notification as read.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'notification_id' => $notificationId,
                    'user_id' => $userId,
                ]);
            }
        } catch (\Throwable $exception) {
            Log::warning('Unable to mark notification as read.', [
                'message' => $exception->getMessage(),
                'notification_id' => $notificationId,
                'user_id' => $userId,
            ]);
        }
    }

    private function formatNotification(array $notification): array
    {
        $type = $notification['notification_type'] ?? 'general';
        $id = $notification['notification_id'] ?? null;

        return [
            'notification_id' => $id,
            'title' => $notification['title'] ?? 'Notification',
            'message' => $notification['message'] ?? '',
            'link' => $this->safeNotificationLink($notification['link'] ?? null),
            'notification_type' => $type,
            'type_label' => $this->typeLabel($type),
            'is_read' => (bool) ($notification['is_read'] ?? false),
            'created_at' => $notification['created_at'] ?? null,
            'time_ago' => $this->timeAgo($notification['created_at'] ?? null),
            'read_url' => $id && Route::has('notifications.read') ? route('notifications.read', $id, false) : null,
        ];
    }

    private function typeLabel(?string $type): string
    {
        return match ($type) {
            'evaluator_assignment', 'assignment_confirmed', 'assignment_cancelled' => 'Assignment',
            'schedule', 'schedule_updated', 'schedule_cancelled', 'assessment_reminder' => 'Schedule',
            'pending_report_reminder', 'pending_evaluator_report' => 'Reminder',
            'class_report_submitted', 'assignment_reports_completed' => 'Class Report',
            'school_report_submitted' => 'School Report',
            'report' => 'Report',
            default => 'Notification',
        };
    }

    private function timeAgo(?string $createdAt): string
    {
        if (! $createdAt) {
            return 'Just now';
        }

        try {
            return Carbon::parse($createdAt)->diffForHumans();
        } catch (\Throwable) {
            return 'Recently';
        }
    }

    private function safeNotificationLink(?string $link): string
    {
        if (! $link) {
            return $this->defaultNotificationUrl();
        }

        if (str_starts_with($link, '/')) {
            return $link;
        }

        $appUrl = rtrim((string) config('app.url'), '/');

        if ($appUrl && str_starts_with($link, $appUrl)) {
            return substr($link, strlen($appUrl)) ?: $this->defaultNotificationUrl();
        }

        return $this->defaultNotificationUrl();
    }

    private function safeRedirect(?string $redirectTo): string
    {
        if (! $redirectTo) {
            return url()->previous();
        }

        if (str_starts_with($redirectTo, '/')) {
            return $redirectTo;
        }

        $appUrl = rtrim((string) config('app.url'), '/');

        if ($appUrl && str_starts_with($redirectTo, $appUrl)) {
            return substr($redirectTo, strlen($appUrl)) ?: url()->previous();
        }

        return url()->previous();
    }

    private function defaultNotificationUrl(): string
    {
        $roleName = strtolower((string) (session('active_designation.role_name') ?? ''));

        if (str_contains($roleName, 'evaluator') || str_contains($roleName, 'teacher')) {
            return $this->routeIfExists('evaluator.assignments');
        }

        if (str_contains($roleName, 'principal')) {
            return $this->routeIfExists('principal.dashboard');
        }

        if (str_contains($roleName, 'school')) {
            return $this->routeIfExists('school-admin.dashboard');
        }

        if (str_contains($roleName, 'district supervisor')) {
            return $this->routeIfExists('district-supervisor.dashboard');
        }

        if (str_contains($roleName, 'district')) {
            return $this->routeIfExists('district-admin.district-admin-dashboard');
        }

        return '#';
    }

    private function routeIfExists(string $name): string
    {
        return Route::has($name) ? route($name, [], false) : '#';
    }

    private function hasSupabaseCredentials(): bool
    {
        return $this->supabaseUrl() !== '' && $this->serviceRoleKey() !== '';
    }

    private function supabaseUrl(): string
    {
        return rtrim((string) env('SUPABASE_URL'), '/');
    }

    private function serviceRoleKey(): string
    {
        return (string) env('SUPABASE_SERVICE_ROLE_KEY');
    }

    private function supabaseHeaders(): array
    {
        return [
            'apikey' => $this->serviceRoleKey(),
            'Authorization' => 'Bearer ' . $this->serviceRoleKey(),
            'Accept' => 'application/json',
        ];
    }
}
