<?php

namespace App\Services;

use App\Models\Server;
use Illuminate\Support\Facades\Crypt;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Net\SSH2;

class SshService
{   
    private $server;
    
    public function on(Server $server): self {
        $this->server = $server;
        return $this;
    }

    public function run(string $cmd): array {
        $ssh = new SSH2($this->server->ip, $this->server->port ?? 22);

        if(!$ssh->login($this->server->username, Crypt::decryptString($this->server->password))) {
            throw new \RuntimeException('SSH login failed');
        }

        $out = $ssh->exec($cmd);
        return ['exit' => $ssh->getExitStatus(), 'output' => $out];
    }
}