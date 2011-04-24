<?php

App::import('Component', 'Mailer.Mailer');

class ErrorMailerComponent extends MailerComponent {

	public function detectTo($params) {
		if ($to = parent::detectTo($params)) {
			return $to;
		}

		$candidates = array(
			Configure::read($this->config . '.addresses.debug'),
			Configure::read($this->config . '.addresses.admin'),
		);

		if ($to = current(array_filter($candidates))) {
			return $to;
		}
	}

	public function detectSubject($params) {
		if ($subject = parent::detectSubject($params)) {
			return $subject;
		}

		$siteNameCandidates = array(
			Configure::read('Site.name'),
			Configure::read($this->config . '.siteName'),
			env('SERVER_NAME'),
			env('HTTP_HOST'),
			env('HOST_NAME'),
			env('COMPUTERNAME'),
		);

		$siteName = current(array_filter($siteNameCandidates));
		if (empty($siteName)) {
			// detecting failed.
			$siteName = 'Unknown';
		}

		return sprintf(__d('mailer', '[%s] An error was occurred', true), $siteName);
	}

}
