<?php

namespace App\Services;

use App\Models\Server;
use App\Models\Site;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use phpseclib3\Net\SFTP;
use phpseclib3\Net\SSH2;

class ServeurService
{
    private SshService $sshService;
    private SftpService $sftpService;

    public function __construct()
    {
        $this->sshService = new SshService;
        $this->sftpService = new SftpService;
    }

    public function getServersList($param = null)
    {
        return Server::all();
    }

    private function getRandomServer(): ?Server
    {
        return $this->getServersList()->random();
    }


    public function addServer($server)
    {

        $this->sshService->on($server);
        $this->sftpService->on($server);

        $this->updateSystem($server);

        $this->installPrometheus($server);

        $this->installWebService($server);
    }

    private function updateSystem(Server $server)
    {
        $this->sshService->on($server)->run('sudo apt update -y');
    }

    private function installPrometheus(Server $server)
    {
        $ssh = $this->sshService->on($server);
        $sftp = $this->sftpService->on($server);

        // ────────────── Création des utilisateurs ──────────────
        $ssh->run('id prometheus >/dev/null 2>&1 || sudo useradd --no-create-home --shell /bin/false prometheus');
        $ssh->run('id node_exporter >/dev/null 2>&1 || sudo useradd --no-create-home --shell /bin/false node_exporter');

        // ────────────── Création des dossiers ──────────────
        $ssh->run('sudo mkdir -p /etc/prometheus /var/lib/prometheus');
        $ssh->run('sudo chown prometheus:prometheus /etc/prometheus /var/lib/prometheus');

        // ────────────── Installation de Prometheus ──────────────
        $promExists = trim($ssh->run('test -f /usr/local/bin/prometheus && echo "exists"')['output']) === 'exists';
        if (!$promExists) {
            $ssh->run('wget https://github.com/prometheus/prometheus/releases/download/v2.52.0/prometheus-2.52.0.linux-amd64.tar.gz -O /tmp/prometheus.tar.gz');
            $ssh->run('tar -xzf /tmp/prometheus.tar.gz -C /tmp');
            $ssh->run('sudo cp /tmp/prometheus-2.52.0.linux-amd64/prometheus /usr/local/bin/');
            $ssh->run('sudo cp /tmp/prometheus-2.52.0.linux-amd64/promtool /usr/local/bin/');
            $ssh->run('sudo cp -r /tmp/prometheus-2.52.0.linux-amd64/consoles /etc/prometheus/');
            $ssh->run('sudo cp -r /tmp/prometheus-2.52.0.linux-amd64/console_libraries /etc/prometheus/');
            $ssh->run('sudo chown -R prometheus:prometheus /etc/prometheus /usr/local/bin/prometheus /usr/local/bin/promtool');
        }

        // ────────────── Configuration prometheus.yml ──────────────
        $configExists = trim($ssh->run('test -f /etc/prometheus/prometheus.yml && echo "exists"')['output']) === 'exists';
        if (!$configExists) {
            $config = <<<EOT
global:
  scrape_interval: 15s

scrape_configs:
  - job_name: 'node_exporter'
    static_configs:
      - targets: ['localhost:9100']
EOT;
            $sftp->uploadContent($config, '/tmp/prometheus.yml');
            $ssh->run('sudo mv /tmp/prometheus.yml /etc/prometheus/prometheus.yml');
            $ssh->run('sudo chown prometheus:prometheus /etc/prometheus/prometheus.yml');
        }

        // ────────────── Service Prometheus ──────────────
        $serviceExists = trim($ssh->run('test -f /etc/systemd/system/prometheus.service && echo "exists"')['output']) === 'exists';
        if (!$serviceExists) {
            $serviceContent = <<<EOT
[Unit]
Description=Prometheus
Wants=network-online.target
After=network-online.target

[Service]
User=prometheus
Group=prometheus
Type=simple
ExecStart=/usr/local/bin/prometheus \\
    --config.file=/etc/prometheus/prometheus.yml \\
    --storage.tsdb.path=/var/lib/prometheus \\
    --web.console.templates=/etc/prometheus/consoles \\
    --web.console.libraries=/etc/prometheus/console_libraries

[Install]
WantedBy=multi-user.target
EOT;
            $sftp->uploadContent($serviceContent, '/tmp/prometheus.service');
            $ssh->run('sudo mv /tmp/prometheus.service /etc/systemd/system/prometheus.service');
            $ssh->run('sudo systemctl daemon-reload && sudo systemctl enable prometheus && sudo systemctl start prometheus');
        }

        // ────────────── Installation Node Exporter ──────────────
        $nodeExists = trim($ssh->run('test -f /usr/local/bin/node_exporter && echo "exists"')['output']) === 'exists';
        if (!$nodeExists) {
            $ssh->run('wget https://github.com/prometheus/node_exporter/releases/download/v1.8.0/node_exporter-1.8.0.linux-amd64.tar.gz -O /tmp/node_exporter.tar.gz');
            $ssh->run('tar -xzf /tmp/node_exporter.tar.gz -C /tmp');
            $ssh->run('sudo cp /tmp/node_exporter-1.8.0.linux-amd64/node_exporter /usr/local/bin/');
            $ssh->run('sudo chown node_exporter:node_exporter /usr/local/bin/node_exporter');
        }

        // ────────────── Service Node Exporter ──────────────
        $nodeServiceExists = trim($ssh->run('test -f /etc/systemd/system/node_exporter.service && echo "exists"')['output']) === 'exists';
        if (!$nodeServiceExists) {
            $nodeServiceContent = <<<EOT
[Unit]
Description=Node Exporter
Wants=network-online.target
After=network-online.target

[Service]
User=node_exporter
Group=node_exporter
Type=simple
ExecStart=/usr/local/bin/node_exporter

[Install]
WantedBy=multi-user.target
EOT;
            $sftp->uploadContent($nodeServiceContent, '/tmp/node_exporter.service');
            $ssh->run('sudo mv /tmp/node_exporter.service /etc/systemd/system/node_exporter.service');
            $ssh->run('sudo systemctl daemon-reload && sudo systemctl enable node_exporter && sudo systemctl start node_exporter');
        }

        echo "✅ Installation de Prometheus et Node Exporter terminée.";
    }

    private function installWebService(Server $server)
    {
        $ssh = $this->sshService->on($server);

        // Installer Apache, MySQL, PHP
        $ssh->run("sudo DEBIAN_FRONTEND=noninteractive apt install -y apache2 mysql-server php php-cli php-mysql");

        // Activer Apache
        $ssh->run("sudo systemctl enable apache2 && sudo systemctl start apache2");
    }

    /**
     * Cette commande permet de crée un website sur un serveur selectionner
     * 
     * @param array $userId Id de l'utilisateur
     * 
     * @param array $websiteInfo Ensemble des information du premier site crée comme le nom du site internet
     * 
     * Il fera donc l'appel de la commande createAccount pour crée l'utilisateur sur le serveur, a partir de la connexion ssh déja existante, 
     * Ensuite il créera les fichier de base pour le bon fonctionnement du site web comme le dossier public,
     * Il enregistre un site web simple
     */
    public function createWebsite(int $userId, array $websiteInfo)
    {
        $user = User::findOrFail($userId);
        $server = $user->preferred_server_id
            ? Server::find($user->preferred_server_id)
            : $this->getRandomServer();

        $ssh = $this->sshService->on($server);

        // Créer site et utilisateur
        $site = Site::create([
            "name" => $websiteInfo['name'],
            "user_id" => $user->id,
            "server_id" => $server->id,
            "domain" => $websiteInfo["domain"],
            "status" => "install",
            "deployement_type" => "mutualise"
        ]);
        
        $ssh->run("sudo useradd -m -s /usr/sbin/nologin {$user->username_sftp}");
        $ssh->run("echo {$user->name}:" . decrypt($user->password_sftp) . " | sudo chpasswd");
        $ssh->run("sudo mkdir -p /home/{$user->name}/public /home/{$user->name}/logs");
        $ssh->run("sudo chown -R {$user->name}:{$user->name} /home/{$user->name}");
        $ssh->run("sudo chown www-data:www-data /home/{$user->name}/logs");

        $indexContent = "<h1>Bienvenue sur le site de {$user->name}</h1>";
        $ssh->run("echo " . escapeshellarg($indexContent) . " | sudo tee /home/{$user->name}/public/index.html");

        $vhostConfig = <<<CONF
<VirtualHost *:80>
    ServerName {$websiteInfo['domain']}
    DocumentRoot /home/{$user->name}/public/
    <Directory /home/{$user->name}/public/>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    ErrorLog /home/{$user->name}/logs/error.log
    CustomLog /home/{$user->name}/logs/access.log combined
</VirtualHost>
CONF;

        $ssh->run("echo " . escapeshellarg($vhostConfig) . " | sudo tee /etc/apache2/sites-available/{$user->domain}.conf");
        $ssh->run("sudo a2ensite {$user->domain}.conf");
        $ssh->run("sudo systemctl reload apache2");
    }
}
