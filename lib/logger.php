<?php
/*
	MyTCG Player Activity Logger
	Logs saved in: /logs/[UserId].log
*/

class Logger extends Controller {
	public function push ($id,$logs)
	{
		// if logs exist, prepend new logs
		if ( $this->f3->read("logs/$id.log") )
			$logs = $logs . "<br>" . $this->f3->read("logs/$id.log");

		( $this->f3->write("logs/$id.log",$logs) ? true : false );
	}
}