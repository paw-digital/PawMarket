<?php
namespace Paw\Controller;

use Paw\AbstractController;
use Paw\Twilio;
use Paw\SocialConnect\Factory;
use Paw\Utils;
use Paw\Twitter;
use Paw\Email;
use Paw\Helper;
use SocialConnect\Common\Entity\User;

class CronController extends AbstractController
{
    public function run()
    {
		$this->sendBackQRAuthDeposits();
	}
	
	// Send all QR auth coins back
	private function sendBackQRAuthDeposits()
	{
		$addresses = $this->getDb()->qr_get_expired_auths();
		if(!$addresses)
			return;
		
		foreach($addresses as $address)
		{
			$node = new \Paw\Node\Client();
			$result = $node->sendBackPAW(AUTH_WALLET, $address->auth_address, $address->private_key);
		}
	}
}