<?php

namespace Controllers\Core\MyTCG;

use \Base;
use \DB\SQL;
use \DB\Jig;
use \Session;

use Models\Core\Releases;

class Controller {

	protected $f3;
	protected $db;
	protected $jig;
	protected $auth;
	protected $releases;

	function __construct() {
		$f3 = Base::instance();

		$this->db = new SQL(
			'mysql:host=' . $f3->get('db_server') . ';port=' . $f3->get('db_port') . ';dbname=' . $f3->get('db_database'),
			$f3->get('db_user'),
			$f3->get('db_password')
		);
		$this->jig = new Jig('storage/jig/');

		$user = new SQL\Mapper($this->db, 'members');
		$this->auth = new \Auth($user, array('id'=>'name', 'pw'=>'password'));
		$this->f3 = $f3;

		$this->releases = new Releases($this->db);
		$this->f3->set('version', $this->releases->version);
	}

	function beforeRoute() {
		new \DB\SQL\Session($this->db);

		if ( $this->f3->get('PARAMS.controller') != 'login' && !$this->f3->exists('SESSION.adminID') )
			$this->f3->reroute('/mytcg/login');
	}

	function afterRoute() {
		$this->f3->clear('SESSION.flash');
	}

}
