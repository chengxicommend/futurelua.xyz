<?php
/**
 * Created by SamHoque
 * Self Explanatory
 */

namespace App\Http\Panel;

use App\Domain\Config;
use App\Domain\Group;
use App\Domain\Helper;
use App\Domain\User;
use App\Http\Container;
use App\Http\Utils;

class HelperController extends Container
{
    public function __invoke($request, $response)
    {
        if (!isset($_SESSION['user'])) {
            return $response->withRedirect($this->router->pathFor('home'));
        }
        $user = User::where('id', $_SESSION['user'])->first();

        if ($user->banned == 1) {
            return $response->withRedirect($this->router->pathFor('banned'));
        }

        $configs = [];
        $groups = Utils::getAdminGroupIDs($user);

        if (sizeof($groups) == 0 && $user->permission_level != 1) {
            return $response->withRedirect(
                $this->router->pathFor('home')
            );
        }


        if ($user->permission_level == 1) {
            $configs = Helper::all();
        } else {
            foreach (Helper::all() as $product) {
                if (in_array($product->group_id, $groups))
                    array_push($configs, $product);
            }
        }


        foreach ($configs as $product) {
            $product->group = Group::where('id', $product->group_id)->first();
        }
        return $this->view->render($response, 'panel/helper/home.twig', [
            'user' => $user,
            'configs' => $configs,
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
        if ($user->banned == 1) {
            return $response->withRedirect($this->router->pathFor('banned'));
        }

        $groups = $user->permission_level == 1 ? Group::all() : Utils::getAdminGroups($user);

        if ($user->permission_level == 1) {
            $groups = Group::all();
        }

        if (sizeof($groups) == 0) {
            return $response->withRedirect(
                $this->router->pathFor('panel')
            );
        }

        return $this->view->render($response, 'panel/helper/create.twig', [
            'user' => $user,
            'groups' => $groups
        ]);
    }

    public function createPost($request, $response)
    {
        $user = User::where('id', $_SESSION['user'])->first();
        if ($user->permission_level == 1 || Utils::isGroupAdmin($user, intval($request->getParam('group_id')))) {
            Helper::create([
                'name' => $request->getParam('name'),
                'code' => $request->getParam('code'),
                'version' => 1,
                'group_id' => $request->getParam('group_id'),
            ]);
        }

        return $response->withRedirect(
            $this->router->pathFor('panel.helper')
        );
    }

    public function editGet($request, $response, $args)
    {
        if (!isset($_SESSION['user'])) {
            return $response->withRedirect(
                $this->router->pathFor('home')
            );
        }

        $user = User::where('id', $_SESSION['user'])->first();
        $product = Helper::where('id', $args['id'])->first();

        if (($user->permission_level != 1 && !Utils::isGroupAdmin($user, $product->group_id)) || !$product) {
            return $response->withRedirect($this->router->pathFor('panel.helper'));
        }
        $groups = $user->permission_level == 1 ? Group::all() : Utils::getAdminGroups($user);
        return $this->view->render($response, 'panel/helper/edit.twig', [
            'user' => $user,
            'helper' => $product,
            'groups' => $groups
        ]);
    }

    public function editPost($request, $response, $args)
    {
        $user = User::where('id', $_SESSION['user'])->first();
        $product = Helper::where('id', $args['id'])->first();
        if (($user->permission_level != 1 && !Utils::isGroupAdmin($user, $product->group_id)) || !$product) {
            return $response->withRedirect($this->router->pathFor('panel.helper'));
        }

        $product->update([
            'name' => $request->getParam('name'),
            'code' => $request->getParam('code'),
            'version' => round($product->version + 0.1, 1),
            'group_id' => intval($request->getParam('group_id')),
        ]);

        return $response->withRedirect(
            $this->router->pathFor('panel.helper')
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
        $product = Helper::where('id', $args['id'])->first();
        if (($user->permission_level != 1 && !Utils::isGroupAdmin($user, $product->group_id)) || !$product) {
            return $response->withRedirect(
                $this->router->pathFor('panel.helper')
            );
        }
        $product->delete();
        return $response->withRedirect(
            $this->router->pathFor('panel.helper')
        );
    }

}