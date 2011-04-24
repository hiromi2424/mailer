<?php
if (!class_exists('Controller')) {
	App::import('Controller', 'Controller', false);
}

class LogMailController extends Controller {
	public $uses = array();
	public $components = array('ErrorMailer');

	public function __construct() {
		if (!App::import('Component', 'ErrorMailer')) {
			$this->components = array('Mailer.ErrorMailer');
			$this->plugin = 'mailer';
		}
		parent::__construct();
	}

}

class MailLog {
	protected $_fatalTypes = array();
	protected $_params = array();

	public function __construct($options = array()) {
		$options += array('fatalTypes' => array('error'));
		$this->_fatalTypes = $options['fatalTypes'];
		$this->_params += $options;
	}

	public function write($type, $message) {
		if (in_array($type, $this->_fatalTypes)) {
			$output = 'Error type:[' . ucfirst($type) . "]\n\n" . $message;

			$Controller = new LogMailController;
			$Controller->constructClasses();
			$Controller->startupProcess();

			if ($Controller->plugin === 'mailer') {
				$Controller->ErrorMailer->mustHeaders = array('to', 'subject');
			}
			$Controller->ErrorMailer->send(array('message' => $output) + $this->_params);
		}
	}
}