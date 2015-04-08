<?php
class AdminController {
	protected $f3;
	protected $db;
	protected $auth;
	function beforeRoute() {
		new Session();
		
		if ( $this->f3->get('PARAMS.controller') != 'login' && !$this->f3->exists('SESSION.adminID') )
			$this->f3->reroute('/mytcg/login');
	}
	function afterRoute() {
		$this->f3->clear('SESSION.flash');
	}
	function __construct() {
		$f3 = Base::instance();
		$db = new DB\SQL(
			'mysql:host=' . $f3->get('db_server') . ';port=3306;dbname=' . $f3->get('db_database'),
			$f3->get('db_user'),
			$f3->get('db_password')
		);
		$user = new \DB\SQL\Mapper($db, 'members');
		$this->auth = new \Auth($user, array('id'=>'name', 'pw'=>'password'));
		$this->f3 = $f3;
		$this->db = $db;
	}
}