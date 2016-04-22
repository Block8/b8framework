<?php

namespace b8\Exception;

use Exception;
use b8\Config;
use b8\Exception\HttpException;
use b8\Http\Request;
use b8\Http\Response;
use b8\View;
use b8\View\Template;

/**
 * b8 Framework Exception Handler - Provides a .NET-like UI for uncaught exceptions.
 * @package b8\Exception
 */
class Handler
{
    /**
     * @var \b8\Config
     */
    protected $config;

    /**
     * @var \b8\Http\Request
     */
    protected $request;

    /**
     * @var \b8\Http\Response
     */
    protected $response;

    /**
     * @var \b8\View
     */
    protected $friendlyTemplate;

    /**
     * @param Config $config
     * @param Request $request
     * @param Response $response
     */
    public function __construct(Config $config, Request $request, Response $response)
    {
        $this->config = $config;
        $this->request = $request;
        $this->response = $response;

        set_exception_handler([$this, 'handleException']);
    }

    /**
     * Set the template to use for exceptions when display_errors is turned off.
     * @param View $template
     */
    public function setFriendlyTemplate(View $template)
    {
        $this->friendlyTemplate = $template;
    }

    public function handleException($ex)
    {
        if (ini_get('display_errors')) {
            $template = Template::createFromFile('UncaughtExceptionTemplate', B8_PATH . 'Exception/');
        } elseif (isset($this->friendlyTemplate)) {
            $template =& $this->friendlyTemplate;
        } else {
            $template = Template::createFromFile('FriendlyUncaughtExceptionTemplate', B8_PATH . 'Exception/');
        }

        $template->file = $ex->getFile();
        $template->line = $ex->getLine();
        $template->message = $ex->getMessage();
        $template->trace = $this->extendTrace($ex->getTrace());
        $template->code = $this->getErrorLines($ex->getFile(), $ex->getLine() - 1);

        list($file, $line) = $this->findApplicationExceptionSource($template->trace);

        if ($file != $ex->getFile() || $line != $ex->getLine()) {
            $template->app_code = $this->getErrorLines($file, $line - 1);
        }

        $template->addFunction('var_dump', function ($args, &$view) {
            $item = $view->getVariable($args['item']);

            ob_start();
            var_dump($item);
            $rtn = ob_get_contents();
            ob_end_clean();

            return $rtn;
        });

        if ($ex instanceof HttpException) {
            header($ex->getHttpHeader(), true, $ex->getErrorCode());
        } else {
            header('HTTP/1.1 500 Internal Server Error', true, 500);
        }

        die($template->render());
    }

    protected function findApplicationExceptionSource(array $trace)
    {
        foreach ($trace as $item) {
            if (!$item['b8_file']) {
                return [!empty($item['file']) ? $item['file'] : '', !empty($item['line']) ? $item['line'] : ''];
            }
        }

        return null;
    }

    protected function extendTrace(array $trace)
    {
        foreach ($trace as &$item) {
            $item['b8_file'] = false;

            if (isset($item['file']) && stripos($item['file'], B8_PATH) !== false) {
                $item['b8_file'] = true;
                $item['file'] = str_replace(B8_PATH, '', $item['file']);
            }

            foreach ($item['args'] as &$arg) {
                switch (gettype($arg)) {
                    case 'boolean':
                        $arg = ['type' => 'bool', 'value' => ($arg ? 'true' : 'false')];
                        break;
                    case 'integer':
                        $arg = ['type' => 'int', 'value' => $arg];
                        break;
                    case 'double':
                        $arg = ['type' => 'double', 'value' => $arg];
                        break;
                    case 'string':
                        $add = strlen($arg) > 100 ? '...' : '';
                        $arg = ['type' => 'string', 'value' => '"' . htmlspecialchars(substr($arg, 0, 100)) . $add . '"'];
                        break;
                    case 'array':
                        $arg = ['type' => 'array'];
                        break;
                    case 'object':
                        $arg = ['type' => 'object', 'value' => get_class($arg)];
                        break;
                    case 'resource':
                        $arg = ['type' => 'resource'];
                        break;
                    case 'NULL':
                        $arg = ['type' => 'null'];
                        break;
                    default:
                        $arg = ['type' => 'unknown'];
                        break;
                }
            }
        }

        return $trace;
    }

    protected function getErrorLines($file, $line)
    {
        $lines = @file($file);

        $startLine = $line - 10;
        $endLine = $line + 10;

        if ($startLine < 0) {
            $startLine = 0;
        }

        if ($endLine >= count($lines)) {
            $endLine = count($lines) - 1;
        }

        $rtn = [];
        for ($i = $startLine; $i <= $endLine; $i++) {
            $rtn[] = [
                'line' => $i,
                'is_error_line' => ($i === $line),
                'code' => htmlspecialchars($lines[$i]),
            ];
        }

        return $rtn;
    }
}
