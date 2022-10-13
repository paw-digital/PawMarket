<?php
namespace Paw\Controller;

use Paw\AbstractController;
use Paw\Email;
use Paw\Utils;
use Paw\Node;
use Paw\Helper;
use Paw\Db;

class LoginController extends AbstractController
{
    public function index()
    {
        $this->addHeader();
        $this->addTemplate('partials/login.phtml');
        $this->addFooter();
        return $this->getResponse();
    }
    public function logout()
    {
		session_destroy();
		header('Location: /');
		die();
	}
    public function qr_auth()
    {
		header("Content-Type: application/json");
		
		$json = FALSE;
		$json['result'] = 'unauthenticated';
		
		if(isset($_POST['hash']))
		{
			$time_end = time() + 10;
			while(true)
			{
				if(time() > $time_end)
					break;
				
				$hash = $_POST['hash'];
				$qr_auth = $this->getDb()->qr_get_auth($hash);
				
				$node = new \Paw\Node\Client();
				$node->receivePending(AUTH_WALLET, $qr_auth->private_key);
				$paw_address = $node->getAuthSender($qr_auth->auth_address);
				
				if($paw_address)
				{
					$json['result'] = 'success';
					
					$this->getDb()->qr_set_auth_used($hash, $paw_address);
					$account = $this->getDb()->market_account_by_paw_address($paw_address);
					if($account)
					{
						$_SESSION["ACCOUNT_ID"] = $account->id;
					}
					else
					{
						$account_id = $this->getDb()->market_create_account($paw_address);
						$_SESSION["ACCOUNT_ID"] = $account_id;
					}
					
					break;
				}
			
				sleep(1);
			}
		}
		
		echo json_encode($json);
		die();
	}
}