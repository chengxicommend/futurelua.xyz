<?php
/**
 * Created by PhpStorm.
 * User: Rab
 * Date: 2/27/2020
 * Time: 3:39 AM
 */

namespace App\Http;

use App\Domain\Client;
use App\Domain\Group;
use App\Domain\User;
use App\Domain\UserClient;

error_reporting(0);

class Utils
{
   
    public static function CCDecode($str)
    {
        return base64_decode(self::str_rot($str, -6));
    }


    public static function CCEncode($str)
    {
        return self::str_rot(base64_encode($str), 6);
    }


    public static function str_rot($s, $n = 6)
    {
        static $letters = 'AaBbCcDdEeFfGgHhIiJjKkLlMmNnOoPpQqRrSsTtUuVvWwXxYyZz';
        $n = (int)$n % 26;
        if (!$n) return $s;
        if ($n < 0) $n += 26;
        if ($n == 13) return str_rot13($s);
        $rep = substr($letters, $n * 2) . substr($letters, 0, $n * 2);
        return strtr($s, $letters, $rep);
    }

    public static function wh_log($log_msg) {
        try {
            $log_filename = $_SERVER['DOCUMENT_ROOT'] . "/log";
            if (!file_exists($log_filename)) {
                // create directory/folder uploads.
                mkdir($log_filename, 0777, true);
            }
            $log_file_data = $log_filename . '/log_' . date('d-M-Y') . '.log';
            file_put_contents($log_file_data, $log_msg . "\n", FILE_APPEND);
        } catch (\Exception $ignored) {

        }
    }

    public static function verifyUserRaw($body, $bypass_length = false)
    {
        $username = $body['username'];
        $password = $body['password'];
        $HWID = json_encode($body['hwid']);
        $user = User::where('name', $username)->first();
        if (!$user) {
            return 'User ' . $username . '  not found';
        }
        if ($password != $user->pass_hash) {
            return 'Incorrect password';
        }
        if ($user->banned) {
            return 'User is banned';
        }

        $hwid = $user->hwid;

        if (empty($hwid)) {
            $user->update(['hwid' => $HWID]);
        } elseif ($hwid != $HWID) {
            $user->update(['hwid_mismatch' => $HWID]);
            $user->update(['banned' => true]);
            $user->update(['ban_reason' => 'Incorrect HWID']);
            return 'Incorrect HWID';
        }

      

        /*if ($bypass_length === false && array_key_exists('version', $body) && array_key_exists('length', $body)) {
            $version = $body['version'];
            $client = Client::first();
            $userClient = UserClient::where('member_id', $user->id)->first();
            if ($version == $client->version) {
                $content = $userClient->code;
                $length = $body['length'];
                if (strlen($content) != $length) {
                    $user->update(['banned' => true]);
                    if (strlen($content) + 69 == $length) {
                        $user->update(['ban_reason' => 'Anti Spoof']);
                    } else {
                        $user->update(['ban_reason' => 'Anti Temper ' . strlen($content) . ' ' . $length]);
                    }
                    return 'Nice try skiddy 0x2';
                }
            }
        }*/
        return null;
    }

    public static function getGroups($user): array
    {
        $additionalGroups = explode(',', $user->additional_groups);
        $finalGroups = [];
        foreach ($additionalGroups as $group2) {
            $actualGroup = Group::where('id', $group2)->first();
            if ($actualGroup) {
                array_push($finalGroups, $actualGroup);
            }
        }
        return $finalGroups;
    }

    public static function getGroupIDS($user): array
    {
        $additionalGroups = explode(',', $user->additional_groups);
        $finalGroups = [];
        foreach ($additionalGroups as $group2) {
            $actualGroup = Group::where('id', $group2)->first();
            if ($actualGroup) {
                array_push($finalGroups, $group2);
            }
        }
        return $finalGroups;
    }

    public static function getAdminGroups($user): array {
        $userid = is_int($user) ? $user : $user->id;
        $finalGroups = [];
        foreach (Group::all() as $group) {
            if(in_array($userid, explode(',', $group->admins))) {
                array_push($finalGroups, $group);
            }
        }
        return $finalGroups;
    }

    public static function getResellerGroups($user): array {
        $userid = is_int($user) ? $user : $user->id;
        $finalGroups = [];
        foreach (Group::all() as $group) {
            if(in_array($userid, explode(',', $group->resellers))) {
                array_push($finalGroups, $group);
            }
        }
        return $finalGroups;
    }


    public static function getAdminGroupIDs($user): array {
        $userid = is_int($user) ? $user : $user->id;
        $finalGroups = [];
        foreach (Group::all() as $group) {
            if(in_array($userid, explode(',', $group->admins))) {
                array_push($finalGroups, $group->id);
            }
        }
        return $finalGroups;
    }

    public static function isGroupAdmin($user, $group) {
        $group = is_int($group) ? Group::where('id', $group)->first() : $group;
        $admins = explode(',', $group->admins);
        $userid = is_int($user) ? $user : $user->id;
        return in_array($userid, $admins);
    }

    public static function isGroupReseller($user, $group) {
        $group = is_int($group) ? Group::where('id', $group)->first() : $group;
        $resellers = explode(',', $group->resellers);
        $userid = is_int($user) ? $user : $user->id;
        return in_array($userid, $resellers);
    }

    public static function isReseller($user) {
        $userid = is_int($user) ? $user : $user->id;

        foreach (Group::all() as $group) {
            if(in_array($userid, explode(',', $group->resellers))) {
                return true;
            }
        }
        
        return false;
    }

    public static function isGroupMember($user, $group) {
        $group = is_int($group) ? Group::where('id', $group)->first() : $group;
        $user = is_int($user) ? User::where('id', $user)->first() : $user;
        $additionalGroups = explode(',', $user->additional_groups);
        return in_array($group->id, $additionalGroups);
    }

    public static function getCurrentLanguage() {
        $language = "en";
        if (isset($_SESSION['user'])) {
            $user = User::where('id', $_SESSION['user'])->first();
            if ($user->isChinese) {
                $language = "cn";
            }
        } else {
            if (isset($_SESSION['isChinese']) && $_SESSION['isChinese']) {
                $language = "cn";
            }
        }
        return $language;
    }
}
