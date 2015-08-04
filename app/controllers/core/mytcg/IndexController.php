<?php

namespace Controllers\Core\MyTCG;

use Models\Core\Members;
use Models\Core\Upcoming;
use Template;

class IndexController extends Controller {
	
	public function index()
	{
		$members = new Members($this->db);
		$upcoming = new Upcoming($this->db);

		$this->f3->set('pendingMembers',$members->getByStatus('pending'));
		$this->f3->set('upcomingDecks',$upcoming->all());
		
		$this->f3->set('content','app/views/mytcg/index.htm');
		echo Template::instance()->render('app/templates/admin.htm');
	}
	
}