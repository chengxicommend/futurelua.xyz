<?php


namespace App\Http;


use App\Domain\User;

class TOSController extends Container
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

            if(!$user) {
                $_SESSION['user'] = null;
            }
        }


        return $this->view->render($response, 'tos.twig', $data);
    }
}