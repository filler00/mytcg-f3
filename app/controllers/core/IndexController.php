<?php

namespace Controllers\Core;

use Template;

class IndexController extends Controller {
	public function index()
	{
		$this->f3->set('content','app/themes/'.$this->f3->get('theme').'/views/index.htm');
		echo Template::instance()->render('app/themes/'.$this->f3->get('theme').'/templates/default.htm');
	}
}