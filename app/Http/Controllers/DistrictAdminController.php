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

        // 🔥 Fetch users from Supabase profiles table
        $response = Http::withHeaders([
            'apikey' => env('SUPABASE_ANON_KEY'),
            'Authorization' => 'Bearer ' . env('SUPABASE_ANON_KEY'),
        ])->get(env('SUPABASE_URL') . '/rest/v1/profiles?select=*');

        $users = $response->successful() ? $response->json() : [];

        return view(
            'pages.district-admin.district-admin-users',
            compact('menuGroups', 'users') // 👈 THIS WAS MISSING
        );
    }


    public function municipalities()
{
    $municipalities = Http::withHeaders([
        'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
        'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
        'Accept' => 'application/json',
    ])->get(
        env('SUPABASE_URL') . '/rest/v1/municipalities?select=municipality_id,municipal_name,district_id,logo,districts(district_name)&order=municipal_name.asc'
    )->json();

    $districts = Http::withHeaders([
        'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
        'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
        'Accept' => 'application/json',
    ])->get(env('SUPABASE_URL') . '/rest/v1/districts?select=district_id,district_name&order=district_name.asc')
      ->json();

    return view('pages.district-admin.district-admin-municipality', [
        'title' => 'Municipality Management',
        'municipalities' => $municipalities,
        'districts' => $districts,
    ]);
}

public function schools()
{
    $schools = Http::withHeaders([
        'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
        'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
        'Accept' => 'application/json',
    ])->get(
        env('SUPABASE_URL') . '/rest/v1/schools?select=school_id,name,logo,address,contact,email,district_id,municipality_id,districts(district_name),municipalities(municipal_name)&order=name.asc'
    )->json();

    $districts = Http::withHeaders([
        'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
        'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
        'Accept' => 'application/json',
    ])->get(env('SUPABASE_URL') . '/rest/v1/districts?select=district_id,district_name&order=district_name.asc')
      ->json();

    $municipalities = Http::withHeaders([
        'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
        'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
        'Accept' => 'application/json',
    ])->get(env('SUPABASE_URL') . '/rest/v1/municipalities?select=municipality_id,municipal_name,district_id&order=municipal_name.asc')
      ->json();

    return view('pages.district-admin.district-admin-schools', [
        'title' => 'School Management',
        'schools' => $schools,
        'districts' => $districts,
        'municipalities' => $municipalities,
    ]);
}
}


