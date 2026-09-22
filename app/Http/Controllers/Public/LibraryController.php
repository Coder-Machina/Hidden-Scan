<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;

class LibraryController extends Controller
{
    public function index()
    {
        return view('public.library');
    }
}