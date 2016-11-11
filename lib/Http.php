<?php

class Simplify_HTTP
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

	const API_NUM_HEADERS     = 7;
	const API_URI             = 'https://api.optune.me/bookings';

	static private $_validMethods = array(
		"post" => self::POST,
		"put" => self::PUT,
		"get" => self::GET,
		"delete" => self::DELETE);

	static private $_userAgent = 'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/40.0.2214.85 Safari/537.36';

	private function request($url, $method, $params = '')
	{
		if (!array_key_exists(strtolower($method), self::$_validMethods)) {
			throw new InvalidArgumentException('Invalid method: '.strtolower($method));
		}

		$method = self::$_validMethods[strtolower($method)];

		$curl = curl_init();

		$options = array();

		$options[CURLOPT_URL] = $url;
		$options[CURLOPT_CUSTOMREQUEST] = $method;
		$options[CURLOPT_RETURNTRANSFER] = true;
		$options[CURLOPT_FAILONERROR] = false;

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
		array_push($headers, 'User-Agent: ' . self::$_userAgent);

		$options[CURLOPT_HTTPHEADER] = $headers;

		curl_setopt_array($curl, $options);

		$data = curl_exec($curl);
		$errno = curl_errno($curl);
		$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

		if ($data == false || $errno != CURLE_OK) {
			throw new Simplify_ApiConnectionException(curl_error($curl));
		}

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
	 * @throws Simplify_AuthenticationException
	 * @throws Simplify_ObjectNotFoundException
	 * @throws Simplify_BadRequestException
	 * @throws Simplify_NotAllowedException
	 * @throws Simplify_SystemException
	 */
	public function apiRequest($url, $method, $params = ''){

		$response = $this->request($url, $method, $params );

		$status = $response['status'];
		$object = $response['object'];

		if ($status == self::HTTP_SUCCESS) {
			return $object;
		}

		if ($status == self::HTTP_REDIRECTED) {
			throw new Simplify_BadRequestException("Unexpected response code returned from the API, have you got the correct URL?", $status, $object);
		} else if ($status == self::HTTP_BAD_REQUEST) {
			throw new Simplify_BadRequestException("Bad request", $status, $object);
		} else if ($status == self::HTTP_UNAUTHORIZED) {
			throw new Simplify_AuthenticationException("You are not authorized to make this request.  Are you using the correct API keys?", $status, $object);
		} else if ($status == self::HTTP_NOT_FOUND) {
			throw new Simplify_ObjectNotFoundException("Object not found", $status, $object);
		} else if ($status == self::HTTP_NOT_ALLOWED) {
			throw new Simplify_NotAllowedException("Operation not allowed", $status, $object);
		} else if ($status < 500) {
			throw new Simplify_BadRequestException("Bad request", $status, $object);
		}
		throw new Simplify_SystemException("An unexpected error has been raised.  Looks like there's something wrong at our end." , $status, $object);
	}

}
