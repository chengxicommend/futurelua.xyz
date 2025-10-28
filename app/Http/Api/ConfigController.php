<?php


namespace App\Http\Api;


use App\Domain\Config;
use App\Domain\User;
use App\Http\Permissions;
use App\Http\Utils;
use App\Http\Utils\Cipher;

class ConfigController
{
    public function getProduct($request, $response, $args)
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
            $user = User::where('name', $username)->first();
            $config = Config::where('id', $args['id'])->first();
            if ($config) {
                if (Utils::isGroupMember($user, $config->group_id) || Utils::isGroupAdmin($user, $config->group_id) || Permissions::isAdmin($user)) {
                    $products = $config->products()->get();
                    $userProducts = [];
                    $groups = Utils::getGroupIDS($user);

                    foreach ($products as $product) {
                        if (in_array($product->group_id, $groups))
                            array_push($userProducts, $product->id);
                    }

                    $ret = json_encode(['success' => true, 'version' => $config->version, 'code' => $config->code, 'luas' => $userProducts]);
                } else {
                    $ret = json_encode(['success' => false, 'error' => 'Insufficient permission']);
                }
            } else {
                $ret = json_encode(['success' => false, 'error' => 'Config not found']);
            }
        }
        $response->getBody()->write($ret);
        return $response->withAddedHeader("Content-Type", "text/plain");
    }
}