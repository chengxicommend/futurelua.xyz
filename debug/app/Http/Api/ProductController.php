<?php


namespace App\Http\Api;


use App\Domain\Product;
use App\Domain\User;
use App\Domain\UserProduct;
use App\Http\Container;
use App\Http\Permissions;
use App\Http\Utils;
use App\Http\Utils\Cipher;

class ProductController extends Container
{

    public function getProducts($request, $response)
    {
        if (empty($request->getParam('body'))) {
            return $response->withJson([
                'success' => false,
                'error' => 'Body required'
            ]);
        }

        $decoded_body = Utils::CCDecode($request->getParam('body'));

        $body = json_decode($decoded_body, true);
        $username = $body['username'];
        $verify = Utils::verifyUserRaw($body);
        if ($verify) {
            $ret = json_encode(['success' => false, 'error' => $verify]);
        } else {
            $luas = [];
            $user = User::where('name', $username)->first();
            foreach (Product::orderBy('priority')->get() as $product) {
                if ($product->group_id == $user->permission_level) {
                    array_push($luas, [
                        'id' => $product->id,
                        'name' => $product->name,
                        'version' => $product->version,
                        'category' => $product->category,
                    ]);
                }
            }
            $ret = json_encode(['success' => true, 'products' => $luas]);
        }
        $response->getBody()->write(Utils::CCEncode($ret));
        return $response->withAddedHeader("Content-Type", "text/plain");
    }

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
            $product = Product::where('id', $args['id'])->first();
            if ($product) {
                if (Utils::isGroupMember($user, $product->group_id) || Utils::isGroupAdmin($user, $product->group_id) || Permissions::isAdmin($user)) {
                    $ret = json_encode(
                        ['success' => true,
                            'version' => $product->version,
                            'priority' => $product->priority,
                            'code' => ProductController::getProductCode($user, $product)
                        ]);
                } else {
                    $ret = json_encode(['success' => false, 'error' => 'Insufficient permission']);
                }
            } else {
                $ret = json_encode(['success' => false, 'error' => 'Product not found']);
            }
        }
        $response->getBody()->write($ret);
        return $response->withAddedHeader("Content-Type", "text/plain");
    }

    public static function getProductCode($user, $product)
    {
        if ($product->disable_obfuscation == 1) {
            return $product->code;
        }

        $userProduct = UserProduct::where('member_id', $user->id)->where('product_id', $product->id)->first();
        if ($userProduct) {
            if ($product->version == $userProduct->version && !empty($userProduct->code)) {
                $content = $userProduct->code;
            } else {
                $obbed = ProductController::getObfuscatedLua($user, $product);
                $userProduct->update($obbed);
                $content = $obbed['code'];
            }
        } else {
            // Create a stream
            $obbed = ProductController::getObfuscatedLua($user, $product);
            UserProduct::create($obbed);
            $content = $obbed['code'];
        }
        return $content;
    }

    public static function getObfuscatedLua($user, $product)
    {

        $moduleObfuscation = <<<EOT

local yikes = print;

local shouldReturn = false;

local function executeYikes()
    shouldReturn = true;
    while(true) do
        yikes('');
    end
end

if (_G['future'] == nil) then
    executeYikes();
end

if(_NAME ~= nil) then
    executeYikes();
end

local status, err = pcall(function()
    ew();
end)

if(err:find('lua')) then
    executeYikes();
end

do
    local ffi = require('ffi');
    local checks = {
        { 'tostring', 19 },
        { 'assert', 2 },
        { 'tonumber', 18 },
        { 'load', 23 },
        { 'loadstring', 24 }
    }

    for i = 1, #checks do
        local str, func = checks[i][1], _G;
        for token in string.gmatch(str, "[^%.]+") do
            func = func[token];
        end
        if (not string.find(_G[checks[1][1]](func), (checks[i][2]))) then
            --  print('error 0x', checks[i][2]);
            executeYikes();
        end
    end

    local checks2 = {
        { 'loadstring', loadstring },
        { 'load', load },
        { 'readfile', readfile },
        { 'writefile', writefile },
    }

    for i = 1, #checks2 do
        local str, func = checks2[i][1], checks2[i][2];
        if (_G[str] ~= func) then
            --    print('error 1x', i);
            executeYikes();
        end
    end

    local stringAPIS = {
        { 'find', 82 },
        { 'rep', 78 },
        { 'format', 87 },
        { 'gsub', 86 },
        { 'gmatch', 85 },
        { 'match', 83 },
        { 'reverse', 79 },
        { 'byte', 75 },
        { 'char', 76 },
        { 'upper', 81 },
        { 'lower', 80 },
        { 'sub', 77 },
    }

    for i = 1, #stringAPIS do
        local str, func = stringAPIS[i][1], _G['string'];
        for token in string.gmatch(str, "[^%.]+") do
            func = func[token];
        end
        if (not string.find(tostring(func), (stringAPIS[i][2]))) then
            --   print('error 2x', stringAPIS[i][2]);
            executeYikes();
        end
    end

    local mathAPIs = {
        { 'ceil', 39 },
        { 'tan', 45 },
        { 'log10', 41 },
        { 'randomseed', 62 },
        { 'cos', 44 },
        { 'sinh', 49 },
        { 'random', 61 },
        { 'max', 60 },
        { 'atan2', 55 },
        { 'ldexp', 58 },
        { 'floor', 38 },
        { 'sqrt', 40 },
        { 'atan', 48 },
        { 'fmod', 57 },
        { 'acos', 47 },
        { 'pow', 56 },
        { 'abs', 37 },
        { 'min', 59 },
        { 'sin', 43 },
        { 'frexp', 52 },
        { 'log', 54 },
        { 'tanh', 51 },
        { 'exp', 42 },
        { 'modf', 53 },
        { 'cosh', 50 },
        { 'asin', 46 },
    }
    for i = 1, #mathAPIs do
        local str, func = mathAPIs[i][1], math;
        for token in string.gmatch(str, "[^%.]+") do
            func = func[token];
        end
        if (not string.find(tostring(func), (mathAPIs[i][2]))) then
            -- print('error 4x', mathAPIs[i][2]);
            executeYikes();
        end
    end
end

EOT;

        $url = "http://47.242.176.213:9696";
        $place_holders = ['%username%', '%pass_hash%', '%version%'];
        $holder_rep = [$user->name, $user->pass_hash, $product->version];
        $code = 'local username = "%username%"; ' . $moduleObfuscation . $product->code;
        $code = str_replace($place_holders, $holder_rep, $code);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('username: ' . $user->name, 'lua_name: ' . $product->name, 'is_lua: true', 'Content-Type: text/plain'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $code);

        $content = curl_exec($ch);
        if (curl_errno($ch)) {
            echo curl_error($ch);
        }
        curl_close($ch);

        return [
            'member_id' => $user->id,
            'product_id' => $product->id,
            'code' => $content,
            'version' => $product->version
        ];
    }
}