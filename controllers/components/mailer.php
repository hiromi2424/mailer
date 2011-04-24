<?php

class MailerComponent extends Object {

	public $Controller;

	public $config = 'Mail';

	public $layout = 'default';
	public $template = null;
	public $sendAs = 'text'; // or html
	public $subject;

	public $components = array();

	public $from;
	public $to;

	public $errorMessages = array();
	protected $_currentError;

	private $__viewBackup;

	// from , to, subject, etc.
	public $mustHeaders = array('from', 'to');

	public function __construct() {
		if (App::import('Component', 'Qdmail')) {
			$this->components = array('Qdmail');
		} else {
			$this->components = array('Mailer.Qdmail');
		}

		// default
		$this->errorMessages = array(
			'invalidAddress'   => __d('mailer', 'Specifying email address was wrong: %s', true),
			'emptyTemplate'    => __d('mailer', 'Template name is required', true),
			'emptyFromAddress' => __d('mailer', 'From address is required', true),
			'emptyToAddress'   => __d('mailer', 'To address(es) is required', true),
			'emptySubject'     => __d('mailer', 'The subject of the mail was not found: %s', true),
			'sendFailed'       => __d('mailer', 'Sending email was failed', true),
		);
	}

	public function initialize($controller, $settings = array()) {
		$this->Controller = $controller;

		if ($errorMessages = Configure::read("{$this->config}.errorMessages")) {
			$this->errorMessages = array_merge($this->errorMessages, $errorMessages);
		}
		if (isset($settings['errorMessages'])) {
			$this->errorMessages = array_merge($this->errorMessages, $settings['errorMessages']);
			unset($settings['errorMessages']);
		}
		$this->_set($settings);

		if (null === $this->template) {
			$this->template = Inflector::underscore(preg_replace('/MailerComponent$/', '', get_class($this)));
		}

		return true;
	}

	public function startup($controller) {
		$this->Qdmail->startup($controller);
		return true;
	}

	public function setFrom($from) {
		$this->from = $this->_normalizeAddress($from);
	}

	public function setTo($to) {
		$this->to = array_map(array($this, '_normalizeAddress'), (array)$to);

		if (count($this->to) === 1) {
			$this->to = current($this->to);
		}
	}

	public function setSubject($subject) {
		$this->subject = $subject;
	}

	protected function _normalizeAddress($addr) {
		if (is_array($addr) && !empty($addr)) {
			switch (count($addr)) {
				case 1:
					if (is_numeric(key($addr))) {
						$addr = current($addr);
					} else {
						$addr = array(current($addr), key($addr));
					}
					break;
				case 2:
					break;
				default:
					$this->_error('invalidAddress', var_export($addr, true));
					return null;
			}
		}
		return $addr;
	}

	public function detectFrom($params) {

		if (isset($params[$this->Controller->modelClass])) {
			$params = $params[$this->Controller->modelClass];
		}

		if (isset($params['from'])) {
			return $params['from'];
		}

		$candidates = array(
			Configure::read($this->config . '.addresses.from'),
			Configure::read($this->config . '.addresses.admin'),
			Configure::read($this->config . '.from'),
		);

		if ($from = current(array_filter($candidates))) {
			return $from;
		}

	}

	public function detectTo($params) {
		if (isset($params[$this->Controller->modelClass])) {
			$params = $params[$this->Controller->modelClass];
		}
		if (isset($params['email'])) {
			return $params['email'];
		} elseif (isset($params['to'])) {
			return $params['to'];
		}
	}

	public function detectSubject($params) {
		if (isset($params[$this->Controller->modelClass])) {
			$params = $params[$this->Controller->modelClass];
		}

		if (isset($params['subject'])) {
			$subject = $params['subject'];
		}

		if (!isset($subject)) {
			$subject = Configure::read("{$this->config}.subjects.{$this->template}");
		}

		if ($subject) {
			$subject = String::insert($subject, $params);
		}

		return $subject;
	}

	public function send($params = array()) {
		$this->reset();
		$this->setSendParameters();

		if (!$this->prepare($params)) {
			return false;
		}

		$result = $this->_send();
		if (!$result) {
			$this->_error('sendFailed');
		}
		return $result;
	}

	public function debug($params = array()) {
		$this->reset();
		$this->setDebugParameters();

		if (!$this->prepare($params)) {
			return false;
		}

		$this->_send();
		$q = $this->Qdmail;
		$body = @$q->qd_convert_encoding($q->content[strtoupper($this->sendAs)]['CONTENT'], 'UTF-8', 'iso-2022-jp');
		$subject = @$q->subject['CONTENT'];
		return array(
			'from' => $q->from,
			'to' => $q->to,
			'subject' => $subject,
			'body' => $body,
		);
	}

	protected function _error($type, $parameters = array()) {
		$message = vsprintf($this->errorMessages[$type], (array)$parameters);
		$this->_currentError = array($type => $message);
		trigger_error($message);
		return false;
	}

	public function getErrorMesssage() {
		if ($this->_currentError) {
			return current($this->_currentError);
		}
		return null;
	}

	public function getErrorType() {
		if ($this->_currentError) {
			return key($this->_currentError);
		}
		return null;
	}

	protected function _detect($type, $params) {
		if (empty($this->$type)) {
			$detectMethod = 'detect' . ucfirst($type);
			$setter = 'set' . ucfirst($type);
			$this->$setter($this->$detectMethod($params));

			$isMust = ((array)$this->mustHeaders === array('*') || in_array($type, array_map('strtolower', (array)$this->mustHeaders)));
			if (empty($this->$type) && $isMust) {
				return false;
			}
		}

		if (!empty($this->$type)) {
			$this->Qdmail->$type($this->$type);
		}

		return true;
	}

	public function prepare($params) {
		$q = $this->Qdmail;

		if (empty($this->template)) {
			return $this->_error('emptyTemplate');
		}

		if ((array)$this->mustHeaders !== array('*')) {
			$this->Qdmail->header_must = array_map('strtoupper', (array)$this->mustHeaders);
		}

		if (!$this->_detect('from', $params)) {
			return $this->_error('emptyFromAddress');
		}

		if (!$this->_detect('to', $params)) {
			return $this->_error('emptyToAddress');
		}

		if (!$this->_detect('subject', $params)) {
			return $this->_error('emptySubject', $this->template);
		}

		if ($this->Controller->view === 'DebugKit.Debug') {
			$this->__viewBackup = $this->Controller->view;
			if (class_exists('DoppelGangerView')) {
				$this->Controller->view = 'DoppelGanger';
			} else {
				$this->Controller->view = 'View';
			}
		}

		$q->CakePHP(array(
			'type' => strtolower($this->sendAs) == 'text' ? null : 'HTML',
			'content' => $this->setParams($params),
			'template' => $this->template,
			'layout' => $this->layout
		));
		return true;
	}

	public function setParams($params) {
		return $params;
	}

	protected function _send() {

		$result = $this->Qdmail->send();

		if (isset($this->__viewBackup)) {
			$this->Controller->view = $this->__viewBackup;
			unset($this->__viewBackup);
		}

		return $result;
	}

	public function setSendParameters() {
		$q = $this->Qdmail;

		$q->errorDisplay(false);
		$q->logPath(LOGS);
		$q->logFilename('mail.log');
		$q->errorlogPath(LOGS);
		$q->errorlogFilename('error_mail.log');
		$q->wordwrapAllow(true);
		$q->errorlogLevel(3);
		$q->logLevel(3);
		$q->renderMode(false);
	}

	public function setDebugParameters() {
		$q = $this->Qdmail;

		$q->errorDisplay(false);
		$q->logPath(LOGS);
		$q->logFilename('mail.log');
		$q->errorlogPath(LOGS);
		$q->errorlogFilename('error_mail.log');
		$q->wordwrapAllow(true);
		$q->errorlogLevel(2);
		$q->logLevel(2);
		$q->renderMode(true);
		$q->debug(2);
	}

	public function reset() {
		$this->Qdmail->reset();
		$this->from = $this->to = $this->subject = null;
	}
}