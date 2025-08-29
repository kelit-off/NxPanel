<?php

namespace App\Http\Controllers;

use App\Models\Server;
use App\Services\ServeurService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class ServerController extends Controller
{   
    /**
     * @param $request Il y a le userId, et de l'ensemble des information sur le site web a mettre en ligne
     */
    public function create_website(Request $request) {
        $validatedData = $request->validate([
            'user_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'domain' => 'required|string|url',
        ]);

        // Séparer les données si nécessaire
        $user_id = $validatedData['user_id'];
        $webSiteInfo = [
            'name' => $validatedData['name'],
            'domain' => $validatedData['domain'],
        ];

        // Appel au service pour crée le site web sur le serveur
        $serverService = new ServeurService();
        $serverService->createWebsite($user_id, $webSiteInfo);
    }

    public function store(Request $request) {
        $request->validate([
            'hostname' => "required|string",
            'username' => "required|string",
            'password' => "required|string",
            'ip' => "required|ip",
            'port' => "required|integer"
        ]);

        $server = Server::create([
            'hostname' => $request->hostname,
            'username' => $request->username,
            'password'  => Crypt::encryptString($request->password),
            'ip'       => $request->ip,
            'port'     => $request->port,
        ]);

        (new ServeurService)->addServer($server);

        return response()->json([

        ], 200);
    }
}
