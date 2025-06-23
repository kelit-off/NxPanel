<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Server_User extends Model
{
    protected $fillable = [
        "user_id",
        "server_id"
    ]
}
