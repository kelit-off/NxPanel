<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Site extends Model
{
    protected $fillable = [
        "name",
        "user_id",
        "server_id",
        "domain",
        "status",
        "deployement_type"
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function server() {
        return $this->belongsTo(Server::class);
    }
}
