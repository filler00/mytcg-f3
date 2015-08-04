<?php

namespace Controllers\Core\MyTCG;

use Models\Core\Members;
use Models\Core\Cards;
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
		
		$this->f3->set('content','app/views/mytcg/members.htm');
		echo Template::instance()->render('app/templates/admin.htm');
	}
	public function edit($id='')
	{
		/***********************************
		Edit form
		************************************/
		$this->f3->scrub($_POST);
		$members = new Members($this->db);
		$this->f3->set('member',$members->read(array('id=?',$id),[])[0]);
		$this->f3->set('SESSION.flash',array());
		$this->f3->set('status',array('Active','Hiatus'));

		$cards = new Cards($this->db);
		$this->f3->set('decks',$cards->allAlpha());

		$this->f3->set('months',array('Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'));
		
		// form submitted
		if($this->f3->exists('POST.edit')) {
			$audit = \Audit::instance();
			// validate form
			if ( !preg_match("/^[\w\-]{2,30}$/", $this->f3->get('POST.name')) )
				$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'Invalid name. Only letters, numbers, underscores (_), and dashes (-) are allowed.'));
			if ( !$audit->email($this->f3->get('POST.email'), FALSE) )
				$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'Invalid email address'));
			if ( !$audit->url($this->f3->get('POST.url')) )
				$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'Invalid trade post URL.'));
			if ( !in_array($this->f3->get('POST.birthday'),$this->f3->get('months')) )
				$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'Invalid birthday'));
			if ( $cards->count(array('id=?',$this->f3->get('POST.collecting'))) == 0 )
				$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'Invalid collecting deck.'));
			if ( $this->f3->get('member')->status !== 'Pending' && !in_array($this->f3->get('POST.status'),$this->f3->get('status')) )
				$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'Invalid status.'));

			// if there are no errors, process the form
			if ( count($this->f3->get('SESSION.flash')) === 0 ) {
				$this->f3->set('collectingID',$this->f3->get('POST.collecting'));
				$this->f3->set('POST.collecting',$cards->getById($this->f3->get('POST.collecting'))->filename);

				if($members->edit($this->f3->get('POST.id'))) {
					$this->f3->push('SESSION.flash',array('type'=>'success','msg'=>'Member '.$this->f3->get('POST.name').' edited!'));
					$this->f3->reroute('/mytcg/members');
				} else {
					$this->f3->push('SESSION.flash',array('type'=>'danger','msg'=>'There was a problem processing your request. Please try again!'));
				}
			}

		}
		$this->f3->set('content','app/views/mytcg/members_edit.htm');
		echo Template::instance()->render('app/templates/admin.htm');
	}
	public function activate($id='')
	{
		/***********************************
		Activate pending members
		************************************/
		$this->f3->scrub($_POST);
		$members = new Members($this->db);
		$this->f3->set('member',$members->read(array('id=?',$id),[])[0]);
		$this->f3->set('SESSION.flash',array());

		if($this->f3->get('member')->status != 'Active') {
			$this->f3->set('POST.status','Active');
			$members->edit($this->f3->get('member')->id);
			$this->f3->push('SESSION.flash',array('type'=>'success','msg'=>'Member '.$this->f3->get('member')->name.' approved!'));
		}
		$this->f3->reroute('/mytcg/members');
		echo Template::instance()->render('app/templates/admin.htm');

	}
	public function delete($id='')
	{
		/***********************************
		Delete members
		************************************/
		$members = new Members($this->db);
		$this->f3->set('member',$members->read(array('id=?',$id),[])[0]);
		$this->f3->set('SESSION.flash',array());

		if($_SERVER['QUERY_STRING'] == "execute") {
			$members->delete($this->f3->get('member')->id);
			$this->f3->push('SESSION.flash',array('type'=>'success','msg'=>'Member '.$this->f3->get('member')->name.' deleted!'));
			$this->f3->reroute('/mytcg/members');
		} 
		$this->f3->set('content','app/views/mytcg/members_delete.htm');
		echo Template::instance()->render('app/templates/admin.htm');

	}
}
