<?php
/*
	MyTCG Player Activity Logger
	Logs saved in: /logs/[UserId].log
*/

namespace Filler00;

use Base;

class Logger {
	
	protected $f3;
	
	function __construct() {
		$f3 = Base::instance();
		$this->f3 = $f3;
	}
	
	public function push ($id,$logs)
	{
		// if logs exist, prepend new logs
		if ( $this->f3->read("storage/logs/$id.log") )
			$logs = $logs . "<br>" . $this->f3->read("logs/$id.log");

		( $this->f3->write("storage/logs/$id.log",$logs) ? true : false );
	}
}