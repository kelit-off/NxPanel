<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeServiceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:service {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crée un service';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->argument('name');
        $serviceClass = Str::studly($name);
        $path = app_path('Services/' . $serviceClass . '.php');

        // Créer le dossier Services s'il n'existe pas
        if (!File::exists(app_path('Services'))) {
            File::makeDirectory(app_path('Services'), 0755, true);
        }

        // Vérifier si le fichier existe déjà
        if (File::exists($path)) {
            $this->error("Le service {$serviceClass} existe déjà.");
            return;
        }

        // Contenu du fichier de service
        $stub = <<<PHP
        <?php

        namespace App\Services;

        class {$serviceClass}
        {
            // Ajoutez ici votre logique métier
        }
        PHP;

        File::put($path, $stub);

        $this->info("Le service {$serviceClass} a été créé avec succès dans app/Services.");
    }

}
