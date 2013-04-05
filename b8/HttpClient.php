<?php

namespace b8;

class HttpClient
{
	protected $_base		= '';
	protected $_params	= array();

	public function __construct($base = null)
	{
		$settings		= \b8\Registry::getInstance()->get('HttpClient');
		$this->_base	= $settings['base'];
		$this->_params	= isset($settings['params']) && is_array($settings['params']) ? $settings['params'] : array();

		if(!is_null($base))
		{
			$this->_base = $base;
		}
	}

	public function request($method, $uri, $params = array())
	{
		// Clean incoming:
		$method									= strtoupper($method);
		$getParams								= $this->_params;
		$bodyParams								= array();

		if($method == 'GET' || $method == 'DELETE')
		{
			$getParams							= array_merge($getParams, $params);
		}
		else
		{
			$bodyParams							= array_merge($bodyParams, $params);
		}
		
		$getParams								= http_build_query($getParams);
		$bodyParams								= http_build_query($bodyParams);

		if(substr($uri, 0, 1) != '/')
		{
			$uri								= '/' . $uri;
		}

		// Build HTTP context array:
		$context								= array();
		$context['http']['user_agent'] 			= 'b8/1.0';
		$context['http']['timeout'] 			= 30;
		$context['http']['method']				= $method;
		$context['http']['ignore_errors']		= true;

		if(in_array($method, array('PUT', 'POST')))
		{
			$context['http']['content']			= $bodyParams;
		}
		
		$uri									.= '?' . $getParams;
		
		$context								= stream_context_create($context);
		$result									= file_get_contents($this->_base . $uri, false, $context);

		$res 									= array();
		$res['headers']							= $http_response_header;
		$res['code']							= (int)preg_replace('/HTTP\/1\.[0-1] ([0-9]+)/', '$1', $res['headers'][0]);
		$res['success']							= false;

		if(B8_DEBUG_MODE && isset($_GET['http']))
		{
			print $result . PHP_EOL;
		}

		$res['body']							= $this->_decodeResponse($result);

		if(B8_DEBUG_MODE && isset($_GET['http']))
		{
			print $res['body']. PHP_EOL;
		}

		if($res['code'] >= 200 && $res['code'] < 300)
		{
			$res['success']						= true;
		}

		// Handle JSON responses:
		foreach($res['headers'] as $header)
		{
			if(stripos($header, 'Content-Type') !== false || stripos($header, 'b8-Type') !== false)
			{
				if(stripos($header, 'application/json') !== false)
				{
					$res['text_body']	= $res['body'];
					$res['body']		= json_decode($res['body'], true);
				}
			}
		}

		if(B8_DEBUG_MODE && isset($_GET['http']))
		{
			print $res['body']. PHP_EOL;
			die;
		}

		return $res;
	}

	public function get($uri, $params = array())
	{
		return $this->request('GET', $uri, $params);
	}

	public function put($uri, $params = array())
	{
		return $this->request('PUT', $uri, $params);
	}

	public function post($uri, $params = array())
	{
		return $this->request('POST', $uri, $params);
	}

	public function delete($uri, $params = array())
	{
		return $this->request('DELETE', $uri, $params);
	}

	protected function _decodeResponse($originalResponse)
	{
		$response = $originalResponse;
		$body = '';

		do
		{
			$line = $this->_readChunk($response);

			if($line == PHP_EOL)
			{
				continue;
			}

			$length = hexdec(trim($line));

			if(!is_int($length) || empty($response))
			{
				return $originalResponse;
			}

			if ($line === false || $length < 1 || empty($response)) 
			{
		        // break out of the streams loop
		        break;
		    }

		    do
		    {
		    	$data = $this->_readChunk($response, $length);

		        // remove the amount received from the total length on the next loop
		        // it'll attempt to read that much less data
		        $length -= strlen($data);

		        // store in string for later use
		        $body .= $data;

		        // zero or less or end of connection break
		        if ($length <= 0 || empty($response))
		        {
		            break;
		        }
		    }
		    while(true);
		}
		while(true);

		return $body;
	}

	function _readChunk(&$string, $len = 4096)
	{
		$rtn = '';
		for($i = 0; $i <= $len; $i++)
		{
			if(empty($string))
			{
				break;
			}

			$char	= $string[0];
			$string	= substr($string, 1);
			$rtn	.= $char;

			if($char == PHP_EOL)
			{
				break;
			}
		}

		return $rtn;
	}
}