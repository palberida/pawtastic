<?php


use App\Models\User;

if (!function_exists('getUsersWithRole')) {
    function getUsersWithRole($roleId)
    {
        //return [User::with('roles')->first()];
        return User::whereHas('roles', function ($query) use ($roleId) {
            $query->where('roles.id', $roleId);
        })->with('roles')->get();
        
       
    }
}

if (!function_exists('getUserBatch')) {
    function getUserBatch($sellerCode)
    {
        //return null;
        return User::where('seller_code', $sellerCode)->first();
    }
}