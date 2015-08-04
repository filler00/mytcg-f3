<?php

namespace Models\Core;

class Cards extends \DB\SQL\Mapper {
	public function __construct(\DB\SQL $db)
	{
		parent::__construct($db,'cards');
	}
	public function all()
	{
		$this->load();
		return $this->query;
	}
	public function allAlpha()
	{
		$this->load('',array('order'=>'filename'));
		return $this->query;
	}
	public function random($params)
	{
		$this->load($params,array('order'=>'RAND()','limit'=>1));
		return $this->filename . str_pad(rand(1,$this->count), 2, '0', STR_PAD_LEFT);
	}
	public function add()
	{
		$this->copyfrom('POST',function($val) {
			return array_intersect_key($val, array_flip(array('filename','deckname','description','category','count','worth','masterable','masters','puzzle')));
		});
		return $this->save();
	}
	public function read($query,$options)
	{
		$this->load($query,$options);
		return $this->query;
	}
	public function getByCat($cat)
	{
		$this->load(array('category=?',$cat));
		return $this->query;
	}
	public function getByFilename($filename)
	{
		$this->load(array('filename=?',$filename));
		return $this;
	}
	public function getById($id)
	{
		$this->load(array('id=?',$id));
		return $this;
	}
	public function edit($id)
	{
		$this->load(array('id=?',$id));
		$this->copyfrom('POST',function($val) {
			return array_intersect_key($val, array_flip(array('filename','deckname','description','category','count','worth','masterable','masters','puzzle')));
		});
		return $this->update();
	}
	public function delete($id)
	{
		$this->load(array('id=?',$id));
		return $this->erase();
	}
}