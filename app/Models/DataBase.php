<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class DataBase extends Model
{
    protected $fillable = [
        "database_name",
        "site_id",
        "username",
        "password",
        "host"
    ];

    public function getPasswordAttribute($value)
    {
        return Crypt::decrypt($value);
    }
}
