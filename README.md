# Mailer Plugin #

This is a CakePHP plugin to help managing to send emails.


* This especially applies the OOP style.
* Possible to use Configure for auto detecting headers for email.
* This uses Qdmail(http://hal456.net/qdmail/), that is very solid for multibyte email.


## Requirements

* PHP > 5
* CakePHP 1.3

## Version

This was versioned as 0.1 Beta.

## Installation

in your plugins directory,

	git clone git://github.com/hiromi2424/mailer.git

or in current directory of your repository,

	git submodule add git://github.com/hiromi2424/mailer.git plugins/mailer

## Example

### You can create your mailers for each dynamic mails.

* app/controllers/components/signup_mailer.php


		<?php
		App::import('Component', 'Mailer.Mailer');
		
		class SignupMailerComponent extends Mailer {
		}


* app/controllers/users_controller.php


		<?php
		
		class UsersController extends AppController {
			public $components = array('SignupMailer');
		
			public function signup() {
				// ...
				$this->SignupMailer->send(array(
					'email' => 'destination@example.com', // or 'to' key
					'subject' => 'signup subject',
					'token' => $token,
				));
			}
		}


* app/views/elements/email/text/signup.ctp

		Dear <?php echo $content['email'] ?>, please register with following url:
		
		<?php echo Router::url(array('controller' => 'users', 'action' => 'register', $content['token'])) ?>


### You can use Mailer component dynamically.

		<?php
		
		class UsersController extends AppController {
			public $components = array('Mailer');
		
			public function signup() {
				// ...
				$this->Mailer->send(array(
					'template' => 'signup',
					'email' => 'destination@example.com', // or 'to' key
					'subject' => 'signup subject',
					'token' => $token,
				));
			}
		}

### Overriding api
It is preferable to override methods to implement your own logic for emails.
Auto detecting headers(from, to, subject), template, qdmail's parameters or so.


* detectFrom() - detecting from address.
* detectTo() - detecting from address.
* detectsubject() - detecting subject.
* prepare() - prepares all properties.
* setParams() - decide the parameters what email template uses.
* setSendParameters() - set Qdmail parameters for actual sending email.
* setDebugParameters() - set Qdmail parameters for debugging email status.

### Using Configure

see https://github.com/hiromi2424/mailer/blob/master/config/mail.php.default for example.

If you use this feature, you won't need to specify "from" and "subject" fields on sending email every time.

## Finally

make your DRY :)

## License

Licensed under The MIT License.
Redistributions of files must retain the above copyright notice.


Copyright 2011 hiromi, https://github.com/hiromi2424

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.