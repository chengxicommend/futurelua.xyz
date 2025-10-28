<?php


namespace App\Http;


use App\Domain\User;

class BanController extends Container
{
    public function __invoke($request, $response, $args)
    {
        $data = [];

        //Append any errors
        if(!empty($request->getParam("error"))) {
            $data['error'] = $request->getParam("error");
        }


        if(isset($_SESSION['user'])) {
            $user = User::where('id', $_SESSION['user'])->first();
            $data['user'] = $user;
            if($user->banned == 0) {
                return $response->withRedirect($this->router->pathFor('panel'));
            }
            if(!$user) {
                $_SESSION['user'] = null;
            }
        }


        return $this->view->render($response, 'ban.twig', $data);
    }
}