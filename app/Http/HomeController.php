<?php


namespace App\Http;


use App\Domain\User;

class HomeController extends Container
{
    public function __invoke($request, $response, $args)
    {
        $data = [];

        //Append any errors
        if(!empty($request->getParam("error"))) {
            $data['error'] = $request->getParam("error");
        }
        $data['language'] = Utils::getCurrentLanguage();

        if(isset($_SESSION['user'])) {
            $user = User::where('id', $_SESSION['user'])->first();

            if(!$user) {
                $_SESSION['user'] = null;
            }

            return $response->withRedirect($this->router->pathFor('panel'));
        }

        return $this->view->render($response, 'home.twig', $data);
    }

    public function logout($request, $response, $args)
    {
        $_SESSION['user'] = null;
        return $response->withRedirect($this->router->pathFor('home'));
    }

    public function switchLanguage($request, $response, $args) {
        if(isset($_SESSION['user'])) {
            $user = User::where('id', $_SESSION['user'])->first();

            $user->update(['isChinese' => $user->isChinese == 1 ? 0 : 1]);
        } else {
            if(isset($_SESSION['isChinese'])) {
                unset($_SESSION['isChinese']);
            } else {
                $_SESSION['isChinese'] = true;
            }
        }
        $refererHeader = $request->getHeader('HTTP_REFERER');
        if ($refererHeader) {
            $referer = array_shift($refererHeader);
            return $response->withRedirect($referer);
        }
        return $response->withRedirect($this->router->pathFor('home'));
    }
}