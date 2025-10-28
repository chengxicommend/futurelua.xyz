<?php

namespace App\Http;

use App\Domain\CoreMember;
use App\Domain\User;

class SigninController extends Container
{
    public function post($request, $response)
    {
        $username = $request->getParam('username');
        $password = $request->getParam('password');
        if (empty($username)) {
            return $response->withRedirect($this->router->pathFor('home', [], [
                'error' => "Username required."
            ]));
        } else if (empty($password)) {
            return $response->withRedirect($this->router->pathFor('home', [], [
                'error' => "Password required."
            ]));
        }
        $user = User::where('name', $username)->first();
        if (!$user) {
            return $response->withRedirect($this->router->pathFor('home', [], [
                'error' => "User not found."
            ]));
        } else if (!password_verify($password, $user->pass_hash)) {
            return $response->withRedirect($this->router->pathFor('home', [], [
                'error' => "Incorrect password."
            ]));
        }
        $_SESSION['user'] = $user->id;
        return $response->withRedirect($this->router->pathFor('home'));
    }
}