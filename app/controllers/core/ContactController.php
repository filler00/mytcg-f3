<?php

namespace Controllers\Core;

use Models\Core\Members;
use Filler00\Mailer;
use Template;

class ContactController extends Controller {

	public function index()
	{
		if ( $this->f3->exists('SESSION.userID') ) {
			$members = new Members($this->db);
			$this->f3->set('member',$members->read(array('id=?',$this->f3->get('SESSION.userID')),[])[0]);
		}
		
		if($this->f3->exists('POST.submit'))
			$this->process();
	
		$this->f3->set('content','app/views/contact.htm');
		echo Template::instance()->render('app/templates/default.htm');
	}
	private function process()
	{
		$this->f3->scrub($_POST);
		$audit = \Audit::instance();
		$this->f3->set('SESSION.flash',array());
		
		// validate form
		if ( !preg_match("/^[\w\- ]{2,30}$/", $this->f3->get('POST.name')) )
			$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'Invalid name.'));
		if ( !$audit->email($this->f3->get('POST.email'), FALSE) )
			$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'Invalid email address'));
		if ( !empty($this->f3->get('POST.url')) && !$audit->url($this->f3->get('POST.url')) )
			$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'Invalid URL.'));
		if ( empty($this->f3->get('POST.message')) )
			$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'Please include a message!'));
		
		// honey pot
		if ( $this->f3->get('POST.username') !== '' )
			$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'Please do not use autofill or similar tools!'));
		
		// if there are no errors, process the form
		if ( count($this->f3->get('SESSION.flash')) === 0 ) {
			$this->f3->set('POST.level',$this->f3->get('member')->level + 1);
			
			$mailer = new Mailer;
			$message = $mailer->message()
			->setSubject($this->f3->get('tcgname') . ': Contact Form')
			->setFrom(array($this->f3->get('noreplyemail') => 'MyTCG'))
			->setTo(array($this->f3->get('tcgemail')))
			->setReplyTo(array($this->f3->get('POST.email')))
			->setBody(Template::instance()->render('app/templates/emails/contact.htm'), 'text/html')
			;
			
			if ( $mailer->send($message) ) {
				$this->f3->push('SESSION.flash',array('type'=>'success','msg'=>'Your form has been sent. Thanks for contacting us!'));
			} else {
				$this->f3->push('SESSION.flash',array('type'=>'danger','msg'=>'There was a problem processing your request. Please try again or contact us for assistance!'));
			}
		}
	}
}