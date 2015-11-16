<?php

namespace Models\Core;

class GameData extends \DB\Jig\Mapper {
	
	public function __construct(\DB\Jig $jig, $id) {
        parent::__construct($jig, 'games/' . $id . '.json' );
    }
	
	/****
	
	Basic CRUD operations
	
	****/
	
	public function add()
	{
		$this->copyfrom('POST',function($val) {
			return array_intersect_key($val, array_flip(array('long-name','schedule-enabled','schedule','current-round','fields','rounds')));
		});
		return $this->insert();
	}
	public function read($filter,$options)
	{
		return $this->find($filter,$options);
	}
	public function edit($id)
	{
		$this->load(array('@_id = ?',$id));
		$this->copyfrom('POST',function($val) {
			return array_intersect_key($val, array_flip(array('long-name','schedule-enabled','schedule','current-round','fields','rounds')));
		});
		return $this->update();
	}
	public function delete($id)
	{
		$this->load(array('@_id = ?',$id));
		return $this->erase();
	}
	
	/****
	
	Helper methods
	
	****/
	
	public function all()
	{
		$this->load([]);
		return $this->query;
	}
	
}