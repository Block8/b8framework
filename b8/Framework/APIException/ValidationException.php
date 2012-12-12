<?php

namespace b8\Framework\APIException;

class ValidationException extends \b8\Framework\APIException\GeneralException
{
	protected $errorCode = 400;
	protected $statusMessage = 'Bad Request';
}