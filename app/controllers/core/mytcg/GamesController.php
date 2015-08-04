<?php

namespace Controllers\Core\MyTCG;

use Models\Core\Games;
use Models\Core\GameData;
use Template;

class GamesController extends Controller {
	
	private $scheduleDays;
	private $scheduleFrequencies;
	private $games;
	
	function __construct() {
		parent::__construct();
		
		$this->games = new Games($this->db);
		
		$this->scheduleDays = [
			'null' => 'No Schedule',
			'sunday' => 'Sundays',
			'monday' => 'Mondays',
			'tuesday' => 'Tuesdays',
			'wednesday' => 'Wednesdays',
			'thursday' => 'Thursdays',
			'friday' => 'Fridays',
			'saturday' => 'Saturdays'
		];
		
		$this->scheduleFrequencies = [
			'weekly' => 'Weekly',
			'biweekly' => 'Bi-Weekly',
			'monthly' => 'Monthly'
		];
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
	
	// create schedule string
	private function setSchedule($day, $frequency)
	{
		if ( $day !== 'null' ) {
			if ( $frequency === 'monthly' )
				return 'first '. $this->f3->get('POST.schedule-day') .' of next month';
			if ( $frequency === 'biweekly' )
				return 'next '. $this->f3->get('POST.schedule-day') .' +1 week';
			if ( $frequency === 'weekly' )
				return 'next '. $this->f3->get('POST.schedule-day');
		} else {
			return null;
		}	
	}
	
	// get schedule from string
	private function getSchedule($schedule)
	{
		$day = strtolower(date('l',strtotime($schedule)));
		
		if ( preg_match("/.* of next month$/i", $schedule) ) {
			$frequency = 'monthly';
		} else if ( preg_match("/.* \+1 week$/i", $schedule) ) {
			$frequency = 'biweekly';
		} else {
			$frequency = 'weekly';
		}
		
		return ['day'=>$day, 'frequency'=>$frequency];
	}
		
	/************************************* 

		Game Management - Main Page 
		
	************************************/
	
	public function index()
	{
		
		// direct form submissions to the appropriate handler
		if($this->f3->exists('POST.new-game-submit'))
			$this->newGame();
		if($this->f3->exists('POST.edit-game-submit'))
			$this->editGame();
		if($this->f3->exists('POST.update-game-submit'))
			$this->updateGame();
		if($this->f3->exists('POST.update-due-submit'))
			$this->updateDue();
		if($this->f3->exists('POST.update-all-submit'))
			$this->updateAll();
		if($this->f3->exists('POST.delete-game-submit'))
			$this->deleteGame();
		
		// get all games
		$games = $this->games->all();
		
		// import Jig data
		$gameData = $this->getData('all');

		$this->f3->set('games', $games);
		$this->f3->set('gameData', $gameData);
		$this->f3->set('scheduleDays', $this->scheduleDays);
		$this->f3->set('scheduleFrequencies', $this->scheduleFrequencies);
		
		$this->f3->set('content','app/views/mytcg/games.htm'); 
		echo Template::instance()->render('app/templates/admin.htm');
	}
	
	/************************************* 

		Game Management - Edit Form 
		
	************************************/
	
	// ../mytcg/games/edit/{id}
	public function edit($id)
	{
		if ( !$this->games->count(array('id=?',$id)) ) { $this->f3->error(404); }
		else {
			$gameData = new GameData($this->jig, $id);
				
			$this->f3->set('game' ,$this->games->read(array('id=?',$id),[])[0]);	
			$this->f3->set('gameData', $gameData->all()[0]->cast());
			
			if ( $this->f3->get('gameData[schedule-enabled]') ) {
				$this->f3->set('gameData[schedule-day]', $this->getSchedule($this->f3->get('gameData.schedule'))['day']);
			} else {
				$this->f3->set('gameData[schedule-day]', 'null');
			}
			$this->f3->set('gameData[schedule-frequency]', $this->getSchedule($this->f3->get('gameData.schedule'))['frequency']);
			
			$this->f3->set('scheduleDays', $this->scheduleDays);
			$this->f3->set('scheduleFrequencies', $this->scheduleFrequencies);
			echo Template::instance()->render('app/views/mytcg/games_edit_form.htm');
		}
	}
	
	/************************************* 

		Process New Game Form! 
		
	************************************/

	private function newGame()
	{
		
		$audit = \Audit::instance();
		$this->f3->scrub($_POST);
		$this->f3->set('SESSION.flash',array());
		
		// validate form
		if ( !$this->f3->exists('POST.long-name') || $this->f3->get('POST.long-name') === '' )
			$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'Invalid game name.'));
		if ( !preg_match("/^[a-z0-9]+[a-z0-9-]{0,29}$/i", $this->f3->get('POST.name')) )
			$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'Invalid alias name.'));
		if ( $this->games->count(array('name=?',$this->f3->get('POST.name'))) != 0  )
			$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'That alias name is already being used for another game.'));
		if ( !isset($this->f3->get('gamecat')[$this->f3->get('POST.category')]) )
			$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'Invalid category.'));
		if ( !array_key_exists($this->f3->get('POST.schedule-day'), $this->scheduleDays) || !array_key_exists($this->f3->get('POST.schedule-frequency'), $this->scheduleFrequencies) )	
			$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'Invalid update schedule values.'));
		
		// process form if there are no errors
		if ( count($this->f3->get('SESSION.flash')) === 0 ) {
			
			// set start date to today if unset
			if ( !$this->f3->exists('POST.start-date') || $this->f3->get('POST.start-date') === '' )
				$this->f3->set('POST.start-date', date('Y-m-d'));
			
			// enable/disable schedules
			( $this->f3->get('POST.schedule-day') === 'null' ) ? $this->f3->set('POST.schedule-enabled', false) : $this->f3->set('POST.schedule-enabled', true);
			
			// cleaning up some values
			$this->f3->set('POST.name', strtolower($this->f3->get('POST.name')));
			$this->f3->set('POST.updated',strtotime($this->f3->get('POST.start-date')));
			$this->f3->set('POST.schedule', $this->setSchedule($this->f3->get('POST.schedule-day'), $this->f3->get('POST.schedule-frequency')));
			$this->f3->set('POST.current-round',1);
			$this->f3->set('POST.fields',[]);
			$this->f3->set('POST.rounds',[]);
			
			// save to db
			if ( $this->games->add() ) {
				$data = new GameData($this->jig, $this->games->id);
				if ( $data->add() ) {
					$this->f3->clear('POST');
					$this->f3->push('SESSION.flash',array('type'=>'success','msg'=>'The new deck has been added successfully!'));
				}
				else {
					$this->f3->push('SESSION.flash',array('type'=>'danger','msg'=>'Failed to create game data record.'));
				}
			} else {
				$this->f3->push('SESSION.flash',array('type'=>'danger','msg'=>'There was a problem processing the request. Please try again.'));
			}
		}

	}
	
	/************************************* 

		Process Edit Game Form! 
		
	************************************/
	
	private function editGame()
	{
		
		$audit = \Audit::instance();
		$this->f3->scrub($_POST);
		$this->f3->set('SESSION.flash',array());
		
		// validate form
		if ( !$this->f3->exists('POST.long-name') || $this->f3->get('POST.long-name') === '' )
			$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'Invalid game name.'));
		if ( !preg_match("/^[a-z0-9]+[a-z0-9-]{0,29}$/i", $this->f3->get('POST.name')) )
			$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'Invalid alias name.'));
		if ( $this->games->count(array('name=?',$this->f3->get('POST.name'))) != 0  )
			$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'That alias name is already being used for another game.'));
		if ( !isset($this->f3->get('gamecat')[$this->f3->get('POST.category')]) )
			$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'Invalid category.'));
		if ( !array_key_exists($this->f3->get('POST.schedule-day'), $this->scheduleDays) || !array_key_exists($this->f3->get('POST.schedule-frequency'), $this->scheduleFrequencies) )	
			$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'Invalid update schedule values.'));
		
		// process form if there are no errors
		if ( count($this->f3->get('SESSION.flash')) === 0 ) {
			
			// set start date to today if unset
			if ( !$this->f3->exists('POST.start-date') || $this->f3->get('POST.start-date') === '' )
				$this->f3->set('POST.start-date', date('Y-m-d'));
			
			// enable/disable schedules
			( $this->f3->get('POST.schedule-day') === 'null' ) ? $this->f3->set('POST.schedule-enabled', false) : $this->f3->set('POST.schedule-enabled', true);
			
			// cleaning up some values
			$this->f3->set('POST.name', strtolower($this->f3->get('POST.name')));
			$this->f3->set('POST.updated',strtotime($this->f3->get('POST.start-date')));
			$this->f3->set('POST.schedule', $this->setSchedule($this->f3->get('POST.schedule-day'), $this->f3->get('POST.schedule-frequency')));
			$this->f3->set('POST.current-round',1);
			$this->f3->set('POST.fields',[]);
			$this->f3->set('POST.rounds',[]);
			
			// save to db
			if ( $this->games->add() ) {
				$data = new GameData($this->jig, $this->games->id);
				if ( $data->add() ) {
					$this->f3->clear('POST');
					$this->f3->push('SESSION.flash',array('type'=>'success','msg'=>'The new deck has been added successfully!'));
				}
				else {
					$this->f3->push('SESSION.flash',array('type'=>'danger','msg'=>'Failed to create game data record.'));
				}
			} else {
				$this->f3->push('SESSION.flash',array('type'=>'danger','msg'=>'There was a problem processing the request. Please try again.'));
			}
		}

	}
	
	/************************************* 

		Delete a Game! 
		
	************************************/
	
	private function deleteGame()
	{
		
		$audit = \Audit::instance();
		$this->f3->scrub($_POST);
		$this->f3->set('SESSION.flash',array());
		
		// process form if there are no errors
		if ( count($this->f3->get('SESSION.flash')) === 0 ) {
			// delete record
			if ( $this->games->delete($this->f3->get('POST.id')) ) {
				$this->f3->push('SESSION.flash',array('type'=>'success','msg'=>'Deck record removed successfully!'));
			} else {
				$this->f3->push('SESSION.flash',array('type'=>'danger','msg'=>'There was a problem processing the request. Please try again.'));
			}
		}
	}

}