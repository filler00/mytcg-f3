<?php

namespace Controllers\Core;

use Web;

class MinifyController extends Controller {
	
	public function js() {
		
		$files = str_replace('../','',$_GET['files']); // close potential hacking attempts  
		echo Web::instance()->minify($files);
		
	}
	
	public function css() {
		
		$files = str_replace('../','',$_GET['files']); // close potential hacking attempts  
		echo Web::instance()->minify($files);
		
	}
	
}