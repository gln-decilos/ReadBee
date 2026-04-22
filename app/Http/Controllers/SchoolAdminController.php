<?php

namespace App\Http\Controllers;

use App\Helpers\SchoolAdminMenuHelper;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class SchoolAdminController extends Controller
{
    public function dashboard()
    {
        $menuGroups = SchoolAdminMenuHelper::getMenuGroups();

        return view('pages.school-admin.school-admin-dashboard', compact('menuGroups'));
    }

    public function profile()
    {
        $menuGroups = SchoolAdminMenuHelper::getMenuGroups();

        return view('pages.school-admin.school-admin-profile', compact('menuGroups'));
    }

    public function users()
    {
        $menuGroups = SchoolAdminMenuHelper::getMenuGroups();

        $activeDesignation = session('active_designation', []);
        $schoolId = $activeDesignation['school_id'] ?? null;

        if (! $schoolId) {
            return redirect()
                ->route('school-admin.dashboard')
                ->with('error', 'No school assigned to your account.');
        }

        $userRolesResponse = Http::withHeaders([
            'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
            'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
            'Accept' => 'application/json',
        ])->get(
            env('SUPABASE_URL') . '/rest/v1/user_roles',
            [
                'school_id' => 'eq.' . $schoolId,
                'select' => 'user_role_id,user_id,assigned_at,school_id,role_id,scope_id,roles(id,name,description),scopes(id,name,scope_type),schools(name)',
                'order' => 'assigned_at.asc',
            ]
        );

        if (! $userRolesResponse->successful()) {
            return view('pages.school-admin.school-admin-users', [
                'menuGroups' => $menuGroups,
                'users' => [],
                'page' => 1,
                'perPage' => 10,
            ])->with('error', 'Failed to load users.');
        }

        $userRoles = $userRolesResponse->json() ?? [];

        $userIds = collect($userRoles)
            ->pluck('user_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $profilesById = collect();

        if (! empty($userIds)) {
            $quotedIds = implode(',', array_map(fn ($id) => '"' . $id . '"', $userIds));

            $profilesResponse = Http::withHeaders([
                'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
                'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
                'Accept' => 'application/json',
            ])->get(
                env('SUPABASE_URL') . '/rest/v1/profiles',
                [
                    'id' => 'in.(' . $quotedIds . ')',
                    'select' => 'id,full_name,email',
                ]
            );

            if ($profilesResponse->successful()) {
                $profilesById = collect($profilesResponse->json())->keyBy('id');
            }
        }

        $users = collect($userRoles)->map(function ($row) use ($profilesById) {
            $profile = $profilesById->get($row['user_id']);

            return [
                'user_role_id' => $row['user_role_id'] ?? null,
                'id' => $row['user_id'] ?? null,
                'full_name' => $profile['full_name'] ?? 'Unknown User',
                'email' => $profile['email'] ?? null,
                'role' => $row['roles']['name'] ?? null,
                'role_id' => $row['roles']['id'] ?? null,
                'scope' => $row['scopes']['name'] ?? null,
                'school_name' => $row['schools']['name'] ?? null,
                'assigned_at' => $row['assigned_at'] ?? null,
            ];
        })->values()->all();

        return view('pages.school-admin.school-admin-users', [
            'menuGroups' => $menuGroups,
            'users' => $users,
            'page' => 1,
            'perPage' => 10,
        ]);
    }
}
