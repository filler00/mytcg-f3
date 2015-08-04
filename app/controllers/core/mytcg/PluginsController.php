<?php

namespace Controllers\Core\MyTCG;

use Template;

class PluginsController extends Controller {
	
	public function index()
	{
		$this->f3->set('content','app/views/mytcg/plugins.htm');
		echo Template::instance()->render('app/templates/admin.htm');
	}
	
}