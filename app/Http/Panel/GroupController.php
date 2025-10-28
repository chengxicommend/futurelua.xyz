<?php


namespace App\Http\Panel;


use App\Domain\Group;
use App\Domain\User;
use App\Http\Container;
use App\Http\Utils;
use App\Http\Permissions;

class GroupController extends Container
{
    public function __invoke($request, $response)
    {
        if (!isset($_SESSION['user'])) {
            return $response->withRedirect(
                $this->router->pathFor('home')
            );
        }
        $user = User::where('id', $_SESSION['user'])->first();
        if (!$user) {
            return $response->withRedirect($this->router->pathFor('home'));
        }

        $group = Utils::getAdminGroups($user);

        if($user->permission_level ==3)
        {
            $resellerGroups = Utils::getResellerGroups($user);
            
            return $this->view->render($response, 'panel/group/home.twig', [
                'user' => $user,
                'groups' => $group,
                'resellerGroups' =>$resellerGroups
            ]);
        }


        if (empty($group) && !Permissions::isAdminOrEmcee($user)) {
            return $response->withRedirect($this->router->pathFor('home'));
        }

        if ( Permissions::isAdminOrEmcee($user) ) {
            $group = Group::all();
        }

        return $this->view->render($response, 'panel/group/home.twig', [
            'user' => $user,
            'groups' => $group
        ]);
    }

    public function createGet($request, $response)
    {
        if (!isset($_SESSION['user'])) {
            return $response->withRedirect(
                $this->router->pathFor('home')
            );
        }

        $user = User::where('id', $_SESSION['user'])->first();

        if (!Permissions::isAdminOrEmcee($user)) {
            return $response->withRedirect($this->router->pathFor('panel'));
        }

        return $this->view->render($response, 'panel/group/create.twig', [
            'user' => $user,
        ]);
    }

    public function createPost($request, $response)
    {

        $user = User::where('id', $_SESSION['user'])->first();

        if (!Permissions::isAdminOrEmcee($user)) {
            return $response->withRedirect($this->router->pathFor('panel'));
        }


        Group::create([
            'name' => $request->getParam("name"),
            'admins' => ''
        ]);

        return $response->withRedirect(
            $this->router->pathFor('panel.groups')
        );
    }

    public function delete($request, $response, $args)
    {
        if (!isset($_SESSION['user'])) {
            return $response->withRedirect(
                $this->router->pathFor('home')
            );
        }

        $user = User::where('id', $_SESSION['user'])->first();
        $group = Group::where('id', $args['id'])->first();
        if ($user->permission_level != 1 || !$group) {
            return $response->withRedirect(
                $this->router->pathFor('panel')
            );
        }
        $group->delete();
        return $response->withRedirect(
            $this->router->pathFor('panel.groups')
        );
    }

    //管理组
    public function manageAdminsGet($request, $response, $args)
    {
        if (!isset($_SESSION['user'])) {
            return $response->withRedirect(
                $this->router->pathFor('home')
            );
        }

        $user = User::where('id', $_SESSION['user'])->first();
        $this->refreshAdmins(Group::where('id', $args['id'])->first());
        $group = Group::where('id', $args['id'])->first();
        if (!$group || ( !Permissions::isAdminOrEmcee($user) && !Utils::isGroupAdmin($user, $group) &&!Utils::isGroupReseller($user, $group) ) ) {
            return $response->withRedirect(
                $this->router->pathFor('panel')
            );
        }

        $adminsObj = [];

        if (!empty($group->admins)) {
            $admins = explode(',', $group->admins);
            foreach ($admins as $admin) {
                array_push($adminsObj, User::where('id', $admin)->first());
            }
        }

        $resellerObj =[];
        if (!empty($group->resellers)) {
            $resellers = explode(',', $group->resellers);
            foreach ($resellers as $reseller) {
                array_push($resellerObj, User::where('id', $reseller)->first());
            }
        }

        $users = [];
        foreach (User::all() as $user1) {
            $additionalGroups = explode(',', $user1->additional_groups);
            if(in_array($group->id, $additionalGroups)) {
                array_push($users, $user1);
            }
        }

        return $this->view->render($response, 'panel/group/admins.twig', [
            'user' => $user,
            'group' => $group,
            'users' => $users,
            'admins' => $adminsObj,
            'resellers' => $resellerObj
        ]);
    }

    public function refreshAdmins($group)
    {
        if (!$group) return;

        $admins = explode(',', $group->admins);
        $finalAdmins = [];
        foreach ($admins as $admin) {
            $actualAdmin = User::where('id', $admin)->first();
            if ($actualAdmin) {
                array_push($finalAdmins, $admin);
            }
        }
        $group->update([
            'admins' => implode(',', $admins)
        ]);
    }

    public function addAdmin($request, $response, $args)
    {
        if (!isset($_SESSION['user'])) {
            return $response->withRedirect(
                $this->router->pathFor('home')
            );
        }

        $user = User::where('id', $_SESSION['user'])->first();
        $this->refreshAdmins(Group::where('id', $args['id'])->first());
        $group = Group::where('id', $args['id'])->first();
        if (!$group || (!Permissions::isAdminOrEmcee($user) && !Utils::isGroupAdmin($user, $group))) {
            return $response->withRedirect(
                $this->router->pathFor('panel')
            );
        }

        $admins = explode(',', $group->admins);

        $user = User::where('name', $request->getParam('username'))->first();
        if ($user) {
            if (!in_array($user->id, $admins)) {
                array_push($admins, $user->id);
            }
        }

        $finalAdmins = [];
        foreach ($admins as $admin) {
            $actualAdmin = User::where('id', $admin)->first();
            if ($actualAdmin) {
                array_push($finalAdmins, $admin);
            }
        }
        $group->update(['admins' => implode(',', $finalAdmins)]);

        return $response->withRedirect($this->router->pathFor('panel.group.admins', ["id" => $args['id']]));
    }

    public function addReseller($request, $response, $args)
    {
        if (!isset($_SESSION['user'])) {
            return $response->withRedirect(
                $this->router->pathFor('home')
            );
        }

        $user = User::where('id', $_SESSION['user'])->first();
        $this->refreshAdmins(Group::where('id', $args['id'])->first());


        $group = Group::where('id', $args['id'])->first();
        if (!$group || (!Permissions::isAdminOrEmcee($user) && !Utils::isGroupAdmin($user, $group))) {
            return $response->withRedirect(
                $this->router->pathFor('panel')
            );
        }

        $resellers = explode(',', $group->resellers);

        $user = User::where('name', $request->getParam('username'))->first();
        //权限判断如果是管理员不添加
        if($user->permission_level==1)
        {
            return $response->withRedirect($this->router->pathFor('panel.group.admins', ["id" => $args['id']]));
        }
        else if($user->permission_level==2 )
        {
            //修改权限到3
            $user->update([
                'permission_level' => 3
            ]);
        }

        if ($user) {
            if (!in_array($user->id, $resellers)) {
                array_push($resellers, $user->id);
            }
        }

        $finalAdmins = [];
        foreach ($resellers as $reseller) {
            $actualAdmin = User::where('id', $reseller)->first();
            if ($actualAdmin) {
                array_push($finalAdmins, $reseller);
            }
        }
        $group->update(['resellers' => implode(',', $finalAdmins)]);

        return $response->withRedirect($this->router->pathFor('panel.group.admins', ["id" => $args['id']]));
    }
    


    public function removeUsers($request, $response, $args)
    {
        if (!isset($_SESSION['user'])) {
            return $response->withRedirect(
                $this->router->pathFor('home')
            );
        }

        $user = User::where('id', $_SESSION['user'])->first();
        $this->refreshAdmins(Group::where('id', $args['id'])->first());
        $group = Group::where('id', $args['id'])->first();
        if (!$group || (!Permissions::isAdminOrEmcee($user) && !Utils::isGroupAdmin($user, $group))) {
            return $response->withRedirect($this->router->pathFor('panel.group.admins', ["id" => $args['id']]));
        }

        $userObj = User::where('id', $args['userid'])->first();

        if($userObj) {
            $additionalGroups = explode(',', $userObj->additional_groups);
            $finalGroups = [];
            foreach ($additionalGroups as $additionalGroup) {
                $actualGroup = Group::where('id', $additionalGroup)->first();

                if ($actualGroup && intval($actualGroup->id) != $group->id) {
                    array_push($finalGroups, $additionalGroup);
                }
            }
            $userObj->update([
                'additional_groups' => implode(',', $finalGroups)
            ]);
        }

        return $response->withRedirect($this->router->pathFor('panel.group.admins', ["id" => $args['id']]));
    }

    public function addResellerUser($request, $response, $args)
    {
        if (!isset($_SESSION['user'])) {
            return $response->withRedirect(
                $this->router->pathFor('home')
            );
        }

        $user = User::where('id', $_SESSION['user'])->first();
        //经销商处理
        if($user->permission_level == 3)
        {
            $group = Group::where('id', $args['id'])->first();
            if (!$group || (!Permissions::isAdminOrEmcee($user) && !Utils::isGroupReseller($user, $group))) {
                return $response->withRedirect($this->router->pathFor('panel.group.admins', ["id" => $args['id']]));
            }

            $userObj = User::where('name', $request->getParam('username'))->first();

            if($userObj) {
    
                $additionalGroups = explode(',', $userObj->additional_groups);
                if (!in_array($group->id, $additionalGroups)) {
                    array_push($additionalGroups, $group->id);
                }
                $finalGroups = [];
                foreach ($additionalGroups as $group) {
                    $actualGroup = Group::where('id', $group)->first();
                    if ($actualGroup) {
                        array_push($finalGroups, $group);
                    }
                }
                $userObj->update([
                    'additional_groups' => implode(',', $finalGroups)
                ]);
            }

            return $response->withRedirect($this->router->pathFor('panel.reseller.user', ["id" => $args['id']]));
        }
    }

    public function addUser($request, $response, $args)
    {
        if (!isset($_SESSION['user'])) {
            return $response->withRedirect(
                $this->router->pathFor('home')
            );
        }

        $user = User::where('id', $_SESSION['user'])->first();
        
        $this->refreshAdmins(Group::where('id', $args['id'])->first());
        $group = Group::where('id', $args['id'])->first();
        if (!$group || ($user->permission_level != 1 && !Utils::isGroupAdmin($user, $group))) {
            return $response->withRedirect($this->router->pathFor('panel.group.admins', ["id" => $args['id']]));
        }

        $userObj = User::where('name', $request->getParam('username'))->first();

        if($userObj) {

            $additionalGroups = explode(',', $userObj->additional_groups);
            if (!in_array($group->id, $additionalGroups)) {
                array_push($additionalGroups, $group->id);
            }
            $finalGroups = [];
            foreach ($additionalGroups as $group) {
                $actualGroup = Group::where('id', $group)->first();
                if ($actualGroup) {
                    array_push($finalGroups, $group);
                }
            }
            $userObj->update([
                'additional_groups' => implode(',', $finalGroups)
            ]);
        }
        return $response->withRedirect($this->router->pathFor('panel.group.admins', ["id" => $args['id']]));
    }


    public function removeAdmin($request, $response, $args)
    {
        if (!isset($_SESSION['user'])) {
            return $response->withRedirect(
                $this->router->pathFor('home')
            );
        }

        $user = User::where('id', $_SESSION['user'])->first();
        $this->refreshAdmins(Group::where('id', $args['id'])->first());
        $group = Group::where('id', $args['id'])->first();
        if (!$group || (!Permissions::isAdminOrEmcee($user) && !Utils::isGroupAdmin($user, $group))) {
            return $response->withRedirect($this->router->pathFor('panel.group.admins', ["id" => $args['id']]));
        }

        $admins = explode(',', $group->admins);


        $finalAdmins = [];
        foreach ($admins as $admin) {
            $actualAdmin = User::where('id', $admin)->first();
            if ($admin != intval($args['userid'])) {
                array_push($finalAdmins, $admin);
            }
        }

        $group->update(['admins' => implode(',', $finalAdmins)]);
        return $response->withRedirect($this->router->pathFor('panel.group.admins', ["id" => $args['id']]));
    }

    public function removeReseller($request, $response, $args)
    {
        if (!isset($_SESSION['user'])) {
            return $response->withRedirect(
                $this->router->pathFor('home')
            );
        }

        $user = User::where('id', $_SESSION['user'])->first();
        $this->refreshAdmins(Group::where('id', $args['id'])->first());
        $group = Group::where('id', $args['id'])->first();
        if (!$group || (!Permissions::isAdminOrEmcee($user) && !Utils::isGroupAdmin($user, $group))) {
            return $response->withRedirect($this->router->pathFor('panel.group.admins', ["id" => $args['id']]));
        }

        $resellers = explode(',', $group->resellers);


        $finalAdmins = [];
        foreach ($resellers as $reseller) {
            $actualAdmin = User::where('id', $reseller)->first();
            if ($reseller != intval($args['userid'])) {
                array_push($finalAdmins, $reseller);
            }
        }

        $group->update(['resellers' => implode(',', $finalAdmins)]);

        //修改权限到2
        $deluser = User::where('id', $args['userid'])->first();
        if($deluser->permission_level == 3)
        {
            if(!Utils::isReseller( $deluser ))
            {
                $deluser->update([
                    'permission_level' => 2
                ]);
            }
        }

        return $response->withRedirect($this->router->pathFor('panel.group.admins', ["id" => $args['id']]));
    }

    

}