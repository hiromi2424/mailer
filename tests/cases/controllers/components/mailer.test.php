<?php

App::import('Component', 'Mailer.Mailer');
App::import('Controller', 'Controller', false);

class TestMailerComponent extends MailerComponent {
	public $useParentSend = false;
	public $sendResult = true;

	protected function _send() {
		if ($this->useParentSend) {
			return parent::_send();
		} else {
			return $this->sendResult;
		}
	}

	public function setDebugParameters() {
		parent::setDebugParameters();
		$this->Qdmail->errorDisplay(false);
	}

}


class MailerComponentTestController extends Controller {
	public $name = 'TransitionComponentTest';
	public $uses = null;
}


/**
 * MailerComponent Test Case
 *
 */
class MailerComponentTestCase extends CakeTestCase {

	protected $_subjects = array(
		'test' => 'test :hoge :piyo',
	);

	public static $sendResult = true;

	protected function _initComponent($settings = array()) {

		$Controller = new MailerComponentTestController;
		$Controller->components = array(
			'TestMailer' => $settings + array(
				'config' => 'TestMailerConfig',
			)
		);
		$Controller->constructClasses();
		$Controller->startupProcess();
		$this->Mailer = $Controller->TestMailer;
		$this->Controller = $Controller;

	}

/**
 * startTest method
 *
 * @return void
 */
	public function startTest($method = null) {

		parent::startTest($method);

		$this->_initComponent();

	}

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {

		Configure::write('TestMailerConfig.subjects', $this->_subjects);

		App::build(array('views' => array(App::pluginPath('mailer') . 'tests' . DS . 'test_app' . DS . 'views' . DS)));

		parent::setUp();

	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {

		Configure::delete('TestMailerConfig');

		unset($this->Mailer);
		ClassRegistry::flush();

		parent::tearDown();

	}

	public function testSetFromAndSetTo() {

		$this->Mailer->setFrom('hoge@example.com');
		$this->assertEqual($this->Mailer->from, 'hoge@example.com');

		$this->Mailer->setFrom(array('hoge@example.com'));
		$this->assertEqual($this->Mailer->from, 'hoge@example.com');

		$this->Mailer->setFrom(array('hoge@example.com', 'hiroshi'));
		$this->assertEqual($this->Mailer->from, array('hoge@example.com', 'hiroshi'));

		$this->Mailer->setFrom(array('hiroshi' => 'hoge@example.com'));
		$this->assertEqual($this->Mailer->from, array('hoge@example.com', 'hiroshi'));

		$this->Mailer->setTo('hoge@example.com');
		$this->assertEqual($this->Mailer->to, 'hoge@example.com');

		$this->Mailer->setTo(array('hoge@example.com'));
		$this->assertEqual($this->Mailer->to, 'hoge@example.com');

		$this->Mailer->setTo(array('hoge@example.com', 'hiroshi'));
		$this->assertEqual($this->Mailer->to, array('hoge@example.com', 'hiroshi'));

		$this->Mailer->setTo(array(array('hoge@example.com', 'hiroshi')));
		$this->assertEqual($this->Mailer->to, array('hoge@example.com', 'hiroshi'));

		$this->Mailer->setTo(array(array('hoge@example.com', 'hiroshi'), array('fuga@example.com')));
		$this->assertEqual($this->Mailer->to, array(array('hoge@example.com', 'hiroshi'), 'fuga@example.com'));

		$this->expectError();
		$this->Mailer->setFrom(array('hoge@example.com', 'hiroshi', 'hitomi'));

	}

	public function testDetectMethods() {

		$this->Mailer->Controller->modelClass = 'TestAlias';

		$this->assertNull($this->Mailer->detectFrom(array()));
		$this->assertEqual('hoge', $this->Mailer->detectFrom(array('from' => 'hoge')));
		$this->assertEqual('fuga', $this->Mailer->detectFrom(array('TestAlias' => array('from' => 'fuga'))));


		$this->assertNull($this->Mailer->detectTo(array()));
		$this->assertEqual('hoge', $this->Mailer->detectTo(array('email' => 'hoge')));
		$this->assertEqual('fuga', $this->Mailer->detectTo(array('TestAlias' => array('email' => 'fuga'))));
		$this->assertEqual('hoge', $this->Mailer->detectTo(array('to' => 'hoge')));
		$this->assertEqual('fuga', $this->Mailer->detectTo(array('TestAlias' => array('to' => 'fuga'))));

		$this->Mailer->template = 'test';

		$this->assertEqual($this->Mailer->detectSubject(array()), 'test :hoge :piyo');
		$this->assertEqual($this->Mailer->detectSubject(array('hoge' => 'hey,', 'piyo' => 'say yeah')), 'test hey, say yeah');

		$this->Mailer->template = 'undefined';
		$this->assertNull($this->Mailer->detectSubject(array()));

		$this->assertEqual($this->Mailer->detectSubject(array('subject' => 'assoc subject')), 'assoc subject');


		$this->Mailer->Controller->modelClass = 'TestAlias';
		$this->assertEqual($this->Mailer->detectSubject(array('TestAlias' => array('subject' => 'alias subject'))), 'alias subject');

	}

	public function testPrepare() {

		$this->Mailer->template = 'test';

		$this->assertTrue($this->Mailer->prepare(array('from' => 'piyo@example.com', 'to' => 'hoge@example.com', 'hoge' => 'hey,', 'piyo' => 'say yeah')));
		$this->assertEqual($this->Mailer->from, 'piyo@example.com');
		$this->assertEqual($this->Mailer->to, 'hoge@example.com');
		$this->assertEqual($this->Mailer->subject, 'test hey, say yeah');

		$this->expectError();
		$this->Mailer->template = 'undefined';
		$this->Mailer->mustHeaders = array('subject');
		$this->Mailer->reset();
		$this->assertFalse($this->Mailer->prepare(array()));
		$this->assertEqual($this->Mailer->getErrorType(), 'emptySubject');

		$this->expectError();
		$this->Mailer->mustHeaders = array('to');
		$this->Mailer->reset();
		$this->assertFalse($this->Mailer->prepare(array()));
		$this->assertEqual($this->Mailer->getErrorType(), 'emptyToAddress');

		$this->expectError();
		$this->Mailer->mustHeaders = array('from');
		$this->Mailer->reset();
		$this->Mailer->template = false;
		$this->assertFalse($this->Mailer->prepare(array('to' => 'hoge@example.com')));
		$this->assertEqual($this->Mailer->getErrorType(), 'emptyTemplate');

	}

	public function testSend() {

		$this->Mailer->template = 'test';
		$this->Mailer->mustHeaders = array();

		$this->expectError();
		$this->Mailer->sendResult = false;
		$this->assertFalse($this->Mailer->send(array('to' => 'hoge@example.com')));
		$this->assertEqual($this->Mailer->getErrorType(), 'sendFailed');

	}

	public function testDebug() {

		$this->Mailer->template = 'test';
		$this->Mailer->useParentSend = true;
		$this->Mailer->mustHeaders = array();

		ob_start();
		$result = $this->Mailer->debug(array('from' => 'hoge@example.com', 'to' => 'fuga@example.com', 'hoge' => 'hey,', 'piyo' => 'say yeah'));
		$debugOutput = ob_get_clean();

		$this->assertEqual($result['from'], array(array('mail' => 'hoge@example.com', 'name' => '')));
		$this->assertEqual($result['to'], array(array('mail' => 'fuga@example.com', 'name' => '')));
		$this->assertEqual($result['subject'], 'test hey, say yeah');
		$this->assertEqual($result['body'], 'text template');
		$this->assertPattern('/From: hoge@example.com/', $debugOutput);
		$this->assertPattern('/To: fuga@example.com/', $debugOutput);
		$this->assertPattern('/Subject: test hey, say yeah/', $debugOutput);
		$this->assertPattern('/\r\n\r\ntext template/', $debugOutput);

		$this->Mailer->Controller->view = 'DebugKit.Debug';
		ob_start();
		$result = $this->Mailer->debug(array('to' => 'hoge@example.com', 'hoge' => 'hey,', 'piyo' => 'say yeah'));
		$debugOutput = ob_get_clean();
		$this->assertTrue($result);

		$this->Mailer->sendAs = 'html';
		ob_start();
		$result = $this->Mailer->debug(array('from' => 'hoge@example.com', 'to' => 'fuga@example.com', 'hoge' => 'hey,', 'piyo' => 'say yeah'));
		$debugOutput = ob_get_clean();

		$this->assertPattern('|\r\n\r\n&lt;html&gt;html template&lt;/html&gt;|', $debugOutput);

	}

}
