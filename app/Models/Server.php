<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Server extends Model
{
    protected $fillable = [
        "hostname",
        "username",
        "ip",
        "password",
        "status"
    ];

    public function sites() {
        return $this->hasMany(Site::class);
    }
}
