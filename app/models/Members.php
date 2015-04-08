<?php
class Members extends DB\SQL\Mapper {
	public function __construct(DB\SQL $db)
	{
		parent::__construct($db,'members');
	}
	public function all()
	{
		$this->load();
		return $this->query;
	}
	public function allWhereMemCards()
	{
		$this->load('membercard="Yes"');
		return $this->query;
	}
	public function add($fields = array('name','email','url','birthday','status','password','level','collecting','membercard','mastered','wishlist','biography','refer'))
	{
		$this->copyfrom('POST',function($val) use($fields) {
			return array_intersect_key($val, array_flip($fields));
		});
		return $this->save();
	}
	public function read($query,$options)
	{
		$this->load($query,$options);
		return $this->query;
	}
	public function getByName($name)
	{
		$this->load(array('name=?',$name));
		return $this;
	}
	public function getById($id)
	{
		$this->load(array('id=?',$id));
		return $this->query;
	}
	public function getActiveByLvl($lvl)
	{
		$this->load(array('status=? AND level=?','active',$lvl));
		return $this->query;
	}
	public function getByStatus($status)
	{
		$this->load(array('status=?',$status));
		return $this->query;
	}
	public function edit($id,$fields = array('name','email','url','birthday','status','password','level','collecting','membercard','mastered','wishlist','biography','refer'))
	{
		$this->load(array('id=?',$id));
		$this->copyfrom('POST',function($val) use($fields) {
			return array_intersect_key($val, array_flip($fields));
		});
		return $this->update();
	}
	public function delete($id)
	{
		$this->load(array('id=?',$id));
		$this->erase();
	}
}