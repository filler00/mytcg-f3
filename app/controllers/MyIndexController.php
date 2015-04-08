<?php
class MyIndexController extends AdminController {
	public function index()
	{
		$this->f3->set('content','app/views/mytcg/index.htm');
		echo Template::instance()->render('app/templates/admin.htm');
	}
}