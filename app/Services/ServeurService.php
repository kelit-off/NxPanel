<?php

namespace App\Services;

use App\Models\Server;
use phpseclib3\Net\SSH2;

class ServeurService
{   
    private $serverList ;
    private $server_available;
    private $ssh;

    public function __construct($mode = "get")
    {
        if($mode == "get") {
            $this->serverList  = $this->getServersList();
            $this->server_available = $this>-getServerAvaialble($serverList);
        }elseif($mode == "add") {

        }elseif($mode == "remove") {

        } else {

        }
    }

    private function getServersList($param = null) {
        return Server::all();
    }

    private function getServerAvaialble($serverList) {
        foreach($serverList as $server) {
            $prometheus = new PrometheusService($server->ip);

        }
    }

    public function addServer(array $server) {
        Server::create([
            "hostname" => $server["hostname"],
            "username" => $server["username"],
            "password" => $server["password"],
            "ip" => $server["ip"]
        ]);

        $ssh = new SSH2($server['ip']);

        if (!$this->ssh->login($server['username'], $server['password'])) {
            throw new \RuntimeException("SSH login failed for {$server['username']}@{$server['host']}");
        }

        $this->updateSystem();


    }

    private function updateSystem() {
        $upgradable = $this->ssh->run('apt update > /dev/null && apt list --upgradable 2>/dev/null');
        if (str_contains($upgradable, 'upgradable')) {
            echo "Mises à jour disponibles :\n" . $upgradable;
        } else {
            echo "Système déjà à jour.";
        }
    }

    private function installPrometheus() {
        
    }
}