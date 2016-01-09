<?php

namespace Controllers\Core;

use \Base;
use \DB\SQL;
use \DB\Jig;
use \Session;

class Controller {

	protected $f3;
	protected $db;
	protected $jig;
	protected $auth;

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

	}

	function beforeRoute() {
		new \DB\SQL\Session($this->db);

		// uncomment to make the user object available on each page when the user is signed in
		/*
		if ( $this->f3->exists('SESSION.userID')  ) {
			$members = new Members($this->db);
			$this->f3->set('user',$members->read(array('id=?', $this->f3->get('SESSION.userID') ),[])[0]);
		}
		*/
	}

	function afterRoute() {
		$this->f3->clear('SESSION.flash');
	}

}
