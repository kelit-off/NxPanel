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
    private $serverList ;
    private $server_available;
    private $ssh;
    private $sftp;

    public function __construct()
    {
        
    }

    public function getServersList($param = null) {
        return Server::all();
    }

    private function getServer()
    {
        $servers = $this->getServersList();

        if ($servers->isEmpty()) {
            return null; // ou gérer l'erreur selon ton besoin
        }

        // Prendre un serveur au hasard
        return $servers->random()->id;
    }


    public function addServer(array $server) {
        Server::create([
            "hostname" => $server["hostname"],
            "username" => $server["username"],
            "password" => Crypt::encrypt($server["password"]),
            "ip" => $server["ip"],
            "port" => $server["port"] ?? 22,
            "status" => "install",
        ]);

        $this->ssh = new SSH2($server['ip']);

        if (!$this->ssh->login($server['username'], $server['password'])) {
            throw new \RuntimeException("SSH login failed for {$server['username']}@{$server['host']}");
        }

        $this->sftp = new SFTP($server['ip']);

        if (!$this->sftp->login($server['username'], $server['password'])) {
            throw new \RuntimeException("SFTP login failed for {$server['username']}@{$server['host']}");
        }

        $this->updateSystem();

        $this->installPrometheus();

        $this->installWebService();
    }

    private function updateSystem() {
        $upgradable = $this->ssh->exec('apt update > /dev/null && apt list --upgradable 2>/dev/null');
        if (str_contains($upgradable, 'upgradable')) {
            echo "Mises à jour disponibles :\n" . $upgradable;
        } else {
            echo "Système déjà à jour.";
        }
    }

    private function installPrometheus() {
        // Création des utilisateurs s'ils n'existent pas
        $this->ssh->exec('id prometheus >/dev/null 2>&1 || sudo useradd --no-create-home --shell /bin/false prometheus');
        $this->ssh->exec('id node_exporter >/dev/null 2>&1 || sudo useradd --no-create-home --shell /bin/false node_exporter');

        // Création des dossiers
        $this->ssh->exec('sudo mkdir -p /etc/prometheus /var/lib/prometheus');
        $this->ssh->exec('sudo chown prometheus:prometheus /etc/prometheus /var/lib/prometheus');

        // Vérifier si Prometheus est déjà installé
        $result = $this->ssh->exec('test -f /usr/local/bin/prometheus && echo "exists"');
        if (trim($result) !== 'exists') {
            // Téléchargement et installation (wget dans /tmp, ok sans sudo)
            $this->ssh->exec('wget https://github.com/prometheus/prometheus/releases/download/v2.52.0/prometheus-2.52.0.linux-amd64.tar.gz -O /tmp/prometheus.tar.gz');
            $this->ssh->exec('tar -xzf /tmp/prometheus.tar.gz -C /tmp');
            // Copier dans /usr/local/bin nécessite sudo
            $this->ssh->exec('sudo cp /tmp/prometheus-2.52.0.linux-amd64/prometheus /usr/local/bin/');
            $this->ssh->exec('sudo cp /tmp/prometheus-2.52.0.linux-amd64/promtool /usr/local/bin/');
            $this->ssh->exec('sudo cp -r /tmp/prometheus-2.52.0.linux-amd64/consoles /etc/prometheus/');
            $this->ssh->exec('sudo cp -r /tmp/prometheus-2.52.0.linux-amd64/console_libraries /etc/prometheus/');
            $this->ssh->exec('sudo chown -R prometheus:prometheus /etc/prometheus /usr/local/bin/prometheus /usr/local/bin/promtool');
        } else {
            echo "Prometheus déjà installé. Skipping binary setup.\n";
        }

        // Configuration Prometheus si non présent
        $fileExists = $this->ssh->exec('test -f /etc/prometheus/prometheus.yml && echo "exists"');
        if (trim($fileExists) !== 'exists') {
            $config = <<<EOT
    global:
    scrape_interval: 15s

    scrape_configs:
    - job_name: 'node_exporter'
        static_configs:
        - targets: ['localhost:9100']
    EOT;
            $this->sftp->put('/tmp/prometheus.yml', $config);
            $this->ssh->exec('sudo mv /tmp/prometheus.yml /etc/prometheus/prometheus.yml');
            $this->ssh->exec('sudo chown prometheus:prometheus /etc/prometheus/prometheus.yml');
        } else {
            echo "Fichier prometheus.yml déjà présent. Skipping config.\n";
        }

        // Service Prometheus
        $serviceExists = $this->ssh->exec('test -f /etc/systemd/system/prometheus.service && echo "exists"');
        if (trim($serviceExists) !== 'exists') {
            $prometheusService = <<<EOT
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
            $this->sftp->put('/tmp/prometheus.service', $prometheusService);
            $this->ssh->exec('sudo mv /tmp/prometheus.service /etc/systemd/system/prometheus.service');
            $this->ssh->exec('sudo systemctl daemon-reload && sudo systemctl enable prometheus && sudo systemctl start prometheus');
        } else {
            echo "Service Prometheus déjà présent. Skipping.\n";
        }

        // Node Exporter
        $nodeExists = $this->ssh->exec('test -f /usr/local/bin/node_exporter && echo "exists"');
        if (trim($nodeExists) !== 'exists') {
            $this->ssh->exec('wget https://github.com/prometheus/node_exporter/releases/download/v1.8.0/node_exporter-1.8.0.linux-amd64.tar.gz -O /tmp/node_exporter.tar.gz');
            $this->ssh->exec('tar -xzf /tmp/node_exporter.tar.gz -C /tmp');
            $this->ssh->exec('sudo cp /tmp/node_exporter-1.8.0.linux-amd64/node_exporter /usr/local/bin/');
            $this->ssh->exec('sudo chown node_exporter:node_exporter /usr/local/bin/node_exporter');
        } else {
            echo "Node Exporter déjà installé.\n";
        }

        // Service Node Exporter
        $nodeService = $this->ssh->exec('test -f /etc/systemd/system/node_exporter.service && echo "exists"');
        if (trim($nodeService) !== 'exists') {
            $nodeExporterService = <<<EOT
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
            $this->sftp->put('/tmp/node_exporter.service', $nodeExporterService);
            $this->ssh->exec('sudo mv /tmp/node_exporter.service /etc/systemd/system/node_exporter.service');
            $this->ssh->exec('sudo systemctl daemon-reload && sudo systemctl enable node_exporter && sudo systemctl start node_exporter');
        } else {
            echo "Service Node Exporter déjà présent. Skipping.\n";
        }

        echo "✅ Installation terminée (avec vérifications).";
    }

    private function installWebService() {
        // Mise à jour avant installation
        $this->ssh->exec("sudo apt update");

        // Installer Apache2, PHP, MySQL (sans interaction utilisateur)
        $this->ssh->exec("sudo DEBIAN_FRONTEND=noninteractive apt install -y apache2 php mysql-server");

        // Activer et démarrer Apache2 (optionnel)
        $this->ssh->exec("sudo systemctl enable apache2");
        $this->ssh->exec("sudo systemctl start apache2");

        echo "✅ Services web installés.";
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
    public function create_website(int $userId, $websiteInfo) {
        if(is_null($userId) || empty($userId)) {
            return ["status" => "error", "messages" => "UserId invalide"];
        }

        $user = User::where("id", $userId);

        if(!is_null($user->preferred_server_id)) {
            $server = Server::where("ip", $user->preferred_server_id);
        } else {
            $server = $this->getServer();
        }

        $site = Site::create([
            "name" => $websiteInfo['name'],
            "user_id" => $user->id,
            "server_id" => $server->id,
            "domain" => $websiteInfo["domain"],
            "status" => "install",
            "deployement_type" => "mutualise"
        ]);

        $this->ssh = new SSH2($server->ip);
        if (!$this->ssh->login($server->username, decrypt($server->password))) {
            throw new \RuntimeException("SSH login failed for {$server['username']}@{$server['host']}");
        }

        $this->ssh->exec("sudo useradd -m -s /usr/sbin/nologin {$user->name}");
        $password_sftp = decrypt($user->password_sftp);
        $this->ssh->exec("echo {$user->name}:{$password_sftp} | sudo chpasswd");

        $this->ssh->exec("sudo mkdir /home/{$user->name}/public");

        $this->ssh->exec("sudo mkdir -p /home/{$user->name}/logs");
        $this->ssh->exec("sudo chown www-data:www-data /home/{$user->name}/logs");
        
        $this->ssh->exec("sudo chown -R {$user->name}:{$user->name} /home/{$user->name}");

        $indexContent = "<h1>Bienvenue sur le site de {$user->name}</h1>";
        $this->ssh->exec("echo " . escapeshellarg($indexContent) . " | sudo tee /home/{$user->name}/public/index.html");
        $this->ssh->exec("sudo chown {$user->name}:{$user->name} /home/{$user->name}/public/index.html");

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

        $this->ssh->exec("echo " . escapeshellarg($vhostConfig) . " | sudo tee /etc/apache2/sites-available/{$user->domain}.conf");
        $this->ssh->exec("sudo a2ensite {$user->domain}.conf");
        $this->ssh->exec("sudo systemctl reload apache2");
    }

    public function __destruct()
    {
        if ($this->ssh instanceof \phpseclib3\Net\SSH2 && $this->ssh->isConnected()) {
            $this->ssh->disconnect();
        }

        if ($this->sftp instanceof \phpseclib3\Net\SFTP && $this->sftp->isConnected()) {
            $this->sftp->disconnect();
        }
    }
}