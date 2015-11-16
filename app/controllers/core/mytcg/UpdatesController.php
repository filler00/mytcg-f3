<?php

namespace Controllers\Core\MyTCG;

use Template;

class UpdatesController extends Controller {
	
	public function index()
	{
		$this->f3->set('content','app/themes/'.$this->f3->get('admintheme').'/views/mytcg/updates.htm');
		echo Template::instance()->render('app/themes/'.$this->f3->get('admintheme').'/templates/admin.htm');
	}
	
	public function version()
	{
		$head = $this->f3->read('.git/refs/heads/master');
	}
	
}