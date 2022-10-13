<?php
namespace Paw;

class Db
{
    protected $mysqli;

    function __construct()
    {
        $this->setMysqli(new \mysqli(MYSQL_DB_HOST, MYSQL_DB_USER, MYSQL_DB_PASSWORD, MYSQL_DB_NAME));
        if ($this->getMysqli()->connect_errno) {
            printf("Connect failed: %s\n", $this->getMysqli()->connect_error);
            exit();
        }
    }

	/** QR AUTH **/
    function qr_get_auth($hash)
    {
        $query = $this->getMysqli()->query(sprintf("SELECT * FROM qr_auth WHERE hash='%s' AND used=%d", mysqli_escape_string($this->getMysqli(), $hash), 0));

        $result = $query->fetch_object();
        return $result ? $result : FALSE;
    }
	function qr_insert_auth($auth_address, $private_key, $hash)
	{
        $ip = isset($_SERVER["HTTP_CF_CONNECTING_IP"]) ? $_SERVER["HTTP_CF_CONNECTING_IP"] : $_SERVER["REMOTE_ADDR"];
        $result = $this->getMysqli()->query(sprintf("INSERT INTO qr_auth (auth_address, private_key, hash, ip, time_added) VALUES ('%s', '%s','%s', '%s', %d)", mysqli_escape_string($this->getMysqli(), $auth_address), mysqli_escape_string($this->getMysqli(), $private_key), mysqli_escape_string($this->getMysqli(), $hash), mysqli_escape_string($this->getMysqli(), $ip), time()));
        if (!$result) {
            printf($this->getMysqli()->error);
            die();
        }

        return $result;
	}
    function qr_set_auth_used($hash, $paw_address)
    {
        $this->getMysqli()->query(sprintf("UPDATE qr_auth SET used=%d, used_paw_address='%s' WHERE hash='%s' AND used=%d", 1, mysqli_escape_string($this->getMysqli(), $paw_address), mysqli_escape_string($this->getMysqli(), $hash), 0));
    }
    function qr_get_expired_auths()
    {
        $query = $this->getMysqli()->query(sprintf("SELECT * FROM qr_auth WHERE time_added<%d ORDER BY id DESC LIMIT 1000", time()-60*10));

        $rows = FALSE;
        while ($row = $query->fetch_object()) {
            $rows[] = $row;
        }

        return $rows;
    }

	/* Market Account */
    function market_create_account($paw_address)
    {
        $result = $this->getMysqli()->query(sprintf("INSERT INTO market_accounts (paw_address, time_created) VALUES ('%s', %d)", mysqli_escape_string($this->getMysqli(), $paw_address), time()));
        if (!$result) {
            printf($this->getMysqli()->error);
            die();
        }

        return $this->getMysqli()->insert_id;
    }
    function market_account_by_paw_address($paw_address)
    {
        $query = $this->getMysqli()->query(sprintf("SELECT * FROM market_accounts WHERE paw_address='%s'", mysqli_escape_string($this->getMysqli(), $paw_address)));

        if (!$query)
            printf($this->getMysqli()->error);

        $result = $query->fetch_object();
		
        return $result ? $result : FALSE;
    }
    function market_account_by_id($id)
    {
        $query = $this->getMysqli()->query(sprintf("SELECT * FROM market_accounts WHERE id='%d'", mysqli_escape_string($this->getMysqli(), $id)));

        if (!$query)
            printf($this->getMysqli()->error);

        $result = $query->fetch_object();
		
        return $result ? $result : FALSE;
    }
    function market_account_update($id, $email, $telegram, $discord)
    {
        $this->getMysqli()->query(sprintf("UPDATE market_accounts SET email='%s', telegram='%s', discord='%s' WHERE id=%d",
		mysqli_escape_string($this->getMysqli(), $email), mysqli_escape_string($this->getMysqli(), $telegram), mysqli_escape_string($this->getMysqli(), $discord), mysqli_escape_string($this->getMysqli(), $id)));
    }
	
	/* Market Listing */
    function market_add_listing($account_id, $type, $title, $body, $price_usd, $price_paw, $image_path)
    {
        $result = $this->getMysqli()->query(sprintf("INSERT INTO market_listings (account_id, type, title, body, price_usd, price_paw, image_path, time_created) VALUES (%d, %d, '%s', '%s', '%s', %d, '%s', %d)",
		mysqli_escape_string($this->getMysqli(), $account_id), mysqli_escape_string($this->getMysqli(), $type), mysqli_escape_string($this->getMysqli(), $title), mysqli_escape_string($this->getMysqli(), $body),
		mysqli_escape_string($this->getMysqli(), $price_usd), mysqli_escape_string($this->getMysqli(), $price_paw), mysqli_escape_string($this->getMysqli(), $image_path), time()));
		
        if (!$result) {
            printf($this->getMysqli()->error);
            die();
        }

        return $this->getMysqli()->insert_id;
    }
    function market_update_listing($id, $account_id, $type, $title, $body, $price_usd, $price_paw, $image_path)
    {
        $this->getMysqli()->query(sprintf("UPDATE market_listings SET account_id=%d, type=%d, title='%s', body='%s', price_usd='%s', price_paw='%s', image_path='%s' WHERE id=%d",
		mysqli_escape_string($this->getMysqli(), $account_id), mysqli_escape_string($this->getMysqli(), $type), mysqli_escape_string($this->getMysqli(), $title), mysqli_escape_string($this->getMysqli(), $body),
		mysqli_escape_string($this->getMysqli(), $price_usd), mysqli_escape_string($this->getMysqli(), $price_paw), mysqli_escape_string($this->getMysqli(), $image_path), mysqli_escape_string($this->getMysqli(), $id)));
    }
    function market_delete_listing($id)
    {
        $this->getMysqli()->query(sprintf("UPDATE market_listings SET removed=%d WHERE id=%d",
		1, mysqli_escape_string($this->getMysqli(), $id)));
    }
    function market_listing_remove_image($id)
    {
        $this->getMysqli()->query(sprintf("UPDATE market_listings SET image_path='' WHERE id=%d",
		mysqli_escape_string($this->getMysqli(), $id)));
    }
    function market_listing_by_id($id)
    {
        $query = $this->getMysqli()->query(sprintf("SELECT * FROM market_listings WHERE id='%d' AND expired=0 AND removed=0", mysqli_escape_string($this->getMysqli(), $id)));

        if (!$query)
            printf($this->getMysqli()->error);

        $result = $query->fetch_object();
		
        return $result ? $result : FALSE;
    }
    function market_all_listings()
    {
        $query = $this->getMysqli()->query(sprintf("SELECT market_listings.*, market_accounts.paw_address FROM market_listings LEFT JOIN market_accounts ON (account_id=market_accounts.id) WHERE expired=0 AND removed=0 ORDER BY market_listings.id DESC"));
        if (!$query)
            printf($this->getMysqli()->error);

		$listings = FALSE;
		while ($row = $query->fetch_array())
			$listings[] = $row;
		
        return $listings;
    }


	/*** CACHING DATA ***/
	function get_cached_item($item)
	{
		$query = $this->getMysqli()->query(sprintf("SELECT * FROM cache WHERE item='%s' ORDER BY id DESC LIMIT 1", mysqli_escape_string($this->getMysqli(), $item)));
		
		if(!$query)
			printf($this->getMysqli()->error);
		
		return $query ? $query->fetch_array() : FALSE;
	}
	function set_cached_item($item, $value)
	{
		if(!$this->get_cached_item($item))
			$query = $this->getMysqli()->query(sprintf("INSERT INTO cache (item,data,last_change) VALUES ('%s','%s',%d)", mysqli_escape_string($this->getMysqli(), $item), mysqli_escape_string($this->getMysqli(), $value), time()));
		else
			$query = $this->getMysqli()->query(sprintf("UPDATE cache SET data='%s', last_change=%d WHERE item='%s'", mysqli_escape_string($this->getMysqli(), $value), time(), mysqli_escape_string($this->getMysqli(), $item)));
		
		if(!$query)
			printf($this->getMysqli()->error);
		
		return $query ? TRUE : FALSE;
	}
	

    public function getMysqli()
    {
        return $this->mysqli;
    }

    public function setMysqli(\Mysqli $mysqli)
    {
        $this->mysqli = $mysqli;
        return $this;
    }
}
