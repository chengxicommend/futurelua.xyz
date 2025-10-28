<?php

namespace App\Http;

use App\Domain\User;

class Container
{
    protected $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function __get($property)
    {
        if ($this->container->{$property}) {
            return $this->container->{$property};
        }
    }

    public function render($path, $data = [])
    {
        return $this->view->render($this->response, str_replace('.', '/', $path) . '.twig', $data);
    }

    public function redirect($to, $args = [])
    {
        return $this->response->withRedirect($this->router->pathFor($to, $args));
    }
}