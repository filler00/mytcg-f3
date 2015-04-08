<?php
class Upcoming extends DB\SQL\Mapper {
	public function __construct(DB\SQL $db)
	{
		parent::__construct($db,'upcoming');
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
	public function add()
	{
		$this->copyfrom('POST',function($val) {
			return array_intersect_key($val, array_flip(array('name','email','tcgname','url','button','status')));
		});
		return $this->save();
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
		return $this->query;
	}
	public function edit($id)
	{
		$this->load(array('id=?',$id));
		$this->copyFrom('POST');
		$this->update();
	}
	public function delete($id)
	{
		$this->load(array('id=?',$id));
		$this->erase();
	}
}