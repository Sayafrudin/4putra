<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Gmail yang boleh login via Google (petinggi 4PUTRA) — dikelola admin. */
class GoogleWhitelist extends Model
{
    protected $fillable = ['email', 'nama', 'role'];
}
