<?php

namespace App\Http\Controllers;

use App\Helpers\EvaluatorMenuHelper;

class EvaluatorController extends Controller
{
    public function dashboard()
    {
        $menuGroups = EvaluatorMenuHelper::getMenuGroups();

        return view('pages.evaluator.evaluator-dashboard', [
            'title' => 'Evaluator Dashboard',
            'menuGroups' => $menuGroups,
        ]);
    }

    public function profile()
    {
        $menuGroups = EvaluatorMenuHelper::getMenuGroups();

        return view('pages.evaluator.evaluator-profile', [
            'title' => 'Evaluator Profile',
            'menuGroups' => $menuGroups,
        ]);
    }
}
