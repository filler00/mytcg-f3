<?php

namespace Filler00;

use Template;

class Router {
	
	private $corePath = 'Controllers\\Core\\';
	private $localPath = 'Controllers\\Local\\';
	private $adminCorePath = 'Controllers\\Core\\MyTCG\\';
	private $adminLocalPath = 'Controllers\\Local\\MyTCG\\';

	public function routeAdmin ($f3,$params) {
		$localPath = $this->adminLocalPath;
		$corePath = $this->adminCorePath;
		
		// detect local or core base controller
		if ( class_exists($localPath . 'Controller') ) {
			$ctrl = $localPath . 'Controller';
			$ctrl = new $ctrl;
		} else {
			$ctrl = $corePath . 'Controller';
			$ctrl = new $ctrl;
		}
		
		$ctrl->beforeRoute();
		
		// get controller if it exists in the URL
		if ( $f3->exists('PARAMS.controller') )
			$className = ucfirst($f3->get('PARAMS.controller')) . 'Controller';
		else
			$className = false;
		
		// get action if it exists in the URL
		if ( $f3->exists('PARAMS.action') )
			$actionName = $f3->get('PARAMS.action');
		else
			$actionName = false;

		// validate controller and method, if both are set
		if ( $className && $actionName ) {
			
			// check if class and action exists in Local namespace
			if ( method_exists($localPath . $className,$actionName) ) {
				$className = $localPath . $className;
			// check if class and action exists in Core namespace
			} else if ( method_exists($corePath . $className,$actionName) ) {
				$className = $corePath . $className;
			// throw a 404 if a non-existant action was requested on an existant Class
			} else if ( class_exists($localPath . $className) || class_exists($corePath . $className) ) {
				$f3->error(404);
			// Check if the file exists in the WWW directory
			} else if ( file_exists('app/www/mytcg/'. $f3->get('PARAMS.controller') .'.htm') ) {
				$loadWWW = true;
				$className = false;
			} else {
				$className = false;
				$actionName = false;
			}
		}
		// if only the controller is set:
		else if ( $className ) {
			
			// check if class exists in the Local namespace
			if ( class_exists($localPath . $className) ) {
				$className = $localPath . $className;
			// check if class exists in the Core namespace
			} else if ( class_exists($corePath . $className) ) {
				$className = $corePath . $className;
			// Check if the file exists in the WWW directory
			} else if ( file_exists('app/www/mytcg/'. $f3->get('PARAMS.controller') .'.htm') ) {
				$loadWWW = true;
				$className = false;
			}
			else {
				$className = false;
			}
		}
		// if there's no controller, route to the index
		else {
			// check if class and action exists in Local namespace
			if ( class_exists($localPath . 'IndexController') ) {
				$className = $localPath . 'IndexController';
			// check if class and action exists in Core namespace
			} else {
				$className = $corePath . 'IndexController';
			}
		}
		
		
		if ( $className && $actionName && $f3->exists('PARAMS.id') ) {
			$class = new $className; $class->$actionName($f3->get('PARAMS.id'));
		} else if ( $className && $actionName ) {
			$class = new $className; $class->$actionName();
		} else if ( $className ) {
			$class = new $className; $class->index();
		} else if ( isset($loadWWW) && $loadWWW ) {
			$f3->set('content','app/www/mytcg/'. $f3->get('PARAMS.controller') .'.htm');
			echo Template::instance()->render('app/templates/admin.htm');
		} else {
			$f3->error(404);
		}
		
		$ctrl->afterRoute();
	}
	
	public function route ($f3,$params) {
		$localPath = $this->localPath;
		$corePath = $this->corePath;
		
		// detect local or core base controller
		if ( class_exists($localPath . 'Controller') ) {
			$ctrl = $localPath . 'Controller';
			$ctrl = new $ctrl;
		} else {
			$ctrl = $corePath . 'Controller';
			$ctrl = new $ctrl;
		}
		
		$ctrl->beforeRoute();
		
		// get controller if it exists in the URL
		if ( $f3->exists('PARAMS.controller') )
			$className = ucfirst($f3->get('PARAMS.controller')) . 'Controller';
		else
			$className = false;
		
		// get action if it exists in the URL
		if ( $f3->exists('PARAMS.action') )
			$actionName = $f3->get('PARAMS.action');
		else
			$actionName = false;

		// validate controller and method, if both are set
		if ( $className && $actionName ) {
			
			// check if class and action exists in Local namespace
			if ( method_exists($localPath . $className,$actionName) ) {
				$className = $localPath . $className;
			// check if class and action exists in Core namespace
			} else if ( method_exists($corePath . $className,$actionName) ) {
				$className = $corePath . $className;
			// throw a 404 if a non-existant action was requested on an existant Class
			} else if ( class_exists($localPath . $className) || class_exists($corePath . $className) ) {
				$f3->error(404);
			// Check if the file exists in the WWW directory
			} else if ( file_exists('app/www/'. $f3->get('PARAMS.controller') .'.htm') ) {
				$loadWWW = true;
				$className = false;
			} else {
				$className = false;
				$actionName = false;
			}
		}
		// if only the controller is set:
		else if ( $className ) {
			
			// check if class exists in the Local namespace
			if ( class_exists($localPath . $className) ) {
				$className = $localPath . $className;
			// check if class exists in the Core namespace
			} else if ( class_exists($corePath . $className) ) {
				$className = $corePath . $className;
			// Check if the file exists in the WWW directory
			} else if ( file_exists('app/www/'. $f3->get('PARAMS.controller') .'.htm') ) {
				$loadWWW = true;
				$className = false;
			}
			else {
				$className = false;
			}
		}
		// if there's no controller, route to the index
		else {
			// check if class and action exists in Local namespace
			if ( class_exists($localPath . 'IndexController') ) {
				$className = $localPath . 'IndexController';
			// check if class and action exists in Core namespace
			} else {
				$className = $corePath . 'IndexController';
			}
		}
		
		
		if ( $className && $actionName && $f3->exists('PARAMS.id') ) {
			$class = new $className; $class->$actionName($f3->get('PARAMS.id'));
		} else if ( $className && $actionName ) {
			$class = new $className; $class->$actionName();
		} else if ( $className ) {
			$class = new $className; $class->index();
		} else if ( isset($loadWWW) && $loadWWW ) {
			$f3->set('content','app/www/'. $f3->get('PARAMS.controller') .'.htm');
			echo Template::instance()->render('app/templates/default.htm');
		} else {
			$f3->error(404);
		}
		
		$ctrl->afterRoute();
	}

}