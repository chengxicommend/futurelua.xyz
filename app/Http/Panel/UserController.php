<?php


namespace App\Http\Panel;


use App\Domain\Group;
use App\Domain\User;
use App\Http\Container;
use App\Http\Permissions;

class UserController extends Container
{

    public function refreshGroups($editUser)
    {
        if(!$editUser) return;
        $additionalGroups = explode(',', $editUser->additional_groups);
        $finalGroups = [];
        foreach ($additionalGroups as $group) {
            $actualGroup = Group::where('id', $group)->first();
            if ($actualGroup) {
                array_push($finalGroups, $group);
            }
        }
        $editUser->update([
            'additional_groups' => implode(',', $finalGroups)
        ]);
    }

    public function __invoke($request, $response)
    {
        if (!isset($_SESSION['user'])) {
            return $response->withRedirect(
                $this->router->pathFor('home')
            );
        }

        $user = User::where('id', $_SESSION['user'])->first();
        $users = User::all();
        if (!Permissions::hasEditUserPermission($user)) {
            $users = $users->where('referer', $user->id);
        }

        if ($users->count() <= 0) {
            return $response->withRedirect($this->router->pathFor('home'));
        }

        $finalUsers = [];
        foreach ($users as $u) {
            array_push($finalUsers, [
                'id' => $u->id,
                'name' => $u->name,
                'permission_level' => Permissions::getPermission($u->permission_level),
                'referer' => User::where('id', $u->referer)->first()
            ]);
        }
        return $this->view->render($response, 'panel/user/home.twig', [
            'user' => $user,
            'users' => $finalUsers,
        ]);
    }

    public function editUserGet($request, $response, $args)
    {
        if (!isset($_SESSION['user'])) {
            return $response->withRedirect(
                $this->router->pathFor('home')
            );
        }
        $user = User::where('id', $_SESSION['user'])->first();
        $this->refreshGroups(User::where('id', $args['id'])->first());
        $editUser = User::where('id', $args['id'])->first();

        $editUser->hwid_decoded = json_decode($editUser->hwid, TRUE);
        $editUser->hwid_mismatch_decoded = json_decode($editUser->hwid_mismatch, TRUE);
        if ( !Permissions::hasEditUserPermission($user) && $editUser->referer != $user->id) {
            return $response->withRedirect($this->router->pathFor('panel'));
        }

        $additionalGroups = explode(',', $editUser->additional_groups);
        $additionalGroupsObj = [];
        foreach ($additionalGroups as $additionalGroup) {
            array_push($additionalGroupsObj, Group::where('id', $additionalGroup)->first());
        }
        $groups = Group::all();
        $notInGroups = [];
        foreach ($groups as $group) {
            if (!in_array($group->id, $additionalGroups)) {
                array_push($notInGroups, $group);
            }
        }
        return $this->view->render($response, 'panel/user/edit.twig', [
            'user' => $editUser,
            'panelUser' => $user,
            'permissions' => Permissions::getPermissions(),
            'additionalGroups' => $additionalGroupsObj,
            'availGroups' => $notInGroups
        ]);
    }

    public function editUserPost($request, $response, $args)
    {


        if (!isset($_SESSION['user'])) {
            return $response->withRedirect(
                $this->router->pathFor('home')
            );
        }
        $user = User::where('id', $_SESSION['user'])->first();

     

        $editUser = User::where('id', $args['id'])->first();

        if (!Permissions::hasEditUserPermission($user) && $editUser->referer != $user->id) {
	        return $response->withRedirect(
	            $this->router->pathFor('panel')
	        );
        }


        if ($editUser) {
            $permission_level = $request->getParam('permission_level');
            $arr = [
                'banned' => $request->getParam('banned') == "on"
            ];

            if ( Permissions::hasEditUserPermission($user) ||$user->permission_level == 3 ) {
                $arr['permission_level'] = Permissions::getPermissionByValue($permission_level);
                $name = $request->getParam('username');

                if (!empty($name)) {
                    $temp = User::where('name', $name)->first();
                    if (!$temp) {
                        $arr['name'] = $name;
                    }
                }

                if (!empty($request->getParam('password'))) {
                    $arr['pass_hash'] = password_hash($request->getParam('password'), PASSWORD_DEFAULT);
                }
            }
            $editUser->update($arr);
        }
        return $response->withRedirect($this->router->pathFor('panel.users'));
    }

    public function resetHWID($request, $response, $args)
    {
        if (!isset($_SESSION['user'])) {
            return $response->withRedirect(
                $this->router->pathFor('home')
            );
        }
        $user = User::where('id', $_SESSION['user'])->first();

        $editUser = User::where('id', $args['id'])->first();

        if ($user->permission_level != 1 && $editUser->referer != $user->id) {
            return $response->withRedirect(
                $this->router->pathFor('panel')
            );
        }

        if ($editUser) {
            $editUser->update([
                'hwid' => '',
                'hwid_mismatch' => ''
            ]);
        }
        return $response->withRedirect($this->router->pathFor('panel.user.edit', [
            "id" => $args['id']
        ]));
    }

    public function addGroup($request, $response, $args)
    {
        if (!isset($_SESSION['user'])) {
            return $response->withRedirect(
                $this->router->pathFor('home')
            );
        }
        $user = User::where('id', $_SESSION['user'])->first();

        $editUser = User::where('id', $args['id'])->first();

        if ($user->permission_level != 1 && $editUser->referer != $user->id) {
            return $response->withRedirect($this->router->pathFor('panel'));
        }

        $additionalGroups = explode(',', $editUser->additional_groups);
        $groupName = $request->getParam('group_name');
        if (!in_array($groupName, $additionalGroups)) {
            array_push($additionalGroups, $groupName);
        }
        $finalGroups = [];
        foreach ($additionalGroups as $group) {
            $actualGroup = Group::where('id', $group)->first();
            if ($actualGroup) {
                array_push($finalGroups, $group);
            }
        }
        $editUser->update([
            'additional_groups' => implode(',', $finalGroups)
        ]);
        return $response->withRedirect($this->router->pathFor('panel.user.edit', [
            "id" => $args['id']
        ]));
    }

    public function removeGroup($request, $response, $args)
    {
        if (!isset($_SESSION['user'])) {
            return $response->withRedirect(
                $this->router->pathFor('home')
            );
        }
        $user = User::where('id', $_SESSION['user'])->first();

        $editUser = User::where('id', $args['userid'])->first();

        if (!$editUser) {
            return $response->withRedirect($this->router->pathFor('panel'));
        }
        if ($user->permission_level != 1 && $editUser->referer != $user->id) {
            return $response->withRedirect($this->router->pathFor('panel'));
        }
        $additionalGroups = explode(',', $editUser->additional_groups);
        $finalGroups = [];
        foreach ($additionalGroups as $group) {
            $actualGroup = Group::where('id', $group)->first();

            if ($actualGroup && intval($actualGroup->id) != intval($args['id'])) {
                array_push($finalGroups, $group);
            }
        }
        $editUser->update([
            'additional_groups' => implode(',', $finalGroups)
        ]);
        return $response->withRedirect($this->router->pathFor('panel.user.edit', [
            "id" => $args['userid']
        ]));
    }
}