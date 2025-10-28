<?php

namespace App\Http\Middleware;

use App\Domain\User;
use App\Http\Container;
use App\Http\Permissions;

class Authenticated extends Container
{
	public function __invoke($request, $response, $next)
	{
        if (isset($_SESSION['user'])) {
            $user = User::where('id', $_SESSION['user'])->first();
            if (!$user) {
                unset($_SESSION['user']);
                return $this->response->withRedirect($this->router->pathFor('home'));
            }

          

            if ($user->banned == 1) {
                return $this->response->withRedirect($this->router->pathFor('banned'));
            }

            if(Permissions::getPermission($user) == "N/A") {
                $user->update(['permission_level' => 2]);
            }
        }
		return $next($request, $response);
	}
}