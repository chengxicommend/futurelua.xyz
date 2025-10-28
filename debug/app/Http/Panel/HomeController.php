<?php

namespace App\Http\Panel;

use App\Domain\Group;
use App\Domain\Invitation;
use App\Domain\Product;
use App\Domain\User;
use App\Http\Container;
use App\Http\Permissions;
use App\Http\Utils;

class HomeController extends Container
{



    public function __invoke($request, $response)
    {
        if (!isset($_SESSION['user'])) {
            return $response->withRedirect(
                $this->router->pathFor('home')
            );
        }
        $user = User::where('id', $_SESSION['user'])->first();
        if(!$user) {
            return $response->withRedirect($this->router->pathFor('home'));
        }
        $user->inviter = User::where('id', $user->referer)->first();

        $users = User::all();
        if ($user->permission_level != 1) {
            $users = $users->where('referer', $user->id);
        }

        $invitations = Invitation::where('inviter', $user->id)->where('invitee', -1)->orderBy('created_at', 'DESC')->get();

        $groups = Utils::getGroups($user);

        return $this->view->render($response, 'panel/home.twig', [
            'products' => Product::all(),
            'user' => $user,
            'language' => Utils::getCurrentLanguage(),
            'userCount' => User::all()->count(),
            'users' => $users,
            'invitations' => $invitations,
            'permissionLevel' => Permissions::getPermission($user),
            'groups' => $groups,
            'displayUsers' => $users->count() > 0,
            'isGroupAdmin' => sizeof(Utils::getAdminGroupIDs($user)) > 0,
            'isReseller' => $user->permission_level == 3
        ]);
    }

    public function changeEmail($request, $response) {
        $email = $request->getParam('email');
        $user = User::where('id', $_SESSION['user'])->first();
        $emailUser = User::where('email', $email)->first();
        
        if ($user && filter_var($email, FILTER_VALIDATE_EMAIL) && !$emailUser) {
            $user->update(['email' => $email]);
        }
        return $response->withRedirect($this->router->pathFor('home'));
    }


}