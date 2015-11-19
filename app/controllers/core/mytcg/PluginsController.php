<?php

namespace Controllers\Core\MyTCG;

use Template;
use Models\Core\Plugins;

class PluginsController extends Controller {
	
	private $plugins;
	
	function __construct() {
		parent::__construct();
		
		$this->plugins = new Plugins($this->jig);
	}
	
	public function index()
	{
		// direct form submissions to the appropriate handler
		if($this->f3->exists('POST.add-plugin-submit'))
			$this->addPlugin();
			
		$this->f3->set('plugins', $this->plugins->all());
		
		$this->f3->set('content','app/themes/'.$this->f3->get('admintheme').'/views/mytcg/plugins.htm');
		echo Template::instance()->render('app/themes/'.$this->f3->get('admintheme').'/templates/admin.htm');
	}
	
	/************************************* 

		Plugins - Add Form 
		
	************************************/
	
	public function add()
	{
		$this->f3->set('registry', $this->plugins->registry());
		$this->f3->set('installed', $this->plugins->listPackages());
		
		echo Template::instance()->render('app/themes/'.$this->f3->get('admintheme').'/views/mytcg/plugins_add_form.htm');
	}
	
	/************************************* 

		Process Add Plugin Form! 
		
	************************************/

	private function addPlugin()
	{
		
		$audit = \Audit::instance();
		$this->f3->scrub($_POST);
		$this->f3->set('SESSION.flash',array());
		
		// process form if > 0 plugins have been selected
		if ( $this->f3->exists('POST.plugins') && count($this->f3->get('POST.plugins')) > 0 ) {
			
			foreach ( $this->f3->get('POST.plugins') as $package ) {
				
				// validate plugin
				if ( $this->plugins->getPackage($package) !== false )
					$this->f3->push('SESSION.flash', array('type'=>'warning','msg'=>'"'. $package .'" is already installed. Skipping.'));
				else if ( !$config = $this->plugins->getRemoteConfig($package) )
					$this->f3->push('SESSION.flash', array('type'=>'danger','msg'=>'"'. $package .'" could not be installed. (missing mytcg.json config file)'));
				else if ( !isset($config['name']) || !isset($config['author']) || !isset($config['version']) || !isset($config['description']) )
					$this->f3->push('SESSION.flash', array('type'=>'danger','msg'=>'"'. $package .'" could not be installed. (invalid mytcg.json config file)'));
				
				// process install if there are no errors
				if ( count($this->f3->get('SESSION.flash')) === 0 ) {
					
					if ( $this->plugins->install($package, $this->plugins) )
						$this->f3->push('SESSION.flash', array('type'=>'success','msg'=>'"'. $package .'" has been installed successfully!'));
					else
						$this->f3->push('SESSION.flash', array('type'=>'danger','msg'=>'"'. $package .'" could not be installed.'));
				
				}
				
			}
			
			
		}

	}
	
}