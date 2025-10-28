<?php


namespace App\Http;


class Permissions
{
    public static $permissions = [
        1 => "Admin",
        2 => "User",
        3 => "Reseller",
        4 => "Emcee",
    ];

    public static function getPermissionByValue($value): int
    {
        foreach (self::getPermissions() as $key => $permission) {
            if ($permission == $value) {
                return $key;
            }
        }
        return -1;
    }

    public static function getPermissions(): array
    {
        return self::$permissions;
    }

    public static function isAdmin($id): bool
    {

        return self::getPermission($id) == "Admin";
    }

    public static function getPermission($id): string
    {
        if (is_string($id)) {
            $id = intval($id);
        }
        if (!is_int($id)) {
            //ID is an user model
            $id = $id->permission_level;
        }
        $perms = self::getPermissions();
        return array_key_exists($id, $perms) ? self::getPermissions()[$id] : "N/A";
    }

    public static function isUser($id): bool
    {
        return self::getPermission($id) == "User";
    }

    public static function isReseller($id): bool
    {
        return self::getPermission($id) == "Reseller";
    }

    public static function isAdminOrEmcee($id):bool
    {
        if (is_string($id)) {
            $id = intval($id);
        }
        if (!is_int($id)) {
            //ID is an user model
            $id = $id->permission_level;
        }
        if($id ==1|| $id==4){
            return true;
        }
        return false;
    }

    public static function hasEditUserPermission($id):bool
    {
        if (is_string($id)) {
            $id = intval($id);
        }
        if (!is_int($id)) {
            //ID is an user model
            $id = $id->permission_level;
        }
        if($id ==1|| $id==4){
            return true;
        }
        return false;
    }
}