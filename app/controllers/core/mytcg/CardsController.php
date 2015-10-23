<?php

namespace Controllers\Core\MyTCG;

use Models\Core\Cards;
use Models\Core\Upcoming;
use Template;

class CardsController extends Controller {
	
	private $cards;
	private $upcoming;
	
	function __construct() {
		parent::__construct();
		
		$this->cards = new Cards($this->db);
		$this->upcoming = new Upcoming($this->db);
	}
	
	/************************************* 

		Cards Management - Main Page 
		
	************************************/
	
	public function index()
	{
		// direct form submissions to the appropriate handler
		if($this->f3->exists('POST.new-cards-submit'))
			$this->newCards();
		if($this->f3->exists('POST.edit-cards-submit'))
			$this->editCards();
		if($this->f3->exists('POST.delete-cards-submit'))
			$this->deleteCards();
		
		
		$this->f3->set('cards',$this->cards->all());
		$this->f3->set('upcoming',$this->upcoming->all());
		$decks = $this->f3->merge('upcoming','cards');
		$this->f3->set('decks',$decks);
		
		$this->f3->set('content','app/themes/'.$this->f3->get('admintheme').'/views/mytcg/cards.htm'); 
		echo Template::instance()->render('app/themes/'.$this->f3->get('admintheme').'/templates/admin.htm');
	}
	
	/************************************* 

		Show the Edit Deck form
		
	************************************/
	
	// Since Upcoming decks are stored in a separate table, we have two different routes so we can determine the appropriate table to push changes to.
	// Both routes push data to the same edit() method, which will then save data to the correct table
	
	// ../mytcg/cards/editReleased/{id}
	public function editReleased($id) {
		$this->edit($id,'Released');
	}
	
	// ../mytcg/cards/editUpcoming/{id}
	public function editUpcoming($id) {
		$this->edit($id,'Upcoming');
	}
	
	private function edit($id,$status = 'Released')
	{
		switch($status){
			case "Upcoming":
				$cards = $this->upcoming;
				break;
			case "Released":
				$cards = $this->cards;
				break;
			default:
				$this->f3->error(404);
		}
		
		if ( !$cards->count(array('id=?',$id)) ) { $this->f3->error(404); }
		else {
			$this->f3->set('deck',$cards->read(array('id=?',$id),[])[0]);
			$this->f3->set('status',$status);
			echo Template::instance()->render('app/themes/'.$this->f3->get('admintheme').'/views/mytcg/cards_edit_form.htm');
		}
	}
	
	/************************************* 

		Process New Deck Form! 
		
	************************************/

	private function newCards()
	{
		
		$audit = \Audit::instance();
		$this->f3->scrub($_POST);
		$this->f3->set('SESSION.flash',array());
		
		switch( $this->f3->get('POST.status') ){
			case "Upcoming":
				$cards = $this->upcoming;
				break;
			case "Released":
				$cards = $this->cards;
				break;
			default:
				$this->f3->error(404);
		}
		
		// adjust some values
		if ( $this->f3->get('POST.status') == 'Released' ) {
			if ( !$this->f3->exists('POST.puzzle') )
				$this->f3->set('POST.puzzle','No');
			if ( !$this->f3->exists('POST.masterable') )
				$this->f3->set('POST.masterable','No');
		}
		
		// validate form
		if ( !preg_match("/^[\w\- ]{2,30}$/", $this->f3->get('POST.deckname')) )
			$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'Invalid deck name.'));
		if ( $this->f3->get('POST.status') == 'Released' && !preg_match("/^[\w\-]{2,30}$/", $this->f3->get('POST.filename')) )
			$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'Invalid file name.'));
		if ( !isset($this->f3->get('category')[$this->f3->get('POST.category')]) )
			$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'Invalid category.'));
		if ( $this->f3->get('POST.status') == 'Released' && !preg_match("/^[0-9]+$/", $this->f3->get('POST.count')) )
			$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'Invalid card count value.'));
		if ( $this->f3->get('POST.status') == 'Released' && !preg_match("/^[0-9]+$/", $this->f3->get('POST.worth')) )
			$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'Invalid card worth value.'));
		if ( $this->f3->get('POST.status') == 'Released' && !in_array($this->f3->get('POST.puzzle'),['Yes','No']) )
			$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'Invalid puzzle value.'));
		if ( $this->f3->get('POST.status') == 'Released' && !in_array($this->f3->get('POST.masterable'),['Yes','No']) )
			$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'Invalid masterable value.'));
		
		// process form if there are no errors
		if ( count($this->f3->get('SESSION.flash')) === 0 ) {
			
			// save to db
			if ( $cards->add() ) {
				$this->f3->clear('POST');
				$this->f3->push('SESSION.flash',array('type'=>'success','msg'=>'The new deck has been added successfully!'));
			} else {
				$this->f3->push('SESSION.flash',array('type'=>'danger','msg'=>'There was a problem processing the request. Please try again.'));
			}
		}

	}
	
	/************************************* 

		Process Edit Deck Form! 
		
	************************************/
	
	private function editCards()
	{
		
		$audit = \Audit::instance();
		$this->f3->scrub($_POST);
		$this->f3->set('SESSION.flash',array());
		$statusChange = false;
		
		// determine whether to update in the cards or upcoming table
		switch( $this->f3->get('POST.status') ){
			case "Upcoming":
				$cards = $this->upcoming;
				break;
			case "Released":
				$cards = $this->cards;
				break;
			default:
				$this->f3->error(404);
		}
		
		// are we changing status? then we need to remove the entry from the other table (HELLO terrible database design o/)
		if ( $this->f3->get('POST.status') !== $this->f3->get('POST.original-status') ) {
			$statusChange = true;
		}
		
		// adjust some values
		if ( $this->f3->get('POST.status') == 'Released' ) {
			if ( !$this->f3->exists('POST.puzzle') )
				$this->f3->set('POST.puzzle','No');
			if ( !$this->f3->exists('POST.masterable') )
				$this->f3->set('POST.masterable','No');
		}
		
		// validate form
		if ( $this->f3->get('POST.status') == 'Released' && !preg_match("/^[\w\-]{2,30}$/", $this->f3->get('POST.filename')) )
			$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'Invalid file name.'));
		if ( !isset($this->f3->get('category')[$this->f3->get('POST.category')]) )
			$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'Invalid category.'));
		if ( $this->f3->get('POST.status') == 'Released' && !preg_match("/^[0-9]+$/", $this->f3->get('POST.count')) )
			$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'Invalid card count value.'));
		if ( $this->f3->get('POST.status') == 'Released' && !preg_match("/^[0-9]+$/", $this->f3->get('POST.worth')) )
			$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'Invalid card worth value.'));
		if ( $this->f3->get('POST.status') == 'Released' && !in_array($this->f3->get('POST.puzzle'),['Yes','No']) )
			$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'Invalid puzzle value.'));
		if ( $this->f3->get('POST.status') == 'Released' && !in_array($this->f3->get('POST.masterable'),['Yes','No']) )
			$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'Invalid masterable value.'));
		
		// process form if there are no errors
		if ( count($this->f3->get('SESSION.flash')) === 0 ) {
			// save to db
			if ( ( $statusChange == true && $cards->add() ) || ( $statusChange == false && $cards->edit($this->f3->get('POST.id')) ) ) {
				$this->f3->push('SESSION.flash',array('type'=>'success','msg'=>'Deck information updated successfully!'));
				
				if ( $statusChange ) {
					// if status was changed, delete entry from other table
					switch( $this->f3->get('POST.original-status') ){
						case "Upcoming":
							$this->upcoming->delete($this->f3->get('POST.id'));
							break;
						case "Released":
							$this->cards->delete($this->f3->get('POST.id'));
							break;
						default:
							$this->f3->error(404);
					}
				}
				
			} else {
				$this->f3->push('SESSION.flash',array('type'=>'danger','msg'=>'There was a problem processing the request. Please try again.'));
			}
		}
	}
	
	/************************************* 

		Delete a Deck! 
		
	************************************/
	
	private function deleteCards()
	{
		
		$audit = \Audit::instance();
		$this->f3->scrub($_POST);
		$this->f3->set('SESSION.flash',array());
		
		// determine whether to update the cards or upcoming table
		switch( $this->f3->get('POST.status') ){
			case "Upcoming":
				$cards = $this->upcoming;
				break;
			case "Released":
				$cards = $this->cards;
				break;
			default:
				$this->f3->error(404);
		}
		
		// process form if there are no errors
		if ( count($this->f3->get('SESSION.flash')) === 0 ) {
			// delete record
			if ( $cards->delete($this->f3->get('POST.id')) ) {
				$this->f3->push('SESSION.flash',array('type'=>'success','msg'=>'Deck record removed successfully!'));
			} else {
				$this->f3->push('SESSION.flash',array('type'=>'danger','msg'=>'There was a problem processing the request. Please try again.'));
			}
		}
	}
	
}