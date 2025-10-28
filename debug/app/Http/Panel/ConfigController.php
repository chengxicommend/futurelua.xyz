<?php


namespace App\Http\Panel;


use App\Domain\Config;
use App\Domain\Group;
use App\Domain\Product;
use App\Domain\User;
use App\Http\Container;
use App\Http\Utils;

class ConfigController extends Container
{
    public function __invoke($request, $response)
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

        $configs = [];
        $groups = Utils::getAdminGroupIDs($user);

        if (sizeof($groups) == 0 && $user->permission_level != 1) {
            return $response->withRedirect(
                $this->router->pathFor('home')
            );
        }


        if ($user->permission_level == 1) {
            $configs = Config::all();
        } else {
            foreach (Config::all() as $product) {
                if (in_array($product->group_id, $groups))
                    array_push($configs, $product);
            }
        }


        foreach ($configs as $product) {
            $product->group = Group::where('id', $product->group_id)->first();
        }
        return $this->view->render($response, 'panel/config/home.twig', [
            'user' => $user,
            'configs' => $configs,
        ]);
    }


    public function createProductGet($request, $response)
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

        return $this->view->render($response, 'panel/config/create.twig', [
            'user' => $user,
            'groups' => $groups
        ]);
    }

    public function createProductPost($request, $response)
    {
        $user = User::where('id', $_SESSION['user'])->first();
        if ($user->permission_level == 1 || Utils::isGroupAdmin($user, intval($request->getParam('group_id')))) {
            Config::create([
                'name' => $request->getParam('name'),
                'code' => $request->getParam('code'),
                'version' => 1,
                'group_id' => $request->getParam('group_id'),
            ]);
        }

        return $response->withRedirect(
            $this->router->pathFor('panel.configs')
        );
    }

    public function editProductGet($request, $response, $args)
    {
        if (!isset($_SESSION['user'])) {
            return $response->withRedirect(
                $this->router->pathFor('home')
            );
        }

        $user = User::where('id', $_SESSION['user'])->first();
        $product = Config::where('id', $args['id'])->first();
        if (($user->permission_level != 1 && !Utils::isGroupAdmin($user, $product->group_id)) || !$product) {
            return $response->withRedirect($this->router->pathFor('panel.configs'));
        }
        $groups = $user->permission_level == 1 ? Group::all() : Utils::getAdminGroups($user);
        return $this->view->render($response, 'panel/config/edit.twig', [
            'user' => $user,
            'config' => $product,
            'groups' => $groups
        ]);
    }

    public function editProductPost($request, $response, $args)
    {
        $user = User::where('id', $_SESSION['user'])->first();
        $product = Config::where('id', $args['id'])->first();
        if (($user->permission_level != 1 && !Utils::isGroupAdmin($user, $product->group_id)) || !$product) {
            return $response->withRedirect($this->router->pathFor('panel.configs'));
        }

        $product->update([
            'name' => $request->getParam('name'),
            'code' => $request->getParam('code'),
            'version' => round($product->version + 0.1, 1),
            'group_id' => intval($request->getParam('group_id')),
        ]);

        return $response->withRedirect(
            $this->router->pathFor('panel.configs')
        );
    }

    public function configLuasGet($request, $response, $args)
    {
        $user = User::where('id', $_SESSION['user'])->first();
        $config = Config::where('id', $args['id'])->first();
        if (($user->permission_level != 1 && !Utils::isGroupAdmin($user, $config->group_id)) || !$config) {
            return $response->withRedirect($this->router->pathFor('panel.configs'));
        }
        $groups = $user->permission_level == 1 ? Group::all() : Utils::getAdminGroups($user);
        $products = $config->products()->get();
        $id = $config->id;
        $availProducts = Product::whereDoesntHave('configs', function ($query) use ($id) {
            $query->whereConfigId($id);
        })->get();
        return $this->view->render($response, 'panel/config/products.twig', [
            'user' => $user,
            'config' => $config,
            'groups' => $groups,
            'products' => $products,
            'availProducts' => $availProducts
        ]);
    }

    public function configLuasAdd($request, $response, $args)
    {
        $user = User::where('id', $_SESSION['user'])->first();
        $config = Config::where('id', $args['id'])->first();
        if (($user->permission_level != 1 && !Utils::isGroupAdmin($user, $config->group_id)) || !$config) {
            return $response->withRedirect($this->router->pathFor('panel.config.products', ['id' => $args['id']]));
        }

        $productId = $request->getParam('product_name');
        if (!$config->products->contains($productId)) {
            $config->products()->attach($productId);
        }
        return $response->withRedirect(
            $this->router->pathFor('panel.config.products', ['id' => $args['id']])
        );
    }

    public function configLuasDelete($request, $response, $args)
    {
        $user = User::where('id', $_SESSION['user'])->first();
        $config = Config::where('id', $args['id'])->first();
        if (($user->permission_level != 1 && !Utils::isGroupAdmin($user, $config->group_id)) || !$config) {
            return $response->withRedirect($this->router->pathFor('panel.config.products', ['id' => $args['id']]));
        }

        $productId = $args['productid'];
        if ($config->products->contains($productId)) {
            $config->products()->detach($productId);
        }
        return $response->withRedirect($this->router->pathFor('panel.config.products', ['id' => $args['id']]));
    }


    public function delete($request, $response, $args)
    {
        if (!isset($_SESSION['user'])) {
            return $response->withRedirect(
                $this->router->pathFor('home')
            );
        }

        $user = User::where('id', $_SESSION['user'])->first();
        $product = Config::where('id', $args['id'])->first();
        if (($user->permission_level != 1 && !Utils::isGroupAdmin($user, $product->group_id)) || !$product) {
            return $response->withRedirect(
                $this->router->pathFor('panel.configs')
            );
        }
        $product->delete();
        return $response->withRedirect(
            $this->router->pathFor('panel.configs')
        );
    }


}