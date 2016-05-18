<?php

namespace b8\Http;

class Request
{
    /**
     * @var array
     */
    protected $params = array();

    /**
     * Request data.
     */
    protected $data = array();

    /**
     * Set up the request.
     */
    public function __construct()
    {
        $this->parseInput();

        $this->data['path'] = $this->getRequestPath();
        $this->data['parts'] = array_values(array_filter(explode('/', $this->data['path'])));
    }

    protected function getRequestPath()
    {
        $path = '';

        // Start out with the REQUEST_URI:
        if (!empty($_SERVER['REQUEST_URI'])) {
            $path = $_SERVER['REQUEST_URI'];
        }

        // This should fix things if we're not in the document root:
        if (realpath($_SERVER['DOCUMENT_ROOT']) != dirname($_SERVER['SCRIPT_FILENAME'])) {
            $basePath = str_replace($_SERVER['DOCUMENT_ROOT'], '', dirname($_SERVER['SCRIPT_FILENAME']));
            $path = substr($path, strlen($basePath));
        }

        // Remove index.php from the URL if it is present:
        $path = str_replace(array('/index.php', 'index.php'), '', $path);

        // Also cut out the query string:
        $path = explode('?', $path);
        $path = array_shift($path);

        return $path;
    }

    /**
     * Parse incoming variables, incl. $_GET, $_POST and also reads php://input for PUT/DELETE.
     */
    protected function parseInput()
    {
        $params = $_REQUEST;

        if (!isset($_SERVER['REQUEST_METHOD']) || in_array($_SERVER['REQUEST_METHOD'], array('PUT', 'DELETE'))) {
            $vars = file_get_contents('php://input');

            if (!is_string($vars) || strlen(trim($vars)) === 0) {
                $vars = '';
            }

            $inputData = array();
            parse_str($vars, $inputData);

            $params = array_merge($params, $inputData);
        }

        $this->setParams($params);
    }

    /**
     * Returns all request parameters.
     * @var array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Return a specific request parameter, or a default value if not set.
     */
    public function getParam($key, $default = null)
    {
        if (isset($this->params[$key])) {
            return $this->params[$key];
        } else {
            return $default;
        }
    }

    /**
     * Check whether or not a specific request parameter is set.
     * @param $key
     * @return bool
     */
    public function hasParam($key)
    {
        return array_key_exists($key, $this->params);
    }

    /**
     * Set or override a request parameter.
     */
    public function setParam($key, $value = null)
    {
        $this->params[$key] = $value;
    }

    /**
     * Set an array of request parameters.
     */
    public function setParams(array $params)
    {
        $this->params = array_merge($this->params, $params);
    }

    /**
     * Un-set a specific parameter.
     */
    public function unsetParam($key)
    {
        unset($this->params[$key]);
    }

    public function getMethod()
    {
        return strtoupper($_SERVER['REQUEST_METHOD']);
    }

    public function getPath()
    {
        return $this->data['path'];
    }

    public function getPathParts()
    {
        return $this->data['parts'];
    }

    public function isAjax()
    {
        if (!isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            return false;
        }

        if (strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            return true;
        }

        return false;
    }
}
