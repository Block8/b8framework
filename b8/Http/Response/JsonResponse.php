<?php

namespace b8\Http\Response;

use b8\Http\Response;

class JsonResponse extends Response
{
    public function __construct(Response $createFrom = null)
    {
        parent::__construct($createFrom);

        $this->setContent(array());
        $this->setHeader('Content-Type', 'application/json');
    }

    public function hasLayout()
    {
        return false;
    }

    protected function flushBody()
    {
        if (isset($this->data['body'])) {
            return json_encode($this->data['body']);
        }

        return json_encode(null);
    }
}
