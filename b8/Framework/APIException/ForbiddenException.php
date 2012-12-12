<?php

namespace b8\Framework\APIException;

class ForbiddenException extends \b8\Framework\APIException\GeneralException
{
	protected $errorCode = 403;
	protected $statusMessage = 'Forbidden';
}