<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class StaticPageController extends Controller
{
    /**
     * Display Terms of Service page
     */
    public function terms(): View
    {
        return view('pages.terms');
    }

    /**
     * Display Privacy Policy page
     */
    public function privacy(): View
    {
        return view('pages.privacy');
    }

    /**
     * Display Documentation page
     */
    public function documentation(): View
    {
        return view('pages.documentation');
    }
}
