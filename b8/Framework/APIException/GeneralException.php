<?php

namespace b8\Framework\APIException;

class GeneralException extends \Exception
{
	protected $errorCode = 500;
	protected $statusMessage = 'Internal Server Error';
	
	public function getErrorCode()
	{
		return $this->errorCode;
	}
	
	public function getStatusMessage()
	{
		return $this->statusMessage;
	}
	
	public function getHttpHeader()
	{
		return 'HTTP/1.1 ' . $this->errorCode . ' ' . $this->statusMessage;
	}
}