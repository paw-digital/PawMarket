<?php
namespace Paw;

class Price
{
    public static function getPriceUSD()
    {
		$db = new \Paw\Db();
		$cache = $db->get_cached_item('PRICE_PAW_USD');
		if($cache && $cache['last_change'] > time()-60*10)
			return $cache['data'];
		
		$price = 0.00003000;
		$vite = json_decode(Price::curl_url('https://api.vitex.net/api/v2/market?symbol=PAW-000_USDT-000'));
		$price = $vite->data->lastPrice;
		
		$db->set_cached_item('PRICE_PAW_USD', $price);
		return $price;
    }
	public static function curl_url ($url) {

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 1); 
		curl_setopt($ch, CURLOPT_VERBOSE, 0);
        //curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);

		$output = curl_exec($ch);
		if (curl_errno($ch)) {
			var_dump(curl_error($ch));
		}
		curl_close($ch);
		
		return $output;
	}
}