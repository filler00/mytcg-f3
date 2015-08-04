<?php
/*
	SwiftMailer Wrapper
	Automatically sets the transport based on your MyTCG settings (config.ini)
	Also automatically generates plain-text alternate message
*/

namespace Filler00;

use Base;
use Swift_Mailer;
use Swift_Message;
use Swift_SmtpTransport;
use Swift_SendmailTransport;
use Swift_MailTransport;

class Mailer {
	
	protected $f3;
	
	function __construct() {
		$f3 = Base::instance();
		$this->f3 = $f3;
	}
	
	public function message ()
	{
		require('vendor/swiftmailer/lib/swift_required.php');
		return Swift_Message::newInstance();
	}
	public function send ($message) 
	{
	
		// Create the Transport
		if ( $this->f3->get('emailtransport') == 'smtp' ) {
			// SMTP
			$transport = Swift_SmtpTransport::newInstance($this->f3->get('smtpserver'), $this->f3->get('smtpport'))
				->setUsername($this->f3->get('smtpuser'))
				->setPassword($this->f3->get('smtppass'))
				;
		} 
		else if ( $this->f3->get('emailtransport') == 'sendmail' )
		{
			// Sendmail
			$transport = Swift_SendmailTransport::newInstance('/usr/sbin/sendmail -bs');
		}
		else 
		{
			// use PHP mail by default
			$transport = Swift_MailTransport::newInstance();
		}

		// Create the Mailer using the specified Transport
		$mailer = Swift_Mailer::newInstance($transport);
		
		// Generate plain-text alternate email
		$plaintext = html_entity_decode(strip_tags($message->getBody()), null, 'utf-8');
		$message = $message->addPart($plaintext, 'text/plain');
		
		return $mailer->send($message);
		
	}
}