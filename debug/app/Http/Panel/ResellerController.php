<?php


namespace App\Http\Panel;


use App\Domain\Group;
use App\Domain\User;
use App\Domain\Reseller;
use App\Http\Container;
use App\Http\Utils;

class ResellerController extends Container
{

    public function __invoke($request, $response)
    {
        //是否登录
        if (!isset($_SESSION['user'])) {
            return $response->withRedirect(
                $this->router->pathFor('home')
            );
        }

        //权限查看
        $user = User::where('id', $_SESSION['user'])->first();
        if ( $user->permission_level != 3) {
            return $response->withRedirect(
                $this->router->pathFor('home')
            );
        }

        //错误信息
        $error;
        //Append any errors
        if(!empty($request->getParam("error"))) {
            $error = $request->getParam("error");
        }

        //用户列表
        $userList = Reseller::all();
        //$userList = Reseller::where("admin_id",$user->id);

        //查询数据
        // $users = [];
        // foreach ($userList as $user1) {
        //     if($user1->admin_id == $user->id)
        //     {
        //         array_push($users, User::where('id', $user1->user_id)->first());
        //     }
        // }

        //显示信息
        return $this->view->render($response, 'panel/reseller/user.twig', [
            'users' => $users,
            'error' => $error
        ]);
    }

    public function manageGroupsGet($request, $response)
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
            $group2 = Utils::getResellerGroups($user);
            
            return $this->view->render($response, 'panel/reseller/group.twig', [
                'user' => $user,
                'groups' => $group2
            ]);
        }


        if (empty($group) && $user->permission_level != 1) {
            return $response->withRedirect($this->router->pathFor('home'));
        }

        if ($user->permission_level == 1) {
            $group = Group::all();
        }

        return $this->view->render($response, 'panel/reseller/group.twig', [
            'user' => $user,
            'groups' => $group
        ]);



    }
    

    public function manageReseller($request, $response)
    {
        if (!isset($_SESSION['user'])) {
            return $response->withRedirect(
                $this->router->pathFor('home')
            );
        }

        //权限查看
        $user = User::where('id', $_SESSION['user'])->first();
        if ( $user->permission_level != 1) {
            return $response->withRedirect(
                $this->router->pathFor('home')
            );
        }

         //错误信息
         $error;
         //Append any errors
         if(!empty($request->getParam("error"))) {
             $error = $request->getParam("error");
         }
 
        $reseller = User::where('permission_level', 3)->get();

         //用户列表
         $userList = Reseller::all();
         //$userList = Reseller::where("admin_id",$user->id);
 
         //查询数据
         $arr=[];
         $users=[];
         foreach($reseller as $reseller1)
         {
            array_push($users, $reseller1);
            $arr[$reseller1->id]=0;
         }
         foreach ($userList as $user1) {
            if(array_key_exists($user1->admin_id, $arr)) 
            {
                $arr[$user1->admin_id]=$arr[$user1->admin_id]+1;
            }
            else
            {
                $arr[$user1->admin_id]=1;
            }
         }

        
        //  foreach($arr as $x=>$x_value)
        //  {
        //     array_push($users, User::where('id', $x)->first());
        //  }
         


         //显示信息
         return $this->view->render($response, 'panel/reseller/admin.twig', [
             'users' => $users,
             'number'=> $arr,
             'error' => $error
         ]);
    }

    public function aadResellerPost($request, $response)
    {
        $user = User::where('id', $_SESSION['user'])->first();

        if ($user->permission_level != 1) {
            return $response->withRedirect($this->router->pathFor('panel'));
        }

        //添加的用户名
        $appendName = $request->getParam('username');
        if (empty($appendName)) {
            return $response->withRedirect($this->router->pathFor('panel.reseller', [], [
                'error' => "Username can not be empty."
            ]));
        }

        //查询添加的用户
        $appendUser = User::where('name', $appendName)->first();
        if (empty($appendUser)) {
            return $response->withRedirect($this->router->pathFor('panel.reseller', [], [
                'error' => "Username '". $appendName . "' is not registered."
            ]));
        }

        //已经是经销商
        if($appendUser->permission_level==3)
        {
            return $response->withRedirect($this->router->pathFor('panel.reseller', [], [
                'error' => "'". $appendName . "' already existing user."
            ]));
        }
        else if($appendUser->permission_level==1 )
        {
            return $response->withRedirect($this->router->pathFor('panel.reseller', [], [
                'error' => "'". $appendName . "' is adminer."
            ]));
        }

        $appendUser->update([
            'permission_level' => 3
        ]);

        return $response->withRedirect(
            $this->router->pathFor('panel.reseller')
        );
    }


    public function addUserPost($request, $response)
    {

        $user = User::where('id', $_SESSION['user'])->first();

        if ($user->permission_level != 3) {
            return $response->withRedirect($this->router->pathFor('panel'));
        }

        //添加的用户名
        $appendName = $request->getParam('username');
        if (empty($appendName)) {
            return $response->withRedirect($this->router->pathFor('panel.reseller.users', [], [
                'error' => "Username can not be empty."
            ]));
        }

        //查询添加的用户
        $appendUser = User::where('name', $appendName)->first();
        if (empty($appendUser)) {
            return $response->withRedirect($this->router->pathFor('panel.reseller.users', [], [
                'error' => "Username '". $appendName . "' is not registered."
            ]));
        }

        //是否已经被添加
        // $item = Reseller::where('user_id',$appendUser->id)->first();
        // if (!empty($item)) {
        //     return $response->withRedirect($this->router->pathFor('panel.reseller.users', [], [
        //         'error' => "'". $appendName . "' has been added."
        //     ]));
        // }
    
        //添加用户
        // Reseller::create([
        //     'user_id' => $appendUser->id,
        //     'admin_id' => $user->id
        // ]);

        return $response->withRedirect(
            $this->router->pathFor('panel.reseller.users')
        );
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
        if (!$group || ($user->permission_level != 1 && !Utils::isGroupAdmin($user, $group))) {
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


    public function manageUserGet($request, $response, $args)
    {
        if (!isset($_SESSION['user'])) {
            return $response->withRedirect(
                $this->router->pathFor('home')
            );
        }

        $user = User::where('id', $_SESSION['user'])->first();
      //  $this->refreshAdmins(Group::where('id', $args['id'])->first());
        $group = Group::where('id', $args['id'])->first();
        if (!$group || ($user->permission_level != 1 && !Utils::isGroupAdmin($user, $group) &&!Utils::isGroupReseller($user, $group) ) ) {
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

        return $this->view->render($response, 'panel/reseller/home.twig', [
            'user' => $user,
            'group' => $group,
            'users' => $users,
            'admins' => $adminsObj,
            'resellers' => $resellerObj
        ]);
    }

}


 