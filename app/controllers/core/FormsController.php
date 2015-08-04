<?php

namespace Controllers\Core;

use Models\Core\Members;
use Models\Core\Cards;
use Filler00\Mailer;
use Filler00\Logger;
use \Template;

class FormsController extends Controller {

	public function index()
	{
		if ( $this->f3->exists('SESSION.userID') ) {
		
			$members = new Members($this->db);
			$cards = new Cards($this->db);
			$this->f3->set('member',$members->read(array('id=?',$this->f3->get('SESSION.userID')),[])[0]);
			$this->f3->set('decks',$cards->allAlpha());
			
			// Pending members can't fill out forms yet! Send them back to the home page~
			if ( $this->f3->get('member')->status == 'Pending' ) {
				$this->f3->set('SESSION.flash',array());
				$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'Please wait for your account to be approved before submitting forms!'));
				$this->f3->reroute('/');
			}
			
			if($this->f3->exists('POST.levelup'))
				$this->levelup();
			if($this->f3->exists('POST.master'))
				$this->master();
			if($this->f3->exists('POST.doubles'))
				$this->doubles();
			if($this->f3->exists('POST.mcrequest'))
				$this->mcrequest();
			if($this->f3->exists('POST.quit'))
				$this->quit();
		
			$this->f3->set('content','app/views/forms.htm');
			echo Template::instance()->render('app/templates/default.htm');
		
		} else {
			// Not logged in?
			$this->f3->reroute('/members/login');
		}
	}
	private function levelup()
	{
		/***********************************
		Process Level Up Form! 
		************************************/
		$this->f3->scrub($_POST);
		$members = new Members($this->db);
		$cards = new Cards($this->db);
		$this->f3->set('member',$members->read(array('id=?',$this->f3->get('SESSION.userID')),[])[0]);
		$this->f3->set('SESSION.flash',array());
		
		// validate form
		if ( $this->f3->get('member')->level >= count($this->f3->get('level')) )
			$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'You\'ve reached the max level for this TCG!'));
		$i = 0; foreach ( $this->f3->get('POST.choiceDeck') as $deck ) { $num = $i + 1;
			if ( $cards->count(array('id=?',$deck)) == 0 )
				$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'Choice card #'. $num .' is invalid.'));
			if ( $cards->read(array('id=?',$deck),[])[0]['count'] < intval($this->f3->get('POST.choiceNum['.$i.']')) )
				$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'Choice card #'. $num .' is not a valid number.'));
			$i++;
		}
		
		// if there are no errors, process the form
		if ( count($this->f3->get('SESSION.flash')) === 0 ) {
			$this->f3->set('POST.level',$this->f3->get('member')->level + 1);
			
			$mailer = new Mailer;
			$message = $mailer->message()
			->setSubject($this->f3->get('tcgname') . ': ' . $this->f3->get('member')->name . ' Leveled Up!')
			->setFrom(array($this->f3->get('noreplyemail') => 'MyTCG'))
			->setTo(array($this->f3->get('tcgemail')))
			->setReplyTo(array($this->f3->get('member')->email))
			->setBody(Template::instance()->render('app/templates/emails/levelup-notif.htm'), 'text/html')
			;
			
			if ( $mailer->send($message) && $members->edit($this->f3->get('SESSION.userID'),array('level')) ) {
			
				$this->f3->set('member',$members->read(array('id=?',$this->f3->get('SESSION.userID')),[])[0]);
				$this->f3->set('rewardType','Level Up');
				
				// Generate rewards!
				$this->f3->set('rewards',array());
				// choice cards
				for ( $i = 0; $i < $this->f3->get('num_lvlchoice'); $i++ ) { $this->f3->push('rewards',$cards->read(array('id=?',$this->f3->get('POST.choiceDeck['.$i.']')),[])[0]->filename . str_pad($this->f3->get('POST.choiceNum['.$i.']'), 2, "0", STR_PAD_LEFT)); }
				// random regular cards
				for ( $i = 0; $i < $this->f3->get('num_lvlreg'); $i++ ) { $this->f3->push('rewards',$cards->random(array('worth=?',1))); }
				// random special cards
				for ( $i = 0; $i < $this->f3->get('num_lvlspc'); $i++ ) { $this->f3->push('rewards',$cards->random(array('worth=?',2))); }

				$log = '['. date("D, d M Y H:i:s") .'] <strong>Level Up ('. $this->f3->get('POST.level') .')</strong>: ' . implode(', ', $this->f3->get('rewards'));
				$logger = new Logger; $logger->push($this->f3->get('SESSION.userID'),$log);
				
				$mailer = new Mailer;
				$message = $mailer->message()
					->setSubject($this->f3->get('tcgname') . ': Level Up')
					->setFrom(array($this->f3->get('noreplyemail') => $this->f3->get('tcgname')))
					->setTo(array($this->f3->get('member')->email))
					->setReplyTo(array($this->f3->get('tcgemail')))
					->setBody(Template::instance()->render('app/templates/emails/levelup.htm'), 'text/html')
					;
				$result = $mailer->send($message);
				
			} else {
				$this->f3->push('SESSION.flash',array('type'=>'danger','msg'=>'There was a problem processing your request. Please try again or contact us for assistance!'));
			}
		}
	}
	private function master()
	{
		/***********************************
		Process Deck Mastery Form! 
		************************************/
		$this->f3->scrub($_POST);
		$members = new Members($this->db);
		$cards = new Cards($this->db);
		$this->f3->set('member',$members->read(array('id=?',$this->f3->get('SESSION.userID')),[])[0]);
		$this->f3->set('SESSION.flash',array());
		
		// validate form
		if ( $cards->count(array('id=?',$this->f3->get('POST.mastered'))) == 0 )
				$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'Invalid mastered deck.'));
		if ( $cards->count(array('id=?',$this->f3->get('POST.collecting'))) == 0 )
				$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'Invalid collecting deck.'));
		$i = 0; foreach ( $this->f3->get('POST.choiceDeck') as $deck ) { $num = $i + 1;
			if ( $cards->count(array('id=?',$deck)) == 0 )
				$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'Choice card #'. $num .' is invalid.'));
			if ( $cards->read(array('id=?',$deck),[])[0]['count'] < intval($this->f3->get('POST.choiceNum['.$i.']')) )
				$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'Choice card #'. $num .' is not a valid number.'));
			$i++;
		}
		
		// if there are no errors, process the form
		if ( count($this->f3->get('SESSION.flash')) === 0 ) {
			$this->f3->set('masteredID',$this->f3->get('POST.mastered'));
			$this->f3->set('POST.mastered',$cards->getById($this->f3->get('POST.mastered'))->filename);
			$this->f3->set('collectingID',$this->f3->get('POST.collecting'));
			$this->f3->set('POST.collecting',$cards->getById($this->f3->get('POST.collecting'))->filename);
			
			$mailer = new Mailer;
			$message = $mailer->message()
			->setSubject($this->f3->get('tcgname') . ': ' . $this->f3->get('member')->name . ' Mastered ' . $this->f3->get('POST.mastered') . '!')
			->setFrom(array($this->f3->get('noreplyemail') => 'MyTCG'))
			->setTo(array($this->f3->get('tcgemail')))
			->setReplyTo(array($this->f3->get('member')->email))
			->setBody(Template::instance()->render('app/templates/emails/mastery-notif.htm'), 'text/html')
			;
			
			if ( $mailer->send($message) && $members->edit($this->f3->get('SESSION.userID'),array('collecting')) ) {
			
				$this->f3->set('member',$members->read(array('id=?',$this->f3->get('SESSION.userID')),[])[0]);
				$this->f3->set('rewardType','Deck Mastery');
				
				// Generate rewards!
				$this->f3->set('rewards',array());
				// choice cards
				for ( $i = 0; $i < $this->f3->get('num_maschoice'); $i++ ) { $this->f3->push('rewards',$cards->read(array('id=?',$this->f3->get('POST.choiceDeck['.$i.']')),[])[0]->filename . str_pad($this->f3->get('POST.choiceNum['.$i.']'), 2, "0", STR_PAD_LEFT)); }
				// random regular cards
				for ( $i = 0; $i < $this->f3->get('num_masreg'); $i++ ) { $this->f3->push('rewards',$cards->random(array('worth=?',1))); }
				// random special cards
				for ( $i = 0; $i < $this->f3->get('num_masspc'); $i++ ) { $this->f3->push('rewards',$cards->random(array('worth=?',2))); }

				$log = '['. date("D, d M Y H:i:s") .'] <strong>Deck Mastery ('. $this->f3->get('POST.mastered') .')</strong>: ' . implode(', ', $this->f3->get('rewards'));
				$logger = new Logger; $logger->push($this->f3->get('SESSION.userID'),$log);
				
				$mailer = new Mailer;
				$message = $mailer->message()
					->setSubject($this->f3->get('tcgname') . ': Mastered ' . $this->f3->get('POST.mastered'))
					->setFrom(array($this->f3->get('noreplyemail') => $this->f3->get('tcgname')))
					->setTo(array($this->f3->get('member')->email))
					->setReplyTo(array($this->f3->get('tcgemail')))
					->setBody(Template::instance()->render('app/templates/emails/mastery.htm'), 'text/html')
					;
				$result = $mailer->send($message);
				
			} else {
				$this->f3->push('SESSION.flash',array('type'=>'danger','msg'=>'There was a problem processing your request. Please try again or contact us for assistance!'));
			}
		}
	}
	private function doubles()
	{
		/***********************************
		Process Doubles Exchange! 
		************************************/
		$this->f3->scrub($_POST);
		$members = new Members($this->db);
		$cards = new Cards($this->db);
		$this->f3->set('member',$members->read(array('id=?',$this->f3->get('SESSION.userID')),[])[0]);
		$this->f3->set('SESSION.flash',array());
		
		// validate form
		if ( $this->f3->get('POST.type') != 'Regular' && $this->f3->get('POST.type') != 'Special' )
				$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'Invalid type.'));
		if ( intval($this->f3->get('POST.number')) > 20 || intval($this->f3->get('POST.number')) < 1 )
				$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'Limit 20 cards per exchange. Please submit the form again if you need more!'));
		
		// if there are no errors, process the form
		if ( count($this->f3->get('SESSION.flash')) === 0 ) {
			
			$mailer = new Mailer;
			$message = $mailer->message()
			->setSubject($this->f3->get('tcgname') . ': ' . $this->f3->get('member')->name . ' Doubles Exchange')
			->setFrom(array($this->f3->get('noreplyemail') => 'MyTCG'))
			->setTo(array($this->f3->get('tcgemail')))
			->setReplyTo(array($this->f3->get('member')->email))
			->setBody(Template::instance()->render('app/templates/emails/doubles-notif.htm'), 'text/html')
			;
			
			if ( $mailer->send($message) ) {
			
				$this->f3->set('member',$members->read(array('id=?',$this->f3->get('SESSION.userID')),[])[0]);
				$this->f3->set('rewardType','Doubles Exchange');
				
				// Generate rewards!
				$this->f3->set('rewards',array());
				// random regular cards
				if ( $this->f3->get('POST.type') == 'Regular' )
					for ( $i = 0; $i < $this->f3->get('POST.number'); $i++ ) { $this->f3->push('rewards',$cards->random(array('worth=?',1))); }
				// random special cards
				if ( $this->f3->get('POST.type') == 'Special' )
					for ( $i = 0; $i < $this->f3->get('POST.number'); $i++ ) { $this->f3->push('rewards',$cards->random(array('worth=?',2))); }

				$log = '['. date("D, d M Y H:i:s") .'] <strong>Doubles Exchange</strong>: ' . implode(', ', $this->f3->get('rewards'));
				$logger = new Logger; $logger->push($this->f3->get('SESSION.userID'),$log);
				
			} else {
				$this->f3->push('SESSION.flash',array('type'=>'danger','msg'=>'There was a problem processing your request. Please try again or contact us for assistance!'));
			}
		}
	}
	private function mcrequest()
	{
		/***********************************
		Process Member Card Request! 
		************************************/
		$this->f3->scrub($_POST);
		$audit = \Audit::instance();
		$members = new Members($this->db);
		$this->f3->set('member',$members->read(array('id=?',$this->f3->get('SESSION.userID')),[])[0]);
		$this->f3->set('SESSION.flash',array());
		
		// validate the form!
		if ( !$audit->url($this->f3->get('POST.image')) )
				$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'Invalid image URL.'));
		
		// if there are no errors, process the form
		if ( count($this->f3->get('SESSION.flash')) === 0 ) {
			
			$mailer = new Mailer;
			$message = $mailer->message()
			->setSubject($this->f3->get('tcgname') . ': Member Card Request (' . $this->f3->get('member')->name . ')')
			->setFrom(array($this->f3->get('noreplyemail') => 'MyTCG'))
			->setTo(array($this->f3->get('tcgemail')))
			->setReplyTo(array($this->f3->get('member')->email))
			->setBody(Template::instance()->render('app/templates/emails/mcrequest-notif.htm'), 'text/html')
			;
			
			if ( $mailer->send($message) ) {
				$this->f3->push('SESSION.flash',array('type'=>'success','msg'=>'Your member card request has been sent!'));
			} else {
				$this->f3->push('SESSION.flash',array('type'=>'danger','msg'=>'There was a problem processing your request. Please try again or contact us for assistance!'));
			}
		}
	}
	private function quit()
	{
		/***********************************
		Process Quit Form! 
		************************************/
		$this->f3->scrub($_POST);
		$members = new Members($this->db);
		$this->f3->set('member',$members->read(array('id=?',$this->f3->get('SESSION.userID')),[])[0]);
		$this->f3->set('SESSION.flash',array());
		
		// nothing to validate!
		
		// if there are no errors, process the form
		if ( count($this->f3->get('SESSION.flash')) === 0 ) {
			
			$mailer = new Mailer;
			$message = $mailer->message()
			->setSubject($this->f3->get('tcgname') . ': Quit Form (' . $this->f3->get('member')->name . ')')
			->setFrom(array($this->f3->get('noreplyemail') => 'MyTCG'))
			->setTo(array($this->f3->get('tcgemail')))
			->setReplyTo(array($this->f3->get('member')->email))
			->setBody(Template::instance()->render('app/templates/emails/quit-notif.htm'), 'text/html')
			;
			
			if ( $mailer->send($message) ) {
				$this->f3->push('SESSION.flash',array('type'=>'success','msg'=>'Your quit form has been sent. Sorry to see you leave - we hope you\'ll change your mind and join us again in the future!'));
			} else {
				$this->f3->push('SESSION.flash',array('type'=>'danger','msg'=>'There was a problem processing your request. Please try again or contact us for assistance!'));
			}
		}
	}
}