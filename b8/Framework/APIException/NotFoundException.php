<?php

namespace b8\Framework\APIException;

class NotFoundException extends \b8\Framework\APIException\GeneralException
{
	protected $errorCode = 404;
	protected $statusMessage = 'Not Found';
}