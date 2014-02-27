<?php

namespace b8\Http;

class Response
{
    protected static $codes = array(
        200 => 'OK',
        301 => 'Moved Permanently',
        302 => 'Moved Temporarily',
        400 => 'Bad Request',
        401 => 'Not Authorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        410 => 'Gone',
        500 => 'Internal Server Error',
        503 => 'Service Temporarily Unavailable',
    );

    protected $data = array();

    public function __construct(Response $createFrom = null)
    {
        if (!is_null($createFrom)) {
            $this->data = $createFrom->getData();
        }
    }

    public function hasLayout()
    {
        return !isset($this->data['layout']) ? true : $this->data['layout'];
    }

    public function disableLayout()
    {
        $this->data['layout'] = false;
    }

    public function enableLayout()
    {
        $this->data['layout'] = true;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setResponseCode($code)
    {
        $this->data['code'] = (int)$code;
    }

    public function setHeader($key, $val)
    {
        $this->data['headers'][$key] = $val;
    }

    public function clearHeaders()
    {
        $this->data['headers'] = array();
    }

    public function setContent($content)
    {
        $this->data['body'] = $content;
    }

    public function getContent()
    {
        return $this->data['body'];
    }

    public function flush()
    {
        $this->sendResponseCode();

        if (isset($this->data['headers'])) {
            foreach ($this->data['headers'] as $header => $val) {
                header($header . ': ' . $val, true);
            }
        }

        return $this->flushBody();
    }

    protected function sendResponseCode()
    {
        $code = 200;

        if (isset($this->data['code'])) {
            $code = $this->data['code'];
        }

        if (!isset(self::$codes[$code])) {
            $code = 500;
        }

        $text = self::$codes[$code];

        header('HTTP/1.1 ' . $code . ' ' . $text, true, $code);
    }

    protected function flushBody()
    {
        if (isset($this->data['body'])) {
            return $this->data['body'];
        }

        return '';
    }

    public function __toString()
    {
        return $this->flush();
    }
}
