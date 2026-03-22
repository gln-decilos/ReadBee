<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DistrictAdminController;
use App\Http\Controllers\DistrictAdmin\DistrictAdminUserController;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\DistrictAdmin\DistrictAdminMunicipalityController;
use App\Http\Controllers\DistrictAdmin\DistrictAdminSchoolController;

// dashboard pages
Route::get('/', function () {
    return view('pages.dashboard.ecommerce', ['title' => 'ReadBee Dashboard']);
})->name('dashboard');

// calender pages
Route::get('/calendar', function () {
    return view('pages.calender', ['title' => 'Calendar']);
})->name('calendar');

// profile pages
Route::get('/profile', function () {
    return view('pages.profile', ['title' => 'Profile']);
})->name('profile');

// form pages
Route::get('/form-elements', function () {
    return view('pages.form.form-elements', ['title' => 'Form Elements']);
})->name('form-elements');

// tables pages
Route::get('/basic-tables', function () {
    return view('pages.tables.basic-tables', ['title' => 'Basic Tables']);
})->name('basic-tables');

// pages

Route::get('/blank', function () {
    return view('pages.blank', ['title' => 'Blank']);
})->name('blank');

// error pages
Route::get('/error-404', function () {
    return view('pages.errors.error-404', ['title' => 'Error 404']);
})->name('error-404');

// chart pages
Route::get('/line-chart', function () {
    return view('pages.chart.line-chart', ['title' => 'Line Chart']);
})->name('line-chart');

Route::get('/bar-chart', function () {
    return view('pages.chart.bar-chart', ['title' => 'Bar Chart']);
})->name('bar-chart');


// authentication pages
Route::get('/signin', function () {
    return view('pages.auth.signin', ['title' => 'Sign In']);
})->name('signin');

Route::get('/signup', function () {
    return view('pages.auth.signup', ['title' => 'Sign Up']);
})->name('signup');

// ui elements pages
Route::get('/alerts', function () {
    return view('pages.ui-elements.alerts', ['title' => 'Alerts']);
})->name('alerts');

Route::get('/avatars', function () {
    return view('pages.ui-elements.avatars', ['title' => 'Avatars']);
})->name('avatars');

Route::get('/badge', function () {
    return view('pages.ui-elements.badges', ['title' => 'Badges']);
})->name('badges');

Route::get('/buttons', function () {
    return view('pages.ui-elements.buttons', ['title' => 'Buttons']);
})->name('buttons');

Route::get('/image', function () {
    return view('pages.ui-elements.images', ['title' => 'Images']);
})->name('images');

Route::get('/videos', function () {
    return view('pages.ui-elements.videos', ['title' => 'Videos']);
})->name('videos');


Route::prefix('district-admin')->group(function () {
    Route::get('dashboard', [DistrictAdminController::class, 'dashboard'])->name('district-admin.district-admin-dashboard');
    Route::get('profile', [DistrictAdminController::class, 'profile'])->name('district-admin.district-admin-profile');
    Route::get('users', [DistrictAdminController::class, 'users'])->name('district-admin.district-admin-users');
    Route::post('users', [DistrictAdminUserController::class, 'store'])->name('district-admin.users.store');
Route::delete('users/delete', [DistrictAdminUserController::class, 'destroy'])->name('district-admin.users.destroy');    Route::get('/users/{userId}/designations', [DistrictAdminUserController::class, 'getUserDesignations'])->name('district-admin.users.designations');
    Route::delete('/designations', [DistrictAdminUserController::class, 'destroyDesignation'])->name('district-admin.designations.destroy');Route::post('/designations', [DistrictAdminUserController::class, 'storeDesignation']);
    Route::get('/roles', function () {
    return Http::withHeaders([
        'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
        'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
        'Accept' => 'application/json'
    ])->get(env('SUPABASE_URL').'/rest/v1/roles?select=id,name')
      ->json();
});

Route::get('/scopes', function (\Illuminate\Http\Request $request) {
    $roleId = $request->query('role_id');

    $query = 'select=id,name,description,scope_type';

    if ($roleId) {
        $query .= "&role_id=eq.$roleId";
    }

    return Http::withHeaders([
        'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
        'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
        'Accept' => 'application/json'
    ])->get(env('SUPABASE_URL')."/rest/v1/scopes?$query")
      ->json();
});

Route::get('/districts', function () {
    return Http::withHeaders([
        'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
        'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
        'Accept' => 'application/json'
    ])->get(env('SUPABASE_URL') . '/rest/v1/districts?select=district_id,district_name&order=district_name.asc')
      ->json();
});

Route::get('/municipalities', function (\Illuminate\Http\Request $request) {
    $districtId = $request->query('district_id');

    $url = env('SUPABASE_URL') . '/rest/v1/municipalities?select=municipality_id,municipal_name,district_id&order=municipal_name.asc';

    if ($districtId) {
        $url .= "&district_id=eq.$districtId";
    }

    return Http::withHeaders([
        'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
        'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
        'Accept' => 'application/json'
    ])->get($url)->json();
});

Route::get('/schools', function (\Illuminate\Http\Request $request) {
    $municipalityId = $request->query('municipality_id');

    $url = env('SUPABASE_URL') . '/rest/v1/schools?select=school_id,name,municipality_id,district_id&order=name.asc';

    if ($municipalityId) {
        $url .= "&municipality_id=eq.$municipalityId";
    }

    return Http::withHeaders([
        'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
        'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
        'Accept' => 'application/json'
    ])->get($url)->json();
});


Route::get('municipality', [DistrictAdminController::class, 'municipalities'])
    ->name('district-admin.municipalities.index');

Route::post('municipality', [DistrictAdminMunicipalityController::class, 'store'])
    ->name('district-admin.municipalities.store');

Route::delete('municipality/delete', [DistrictAdminMunicipalityController::class, 'destroy'])
    ->name('district-admin.municipalities.destroy');

Route::patch('municipality/{id}', [DistrictAdminMunicipalityController::class, 'update'])
    ->name('district-admin.municipalities.update');

Route::get('schools-management', [DistrictAdminController::class, 'schools'])
    ->name('district-admin.schools.index');

Route::post('schools-management', [DistrictAdminSchoolController::class, 'store'])
    ->name('district-admin.schools.store');

Route::delete('schools-management/delete', [DistrictAdminSchoolController::class, 'destroy'])
    ->name('district-admin.schools.destroy');

Route::patch('schools-management/{id}', [DistrictAdminSchoolController::class, 'update'])
    ->name('district-admin.schools.update');

});
