<?php

namespace b8\Framework\APIException;

class NotAuthorizedException extends \b8\Framework\APIException\GeneralException
{
	protected $errorCode = 401;
	protected $statusMessage = 'Not Authorized';
}