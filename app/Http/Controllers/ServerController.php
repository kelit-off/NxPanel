<?php

namespace App\Http\Controllers;

use App\Services\ServeurService;
use Illuminate\Http\Request;

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
        $serverService->create_website($user_id, $webSiteInfo);
    }
}
