<?php


namespace App\Http\Panel;


use App\Domain\Invitation;
use App\Domain\User;
use App\Http\Container;
use App\Http\Permissions;

class InvitationController extends Container
{

    public function __invoke($request, $response)
    {
        if (!isset($_SESSION['user'])) {
            return $response->withRedirect(
                $this->router->pathFor('home')
            );
        }
        $user = User::where('id', $_SESSION['user'])->first();
        
        $invitations = Invitation::orderBy('created_at', 'DESC')->get();
        if (!Permissions::isAdminOrEmcee($user)) {
            $invitations = Invitation::where('inviter', $user->id)->orderBy('created_at', 'DESC')->get();
        }

        $invitationFinal = [];
        foreach ($invitations as $invitation)  {
            $arr = [
                'code' => $invitation->code,
                'inviter' => User::where('id', $invitation->inviter)->first(),
            ];
            if($invitation->invitee != 0) {
                $arr['invitee'] = User::where('id', $invitation->invitee)->first();
            }
            $arr['permission_level'] = Permissions::getPermission($invitation->permission_level);
            array_push($invitationFinal, $arr);
        }

        return $this->view->render($response, 'panel/invitation/home.twig', [
            'user' => $user,
            'invitations' => $invitationFinal,

        ]);
    }

    public function createInvitationGet($request, $response)
    {
        if (!isset($_SESSION['user'])) {
            return $response->withRedirect(
                $this->router->pathFor('home')
            );
        }

        $user = User::where('id', $_SESSION['user'])->first();
        if($user->banned == 1) {
            return $response->withRedirect($this->router->pathFor('banned'));
        }
        if (!Permissions::isAdminOrEmcee($user)) {
            return $response->withRedirect(
                $this->router->pathFor('panel')
            );
        }
        return $this->view->render($response, 'panel/invitation/create.twig', [
            'user' => $user,
            'permissions' => Permissions::getPermissions()
        ]);
    }

    public function createInvitationPost($request, $response)
    {
        if (!isset($_SESSION['user'])) {
            return $response->withRedirect(
                $this->router->pathFor('home')
            );
        }
        $username = $request->getParam('username');
        if (empty($username)) {
			$user = User::where('id', $_SESSION['user'])->first();
            $username = $user->name;
        }
        $user = User::where("name", $username)->first();
        if ($user) {
            $count = $request->getParam('count');
            if (empty($count)) {
                $count = 1;
            } elseif ($count < 1) {
                $count = 1;
            }
            $perm =  Permissions::getPermissionByValue($request->getParam('permission_level'));
            for ($x = 0; $x < $count; $x++) {
                $this->generateInvite($user, $perm);
            }
        }
        return $response->withRedirect($this->router->pathFor('panel.invitations'));
    }

    public function generateInvite($user, $permission_level)
    {
        $invite_code = $this->random_string(48);
        $invitation = Invitation::where('code', $invite_code)->first();
        if (!$invitation) {
            return Invitation::create([
                'code' => $invite_code,
                'inviter' => $user->id,
                'invitee' => -1,
                'permission_level' => $permission_level
            ]);
        }
        return null;
    }

    function random_string($length)
    {
        $key = '';
        $keys = array_merge(range(0, 9), range('a', 'z'));

        for ($i = 0; $i < $length; $i++) {
            $key .= $keys[array_rand($keys)];
        }

        return $key;
    }
}