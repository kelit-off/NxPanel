<?php

namespace Database\Seeders;

use App\Models\Server;
use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SiteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::where('email', 'test@example.com')->first();

        if (!$user) {
            $this->command->error("Utilisateur non trouvé !");
            return;
        }

        $userId = $user->id;

        $server = Server::first(); // ou Server::where(...)->first()

        if (!$server) {
            $this->command->error("Serveur non trouvé !");
            return;
        }

        // Exemple d'info pour le site
        $websiteInfo = [
            'name' => 'Mon premier site',
            'domain' => 'exemple.com'
        ];

        // Créer le site
        $site = Site::create([
            "name" => $websiteInfo['name'],
            "user_id" => $userId,
            "server_id" => $server->id,
            "domain" => $websiteInfo["domain"],
            "status" => "install",
            "deployement_type" => "mutualise"
        ]);
    }
}
