<?php

namespace Filler00;

use Template;
use Db\Jig;
use Models\Core\Plugins;

class Router {
	
	protected $plugins;
	protected $jig;
	
	private $corePath;
	private $localPath;
	private $pluginPath;
	private $adminCorePath;
	private $adminLocalPath;
	private $adminPluginPath;
	
	private $packages;
	
	public function __construct() {
        $this->corePath = 'Controllers\\Core\\';
		$this->localPath = 'Controllers\\Local\\';
		$this->pluginPath = 'Plugins\\%s\\App\\Controllers\\';
		$this->adminCorePath = 'Controllers\\Core\\MyTCG\\';
		$this->adminLocalPath = 'Controllers\\Local\\MyTCG\\';
		$this->adminPluginPath = 'Plugins\\%s\\App\\Controllers\\MyTCG\\';
		
		$this->jig = new Jig('storage/jig/');
		$this->plugins = new Plugins($this->jig);
    }
    
    private function pluginMethodExists ($path, $className, $actionName) {
    	foreach ( $this->plugins->getAllEnabled() as $plugin ) {
    		if ( method_exists(sprintf($path, str_replace('/','\\',$plugin['package'])) . $className, $actionName) )
    			return sprintf($path, str_replace('/','\\',$plugin['package']));
    	}
    	return false;
    }
    
    private function pluginClassExists ($path, $className) {
    	foreach ( $this->plugins->getAllEnabled() as $plugin ) {
    		if ( class_exists(sprintf($path, str_replace('/','\\',$plugin['package'])) . $className) )
    			return sprintf($path, str_replace('/','\\',$plugin['package']));
    	}
    	return false;
    }

	public function routeAdmin ($f3,$params) {
		$localPath = $this->adminLocalPath;
		$corePath = $this->adminCorePath;
		$pluginPath = $this->adminPluginPath;
		
		// detect local or core base controller
		if ( class_exists($localPath . 'Controller') ) {
			$ctrl = $localPath . 'Controller';
			$ctrl = new $ctrl;
		} else {
			$ctrl = $corePath . 'Controller';
			$ctrl = new $ctrl;
		}
		
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
			if ( method_exists($localPath . $className, $actionName) ) {
				$className = $localPath . $className;
			// check if class and action exists in Plugin namespace
			} else if ( $this->plugins->count() > 0 && ($plugin = $this->pluginMethodExists($pluginPath, $className, $actionName)) ) {
				$className = $plugin . $className;
			// check if class and action exists in Core namespace
			} else if ( method_exists($corePath . $className,$actionName) ) {
				$className = $corePath . $className;
			// throw a 404 if a non-existant action was requested on an existant Class
			} else if ( class_exists($localPath . $className) || class_exists($corePath . $className) || $this->pluginClassExists($pluginPath, $className) ) {
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
			// check if class exists in Plugin namespace
			} else if ( $this->plugins->count() > 0 && ($plugin = $this->pluginClassExists($pluginPath, $className)) ) {
				$className = $plugin . $className;
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
			// check if class and action exists in Plugin namespace
			} else if ( ($plugin = $this->pluginClassExists($pluginPath, $className)) ) {
				$className = $plugin . 'IndexController';
			// check if class and action exists in Core namespace
			} else {
				$className = $corePath . 'IndexController';
			}
		}
		
		if ( $className && $actionName && $f3->exists('PARAMS.id') ) {
			$class = new $className; $class->beforeRoute(); $class->$actionName($f3->get('PARAMS.id'));
		} else if ( $className && $actionName ) {
			$class = new $className; $class->beforeRoute(); $class->$actionName();
		} else if ( $className ) {
			$class = new $className; $class->beforeRoute(); $class->index();
		} else if ( isset($loadWWW) && $loadWWW ) {
			$ctrl->beforeRoute();

			$f3->set('content','app/www/mytcg/'. $f3->get('PARAMS.controller') .'.htm');
			echo Template::instance()->render('app/templates/admin.htm');
		} else {
			$ctrl->beforeRoute();
			$f3->error(404);
		}
		
		$ctrl->afterRoute();
	}
	
	public function route ($f3,$params) {
		$localPath = $this->localPath;
		$corePath = $this->corePath;
		$pluginPath = $this->pluginPath;
		
		// detect local or core base controller
		if ( class_exists($localPath . 'Controller') ) {
			$ctrl = $localPath . 'Controller';
			$ctrl = new $ctrl;
		} else {
			$ctrl = $corePath . 'Controller';
			$ctrl = new $ctrl;
		}
		
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
			$class = new $className; $class->beforeRoute(); $class->$actionName($f3->get('PARAMS.id'));
		} else if ( $className && $actionName ) {
			$class = new $className; $class->beforeRoute(); $class->$actionName();
		} else if ( $className ) {
			$class = new $className; $class->beforeRoute(); $class->index();
		} else if ( isset($loadWWW) && $loadWWW ) {
			$ctrl->beforeRoute();
			$f3->set('content','app/www/'. $f3->get('PARAMS.controller') .'.htm');
			echo Template::instance()->render('app/templates/default.htm');
		} else {
			$ctrl->beforeRoute();
			$f3->error(404);
		}
		
		$ctrl->afterRoute();
	}

}