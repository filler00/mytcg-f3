<?php

namespace Controllers\Core;

use Models\Core\Cards;
use Models\Core\Members;
use Vendor\Filler00\Mailer;
use Template;

class JoinController extends Controller {

	public function index()
	{
		$cards = new Cards($this->db);
		$members = new Members($this->db);
		$this->f3->set('months',array('Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'));
		
		if($this->f3->exists('POST.join'))
		{
			$audit = \Audit::instance();
			$this->f3->scrub($_POST);
			$this->f3->set('SESSION.flash',array());
			
			// validate form
			if ( !preg_match("/^[\w\-]{2,30}$/", $this->f3->get('POST.name')) )
				$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'Invalid name. Only letters, numbers, underscores (_), and dashes (-) are allowed.'));
			if ( $members->count(array('name=?',$this->f3->get('POST.name'))) != 0 )
				$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'Your name is already taken by another player. Please select a different name and try again!'));
			if ( !$audit->email($this->f3->get('POST.email'), FALSE) )
				$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'Invalid email address'));
			if ( $members->count(array('email=?',$this->f3->get('POST.email'))) != 0 )
				$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'Your email address is already in use by another player.'));
			if ( !$audit->url($this->f3->get('POST.url')) )
				$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'Invalid trade post URL.'));
			if ( !preg_match("/^.{6,}$/", $this->f3->get('POST.password')) )
				$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'Your password must contain at least 6 characters.'));
			if ( $this->f3->get('POST.password') !== $this->f3->get('POST.password2') )
				$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'Your passwords don\'t match!'));
			if ( !in_array($this->f3->get('POST.birthday'),$this->f3->get('months')) )
				$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'Invalid birthday'));
			if ( $cards->count(array('id=?',$this->f3->get('POST.collecting'))) == 0 )
				$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'Invalid collecting deck.'));
			if ( $this->f3->get('POST.refer') !== '' && $members->count(array('name=?',$this->f3->get('POST.refer'))) == 0 )
				$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'Invalid referral - that player\'s name doesn\'t exist in our database. Please check your spelling and try again!'));
				
			// honey pot
			if ( $this->f3->get('POST.username') !== '' )
				$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'Please do not use autofill or similar tools!'));
			
			// process form if there are no errors
			if ( count($this->f3->get('SESSION.flash')) === 0 ) {
				$this->f3->set('POST.status','Pending');
				$this->f3->set('POST.level',1);
				$this->f3->set('POST.membercard','No');
				$this->f3->set('POST.mastered','None');
				$this->f3->set('POST.wishlist','Coming soon.');
				$this->f3->set('POST.biography','Coming soon.');
				$this->f3->set('POST.password',password_hash($this->f3->get('POST.password'), PASSWORD_DEFAULT));
				$this->f3->set('collectingID',$this->f3->get('POST.collecting'));
				$this->f3->set('POST.collecting',$cards->getById($this->f3->get('POST.collecting'))->filename);
				
				$mailer = new Mailer;
				$message = $mailer->message()
					->setSubject($this->f3->get('tcgname') . ': New Member')
					->setFrom(array($this->f3->get('noreplyemail') => 'MyTCG'))
					->setTo(array($this->f3->get('tcgemail')))
					->setReplyTo(array($this->f3->get('POST.email')))
					->setBody(Template::instance()->render('app/templates/emails/newmember.htm'), 'text/html')
					;

				// send email & save to db
				if ( $mailer->send($message) && $members->add() ) {
					
					$this->f3->set('sp',array());
					// random choice cards
					for ( $i = 0; $i < $this->f3->get('num_startchoice'); $i++ ) { $this->f3->push('sp',$cards->random(array('id=?',$this->f3->get('collectingID')))); }
					// random regular cards
					for ( $i = 0; $i < $this->f3->get('num_startreg'); $i++ ) { $this->f3->push('sp',$cards->random(array('worth=?',1))); }
					// random special cards
					for ( $i = 0; $i < $this->f3->get('num_startspc'); $i++ ) { $this->f3->push('sp',$cards->random(array('worth=?',2))); }
					
					$mailer = new Mailer;
					$message = $mailer->message()
						->setSubject($this->f3->get('tcgname') . ': Starter Pack')
						->setFrom(array($this->f3->get('noreplyemail') => $this->f3->get('tcgname')))
						->setTo(array($this->f3->get('POST.email')))
						->setReplyTo(array($this->f3->get('tcgemail')))
						->setBody(Template::instance()->render('app/templates/emails/starterpack.htm'), 'text/html')
						;
					$result = $mailer->send($message);
					
					// load welcome message
					$this->f3->set('content','app/views/welcome.htm');
				} else {
					$this->f3->push('SESSION.flash',array('type'=>'danger','msg'=>'There was a problem processing your request. Please try again or contact us for assistance!'));
				}
			}
		}
		
		if ( !$this->f3->exists('content') ) { $this->f3->set('content','app/views/join.htm'); }
		$this->f3->set('decks',$cards->allAlpha());
		echo Template::instance()->render('app/templates/default.htm');
	}
	
}