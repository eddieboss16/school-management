<?php

namespace App\Http\Controllers\Parent;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $parent = auth()->user();
        $children = $parent->children;

        return view('parent.dashboard', compact('children'));
    }
}
