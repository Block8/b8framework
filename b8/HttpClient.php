<?php

namespace b8;

class HttpClient
{
    protected $base = '';
    protected $params = array();
    protected $headers = array();
    protected $auth;

    public function __construct($base = null)
    {
        $settings = Config::getInstance()->get('b8.http.client', array('base_url' => '', 'params' => array()));
        $this->base = $settings['base_url'];
        $this->params = isset($settings['params']) && is_array($settings['params']) ? $settings['params'] : array();
        $this->headers = array('Content-Type: application/x-www-form-urlencoded');

        if (!is_null($base)) {
            $this->base = $base;
        }
    }

    public function setBasicAuth($username, $password)
    {
        $this->headers[] = 'Authorization: Basic ' . base64_encode($username . ':' . $password);
    }

    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
    }

    public function request($method, $uri, $params = array())
    {
        list($response, $headers) = $this->makeRequest($method, $uri, $params);

        return $this->processResponse($response, $headers);
    }

    protected function processResponse($response, $headers)
    {
        $return = array();
        $return['headers'] = $headers;
        $return['code'] = (int)preg_replace('/HTTP\/1\.[0-1] ([0-9]+)/', '$1', $headers[0]);
        $return['success'] = false;
        $return['body'] = $this->decodeResponse($response);

        if ($return['code'] >= 200 && $return['code'] < 300) {
            $return['success'] = true;
        }

        $this->processHeaders($headers, $return);

        return $return;
    }

    protected function processHeaders($headers, &$return)
    {
        foreach ($headers as $header) {
            if (stripos($header, 'Content-Type') !== false) {
                if (stripos($header, 'application/json') !== false) {
                    $return['text_body'] = $return['body'];
                    $return['body'] = json_decode($return['body'], true);
                }
            }
        }
    }

    protected function makeRequest($method, $uri, $params = array())
    {
        // Clean incoming:
        $method = strtoupper($method);
        $getParams = $this->params;

        if ($method == 'GET' || $method == 'DELETE') {
            $getParams = array_merge($getParams, $params);
        } else {
            $bodyParams = is_array($params) ? http_build_query($params) : $params;
        }

        $getParams = http_build_query($getParams);

        if (substr($uri, 0, 1) != '/' && !empty($this->base)) {
            $uri = '/' . $uri;
        }

        // Build HTTP context array:
        $context = array();
        $context['http']['user_agent'] = 'b8/1.0';
        $context['http']['timeout'] = 30;
        $context['http']['method'] = $method;
        $context['http']['ignore_errors'] = true;
        $context['http']['header'] = implode(PHP_EOL, $this->headers);

        if (in_array($method, array('PUT', 'POST'))) {
            $context['http']['content'] = $bodyParams;
        }

        $uri .= '?' . $getParams;

        $context = stream_context_create($context);
        $http_response_header = null; // Creating this purely to stop PHPCS complaining.
        $response = file_get_contents($this->base . $uri, false, $context);

        return array($response, $http_response_header);
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

    protected function decodeResponse($chunk)
    {
        $pos = 0;
        $len = strlen($chunk);
        $dechunk = null;

        while (($pos < $len)
            && ($chunkLenHex = substr($chunk, $pos, ($newlineAt = strpos($chunk, "\n", $pos+1))-$pos))) {
            if (!$this->isHex($chunkLenHex)) {
                trigger_error('Value is not properly chunk encoded', E_USER_WARNING);
                return $chunk;
            }

            $pos = $newlineAt + 1;
            $chunkLen = hexdec(rtrim($chunkLenHex, "\r\n"));
            $dechunk .= substr($chunk, $pos, $chunkLen);
            $pos = strpos($chunk, "\n", $pos + $chunkLen) + 1;
        }

        return $dechunk;
    }

    protected function isHex($hex)
    {
        $hex = strtolower(trim(ltrim($hex, "0")));

        if (empty($hex)) {
            $hex = 0;
        };

        $dec = hexdec($hex);
        return ($hex == dechex($dec));
    }
}
