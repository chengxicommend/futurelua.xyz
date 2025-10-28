<?php


namespace App\Http\Api;


use App\Domain\Client;
use App\Domain\User;
use App\Domain\UserClient;
use App\Http\Container;
use App\Http\Panel\DownloadController;
use App\Http\Utils;

class UpdateController extends Container
{
    public function __invoke($request, $response)
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
        $verify = Utils::verifyUserRaw($body, true);
        if ($verify) {
            $ret = json_encode(['success' => false, 'error' => $verify]);
        } else {
            $user = User::where('name', $username)->first();
            $client = Client::first();
            $userClient = UserClient::where('member_id', $user->id)->first();
            $version = $body['version'];
            if (($version != $client->version || $userClient->type != $client->type) && $user && $user->id != 1) {
       
                $content = DownloadController::downloadClient($user);
                $contentEncoded = base64_encode($content);
                $ret = json_encode(['success' => true, 'content' => $contentEncoded]);
            } else {
                $ret = json_encode(['success' => false]);
            }
        }
        $response->getBody()->write(Utils::CCEncode($ret));
        return $response->withAddedHeader("Content-Type", "text/plain");
    }
}