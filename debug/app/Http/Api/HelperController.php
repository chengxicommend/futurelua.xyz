<?php
/**
 * Created by SamHoque
 * Self Explanatory
 */

namespace App\Http\Api;

use App\Domain\Helper;
use App\Domain\User;
use App\Http\Permissions;
use App\Http\Utils;
use App\Http\Utils\Cipher;
use Illuminate\Support\Facades\Crypt;

class HelperController
{
    public function locations($request, $response, $args)
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
            $helpers = [
                [
                    'name' => "Built-in (Legit)",
                    'id' => "builtin_legit",
                    'type' => "remote",
                    'url' => "https://raw.githubusercontent.com/sapphyrus/helper/master/locations/builtin_legit.json",
                    'description' => "Built-in legit grenades",
                    'builtin' => true
                ],
                [
                    'name' => "SoThatWeMayBeFree",
                    'id' => "builtin_sothatwemaybefree",
                    'type' => "remote",
                    'url' => "https://raw.githubusercontent.com/sapphyrus/helper/master/locations/sothatwemaybefree.json",
                    'description' => "Grenades from sothatwemaybefree",
                    'builtin' => true
                ],
                [
                    'name' => "Built-in (Movement)",
                    'id' => "builtin_movement",
                    'type' => "remote",
                    'url' => "https://raw.githubusercontent.com/sapphyrus/helper/master/locations/builtin_movement.json",
                    'description' => "Movement locations for popular maps",
                    'builtin' => true
                ],
                [
                    'name' => "sigma's HvH locations",
                    'id' => "sigma_hvh",
                    'type' => "remote",
                    'url' => "https://pastebin.com/raw/ewHvQ2tD",
                    'description' => "Revolutionizing spread HvH",
                    'builtin' => true
                ]
            ];
            foreach (Helper::all() as $product) {
                if (Utils::isGroupMember($user, $product->group_id) || Utils::isGroupAdmin($user, $product->group_id) || Permissions::isAdmin($user))
                    array_push($helpers, [
                        'name' => $product->name,
                        'id' => 'future_lua_' . $product->id,
                        'type' => "remote",
                        'url' => "https://futurelua.xyz/api/helper/location/" . $product->id,
                        'description' => "helper location from futurelua.xyz",
                        'builtin' => true
                    ]);
            }
            $ret = json_encode(['success' => true, 'locations' => $helpers]);
        }
        $response->getBody()->write(Cipher::encrypt($ret, Cipher::$STATIC_KEY1, Cipher::$STATIC_KEY2));
        return $response->withAddedHeader("Content-Type", "text/plain");
    }

    public function getLocation($request, $response, $args) {
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
            $helper = Helper::where('id', $args['id'])->first();
            if(!$helper || !(Utils::isGroupMember($user, $helper->group_id) || Utils::isGroupAdmin($user, $helper->group_id) || Permissions::isAdmin($user))) {
                $ret = json_encode(['success' => false, 'error' => 'No perms']);
            } else {
                $ret = $helper->code;
            }
        }
        $response->getBody()->write(Cipher::encrypt($ret, Cipher::$STATIC_KEY1, Cipher::$STATIC_KEY2));
        return $response->withAddedHeader("Content-Type", "text/plain");
    }
}