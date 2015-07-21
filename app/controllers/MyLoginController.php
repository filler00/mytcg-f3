<?php
class MyLoginController extends AdminController {
	public function index()
	{
		$this->f3->set('SESSION.flash',array());
		
		if ( $this->f3->exists('SESSION.adminID') ) {
			$this->f3->push('SESSION.flash',array('type'=>'warning','msg'=>'You are already logged in!'));
			$this->f3->reroute('/mytcg'); 
		}
		
		if($this->f3->exists('POST.login'))
			$this->login();
		
		echo Template::instance()->render('app/templates/admin-login.htm');
	}
	private function login()
	{
		$this->f3->set('SESSION.flash',array());
		$this->f3->scrub($_POST);
		
		if ( in_array(strtolower($this->f3->get('POST.username')), array_map('strtolower', $this->f3->get('admin_users'))) && $this->auth->login($this->f3->get('POST.username'),$this->f3->get('POST.password')) ) {
			$members = new Members($this->db);
			$this->f3->set('SESSION.adminID',$members->getByName($this->f3->get('POST.username'))->id);
			$this->f3->set('SESSION.adminName',$members->getByName($this->f3->get('POST.username'))->name);
				
			$this->f3->push('SESSION.flash',array('type'=>'success','msg'=>'Welcome back, ' . $this->f3->get('SESSION.adminName') . '!'));
			$this->f3->reroute('/mytcg');
		} else {
			$this->f3->push('SESSION.flash',array('type'=>'danger','msg'=>'Authentication failed. Please try again or contact us for assistance.'));
		}

	}
	public function logout()
	{
		$this->f3->clear('SESSION');
		$this->f3->set('SESSION.flash',array());
		$this->f3->push('SESSION.flash',array('type'=>'success','msg'=>'You have been logged out!'));
		$this->f3->reroute('/mytcg/login');
	}
}