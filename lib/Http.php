<?php

class Optune_Simplify_HTTP
{
	const DELETE = "DELETE";
	const GET = "GET";
	const POST = "POST";
	const PUT = "PUT";

	const HTTP_SUCCESS = 200;
	const HTTP_REDIRECTED = 302;
	const HTTP_UNAUTHORIZED = 401;
	const HTTP_NOT_FOUND = 404;
	const HTTP_NOT_ALLOWED = 405;
	const HTTP_BAD_REQUEST = 400;

	const API_URI = 'https://api.optune.me/bookings';

	const USER_AGENT = 'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/40.0.2214.85 Safari/537.36';
	const TIMEOUT = 60;


	static private $_validMethods = array(
		"post" => self::POST,
		"put" => self::PUT,
		"get" => self::GET,
		"delete" => self::DELETE);

	private function request($url, $method, $params = '')
	{
		if (!array_key_exists(strtolower($method), self::$_validMethods))
			return array('status' => FALSE, 'object' => FALSE);

		$method = self::$_validMethods[strtolower($method)];

		// Setup full API Url
		$url = self::API_URI . '/' . $url;

		$curl = curl_init();

		$headers = array();
		$options = array();
		$options[CURLOPT_URL] = $url;
		$options[CURLOPT_CUSTOMREQUEST] = $method;
		$options[CURLOPT_RETURNTRANSFER] = TRUE;
		$options[CURLOPT_FAILONERROR] = FALSE;
		$options[CURLOPT_NOBODY] = FALSE; // get body request
		$options[CURLOPT_USERAGENT] = self::USER_AGENT;
		$options[CURLOPT_AUTOREFERER] = TRUE; // add REFERER header
		$options[CURLOPT_FOLLOWLOCATION] = TRUE; // add auto redirect
		$options[CURLOPT_CONNECTTIMEOUT] =  self::TIMEOUT; // set connection timeout
		$options[CURLOPT_TIMEOUT] = self::TIMEOUT;

		if ($method == self::POST || $method == self::PUT) {
			$headers = array(
				 'Content-type: application/json'
			);
			if( !empty( $params ) ){
            $options[CURLOPT_POST] = 1;
				$options[CURLOPT_POSTFIELDS] = self::encode($params); }
		} else {
            $options[CURLOPT_HTTPGET] = 1;}

		array_push($headers, 'Accept: application/json');

		$options[CURLOPT_HTTPHEADER] = $headers;

		curl_setopt_array($curl, $options);

		$data = curl_exec($curl);
		$errno = curl_errno($curl);
		$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

		if ($data == false || $errno != CURLE_OK)
			return array('status' => FALSE, 'object' => FALSE);

		$object = json_decode($data, true);
		$response = array('status' => $status, 'object' => $object);

		return $response;
		curl_close($curl);
	}

    /**
     * @param array $arr An map of param keys to values.
     * @param string|null $prefix
     *
     * Only public for testability, should not be called outside of CurlClient
     *
     * @return string A querystring, essentially.
     */
    private function encode($arr, $prefix = null)
    {
        if (!is_array($arr)) {
            return $arr;
        }

        $r = array();
        foreach ($arr as $k => $v) {
            if (is_null($v)) {
                continue;
            }

            if ($prefix) {
                if ($k !== null && (!is_int($k) || is_array($v))) {
                    $k = $prefix."[".$k."]";
                } else {
                    $k = $prefix."[]";
                }
            }

            if (is_array($v)) {
                $enc = self::encode($v, $k);
                if ($enc) {
                    $r[] = $enc;
                }
            } else {
                $r[] = urlencode($k)."=".urlencode($v);
            }
        }

        return implode("&", $r);
    }

	/**
	 * Handles Simplify API requests
	 *
	 * @param $url
	 * @param $method
	 * @param $authentication
	 * @param string $payload
	 * @return mixed
	 */
	public function apiRequest($url, $method, $params = ''){

		$response = $this->request($url, $method, $params );

		$status = $response['status'];
		$object = $response['object'];

		if ($status == self::HTTP_SUCCESS) {
			return $object;
		}

		return FALSE;
	}

}
