<?php

namespace Controllers\Core;

use Template;

class IndexController extends Controller {
	public function index()
	{
		$this->f3->set('content','app/views/index.htm');
		echo Template::instance()->render('app/templates/default.htm');
	}
}