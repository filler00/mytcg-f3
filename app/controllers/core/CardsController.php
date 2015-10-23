<?php

namespace Controllers\Core;

use Models\Core\Cards;
use Models\Core\Upcoming;
use Models\Core\Members;
use Template;

class CardsController extends Controller {
	public function index()
	{
		$cards = new Cards($this->db);

		$this->f3->set('decks',array());
		foreach ( $this->f3->get('category') as $index => $cat ) {
			$this->f3->set('decks['.$index.']',$cards->getByCat($index));
		}
		
		$this->f3->set('content','app/themes/'.$this->f3->get('theme').'/views/cards.htm'); 
		echo Template::instance()->render('app/themes/'.$this->f3->get('theme').'/templates/default.htm');
	}
	public function alpha()
	{
		$cards = new Cards($this->db);

		$this->f3->set('decks',$cards->allAlpha());
		$this->f3->set('content','app/themes/'.$this->f3->get('theme').'/views/cards-alpha.htm'); 
		echo Template::instance()->render('app/themes/'.$this->f3->get('theme').'/templates/default.htm');
	}
	public function view($id='')
	{
		$cards = new Cards($this->db);
		if ( !$cards->count(array('filename=?',$id)) ) { $this->f3->error(404); }
		else {
			$this->f3->set('info',$cards->getByFilename($id));
			$this->f3->set('content','app/themes/'.$this->f3->get('theme').'/views/cards-view.htm'); 
			echo Template::instance()->render('app/themes/'.$this->f3->get('theme').'/templates/default.htm');
		}
	}
	public function members()
	{
		$mem = new Members($this->db);

		$this->f3->set('members',$mem->allWhereMemCards());
		$this->f3->set('content','app/themes/'.$this->f3->get('theme').'/views/cards-members.htm'); 
		echo Template::instance()->render('app/themes/'.$this->f3->get('theme').'/templates/default.htm');
	}
	public function upcoming()
	{
		$upcoming = new Upcoming($this->db);

		$this->f3->set('decks',$upcoming->all());
		$this->f3->set('content','app/themes/'.$this->f3->get('theme').'/views/cards-upcoming.htm'); 
		echo Template::instance()->render('app/themes/'.$this->f3->get('theme').'/templates/default.htm');
	}
}