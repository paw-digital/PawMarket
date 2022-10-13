<?php
namespace Paw\Node;

use GuzzleHttp\Client as HttpClient;
use Paw\Helper;

class Client
{
    protected $client;
	protected $node_address;

    public function __construct()
    {
        $this->setClient(new HttpClient());
		$this->node_address = NODE_ADDRESS;
    }

    public function send($post)
    {
        try {
            $response = $this->getClient()->post($this->node_address, ['body' => json_encode($post)]);
        } catch (\Exception $e) {
            echo $e->getMessage();
            return;
        }
        return json_decode((string) $response->getBody());
    }
	
	function newAuthAccount()
	{
		$WALLET = FALSE;
		
		// First generate a key
		$key = $this->send([
            "action" => "key_create"
        ]);
		$WALLET['private_key'] = $key->private;
		$WALLET['public_key'] = $key->public;
		$WALLET['account'] = $key->account;
		$WALLET['wallet'] = AUTH_WALLET;

		// Add private key to wallet
		$this->send([
            "action" => "wallet_add",
			"wallet" => AUTH_WALLET,
            "key" => $key->private
        ]);
		
		return $WALLET;
	}
    public function getAuthSender($account)
    {
        $transactions = $this->send([
            "action" => "account_history",
			"count" => "-1",
            "account" => $account
        ]);
		
		if(empty($transactions->history))
			return FALSE;
		
		$sender = FALSE;
		foreach($transactions->history as $tx)
		{
			if($tx->type != 'receive')
				continue;
			
			$sender = $tx->account;
		}
		
		return $sender;
    }
    public function receivePending($wallet, $private_key)
    {
		// Add wallet to account
        $wallet_add = $this->send([
            "action" => "wallet_add",
			"wallet" => $wallet,
            "key" => $private_key
        ]);
		$account = $wallet_add->account;
		
		// get pending
        $pending = $this->send([
            "action" => "pending",
			"count" => 10,
            "account" => $account
        ]);
		
		// Receive pending
		if(!empty($pending->blocks))
		{
			foreach($pending->blocks as $block)
			{
				$res = $this->send([
					"action" => "receive",
					"wallet" => $wallet,
					"account" => $account,
					"block" => $block
				]);
			}
		}
			
		// Remove account
		$this->send([
            "action" => "account_remove",
			"wallet" => $wallet,
            "account" => $account
        ]);
    }
    public function sendBackPAW($wallet, $account, $private_key)
    {
        $balance = $this->send([
            "action" => "account_balance",
            "account" => $account
        ]);
		
		if($balance->pending == '0' && $balance->balance == '0')
			return;
		
		// Add wallet to account
        $wallet_add = $this->send([
            "action" => "wallet_add",
			"wallet" => $wallet,
            "key" => $private_key
        ]);
		$account = $wallet_add->account;
		
		// Receive pending
		if($balance->pending != '0')
		{
			// get pending
			$pending = $this->send([
				"action" => "pending",
				"count" => 10,
				"account" => $account
			]);
			
			if(!empty($pending->blocks))
			{
				foreach($pending->blocks as $block)
				{
					$res = $this->send([
						"action" => "receive",
						"wallet" => $wallet,
						"account" => $account,
						"block" => $block
					]);
				}
			}
		}
		else if($balance->balance != '0')
		{
			// Send back PAW
			$transactions = $this->send([
				"action" => "account_history",
				"count" => "-1",
				"account" => $account
			]);
			if(empty($transactions->history))
				return FALSE;
			
			$sender = FALSE;
			foreach($transactions->history as $tx)
			{
				if($tx->type != 'receive')
					continue;
				
				// Send
				$result = $this->send([
					"action" => "send",
					"wallet" => $wallet,
					"source" => $account,
					"destination" => $tx->account,
					"amount" => $tx->amount,
					"id" => 'back-'.$tx->hash
				]);
			}
		}
		
		// Remove account
		$this->send([
            "action" => "account_remove",
			"wallet" => $wallet,
            "account" => $account
        ]);
    }



    public function getClient()
    {
        return $this->client;
    }
    public function setClient(HttpClient $client)
    {
        $this->client = $client;
        return $this;
    }
}