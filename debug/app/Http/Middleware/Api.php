<?php

namespace App\Http\Middleware;

use App\Http\Container;

class Api extends Container
{
	public function __invoke($request, $response, $next)
	{
		return $next($request, $response);
	}
}