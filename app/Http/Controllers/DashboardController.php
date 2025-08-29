<?php

namespace App\Http\Controllers;

use App\Models\DataBase;
use App\Models\Site;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function Dashboard() {
        $site = Site::with('server')->where("user_id", Auth::id())->first();
        $user = User::find(Auth::id());  
        $ip = $site->server->ip ?? "127.0.0.1";
        return Inertia::render('dashboard', [
            'server_ip' => $ip,
            "sftpUser" => $user->username_sftp,
            "sftpPassword" => decrypt($user->password_sftp),
            "siteId" => $site->id,
            "siteUrl" => $site->domain ?? "exemple.com",
            "databaseListe" => DataBase::where('site_id', $site->id)->get()
        ]);
    }
}
