<?php

namespace b8\Framework\APIException;

class BadRequestException extends \b8\Framework\APIException\GeneralException
{
	protected $errorCode = 400;
	protected $statusMessage = 'Bad Request';
}