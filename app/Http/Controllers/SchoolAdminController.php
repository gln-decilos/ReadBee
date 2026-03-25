<?php

namespace App\Http\Controllers;

use App\Helpers\SchoolAdminMenuHelper;

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
}
