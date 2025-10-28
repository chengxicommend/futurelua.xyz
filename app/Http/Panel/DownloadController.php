<?php

namespace App\Http\Panel;

use App\Domain\Client;
use App\Domain\Group;
use App\Domain\User;
use App\Domain\UserClient;
use App\Http\Container;
use App\Domain\Product;
use App\Http\Permissions;
use App\Http\Utils;

error_reporting(0);
@ini_set('display_errors', 0);

ini_set('default_socket_timeout', 900);

class DownloadController extends Container
{

    public static function getObfuscatedLua($user, $client)
    {
        $url = "http://45.125.34.86:9696";
        $place_holders = ['%username%', '%pass_hash%', '%version%', '%uid%', '%inviter%', '%group%'];
        $inviter = User::where('id', $user->referer)->first();

        $holder_rep = [$user->name, $user->pass_hash, $client->version, $user->id, $inviter->name, Permissions::getPermission($user)];
        $code = str_replace($place_holders, $holder_rep, $client->code);
        $opts = array(
            'http' => array(
                'method' => "POST",
                'header' => "Accept-language: en\r\n" .
                    "Content-Type: application/x-www-form-urlencoded\r\n" .
                    "username: $user->name\r\n",
                'content' => $code,
                'timeout' => 1200,
            )
        );
        $context = stream_context_create($opts);
        $content = file_get_contents($url, false, $context);


        return [
            'member_id' => $user->id,
            'code' => $content,
            'version' => $client->version,
            'group_id' => $user->permission_level
        ];
    }

    public static function downloadClient($user)
    {
        $client = Client::first();
        $userClient = UserClient::where('member_id', $user->id)->first();
        if ($userClient) {
            if ($client->version == $userClient->version && !empty($userClient->code) && $client->group_id == $userClient->group_id) {
                $content = $userClient->code;
            } else {
                $obbed = DownloadController::getObfuscatedLua($user, $client);
                $userClient->update($obbed);
                $content = $obbed['code'];
            }
        } else {
            // Create a stream
            $obbed = DownloadController::getObfuscatedLua($user, $client);
            UserClient::create($obbed);
            $content = $obbed['code'];
        }

        return $content;
    }

    public function __invoke($request, $response)
    {
        if (!isset($_SESSION['user'])) {
            return $response->withRedirect(
                $this->router->pathFor('home')
            );
        }


        $user = User::where('id', $_SESSION['user'])->first();
        if (!$user) {
            return $response->withRedirect(
                $this->router->pathFor('panel')
            );
        }

        if($user->banned == 1) {
            return $response->withRedirect($this->router->pathFor('banned'));
        }


        $content = $this->downloadClient($user);



        if (function_exists('mb_strlen')) {
            $size = mb_strlen($content, '8bit');
        } else {
            $size = strlen($content);
        }


        return $response->withHeader('Content-Type', 'application/force-download')
            ->withHeader('Content-Type', 'application/octet-stream')
            ->withHeader('Content-Description', 'File Transfer')
            ->withHeader('Content-Transfer-Encoding', 'binary')
            ->withHeader('Content-Disposition', 'attachment; filename="Future.lua"')
            ->withHeader('Expires', '0')
            ->withHeader('Cache-Control', 'must-revalidate')
            ->withHeader('Pragma', 'public')
            ->withHeader('Content-Length', $size)
            ->write($content);
    }
}