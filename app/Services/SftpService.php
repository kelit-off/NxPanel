<?php

namespace App\Services;

use App\Models\Server;
use Illuminate\Support\Facades\Crypt;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Net\SFTP;

class SftpService
{
    private $server;
    private $sftp;

    public function on(Server $server): self {
        $this->server = $server;
        return $this;
    }

    /**
     * Connexion SFTP via clé privée
     */
    private function connect(): void {
        $this->sftp = new SFTP($this->server->ip, $this->server->port ?? 22);

        if (!$this->sftp->login($this->server->username, Crypt::decryptString($this->server->password))) {
            throw new \RuntimeException("Connexion SFTP échouée pour {$this->server->username}@{$this->server->ip}");
        }
    }

    /**
     * Upload d'un fichier local vers le serveur
     */
    public function upload(string $localPath, string $remotePath): bool {
        $this->connect();
        return $this->sftp->put($remotePath, file_get_contents($localPath));
    }

    /**
     * Téléchargement d'un fichier du serveur vers local
     */
    public function download(string $remotePath, string $localPath): bool {
        $this->connect();
        $data = $this->sftp->get($remotePath);
        if ($data === false) return false;
        return file_put_contents($localPath, $data) !== false;
    }

    /**
     * Liste des fichiers dans un dossier distant
     */
    public function listFiles(string $remoteDir = '.'): array {
        $this->connect();
        return $this->sftp->nlist($remoteDir) ?: [];
    }

    public function uploadContent(string $content, string $remotePath): bool
    {   
        $this->connect();
        if (!$this->sftp) {
            throw new \RuntimeException("Connexion SFTP non initialisée. Appelez d'abord on(\$server).");
        }

        // phpseclib SFTP permet d'écrire directement une string
        $result = $this->sftp->put($remotePath, $content);

        if (!$result) {
            throw new \RuntimeException("Impossible d'uploader le contenu vers {$remotePath}");
        }

        return true;
    }
}