<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Helpers\DistrictAdminMenuHelper;

class DistrictAdminController extends Controller
{
    public function dashboard()
    {
        $menuGroups = DistrictAdminMenuHelper::getMenuGroups();

        return view(
            'pages.district-admin.district-admin-dashboard',
            compact('menuGroups')
        );
    }

    public function profile()
    {
        $menuGroups = DistrictAdminMenuHelper::getMenuGroups();

        return view(
            'pages.district-admin.district-admin-profile',
            compact('menuGroups')
        );
    }

    public function users()
{
    $menuGroups = DistrictAdminMenuHelper::getMenuGroups();

    $usersResponse = Http::withHeaders([
        'apikey' => env('SUPABASE_ANON_KEY'),
        'Authorization' => 'Bearer ' . env('SUPABASE_ANON_KEY'),
        'Accept' => 'application/json',
    ])->get(
        env('SUPABASE_URL') . '/rest/v1/profiles?select=*&order=full_name.asc'
    );

    $users = $usersResponse->successful()
        ? $usersResponse->json()
        : [];

    return view(
        'pages.district-admin.district-admin-users',
        [
            'menuGroups' => $menuGroups,
            'users' => $users,
            'page' => 1,
            'perPage' => 5,
        ]
    );
}

    public function municipalities()
{
    $menuGroups = DistrictAdminMenuHelper::getMenuGroups();

    $municipalitiesResponse = Http::withHeaders([
        'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
        'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
        'Accept' => 'application/json',
    ])->get(
        env('SUPABASE_URL') . '/rest/v1/municipalities?select=municipality_id,municipal_name,district_id,logo,districts(district_name)&order=municipal_name.asc'
    );

    $municipalities = $municipalitiesResponse->successful()
        ? $municipalitiesResponse->json()
        : [];

    $districts = Http::withHeaders([
        'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
        'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
        'Accept' => 'application/json',
    ])->get(
        env('SUPABASE_URL') . '/rest/v1/districts?select=district_id,district_name&order=district_name.asc'
    )->json();

    return view('pages.district-admin.district-admin-municipality', [
        'title' => 'Municipality Management',
        'menuGroups' => $menuGroups,
        'municipalities' => $municipalities,
        'districts' => $districts,
        'page' => 1,
        'perPage' => 5,
    ]);
}

    public function schools()
    {
        $menuGroups = DistrictAdminMenuHelper::getMenuGroups();

        $schoolsResponse = Http::withHeaders([
            'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
            'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
            'Accept' => 'application/json',
        ])->get(
            env('SUPABASE_URL') . '/rest/v1/schools?select=school_id,name,logo,address,contact,email,district_id,municipality_id,districts(district_name),municipalities(municipal_name)&order=name.asc'
        );

        $schools = $schoolsResponse->successful()
            ? $schoolsResponse->json()
            : [];

        $districts = Http::withHeaders([
            'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
            'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
            'Accept' => 'application/json',
        ])->get(
            env('SUPABASE_URL') . '/rest/v1/districts?select=district_id,district_name&order=district_name.asc'
        )->json();

        $municipalities = Http::withHeaders([
            'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
            'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
            'Accept' => 'application/json',
        ])->get(
            env('SUPABASE_URL') . '/rest/v1/municipalities?select=municipality_id,municipal_name,district_id&order=municipal_name.asc'
        )->json();

        return view('pages.district-admin.district-admin-schools', [
            'title' => 'School Management',
            'menuGroups' => $menuGroups,
            'schools' => $schools,
            'districts' => $districts,
            'municipalities' => $municipalities,
            'page' => 1,
            'perPage' => 5,
        ]);
    }

    private function extractTotalCount($contentRange)
    {
        if ($contentRange && preg_match('/\/(\d+)$/', $contentRange, $matches)) {
            return (int) $matches[1];
        }

        return 0;
    }
}
