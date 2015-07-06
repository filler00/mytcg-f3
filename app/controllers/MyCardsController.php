<?php
class MyCardsController extends AdminController {
	public function index()
	{
		$cards = new Cards($this->db);

		$this->f3->set('decks',array());
		foreach ( $this->f3->get('category') as $index => $cat ) {
			$this->f3->set('decks['.$index.']',$cards->getByCat($index));
		}
		
		$this->f3->set('content','app/views/mytcg/cards.htm');
		echo Template::instance()->render('app/templates/admin.htm');
	}
	public function add()
	{
		/***********************************
		Add form
		************************************/
		$this->f3->scrub($_POST);
		$cards = new Cards($this->db);
		$this->f3->set('SESSION.flash',array());
		if ( $this->f3->exists('POST.add') ) {
			// validate form
			if ( !preg_match("/^[\w\-]{2,30}$/", $this->f3->get('POST.filename')) )
				$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'Invalid name. Only letters, numbers, underscores (_), and dashes (-) are allowed.'));
			if ( $cards->count(array('filename=?',$this->f3->get('POST.filename'))) != 0 )
				$this->f3->push('SESSION.flash',array('type'=>'danger','msg'=>'Filename already exists!'));
			if ( $cards->count(array('deckname=?',$this->f3->get('POST.deckname'))) != 0 )
				$this->f3->push('SESSION.flash',array('type'=>'danger','msg'=>'Deck name already exists!'));

			// if there are no errors, process the form
			if ( count($this->f3->get('SESSION.flash')) === 0 ) {
				$this->f3->set('masters','None');

				if($cards->add()) {
					$this->f3->push('SESSION.flash',array('type'=>'success','msg'=>'Deck '.$this->f3->get('POST.filename').' added!'));
					$this->f3->reroute('/mytcg/cards');
				} else {
					$this->f3->push('SESSION.flash',array('type'=>'danger','msg'=>'There was a problem processing your request. Please try again!'));
				}
			}
		}
		$this->f3->set('content','app/views/mytcg/cards_add.htm');
		echo Template::instance()->render('app/templates/admin.htm');

	}
	public function edit($id='')
	{
		/***********************************
		Edit form
		************************************/
		$this->f3->scrub($_POST);
		$cards = new Cards($this->db);
		$this->f3->set('deck',$cards->read(array('id=?',$id),[])[0]);
		$this->f3->set('SESSION.flash',array());
		if ( $this->f3->exists('POST.edit') ) {
			// validate form
			if ( !preg_match("/^[\w\-]{2,30}$/", $this->f3->get('POST.filename')) )
				$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'Invalid name. Only letters, numbers, underscores (_), and dashes (-) are allowed.'));

			// if there are no errors, process the form
			if ( count($this->f3->get('SESSION.flash')) === 0 ) {
				if($cards->edit($id)) {
					$this->f3->push('SESSION.flash',array('type'=>'success','msg'=>'Deck '.$this->f3->get('POST.filename').' edited!'));
					$this->f3->reroute('/mytcg/cards');
				} else {
					$this->f3->push('SESSION.flash',array('type'=>'danger','msg'=>'There was a problem processing your request. Please try again!'));
				}
			}
		}
		$this->f3->set('content','app/views/mytcg/cards_edit.htm');
		echo Template::instance()->render('app/templates/admin.htm');
	}
	public function delete($id='')
	{
		/***********************************
		Delete form
		************************************/
		$cards = new Cards($this->db);
		$this->f3->set('deck',$cards->read(array('id=?',$id),[])[0]);
		$this->f3->set('SESSION.flash',array());

		if($_SERVER['QUERY_STRING'] == "execute") {
			$cards->delete($this->f3->get('deck')->id);
			$this->f3->push('SESSION.flash',array('type'=>'success','msg'=>$this->f3->get('deck')->filename.' deck deleted!'));
			$this->f3->reroute('/mytcg/cards');
		} 
		$this->f3->set('content','app/views/mytcg/cards_delete.htm');
		echo Template::instance()->render('app/templates/admin.htm');
	}
}
