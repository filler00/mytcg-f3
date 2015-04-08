<?php
class Affiliates extends DB\SQL\Mapper {
	public function __construct(DB\SQL $db)
	{
		parent::__construct($db,'affiliates');
	}
	public function add()
	{
		$this->copyfrom('POST',function($val) {
			return array_intersect_key($val, array_flip(array('name','email','tcgname','url','button','status')));
		});
		return $this->save();
	}
	public function all()
	{
		$this->load();
		return $this->query;
	}
	public function read($query,$options)
	{
		$this->load($query,$options);
		return $this->query;
	}
	public function edit($id)
	{
		$this->load(array('id=?',$id));
		$this->copyfrom('POST',function($val) {
			return array_intersect_key($val, array_flip(array('name','email','tcgname','url','button','status')));
		});
		$this->update();
	}
	public function delete($id)
	{
		$this->load(array('id=?',$id));
		$this->erase();
	}
}