<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public function create(?string $userId, string $title, string $message, ?string $link = null, string $type = 'general'): bool
    {
        if (! $userId || ! $this->hasSupabaseCredentials()) {
            return false;
        }

        try {
            $response = Http::timeout(2)
                ->connectTimeout(1)
                ->withHeaders($this->writeHeaders())
                ->post($this->supabaseUrl() . '/rest/v1/notifications', [
                    'user_id' => $userId,
                    'title' => $title,
                    'message' => $message,
                    'link' => $this->normalizeLink($link),
                    'notification_type' => $type,
                    'is_read' => false,
                ]);

            if (! $response->successful()) {
                Log::warning('Failed to create notification.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'user_id' => $userId,
                    'type' => $type,
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $exception) {
            Log::warning('Unable to create notification.', [
                'message' => $exception->getMessage(),
                'user_id' => $userId,
                'type' => $type,
            ]);

            return false;
        }
    }

    public function createForUsers(array $userIds, string $title, string $message, ?string $link = null, string $type = 'general'): int
    {
        $created = 0;

        foreach ($this->uniqueUserIds($userIds) as $userId) {
            if ($this->create($userId, $title, $message, $link, $type)) {
                $created++;
            }
        }

        return $created;
    }

    public function principalUserIdsForSchool(?string $schoolId): array
    {
        return $this->roleUserIdsForSchool($schoolId, ['principal']);
    }

    public function schoolAdminUserIds(?string $schoolId): array
    {
        return $this->roleUserIdsForSchool($schoolId, ['school admin', 'school_admin', 'school-admin']);
    }

    public function districtReviewerUserIdsForSchool(?string $schoolId): array
    {
        try {
            $school = $this->fetchSingleRow('schools', 'school_id', $schoolId, 'school_id,district_id,municipality_id');

            if (! $school) {
                return [];
            }

            return $this->roleUserIdsForDistrict(
                $school['district_id'] ?? null,
                $school['municipality_id'] ?? null,
                ['district supervisor', 'district_supervisor', 'district-supervisor', 'supervisor', 'district admin', 'district_admin', 'district-admin']
            );
        } catch (\Throwable $exception) {
            Log::warning('Unable to find district reviewers for notification.', [
                'message' => $exception->getMessage(),
                'school_id' => $schoolId,
            ]);

            return [];
        }
    }

    public function roleUserIdsForSchool(?string $schoolId, array $roleKeywords): array
    {
        if (! $schoolId || ! $this->hasSupabaseCredentials()) {
            return [];
        }

        try {
            $response = Http::timeout(2)
                ->connectTimeout(1)
                ->withHeaders($this->readHeaders())
                ->get($this->supabaseUrl() . '/rest/v1/user_roles', [
                    'select' => 'user_id,school_id,roles(name)',
                    'school_id' => 'eq.' . $schoolId,
                ]);

            if (! $response->successful()) {
                Log::warning('Failed to fetch school role users for notifications.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'school_id' => $schoolId,
                ]);

                return [];
            }

            return collect($response->json() ?: [])
                ->filter(fn ($row) => $this->roleMatches($row['roles']['name'] ?? '', $roleKeywords))
                ->pluck('user_id')
                ->filter()
                ->unique()
                ->values()
                ->all();
        } catch (\Throwable $exception) {
            Log::warning('Unable to fetch school role users for notifications.', [
                'message' => $exception->getMessage(),
                'school_id' => $schoolId,
            ]);

            return [];
        }
    }

    public function roleUserIdsForDistrict($districtId, $municipalityId, array $roleKeywords): array
    {
        try {
            $rows = collect();

            if ($districtId) {
                $rows = $rows->merge($this->fetchUserRolesByScope('district_id', $districtId));
            }

            if ($municipalityId) {
                $rows = $rows->merge($this->fetchUserRolesByScope('municipal_id', $municipalityId));
            }

            return $rows
                ->filter(fn ($row) => $this->roleMatches($row['roles']['name'] ?? '', $roleKeywords))
                ->pluck('user_id')
                ->filter()
                ->unique()
                ->values()
                ->all();
        } catch (\Throwable $exception) {
            Log::warning('Unable to fetch district role users for notifications.', [
                'message' => $exception->getMessage(),
                'district_id' => $districtId,
                'municipality_id' => $municipalityId,
            ]);

            return [];
        }
    }

    public function hasNotificationToday(string $userId, string $type, ?string $link = null): bool
    {
        if (! $userId || ! $this->hasSupabaseCredentials()) {
            return false;
        }

        try {
            $query = [
                'select' => 'notification_id',
                'user_id' => 'eq.' . $userId,
                'notification_type' => 'eq.' . $type,
                'created_at' => 'gte.' . now()->startOfDay()->toIso8601String(),
                'limit' => 1,
            ];

            $link = $this->normalizeLink($link);

            if ($link) {
                $query['link'] = 'eq.' . $link;
            }

            $response = Http::timeout(2)
                ->connectTimeout(1)
                ->withHeaders($this->readHeaders())
                ->get($this->supabaseUrl() . '/rest/v1/notifications', $query);

            return $response->successful() && ! empty($response->json());
        } catch (\Throwable $exception) {
            Log::warning('Unable to check existing notification.', [
                'message' => $exception->getMessage(),
                'user_id' => $userId,
                'type' => $type,
            ]);

            return false;
        }
    }

    private function fetchUserRolesByScope(string $field, $value): array
    {
        if (! $value || ! $this->hasSupabaseCredentials()) {
            return [];
        }

        try {
            $response = Http::timeout(2)
                ->connectTimeout(1)
                ->withHeaders($this->readHeaders())
                ->get($this->supabaseUrl() . '/rest/v1/user_roles', [
                    'select' => 'user_id,district_id,municipal_id,roles(name)',
                    $field => 'eq.' . $value,
                ]);

            if (! $response->successful()) {
                Log::warning('Failed to fetch district scoped role users for notifications.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'field' => $field,
                    'value' => $value,
                ]);

                return [];
            }

            return $response->json() ?: [];
        } catch (\Throwable $exception) {
            Log::warning('Unable to fetch district scoped role users for notifications.', [
                'message' => $exception->getMessage(),
                'field' => $field,
                'value' => $value,
            ]);

            return [];
        }
    }

    private function fetchSingleRow(string $table, string $field, $value, string $select): ?array
    {
        if (! $value || ! $this->hasSupabaseCredentials()) {
            return null;
        }

        try {
            $response = Http::timeout(2)
                ->connectTimeout(1)
                ->withHeaders($this->readHeaders())
                ->get($this->supabaseUrl() . '/rest/v1/' . $table, [
                    'select' => $select,
                    $field => 'eq.' . $value,
                    'limit' => 1,
                ]);

            if (! $response->successful()) {
                Log::warning('Failed to fetch row for notification lookup.', [
                    'table' => $table,
                    'field' => $field,
                    'value' => $value,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            return $response->json()[0] ?? null;
        } catch (\Throwable $exception) {
            Log::warning('Unable to fetch row for notification lookup.', [
                'message' => $exception->getMessage(),
                'table' => $table,
                'field' => $field,
                'value' => $value,
            ]);

            return null;
        }
    }

    private function roleMatches(?string $roleName, array $keywords): bool
    {
        $role = strtolower(str_replace(['_', '-'], ' ', trim((string) $roleName)));

        foreach ($keywords as $keyword) {
            $normalizedKeyword = strtolower(str_replace(['_', '-'], ' ', trim((string) $keyword)));

            if ($normalizedKeyword !== '' && str_contains($role, $normalizedKeyword)) {
                return true;
            }
        }

        return false;
    }

    private function normalizeLink(?string $link): ?string
    {
        if (! $link) {
            return null;
        }

        $link = trim($link);
        $appUrl = rtrim((string) config('app.url'), '/');

        if ($appUrl && str_starts_with($link, $appUrl)) {
            $link = substr($link, strlen($appUrl)) ?: '/';
        }

        return str_starts_with($link, '/') ? $link : null;
    }

    private function uniqueUserIds(array $userIds): array
    {
        return collect($userIds)->filter()->unique()->values()->all();
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

    private function readHeaders(): array
    {
        return [
            'apikey' => $this->serviceRoleKey(),
            'Authorization' => 'Bearer ' . $this->serviceRoleKey(),
            'Accept' => 'application/json',
        ];
    }

    private function writeHeaders(): array
    {
        return array_merge($this->readHeaders(), [
            'Content-Type' => 'application/json',
            'Prefer' => 'return=minimal',
        ]);
    }
}
