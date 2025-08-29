<?php

namespace App\Http\Controllers;

use App\Models\DataBase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DataBaseController extends Controller
{   
    // Envoie par l'api
    public function create(Request $request) {
        $request->validate([
            'database_name' => 'required|string',
            'username' => 'required|string',
            'site_id' => 'required|integer',
        ]);
        
        $dbName = preg_replace('/[^A-Za-z0-9_]/', '', $request->database_name);
        $dbUser = preg_replace('/[^A-Za-z0-9_]/', '', $request->username);
        $dbPass = Str::random(12);
        $dbHost = env('WEB_DB_HOST', '127.0.0.1');

        try {
            // Création dans MySQL
            DB::connection('web')->statement("CREATE DATABASE `$dbName`");
            DB::connection('web')->statement("CREATE USER IF NOT EXISTS '$dbUser'@'%' IDENTIFIED BY '$dbPass'");
            DB::connection('web')->statement("GRANT ALL PRIVILEGES ON `$dbName`.* TO '$dbUser'@'%'");
            DB::connection('web')->statement("FLUSH PRIVILEGES");

            // Sauvegarde dans notre table
            $dataBase = DataBase::create([
                'database_name' => $dbName,
                'username' => $dbUser,
                'site_id' => $request->site_id,
                'password' => Crypt::encrypt($dbPass),
                'host' => $dbHost
            ]);

            return response()->json([
                'success' => true,
                'database' => $dataBase
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
