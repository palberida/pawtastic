<?php

namespace App\Guards;

use Illuminate\Auth\SessionGuard;

class MySessionGuard extends SessionGuard
{
    public function getRecallerName()
    {
        // The original class does:
        // `return 'remember_'.$this->name.'_'.sha1(static::class);`
        // which is the same for every app.

        // Return your own cookie name:
        return 'remember_pawtastic';
    }
}
