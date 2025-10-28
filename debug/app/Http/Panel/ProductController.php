<?php


namespace App\Http\Panel;


use App\Domain\Group;
use App\Domain\Product;
use App\Domain\User;
use App\Http\Container;
use App\Http\Utils;

class ProductController extends Container
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

        $products = [];
        $groups = Utils::getAdminGroupIDs($user);

        if (sizeof($groups) == 0 && $user->permission_level != 1) {
            return $response->withRedirect(
                $this->router->pathFor('home')
            );
        }


        if ($user->permission_level == 1) {
            $products = Product::all();
        } else {
            foreach (Product::all() as $product) {
                if (in_array($product->group_id, $groups))
                    array_push($products, $product);
            }
        }


        foreach ($products as $product) {
            $product->group = Group::where('id', $product->group_id)->first();
        }
        return $this->view->render($response, 'panel/product/home.twig', [
            'user' => $user,
            'products' => $products,
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

        return $this->view->render($response, 'panel/product/create.twig', [
            'user' => $user,
            'groups' => $groups
        ]);
    }

    public function createProductPost($request, $response)
    {
        $user = User::where('id', $_SESSION['user'])->first();
        if ($user->permission_level == 1 || Utils::isGroupAdmin($user, intval($request->getParam('group_id')))) {
            Product::create([
                'name' => $request->getParam('name'),
                'code' => $request->getParam('code'),
                'version' => 1,
                'priority' => intval($request->getParam('priority')),
                'group_id' => $request->getParam('group_id'),
                'disable_obfuscation' => $request->getParam('disable_obfuscation') == "on"
            ]);
        }

        return $response->withRedirect(
            $this->router->pathFor('panel.products')
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
        $product = Product::where('id', $args['id'])->first();
        if (($user->permission_level != 1 && !Utils::isGroupAdmin($user, $product->group_id)) || !$product) {
            return $response->withRedirect($this->router->pathFor('panel.products'));
        }
        $groups = $user->permission_level == 1 ? Group::all() : Utils::getAdminGroups($user);
        return $this->view->render($response, 'panel/product/edit.twig', [
            'user' => $user,
            'product' => $product,
            'groups' => $groups
        ]);
    }

    public function editProductPost($request, $response, $args)
    {
        $user = User::where('id', $_SESSION['user'])->first();
        $product = Product::where('id', $args['id'])->first();
        if (($user->permission_level != 1 && !Utils::isGroupAdmin($user, $product->group_id)) || !$product) {
            return $response->withRedirect($this->router->pathFor('panel.products'));
        }

        $product->update([
            'name' => $request->getParam('name'),
            'code' => $request->getParam('code'),
            'version' => round($product->version + 0.1, 1),
            'priority' => intval($request->getParam('priority')),
            'group_id' => intval($request->getParam('group_id')),
            'disable_obfuscation' => $request->getParam('disable_obfuscation') == "on"
        ]);
        return $response->withRedirect(
            $this->router->pathFor('panel.products')
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
        $product = Product::where('id', $args['id'])->first();
        if (($user->permission_level != 1 && !Utils::isGroupAdmin($user, $product->group_id)) || !$product) {
            return $response->withRedirect(
                $this->router->pathFor('panel.products')
            );
        }
        $product->delete();
        return $response->withRedirect(
            $this->router->pathFor('panel.products')
        );
    }

}