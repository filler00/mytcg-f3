<?php
class MyLoginController extends Controller {
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
		
		if ( $this->f3->get('POST.username') == $this->f3->get('USER') && $this->f3->get('POST.password') == $this->f3->get('PASS') ) {
			$this->f3->set('SESSION.adminID',$this->f3->get('USER'));
			$this->f3->push('SESSION.flash',array('type'=>'success','msg'=>'Welcome back, ' . $this->f3->get('SESSION.adminID') . '!'));
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