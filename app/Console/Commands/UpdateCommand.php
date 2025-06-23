<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class UpdateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This can update all project';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("→ Git pull");
        $this->runProcess(["git", "pull"]);

        $this->info('→ Composer Install');
        $this->runProcess(['composer', 'install']);

        $this->info('→ NPM Install');
        $this->runProcess(['npm', 'install']);

        $this->info('→ NPM Build');
        $this->runProcess(['npm', 'run', 'build']);

        $this->info('→ Vérification des différences entre .env et .env.example');
        $this->checkEnvDiff();

        $this->info('✅ Mise à jour terminée.');
    }

    private function runProcess($command) {
        $process = new Process($command);
        $process->setTimeout(null);
        $process->run(function($type, $buffer) {
            echo $buffer;
        });

        if(!$process->isSuccessful()) {
            $this->error("Erreur lors de l'éxecution : ". implode(" ", $command));
        }
    }

    protected function checkEnvDiff()
    {
        $envPath = base_path('.env');
        $examplePath = base_path('.env.example');

        if (!file_exists($envPath) || !file_exists($examplePath)) {
            $this->warn("Fichiers .env ou .env.example manquants.");
            return;
        }

        $envVars = $this->parseEnv(file($envPath));
        $exampleVars = $this->parseEnv(file($examplePath));

        $diff = array_diff_key($exampleVars, $envVars);

        if (!empty($diff)) {
            $this->warn("⚠️ Variables présentes dans .env.example mais manquantes dans .env :");
            foreach ($diff as $key => $value) {
                $this->line("  - {$key}");
            }
        } else {
            $this->info("✅ Aucun écart détecté entre .env et .env.example.");
        }
    }

    protected function parseEnv(array $lines): array
    {
        $vars = [];
        foreach ($lines as $line) {
            if (preg_match('/^\s*([\w.-]+)\s*=\s*(.*)\s*$/', $line, $matches)) {
                $vars[$matches[1]] = $matches[2];
            }
        }
        return $vars;
    }
}
