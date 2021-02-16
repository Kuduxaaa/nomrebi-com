<?php

/**
 * @package simpleapi
 * @author Kuduxaaa
 */

class NomrebiCom
{
	private $cookie_file;
	private String $web_url;
	private String $agent;
	private String $url;

	function __construct()
	{
		$this->agent = 'Mozilla/5.0 (Linux; Android 6.0; LG-H631 Build/MRA58K) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/38.0.2125.102 Mobile Safari/537.36';
		$this->url = 'https://simpleapi.info/apps/numbers-info/info.php';
		$this->web_url = 'https://simpleapi.info/apps/numbers-info/web';
		$this->cookie_file = tempnam("/tmp", "CURLCOOKIE");
		$this->cookies = [];
	}

	function request_post($number, $token)
	{
		$ch = curl_init();
		$data = http_build_query([
			'key' => $token,
			'number' => $number,
			'u_id' => '',
			'u_token' => ''
		]);

		curl_setopt ($ch, CURLOPT_URL, $this->url);
		curl_setopt ($ch, CURLOPT_POST, 1);
		curl_setopt ($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt ($ch, CURLOPT_COOKIEFILE, $this->cookie_file); 
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt ($ch, CURLOPT_HTTPHEADER, ["User-Agent: $this->agent"]);

		return curl_exec($ch);
		curl_close ($ch);
	}


	function validate_number($number)
	{
		if (strlen($number) == 9 && $number[0] == '5')
		{
			return $number;
		}
		else
		{
			return false;
		}
	}


	function get_key()
	{
		$ch = curl_init ($this->web_url);
		curl_setopt ($ch, CURLOPT_COOKIEJAR, $this->cookie_file); 
		curl_setopt ($ch, CURLOPT_HTTPHEADER, ["User-Agent: $this->agent"]);
		curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($ch);

		$func_splited = explode('window.atob(', $response)[1];
		$func_name = explode('.substr', $func_splited)[0];
		$token_tmp_encoded = explode("$func_name = '", $response)[1];
		$token_encoded = explode('\';', $token_tmp_encoded)[0];
		$substr_numbs_tmp = explode("window.atob($func_name.substr(", $response)[1];
		$substr_numbs = explode(')));', $substr_numbs_tmp)[0];
		$substr_numbers = explode(',', $substr_numbs);
		$token_tmp_decoded = base64_decode($token_encoded . '==');

		if (strpos($token_tmp_decoded, 'window.atob'))
		{
			$decode_temp = explode('window.atob(\'', $token_tmp_decoded)[1];
			$decode_temp_ = explode('\')', $decode_temp)[0] . '=';
			$token = base64_decode($decode_temp_);
			return $token;
		}
		else
		{
			return false;
		}

		curl_close($ch);
	}

	function get_info($number)
	{
		$number = $this->validate_number($number);
		if ($number)
		{
			while (true) {
				$key = $this->get_key();
				if ($key) 
				{
					break;
				}
			}

			$response = $this->request_post($number, $key);
			$data = json_decode(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $response), true);
			if ($data['res'] == 'yes')
			{
				return [
					'number' => $data['valid_number'],
					'name' => $data['info']['name']
				];
			}
			else
			{
				return [
					'number' => $number,
					'name' => 'Not Found'
				];
			}
		}
	}
}

// For Example

$nomrebi = new NomrebiCom();
$data = $nomrebi->get_info('555555555')
print_r($data)
