<?php

namespace Controllers\Core;

use Models\Core\Games;
use Models\Core\GameData;
use Template;

class GamesController extends Controller {
	
	private $games;
	
	function __construct() {
		parent::__construct();
		
		$this->games = new Games($this->db);
	}
	
	// get game data from Jig
	private function getData($id = 'all')
	{
		if ( $id === 'all' ) {
			$games = $this->games->all();
			foreach ( $games as &$game ) {
				$data = new GameData($this->jig, $game['id']);
				$gameData[$game['id']] = $data->all()[0]->cast();
			}
		}
		else {
			$data = new GameData($this->jig, intval($id));
			$gameData= $data->all()[0]->cast();
		}
		
		return $gameData;
	}
	
	public function index()
	{
		// get all games by category
		$this->f3->set('games',array());
		foreach ( $this->f3->get('gamecat') as $index => $cat ) {
			$this->f3->set('games['.$index.']',$this->games->getByCat($index));
		}

		// import Jig data
		if ( $this->games->count() > 0 ) {
			$gameData = $this->getData('all');
		} else { $gameData = []; }
		
		$this->f3->set('gameData', $gameData);
		
		$this->f3->set('content','app/themes/'.$this->f3->get('theme').'/views/games.htm'); 
		echo Template::instance()->render('app/themes/'.$this->f3->get('theme').'/templates/default.htm');
	}
	
	public function view($alias)
	{
		$game = $this->games->read(array('name=?',$alias),[])[0];
		$gameData = new GameData($this->jig, $game['id']);
		$gameData = $gameData->all()[0]->cast();
		
		$ctrl = 'Controllers\\Local\\Games\\' . str_replace(' ', '', ucwords(str_replace('-', ' ', $alias)));
		
		$game = new $ctrl($this->f3, $this->db, $game, $gameData);
		$game->run();
	}
	
}