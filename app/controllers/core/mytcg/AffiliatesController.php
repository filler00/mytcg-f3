<?php

namespace Controllers\Core\MyTCG;

use Models\Core\Affiliates;
use Template;

class AffiliatesController extends Controller {
	// ../mytcg/affiliates
	public function index()
	{
		$affiliates = new Affiliates($this->db);
		
		if($this->f3->exists('POST.new-affiliate-submit'))
			$this->newAffiliate($affiliates);
		if($this->f3->exists('POST.edit-affiliate-submit'))
			$this->editAffiliate($affiliates);
		if($this->f3->exists('POST.delete-affiliate-submit'))
			$this->deleteAffiliate($affiliates);
		if($this->f3->exists('POST.approve-affiliate-submit'))
			$this->approveAffiliate($affiliates);
		
		foreach ( $this->f3->get('affiliates_status') as $status ) {
			$this->f3->set("affiliates.{$status}",$affiliates->read(array('status=?',$status),[]));	
		}
		$this->f3->set('content','app/themes/'.$this->f3->get('admintheme').'/views/mytcg/affiliates.htm');
		echo Template::instance()->render('app/themes/'.$this->f3->get('admintheme').'/templates/admin.htm');
	}
	
	// ../mytcg/affiliates/edit/{id}
	public function edit($id)
	{
		$affiliates = new Affiliates($this->db);
		
		if ( !$affiliates->count(array('id=?',$id)) ) { $this->f3->error(404); }
		else {
			$this->f3->set('affiliate',$affiliates->read(array('id=?',$id),[])[0]);	
			echo Template::instance()->render('app/themes/'.$this->f3->get('admintheme').'/views/mytcg/affiliates_edit_form.htm');
		}
	}
	
	private function editAffiliate($affiliates)
	{
		/***********************************
		Process Edit Affiliate Form! 
		************************************/
		
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
		if ( !in_array($this->f3->get('POST.status'),$this->f3->get('affiliates_status')) )
			$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'Invalid status.'));
		
		// process form if there are no errors
		if ( count($this->f3->get('SESSION.flash')) === 0 ) {
			
			// save to db
			if ( $affiliates->edit($this->f3->get('POST.id')) ) {
				$this->f3->push('SESSION.flash',array('type'=>'success','msg'=>'Affiliate information updated successfully!'));
			} else {
				$this->f3->push('SESSION.flash',array('type'=>'danger','msg'=>'There was a problem processing the request. Please try again.'));
			}
		}
	}
	
	private function newAffiliate($affiliates)
	{
		/***********************************
		Process New Affiliate Form! 
		************************************/
		
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
		if ( !in_array($this->f3->get('POST.status'),$this->f3->get('affiliates_status')) )
			$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'Invalid status.'));
		
		// process form if there are no errors
		if ( count($this->f3->get('SESSION.flash')) === 0 ) {
			
			// save to db
			if ( $affiliates->add() ) {
				$this->f3->push('SESSION.flash',array('type'=>'success','msg'=>'The new affiliate has been added successfully!'));
			} else {
				$this->f3->push('SESSION.flash',array('type'=>'danger','msg'=>'There was a problem processing the request. Please try again.'));
			}
		}

	}
	
	private function deleteAffiliate($affiliates)
	{
		/***********************************
		Process Delete Affiliate Form! 
		************************************/
		
		$audit = \Audit::instance();
		$this->f3->scrub($_POST);
		$this->f3->set('SESSION.flash',array());
		
		// process form if there are no errors
		if ( count($this->f3->get('SESSION.flash')) === 0 ) {
			// delete record
			if ( $affiliates->delete($this->f3->get('POST.id')) ) {
				$this->f3->push('SESSION.flash',array('type'=>'success','msg'=>'Affiliate record removed successfully!'));
			} else {
				$this->f3->push('SESSION.flash',array('type'=>'danger','msg'=>'There was a problem processing the request. Please try again.'));
			}
		}
	}
	
	private function approveAffiliate($affiliates)
	{
		/***********************************
		Process Approve Affiliate Form! 
		************************************/
		
		$audit = \Audit::instance();
		$this->f3->scrub($_POST);
		$this->f3->set('SESSION.flash',array());
		
		// process form if there are no errors
		if ( count($this->f3->get('SESSION.flash')) === 0 ) {
			$this->f3->set('POST.status','Active');
			
			// save to db
			if ( $affiliates->edit($this->f3->get('POST.id')) ) {
				
				$mailer = new Mailer;
				$message = $mailer->message()
					->setSubject($this->f3->get('tcgname') . ': Affiliation Approved')
					->setFrom(array($this->f3->get('noreplyemail') => $this->f3->get('tcgname')))
					->setTo(array( $affiliates->read(array('id=?', $this->f3->get('POST.id') ),[])[0]->email ))
					->setReplyTo(array($this->f3->get('tcgemail')))
					->setBody(Template::instance()->render('app/themes/'.$this->f3->get('admintheme').'/templates/emails/affiliate-approved.htm'), 'text/html')
					;
					
				// send email & save to db
				if ( $mailer->send($message) )
					$this->f3->push('SESSION.flash',array('type'=>'success','msg'=>'Approval email sent.'));
				
				$this->f3->push('SESSION.flash',array('type'=>'success','msg'=>'Affiliate approved!'));
				
			} else {
				$this->f3->push('SESSION.flash',array('type'=>'danger','msg'=>'There was a problem processing the request. Please try again.'));
			}
		}
	}
	
}