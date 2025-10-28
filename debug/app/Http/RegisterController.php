<?php

namespace App\Http;

use App\Domain\CoreMember;
use App\Domain\Invitation;
use App\Domain\User;

class RegisterController extends Container
{

    public function createUser($username, $password, $invite, $email)
    {
        $user = User::where('name', $username)->first();
        if (!$user) {
            User::create([
                'name' => $username,
                'pass_hash' => password_hash($password, PASSWORD_DEFAULT),
                'permission_level' => $invite->permission_level,
                'email' => $email,
                'referer' => $invite->inviter,
                'additional_groups' => ""
            ]);
        }
    }

    public function post($request, $response)
    {
        $username = $request->getParam('username');
        $password = $request->getParam('password');
        $invitation = $request->getParam('invitation');
        $email = $request->getParam('email');

        if (empty($username)) {
            return $response->withRedirect($this->router->pathFor('home', [], [
                'error' => "Username required."
            ]));
        } else if (empty($password)) {
            return $response->withRedirect($this->router->pathFor('home', [], [
                'error' => "Password required."
            ]));
        } else if (empty($invitation)) {
            return $response->withRedirect($this->router->pathFor('home', [], [
                'error' => "Invite code required."
            ]));
        }
        if (empty($email)) {
            return $response->withRedirect($this->router->pathFor('home', [], [
                'error' => "Email required."
            ]));
        }

        $user = User::where('name', $username)->first();
        $invite = Invitation::where('code', $invitation)->first();
        if ($user) {
            return $response->withRedirect($this->router->pathFor('home', [], [
                'error' => "Username already taken."
            ]));
        }
        if (preg_match("/[a-zA-Z0-9]/", $user) == 1) {
            return $response->withRedirect($this->router->pathFor('home', [], [
                'error' => "Username may only contain characters and numbers."
            ]));
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $response->withRedirect($this->router->pathFor('home', [], [
                'error' => "Email is not valid."
            ]));
        }
        $user = User::where('email', $email)->first();
        if ($user) {
            return $response->withRedirect($this->router->pathFor('home', [], [
                'error' => "Email is already taken"
            ]));
        }
        if ($invite) {
            if ($invite->invitee == -1) {
                $this->createUser($username, $password, $invite, $email);
                $user = User::where('name', $username)->first();
                $invite->update(['invitee' => $user->id]);
                $_SESSION['user'] = $user->id;
                return $response->withRedirect(
                    $this->router->pathFor('panel')
                );
            } else {
                return $response->withRedirect($this->router->pathFor('home', [], [
                    'error' => "Invalid already redeemed."
                ]));
            }
        } else {
            return $response->withRedirect($this->router->pathFor('home', [], [
                'error' => "Invalid invite code."
            ]));
        }
    }
}