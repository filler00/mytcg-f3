<?php

namespace Controllers\Core\MyTCG;

use Template;
use Markdown;

class UpdatesController extends Controller {

	protected $md;
	
	function __construct()
	{
		parent::__construct();
		$this->md = Markdown::instance();
	}
	
	public function index()
	{
		// direct form submissions to the appropriate handler
		if($this->f3->exists('POST.install'))
			$this->install();
			
		$this->f3->set('feed', $this->releases->feed());
		
		$releaseNotes = $this->md->convert( $this->f3->get('version.current.body') );
		$this->f3->set('releaseNotes', $releaseNotes);
		
		$this->f3->set('content','app/themes/'.$this->f3->get('admintheme').'/views/mytcg/updates.htm');
		echo Template::instance()->render('app/themes/'.$this->f3->get('admintheme').'/templates/admin.htm');
	}
	
	protected function install()
	{
		$audit = \Audit::instance();
		$this->f3->scrub($_POST);
		$this->f3->set('SESSION.flash',array());
		
		if ( !$this->f3->exists('POST.tag') || $this->f3->get('POST.tag') === '' )
			$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'Invalid request. Please try again.'));
			
		// process form if there are no errors
		if ( count($this->f3->get('SESSION.flash')) === 0 ) {
			$this->releases->install($this->f3->get('POST.tag'));
		}
	}
	
}