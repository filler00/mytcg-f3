<?php

namespace Controllers\Core\MyTCG;

use Models\Core\Members;
use Models\Core\Upcoming;
use Models\Core\Cards;
use Models\Core\Games;
use Models\Core\GameData;
use Models\Core\Affiliates;
use Template;

class IndexController extends Controller {

	private $cards;
	private $upcoming;
	private $games;
	private $members;

	function __construct() {
		parent::__construct();

		$this->games = new Games($this->db);
		$this->cards = new Cards($this->db);
		$this->upcoming = new Upcoming($this->db);
		$this->members = new Members($this->db);

	}

	/*************************************

		Getting Game Info

	************************************/

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

	/*************************************

		Index Page

	************************************/

	public function index()
	{

		// Call to the database - Only affiliates since all others are used again in other functions
		$affiliates = new Affiliates($this->db);

		//Check if any post requests were made
		if($this->f3->exists('POST.update-game-submit'))
			$this->updateGame();
		if($this->f3->exists('POST.release-deck-submit'))
			$this->releaseDeck();
		if($this->f3->exists('POST.approve-member-submit'))
			$this->approveMember();
		if($this->f3->exists('POST.delete-member-submit'))
			$this->deleteMember();

		//Setting up total numbers to show on dashboard info
		$this->f3->set('pendingMembers',$this->members->getByStatus('pending'));
		$this->f3->set('upcomingDecks',$this->upcoming->all());
		$this->f3->set('totalmembers', count($this->members->getByStatus('Active')));
		$this->f3->set('totaffiliates',count($affiliates->read(array('status=?','Active'),[])));

		$cardCount = 0;
		foreach ( $this->cards->all() as $deck ) {
		$cardCount += $deck->count;
		}
		$this->f3->set('totalcards', $cardCount);

		$games = $this->games->all();
		$this->f3->set('games',array());
		$this->f3->set('totgames',count($games));

		//Displaying only games that are not updated

		if ( $this->games->count() > 0 ) {
			$gameData = $this->getData('all');
		} else { $gameData = []; }

		$this->f3->set('gameData', $gameData);

		if ( $this->games->count() > 0 ) {
			foreach ($games as $game) {
				if ($gameData[$game['id']]['schedule-enabled'] == true &&  time() >= strtotime($gameData[$game['id']]['schedule'], $game['updated'])){
				$this->f3->push('games',$game);
				}
			}
		}

		//Automatic saving notes

		if(isset($_SERVER['HTTP_X_REQUESTED_WITH'])){
			if(isset($_POST['note'])){
				file_put_contents('storage/admin-notes.txt', $_POST['note']);
			}
			exit;
		}

		//Load theme options

		$this->f3->set('content','app/themes/'.$this->f3->get('admintheme').'/views/mytcg/index.htm');
		echo Template::instance()->render('app/themes/'.$this->f3->get('admintheme').'/templates/admin.htm');
	}

	/*************************************

		Process Update Game Form!

	************************************/

	private function updateGame()
	{

		$audit = \Audit::instance();
		$this->f3->scrub($_POST);
		$this->f3->set('SESSION.flash',array());

		// process form if there are no errors
		if ( count($this->f3->get('SESSION.flash')) === 0 ) {
			$gameData = new GameData($this->jig, $this->f3->get('POST.id'));
			$gameData = $gameData->all()[0]->cast();

			if ( $gameData['schedule-enabled'] )
				$this->f3->set('POST.updated', time());

			if ( $gameData['current-round'] + 1 <= count($gameData['rounds']) )
				$this->f3->set('POST[current-round]', $gameData['current-round'] + 1);

			// save to db
			if ( $this->games->edit($this->f3->get('POST.id')) ) {
				$data = new GameData($this->jig, $this->games->id);
				if ( $data->edit($this->f3->get('POST.dataId')) ) {
					$this->f3->clear('POST');
					$this->f3->push('SESSION.flash',array('type'=>'success','msg'=>'The game has been updated successfully!'));
				}
				else {
					$this->f3->push('SESSION.flash',array('type'=>'danger','msg'=>'Failed to save game data record.'));
				}
			} else {
				$this->f3->push('SESSION.flash',array('type'=>'danger','msg'=>'There was a problem processing the request. Please try again.'));
			}
		}

	}

	/*************************************

		Process Release Deck Form!

	************************************/

	private function releaseDeck()
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

	/***********************************

	Approve pending members

	************************************/

	public function approveMember()
	{

		$this->f3->scrub($_POST);
		$this->f3->set('member',$this->members->read(array('id=?',$this->f3->get('POST.id')),[])[0]);
		$this->f3->set('SESSION.flash',array());

		if($this->f3->get('member')->status != 'Active') {
			$this->f3->set('POST.status','Active');
			$this->members->edit($this->f3->get('POST.id'));
			$this->f3->push('SESSION.flash',array('type'=>'success','msg'=>'Member '.$this->f3->get('member')->name.' approved!'));
		}

	}

	/***********************************
	Delete pending members
	************************************/

	public function deleteMember()
	{

		$this->f3->set('member',$this->members->read(array('id=?',$this->f3->get('POST.id')),[])[0]);
		$this->f3->set('SESSION.flash',array());

		if ( count($this->f3->get('SESSION.flash')) === 0 ) {
			$this->members->delete($this->f3->get('POST.id'));
			$this->f3->push('SESSION.flash',array('type'=>'success','msg'=>'Member '.$this->f3->get('member')->name.' deleted!'));
		}

	}

}
