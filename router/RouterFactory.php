<?php

namespace App;

use
	Nette\Application\Routers\RouteList,
	Nette\Application\Routers\Route;


/**
 * Router factory.
 */
class RouterFactory
{

	/**
	 * @return \Nette\Application\IRouter
	 */
	public function createRouter()
	{
		$router = new RouteList();

		$router[] = $admin = new RouteList('Admin');
		$admin[] = new Route('admin/<presenter>/<action>[/<id>]', 'Homepage:default');

		$router[] = new Route('category/<slug>', 'Content:showCategory');
		$router[] = new Route('content/add[/<slug>]', 'Content:add');
		$router[] = new Route('content/<slug>', 'Content:show');
		$router[] = new Route('<presenter>/<action>[/<id>]', 'Homepage:default');

		return $router;
	}

}
