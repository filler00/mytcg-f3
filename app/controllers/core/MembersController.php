<?php

namespace Controllers\Core;

use Models\Core\Members;
use Models\Core\Cards;
use Filler00\Mailer;
use Template;

class MembersController extends Controller {
	public function index()
	{
		$members = new Members($this->db);
		
		$this->f3->set('levels',array());
		foreach ( $this->f3->get('level') as $index => $lvl ) {
			$this->f3->set('levels['.$index.']',$members->getActiveByLvl($index));
		}
		$this->f3->set('pending',$members->getByStatus('pending'));
		$this->f3->set('hiatus',$members->getByStatus('hiatus'));
		$this->f3->set('content','app/views/members.htm');
		echo Template::instance()->render('app/templates/default.htm');
	}
	public function profile($id)
	{
		$members = new Members($this->db);
		if ( !$members->count(array('name=?',$id)) ) { $this->f3->error(404); }
		else {
			$this->f3->set('member',$members->read(array('name=?',$id),[])[0]); 
			$this->f3->set('content','app/views/profile.htm'); 
			echo Template::instance()->render('app/templates/default.htm');
		}
	}
	public function login()
	{
		$this->f3->set('SESSION.flash',array());
		
		if ( $this->f3->exists('SESSION.userID') ) {
			$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'You are already logged in!'));
			$this->f3->reroute('/'); 
		}
		
		$members = new Members($this->db);
		
		if($this->f3->exists('POST.login'))
		{
			$this->f3->scrub($_POST);
			if ( $this->auth->login($this->f3->get('POST.username'),$this->f3->get('POST.password')) ) {
				$this->f3->set('SESSION.userID',$members->getByName($this->f3->get('POST.username'))->id);
				$this->f3->set('SESSION.userName',$members->getByName($this->f3->get('POST.username'))->name);
				$this->f3->push('SESSION.flash',array('type'=>'success','msg'=>'Welcome back, ' . $this->f3->get('SESSION.userName') . '!'));
				$this->f3->reroute('/');
			} else {
				$this->f3->push('SESSION.flash',array('type'=>'danger','msg'=>'Authentication failed. Please try again or contact us for assistance.'));
			}
		}
		$this->f3->set('content','app/views/login.htm'); 
		echo Template::instance()->render('app/templates/default.htm');
	}
	public function logout()
	{
		$this->f3->clear('SESSION');
		$this->f3->set('SESSION.flash',array());
		$this->f3->push('SESSION.flash',array('type'=>'success','msg'=>'You have been logged out!'));
		$this->f3->reroute('/');
	}
	public function lostpass()
	{
		
		if($this->f3->exists('POST.lostpass'))
		{
			$audit = \Audit::instance();
			$this->f3->scrub($_POST);
			$this->f3->set('SESSION.flash',array());
			$members = new Members($this->db);
			
			// validate form
			if ( !$audit->email($this->f3->get('POST.email'), FALSE) )
				$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'Invalid email address'));
			if ( $members->count(array('email=?',$this->f3->get('POST.email'))) == 0 )
				$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'Couldn\'t find an account associated with that email address.'));
				
			if ( count($this->f3->get('SESSION.flash')) === 0 ) {
				// generate random password
				$this->f3->set('password',md5(time()));
				$this->f3->set('POST.password',password_hash($this->f3->get('password'), PASSWORD_DEFAULT));
				
				$mailer = new Mailer;
				$message = $mailer->message()
					->setSubject($this->f3->get('tcgname') . ': Password Reset')
					->setFrom(array($this->f3->get('noreplyemail') => $this->f3->get('tcgname')))
					->setTo(array($this->f3->get('POST.email')))
					->setReplyTo(array($this->f3->get('tcgemail')))
					->setBody(Template::instance()->render('app/templates/emails/pwreset.htm'), 'text/html')
					;
				
				// save new password and email to member
				if ( $members->edit($members->read(array('email=?',$this->f3->get('POST.email')),[])[0]->id,array('password')) && $mailer->send($message) ) {
					$this->f3->push('SESSION.flash',array('type'=>'success','msg'=>'Your password has been reset! Please check your email.'));
				} else {
					$this->f3->push('SESSION.flash',array('type'=>'danger','msg'=>'Password reset failed. Please try again or contact us for assistance.'));
				}
			}
		}
		$this->f3->set('content','app/views/lostpass.htm'); 
		echo Template::instance()->render('app/templates/default.htm');
	}
	public function settings() {
		if ( $this->f3->exists('SESSION.userID') ) {
		
			$cards = new Cards($this->db);
			$members = new Members($this->db);
			$this->f3->set('status',array('Active','Hiatus'));
			
			$this->f3->set('decks',$cards->allAlpha());
			$this->f3->set('member',$members->read(array('id=?',$this->f3->get('SESSION.userID')),[])[0]);
			
			if($this->f3->exists('POST.update'))
			{
				$audit = \Audit::instance();
				$this->f3->scrub($_POST);
				$this->f3->set('SESSION.flash',array());
				
				// validate form
				if ( !$audit->email($this->f3->get('POST.email'), FALSE) )
					$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'Invalid email address.'));
				if ( $this->f3->get('POST.email') != $this->f3->get('member')->email && $members->count(array('email=?',$this->f3->get('POST.email'))) != 0 )
					$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'Your email address is already in use by another player.'));
				if ( !$audit->url($this->f3->get('POST.url')) )
					$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'Invalid trade post URL.'));
				if ( $this->f3->get('POST.password') !== '' && !preg_match("/^.{6,}$/", $this->f3->get('POST.password')) )
					$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'Your password must contain at least 6 characters.'));
				if ( $this->f3->get('POST.password') !== '' && $this->f3->get('POST.password') !== $this->f3->get('POST.password2') )
					$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'Your passwords don\'t match!'));
				if ( $this->f3->get('member')->status !== 'Pending' && !in_array($this->f3->get('POST.status'),$this->f3->get('status')) )
					$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'Invalid status.'));
				if ( $cards->count(array('id=?',$this->f3->get('POST.collecting'))) == 0 )
					$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'Invalid collecting deck.'));
				if ( !preg_match("/^.{0,875}$/",$this->f3->get('POST.biography')) || !preg_match("/^.{0,875}$/",$this->f3->get('POST.wishlist')) )
					$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'Please limit your Profile details to 875 characters.'));
				
				// process form if there are no errors
				if ( count($this->f3->get('SESSION.flash')) === 0 ) {
					if ( $this->f3->get('member')->status == 'Pending' ) // If they're pending, don't let them change their status!
						$this->f3->set('POST.status','Pending');
					if ( $this->f3->exists('POST.password') && $this->f3->get('POST.password') != '' ) { // if password was changed, hash it
						$this->f3->set('POST.password',password_hash($this->f3->get('POST.password'), PASSWORD_DEFAULT));
					} else { $this->f3->clear('POST.password'); }
					
					$this->f3->set('collectingID',$this->f3->get('POST.collecting'));
					$this->f3->set('POST.collecting',$cards->getById($this->f3->get('POST.collecting'))->filename);

					// update settings in db
					if ( $members->edit($this->f3->get('SESSION.userID'),array('email','url','status','password','level','collecting','wishlist','biography')) ) {
						$this->f3->push('SESSION.flash',array('type'=>'success','msg'=>'Your settings have been updated!'));
						$this->f3->set('member',$members->read(array('id=?',$this->f3->get('SESSION.userID')),[])[0]);
					} else {
						$this->f3->push('SESSION.flash',array('type'=>'danger','msg'=>'There was a problem processing your request. Please try again or contact us for assistance!'));
					}
				}
			}
			
			$this->f3->set('content','app/views/settings.htm');
			echo Template::instance()->render('app/templates/default.htm');
			
		} else {
			$this->f3->reroute('/members/login');
		}
	}
	public function logs()
	{
		if ( $this->f3->exists('SESSION.userID') ) {
		
			$members = new Members($this->db);
			$this->f3->set('member',$members->read(array('id=?',$this->f3->get('SESSION.userID')),[])[0]);
			$this->f3->set('content','app/views/logs.htm'); 
			echo Template::instance()->render('app/templates/default.htm');
			
		} else {
			$this->f3->reroute('/members/login');
		}
	}
}