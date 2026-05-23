<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DistrictAdminController;
use App\Http\Controllers\DistrictAdmin\DistrictAdminUserController;
use App\Http\Controllers\DistrictAdmin\DistrictAdminMunicipalityController;
use App\Http\Controllers\DistrictAdmin\DistrictAdminSchoolController;
use App\Http\Controllers\DistrictAdmin\DistrictAdminSchoolYearController;
use App\Http\Controllers\Auth\SignInController;
use App\Http\Controllers\SchoolAdminController;
use App\Http\Controllers\SchoolAdmin\SchoolAdminUserController;
use App\Http\Controllers\SchoolAdmin\SchoolAdminUserImportController;
use App\Http\Controllers\SchoolAdmin\SchoolAdminClassController;
use App\Http\Controllers\PrincipalController;
use App\Http\Controllers\Principal\PrincipalReadingMaterialController;
use App\Http\Controllers\Principal\PrincipalPupilsController;
use App\Http\Controllers\Principal\PrincipalAssessmentScheduleController;
use App\Http\Controllers\Principal\PrincipalAssignEvaluatorController;
use App\Http\Controllers\EvaluatorController;
use App\Http\Controllers\Evaluator\EvaluatorAssignmentController;
use App\Http\Controllers\Evaluator\EvaluatorReadingMaterialController;
use App\Http\Controllers\Evaluator\EvaluatorPupilsController;

// landing page
Route::get('/', function () {
    return view('pages.landing', ['title' => 'ReadBee']);
})->name('landing');

// dashboard pages
Route::get('/dashboard', function () {
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

Route::post('/signin', [SignInController::class, 'login'])->name('signin.login');

Route::get('/signup', function () {
    return view('pages.auth.signup', ['title' => 'Sign Up']);
})->name('signup');

Route::post('/logout', [SignInController::class, 'logout'])->name('logout');
Route::post('/designation/switch', [SignInController::class, 'switchDesignation'])->name('designation.switch');

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
    Route::get('dashboard', [DistrictAdminController::class, 'dashboard'])
        ->name('district-admin.district-admin-dashboard');

    Route::get('profile', [DistrictAdminController::class, 'profile'])
        ->name('district-admin.district-admin-profile');

    Route::get('users', [DistrictAdminController::class, 'users'])
        ->name('district-admin.district-admin-users');

    Route::post('users', [DistrictAdminUserController::class, 'store'])
        ->name('district-admin.users.store');

    Route::delete('users/delete', [DistrictAdminUserController::class, 'destroy'])
        ->name('district-admin.users.destroy');

    Route::get('users/{userId}/designations', [DistrictAdminUserController::class, 'getUserDesignations'])
        ->name('district-admin.users.designations');

    Route::delete('designations', [DistrictAdminUserController::class, 'destroyDesignation'])
        ->name('district-admin.designations.destroy');

    Route::post('designations', [DistrictAdminUserController::class, 'storeDesignation']);

    Route::get('roles', function () {
        return Http::withHeaders([
            'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
            'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
            'Accept' => 'application/json',
        ])->get(
            env('SUPABASE_URL') . '/rest/v1/roles?select=id,name'
        )->json();
    });

    Route::get('scopes', function (\Illuminate\Http\Request $request) {
        $roleId = $request->query('role_id');

        $query = 'select=id,name,description,scope_type';

        if ($roleId) {
            $query .= "&role_id=eq.$roleId";
        }

        return Http::withHeaders([
            'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
            'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
            'Accept' => 'application/json',
        ])->get(
            env('SUPABASE_URL') . "/rest/v1/scopes?$query"
        )->json();
    });

    Route::get('districts', function () {
        return Http::withHeaders([
            'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
            'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
            'Accept' => 'application/json',
        ])->get(
            env('SUPABASE_URL') . '/rest/v1/districts?select=district_id,district_name&order=district_name.asc'
        )->json();
    });

    Route::get('municipalities', function (\Illuminate\Http\Request $request) {
        $districtId = $request->query('district_id');

        $url = env('SUPABASE_URL') . '/rest/v1/municipalities?select=municipality_id,municipal_name,district_id&order=municipal_name.asc';

        if ($districtId) {
            $url .= "&district_id=eq.$districtId";
        }

        return Http::withHeaders([
            'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
            'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
            'Accept' => 'application/json',
        ])->get($url)->json();
    });

    Route::get('schools', function (\Illuminate\Http\Request $request) {
        $municipalityId = $request->query('municipality_id');

        $url = env('SUPABASE_URL') . '/rest/v1/schools?select=school_id,name,municipality_id,district_id&order=name.asc';

        if ($municipalityId) {
            $url .= "&municipality_id=eq.$municipalityId";
        }

        return Http::withHeaders([
            'apikey' => env('SUPABASE_SERVICE_ROLE_KEY'),
            'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_ROLE_KEY'),
            'Accept' => 'application/json',
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

    Route::get('school-year', [DistrictAdminController::class, 'schoolYears'])
        ->name('district-admin.school-year.index');

    Route::post('school-year', [DistrictAdminSchoolYearController::class, 'store'])
        ->name('district-admin.school-year.store');

    Route::delete('school-year/delete', [DistrictAdminSchoolYearController::class, 'destroy'])
        ->name('district-admin.school-year.destroy');

    Route::patch('school-year/{id}', [DistrictAdminSchoolYearController::class, 'update'])
        ->name('district-admin.school-year.update');

});

Route::prefix('school-admin')->group(function () {
      Route::get('dashboard', [SchoolAdminController::class, 'dashboard'])
          ->name('school-admin.dashboard');

      Route::get('profile', [SchoolAdminController::class, 'profile'])
          ->name('school-admin.profile');
  });


Route::prefix('school-admin')->group(function () {
    Route::get('dashboard', [SchoolAdminController::class, 'dashboard'])
        ->name('school-admin.dashboard');

    Route::get('profile', [SchoolAdminController::class, 'profile'])
        ->name('school-admin.profile');

    Route::get('users', [SchoolAdminController::class, 'users'])
        ->name('school-admin.users.index');

    Route::post('users', [SchoolAdminUserController::class, 'store'])
        ->name('school-admin.users.store');

    Route::delete('users/delete', [SchoolAdminUserController::class, 'destroy'])
        ->name('school-admin.users.destroy');

    Route::get('roles', [SchoolAdminUserController::class, 'roles'])
        ->name('school-admin.roles.index');

    Route::get('dashboard', [SchoolAdminController::class, 'dashboard'])
        ->name('school-admin.dashboard');

    Route::get('profile', [SchoolAdminController::class, 'profile'])
        ->name('school-admin.profile');


    //User management routes
    Route::get('users', [SchoolAdminController::class, 'users'])
        ->name('school-admin.users.index');

    Route::post('users', [SchoolAdminUserController::class, 'store'])
        ->name('school-admin.users.store');

    Route::delete('users/delete', [SchoolAdminUserController::class, 'destroy'])
        ->name('school-admin.users.destroy');

    Route::get('roles', [SchoolAdminUserController::class, 'roles'])
        ->name('school-admin.roles.index');

    Route::get('users/import', [SchoolAdminUserImportController::class, 'index'])
        ->name('school-admin.users.import.index');

    Route::get('users/import/template', [SchoolAdminUserImportController::class, 'downloadTemplate'])
        ->name('school-admin.users.import.template');

    Route::post('users/import/preview', [SchoolAdminUserImportController::class, 'preview'])
        ->name('school-admin.users.import.preview');

    Route::post('users/import/validate', [SchoolAdminUserImportController::class, 'validateRows'])
        ->name('school-admin.users.import.validate');

    Route::post('users/import/commit', [SchoolAdminUserImportController::class, 'commit'])
        ->name('school-admin.users.import.commit');

    //Class management routes
    Route::get('classes', [SchoolAdminClassController::class, 'index'])
        ->name('school-admin.classes.index');

    Route::post('classes', [SchoolAdminClassController::class, 'store'])
        ->name('school-admin.classes.store');

    Route::patch('classes/{sectionId}', [SchoolAdminClassController::class, 'update'])
        ->name('school-admin.classes.update');

    Route::patch('classes/{sectionId}/archive', [SchoolAdminClassController::class, 'archive'])
        ->name('school-admin.classes.archive');

    Route::post('classes/{sectionId}/adviser', [SchoolAdminClassController::class, 'assignAdviser'])
        ->name('school-admin.classes.assign-adviser');

    Route::delete('classes/{sectionId}', [SchoolAdminClassController::class, 'destroy'])
        ->name('school-admin.classes.destroy');





});


Route::prefix('principal')->group(function () {
    Route::get('dashboard', [PrincipalController::class, 'dashboard'])
        ->name('principal.dashboard');

    Route::get('profile', [PrincipalController::class, 'profile'])
        ->name('principal.profile');

    Route::get('reading-materials', [PrincipalController::class, 'readingMaterials'])
        ->name('principal.reading-materials');

    //Reading Materials Management
    Route::post('reading-materials', [PrincipalReadingMaterialController::class, 'store'])
        ->name('principal.reading-materials.store');

    Route::patch('reading-materials/{materialId}/approve', [PrincipalReadingMaterialController::class, 'approve'])
        ->name('principal.reading-materials.approve');

    Route::patch('reading-materials/{materialId}/archive', [PrincipalReadingMaterialController::class, 'archive'])
        ->name('principal.reading-materials.archive');

    Route::get('pupils', [PrincipalController::class, 'pupils'])
        ->name('principal.pupils');

    //Pupils Management
    Route::get('pupils', [PrincipalPupilsController::class, 'index'])
    ->name('principal.pupils');

    Route::post('pupils', [PrincipalPupilsController::class, 'store'])
        ->name('principal.pupils.store');

    Route::patch('pupils/{pupilId}', [PrincipalPupilsController::class, 'update'])
        ->name('principal.pupils.update');

    Route::patch('pupils/{pupilId}/drop', [PrincipalPupilsController::class, 'markDropped'])
        ->name('principal.pupils.drop');

    Route::patch('pupils/{pupilId}/restore', [PrincipalPupilsController::class, 'restore'])
        ->name('principal.pupils.restore');

    Route::patch('pupils/{pupilId}/transfer-section', [PrincipalPupilsController::class, 'transferSection'])
        ->name('principal.pupils.transfer-section');

    Route::delete('pupils/{pupilId}', [PrincipalPupilsController::class, 'delete'])
        ->name('principal.pupils.delete');

    Route::delete('pupils', [PrincipalPupilsController::class, 'bulkDelete'])
        ->name('principal.pupils.bulk-delete');

    // Optional backwards-compatible archive route if your UI still references it.
    Route::patch('pupils/{pupilId}/archive', [PrincipalPupilsController::class, 'archive'])
        ->name('principal.pupils.archive');

    Route::get('pupils/import/template', [PrincipalPupilsController::class, 'downloadImportTemplate'])
    ->name('principal.pupils.import.template');

    Route::post('pupils/import/preview', [PrincipalPupilsController::class, 'previewImport'])
        ->name('principal.pupils.import.preview');

    Route::post('pupils/import/commit', [PrincipalPupilsController::class, 'commitImport'])
        ->name('principal.pupils.import.commit');
        //Assessment Schedule Management

    Route::get('assessment-schedule', [PrincipalAssessmentScheduleController::class, 'index'])
        ->name('principal.assessment-schedule');

    Route::post('assessment-schedule', [PrincipalAssessmentScheduleController::class, 'store'])
        ->name('principal.assessment-schedule.store');

    Route::patch('assessment-schedule/{scheduleId}', [PrincipalAssessmentScheduleController::class, 'update'])
        ->name('principal.assessment-schedule.update');

    Route::delete('assessment-schedule/{scheduleId}', [PrincipalAssessmentScheduleController::class, 'destroy'])
        ->name('principal.assessment-schedule.destroy');

    //Assign Evaluator Management
    Route::get('assign-evaluator', [PrincipalAssignEvaluatorController::class, 'index'])
        ->name('principal.assign-evaluator');

    Route::post('assign-evaluator', [PrincipalAssignEvaluatorController::class, 'store'])
        ->name('principal.assign-evaluator.store');

    Route::post('assign-evaluator/bulk', [PrincipalAssignEvaluatorController::class, 'bulkStore'])
        ->name('principal.assign-evaluator.bulk-store');

    Route::post('assign-evaluator/{assignmentId}/resend', [PrincipalAssignEvaluatorController::class, 'resend'])
        ->name('principal.assign-evaluator.resend');

    Route::delete('assign-evaluator/{assignmentId}', [PrincipalAssignEvaluatorController::class, 'destroy'])
        ->name('principal.assign-evaluator.destroy');

    Route::get('evaluator-assignments/{assignmentId}/confirm', [PrincipalAssignEvaluatorController::class, 'confirm'])
        ->name('principal.assign-evaluator.confirm')
        ->middleware('signed');



});


Route::prefix('evaluator')->group(function () {
    Route::get('dashboard', [EvaluatorController::class, 'dashboard'])
        ->name('evaluator.dashboard');

    Route::get('profile', [EvaluatorController::class, 'profile'])
        ->name('evaluator.profile');

    Route::get('assignments', [EvaluatorAssignmentController::class, 'index'])
        ->name('evaluator.assignments');

    Route::patch('assignments/{assignmentId}/confirm', [EvaluatorAssignmentController::class, 'confirm'])
        ->name('evaluator.assignments.confirm');


    Route::get('pupils', [EvaluatorPupilsController::class, 'index'])
        ->name('evaluator.pupils');

    Route::post('pupils', [EvaluatorPupilsController::class, 'store'])
        ->name('evaluator.pupils.store');

    Route::patch('pupils/{pupilId}', [EvaluatorPupilsController::class, 'update'])
        ->name('evaluator.pupils.update');

    Route::patch('pupils/{pupilId}/drop', [EvaluatorPupilsController::class, 'markDropped'])
        ->name('evaluator.pupils.drop');

    Route::patch('pupils/{pupilId}/restore', [EvaluatorPupilsController::class, 'restore'])
        ->name('evaluator.pupils.restore');

    Route::patch('pupils/{pupilId}/transfer-section', [EvaluatorPupilsController::class, 'transferSection'])
        ->name('evaluator.pupils.transfer-section');

    Route::delete('pupils/{pupilId}', [EvaluatorPupilsController::class, 'delete'])
        ->name('evaluator.pupils.delete');

    Route::delete('pupils', [EvaluatorPupilsController::class, 'bulkDelete'])
        ->name('evaluator.pupils.bulk-delete');

    Route::patch('pupils/{pupilId}/archive', [EvaluatorPupilsController::class, 'archive'])
        ->name('evaluator.pupils.archive');

    Route::get('pupils/import/template', [EvaluatorPupilsController::class, 'downloadImportTemplate'])
        ->name('evaluator.pupils.import.template');

    Route::post('pupils/import/preview', [EvaluatorPupilsController::class, 'previewImport'])
        ->name('evaluator.pupils.import.preview');

    Route::post('pupils/import/commit', [EvaluatorPupilsController::class, 'commitImport'])
        ->name('evaluator.pupils.import.commit');

    Route::get('reading-materials', [EvaluatorReadingMaterialController::class, 'index'])
        ->name('evaluator.reading-materials');

    Route::post('reading-materials', [EvaluatorReadingMaterialController::class, 'store'])
        ->name('evaluator.reading-materials.store');
});

// Backwards-compatible teacher dashboard route for existing teacher redirects.
Route::get('/teacher/dashboard', [EvaluatorController::class, 'dashboard'])
    ->name('teacher.dashboard');

