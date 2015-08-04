<?php

namespace Models\Core;

class Plugins extends \DB\Jig\Mapper {
	
	public function __construct(\DB\Jig $jig) {
        parent::__construct($jig, 'plugins.json' );
    }
	
	/****
	
	Basic CRUD operations
	
	****/
	
	public function add()
	{
		$this->copyfrom('POST',function($val) {
			return array_intersect_key($val, array_flip(array('name','email','tcgname','url','button','status')));
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
			return array_intersect_key($val, array_flip(array('name','email','tcgname','url','button','status')));
		});
		return $this->update();
	}
	public function delete($id)
	{
		$this->load(array('@_id = ?=?',$id));
		return $this->erase();
	}
	
	/****
	
	Helper methods
	
	****/
	
	public function all()
	{
		return $this->load();
		//return $this->query;
	}
	
}