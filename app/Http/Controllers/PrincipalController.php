<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PrincipalController extends Controller
{
    public function dashboard()
    {
        return view('pages.principal.principal-dashboard');
    }

    public function profile()
    {
        return view('pages.principal.principal-profile');
    }

    public function readingMaterials()
    {
        return view('pages.principal.principal-reading-materials');
    }

    public function pupils()
    {
        return view('pages.principal.principal-pupils');
    }
}
