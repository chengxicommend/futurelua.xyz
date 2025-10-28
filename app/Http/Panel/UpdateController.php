<?php


namespace App\Http\Panel;


use App\Domain\Client;
use App\Domain\Group;
use App\Http\Container;
use App\Domain\User;

class UpdateController extends Container
{

    public function createOrUpdateClient($request)
    {
        $code = $request->getParam('loader_code');
        $client = Client::first();
        if ($client) {
            if($client->code != $code) {
                $client->update([
                    'code' => $code,
                    'version' => round($client->version + 0.1, 1),
                ]);
            }
        } else {
            Client::create([
                'code' => $code,
                'version' => 1,

            ]);
        }
    }

    public function getClientCode($type) {
        $client = Client::where('group_id', $type)->first();
        return $client ? $client->code : '';
    }

    public function __invoke($request, $response)
    {
        $user = User::where('id', $_SESSION['user'])->first();

        if ($user->permission_level != 1) {
            return $response->withRedirect(
                $this->router->pathFor('panel')
            );
        }

        $client = Client::first();

        return $this->view->render($response, 'panel/update/home.twig', [
            'user' => $user,
            'client' => $client
        ]);
    }


    public function post($request, $response)
    {
        $user = User::where('id', $_SESSION['user'])->first();
        if ($user->permission_level == 1) {
            Client::first()->update(['code' => $request->getParam('loader_code')]);
        }
        return $response->withRedirect($this->router->pathFor('panel.update'));
    }
}