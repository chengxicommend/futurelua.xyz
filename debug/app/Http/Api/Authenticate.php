<?php


namespace App\Http\Api;


use App\Domain\Client;
use App\Domain\Config;
use App\Domain\Group;
use App\Domain\Product;
use App\Domain\User;
use App\Domain\UserClient;
use App\Http\Container;
use App\Http\Panel\DownloadController;
use App\Http\Permissions;
use App\Http\Utils;
use App\Http\Utils\Cipher;

error_reporting(0);

class Authenticate extends Container
{

    public function __invoke($request, $response)
    {
        if (empty($request->getParam('body'))) {
            return $response->withJson([
                'success' => false,
                'error' => 'Body required'
            ]);
        }

        $decoded_body = Cipher::decrypt($request->getParam('body'), Cipher::$STATIC_KEY1, Cipher::$STATIC_KEY2);

        $body = json_decode($decoded_body, true);
        $username = $body['username'];
        $verify = Utils::verifyUserRaw($body);

        if ($verify) {
            $ret = json_encode(['success' => false, 'error' => $verify]);
        } else {
            //Define shit
            $user = User::where('name', $username)->first();
            $client = Client::first();

            $luas = [];
            foreach (Product::orderBy('priority')->get() as $product) {
                if (Utils::isGroupMember($user, $product->group_id) || Utils::isGroupAdmin($user, $product->group_id) || Permissions::isAdmin($user)) {
                    array_push($luas, [
                        "id" => $product->id,
                        "version" => $product->version,
                        "name" => $product->name,
                        "group_name" => Group::where('id', $product->group_id)->first()->name,
                        "modified" => date_format($product->updated_at,"d:m H:i")
                    ]);
                }
            }

            $configs = [];
            foreach (Config::all() as $config) {
                if (Utils::isGroupMember($user, $config->group_id) || Utils::isGroupAdmin($user, $config->group_id) || Permissions::isAdmin($user)) {
                    array_push($configs, [
                        "id" => $config->id,
                        "version" => $config->version,
                        "name" => $config->name,
                        "group_name" => Group::where('id', $config->group_id)->first()->name,
                        "modified" => date_format($config->updated_at,"d:m H:i")
                    ]);
                }
            }


            $update = "";
            if (($body['version'] != $client->version)  && $user->id != 1) {
               // $update = DownloadController::downloadClient($user);
            }

            $return = ['success' => true, 'products' => ['luas' => $luas, 'configs' => $configs]];
            if (!empty($update)) {
                $return["update"] = $update;
            }
            $ret = json_encode($return);
        }

        $log = ['in' => $request->getParam('body'), 'decrypted' => $decoded_body, 'out' => json_decode($ret)];
        Utils::wh_log(json_encode($log));
        $response->getBody()->write($ret);
        return $response->withAddedHeader("Content-Type", "text/plain");
    }
}
