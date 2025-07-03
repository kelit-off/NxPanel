<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminController extends Controller
{
    public function LandingPage() {
        return Inertia::render("admin.LandingPage");
    }
}
