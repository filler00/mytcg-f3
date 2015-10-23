<?php

namespace Controllers\Core;

use Models\Core\Affiliates;
use Filler00\Mailer;
use Template;

class AffiliatesController extends Controller {
	
	public function index()
	{
		$affiliates = new Affiliates($this->db);
		$this->f3->set('affiliates',$affiliates->read(array('status=?','Active'),[]));
		$this->f3->set('content','app/themes/'.$this->f3->get('theme').'/views/affiliates.htm');
		echo Template::instance()->render('app/themes/'.$this->f3->get('theme').'/templates/default.htm');
	}
	
	public function request()
	{
		$affiliates = new Affiliates($this->db);
		if($this->f3->exists('POST.request'))
		{
			$audit = \Audit::instance();
			$this->f3->scrub($_POST);
			$this->f3->set('SESSION.flash',array());
			
			// validate form
			if ( !preg_match("/^[\w\- ]{2,30}$/", $this->f3->get('POST.name')) )
				$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'Invalid name.'));
			if ( !$audit->email($this->f3->get('POST.email'), FALSE) )
				$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'Invalid email address'));
			if ( !preg_match("/^.{2,30}$/", $this->f3->get('POST.tcgname')) )
				$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'Invalid TCG Name.'));
			if ( !$audit->url($this->f3->get('POST.url')) )
				$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'Invalid URL.'));
			if ( !$audit->url($this->f3->get('POST.button')) )
				$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'Invalid button URL.'));
			
			// process form if there are no errors
			if ( count($this->f3->get('SESSION.flash')) === 0 ) {
				$this->f3->set('POST.status','Pending');
				
				$mailer = new Mailer;
				$message = $mailer->message()
					->setSubject($this->f3->get('tcgname') . ': Affiliation Request')
					->setFrom(array($this->f3->get('noreplyemail') => 'MyTCG'))
					->setTo(array($this->f3->get('tcgemail')))
					->setReplyTo(array($this->f3->get('POST.email')))
					->setBody(Template::instance()->render('app/themes/'.$this->f3->get('theme').'/templates/emails/affiliation.htm'), 'text/html')
					;
				
				// send email & save to db
				if ( $mailer->send($message) && $affiliates->add() ) {
					$this->f3->push('SESSION.flash',array('type'=>'success','msg'=>'Your affiliation request has been sent successfully!'));
				} else {
					$this->f3->push('SESSION.flash',array('type'=>'danger','msg'=>'There was a problem processing your request. Please try again or contact us for assistance!'));
				}
			}
			
		}
		
		$this->f3->reroute('/affiliates');
	}
	
}