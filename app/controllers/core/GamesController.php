<?php

namespace Controllers\Core;

use Models\Core\Games;
use Template;

class GamesController extends Controller {
	public function index()
	{
		$games = new Games($this->db);

		$this->f3->set('games',array());
		foreach ( $this->f3->get('gamecat') as $index => $cat ) {
			$this->f3->set('games['.$index.']',$games->getByCat($index));
		}
		
		$this->f3->set('content','app/views/games.htm'); 
		echo Template::instance()->render('app/templates/default.htm');
	}
	public function view($id='')
	{
		$games = new Games($this->db);

		$this->f3->set('games',array());
		foreach ( $this->f3->get('gamecat') as $index => $cat ) {
			$this->f3->set('games['.$index.']',$games->getByCat($index));
		}
		
		$this->f3->set('content','app/views/games.htm'); 
		echo Template::instance()->render('app/templates/default.htm');
	}
}