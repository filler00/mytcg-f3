<?php

// Admin panel routes
$f3->route('GET|POST /mytcg/@controller/@action/@id','Filler00\Router->routeAdmin');

$f3->route('GET|POST /mytcg/@controller/@action', 'Filler00\Router->routeAdmin');

$f3->route('GET|POST /mytcg/@controller', 'Filler00\Router->routeAdmin');

$f3->route('GET /mytcg', 'Filler00\Router->routeAdmin');

// Front end routes
$f3->route('GET|POST /@controller/@action/@id','Filler00\Router->route');

$f3->route('GET|POST /@controller/@action', 'Filler00\Router->route');

$f3->route('GET|POST /@controller', 'Filler00\Router->route');

$f3->route('GET /', 'Filler00\Router->route');

/*
$f3->route('GET|POST /@controller/@action/@id',
	function($f3) {
		$ctrl = new Controller; $ctrl->beforeRoute();
		$className = ucfirst($f3->get('PARAMS.controller')) . 'Controller';
		$actionName = $f3->get('PARAMS.action');
		if ( method_exists($className,$actionName) ) {
			$$className = new $className;
			$$className->$actionName($f3->get('PARAMS.id'));
		} else if ( class_exists($className) ) {
			$f3->error(404);
		} else if ( file_exists('app/www/'. $f3->get('PARAMS.controller') .'.htm') ) {
			$f3->set('content','app/www/'. $f3->get('PARAMS.controller') .'.htm');
			echo Template::instance()->render('app/templates/default.htm');
		} else {
			$f3->error(404);
		}
		$ctrl->afterRoute();
	}
);

$f3->route('GET|POST /@controller/@action',
	function($f3) {
		$ctrl = new Controller; $ctrl->beforeRoute();
		$className = ucfirst($f3->get('PARAMS.controller')) . 'Controller';
		$actionName = $f3->get('PARAMS.action');
		if ( method_exists($className,$actionName) ) {
			$$className = new $className;
			$$className->$actionName();
		} else if ( class_exists($className) ) {
			$f3->error(404);
		} else if ( file_exists('app/www/'. $f3->get('PARAMS.controller') .'.htm') ) {
			$f3->set('content','app/www/'. $f3->get('PARAMS.controller') .'.htm');
			echo Template::instance()->render('app/templates/default.htm');
		} else {
			$f3->error(404);
		}
		$ctrl->afterRoute();
	}
);

$f3->route('GET|POST /@controller',
	function($f3) {
		$ctrl = new Controller; $ctrl->beforeRoute();
		$className = ucfirst($f3->get('PARAMS.controller')) . 'Controller';
		if ( method_exists($className,'index') ) {
			$$className = new $className;
			$$className->index();
		} else if ( class_exists($className) ) {
			$f3->error(404);
		} else if ( file_exists('app/www/'. $f3->get('PARAMS.controller') .'.htm') ) {
			$f3->set('content','app/www/'. $f3->get('PARAMS.controller') .'.htm');
			echo Template::instance()->render('app/templates/default.htm');
		} else {
			$f3->error(404);
		}
		$ctrl->afterRoute();
	}
);

$f3->route('GET /',
	function($f3) {
		$ctrl = new Controller; $ctrl->beforeRoute();
		if ( method_exists('IndexController','index') ) {
			$IndexController = new IndexController;
			$IndexController->index();
		} else if ( class_exists('IndexController') ) {
			$f3->error(404);
		} else if ( file_exists('app/www/index.htm') ) {
			$f3->set('content','app/www/index.htm');
			echo Template::instance()->render('app/templates/default.htm');
		} else {
			$f3->error(404);
		}
		$ctrl->afterRoute();
	}
);
*/