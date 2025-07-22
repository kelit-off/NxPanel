<?php

namespace App\Http\Controllers;

use App\Models\Server;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminController extends Controller
{
    public function LandingPage() {
        return Inertia::render("admin/landingPage", [
            "clientCount" => User::count(),
            "serverCount" => Server::count()
        ]);
    }
}
