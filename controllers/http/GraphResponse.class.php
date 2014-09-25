<?php
namespace Graphene\controllers\http;
use Graphene\Graphene;
use Graphene\models\Bean;

class GraphResponse
{

	public function __construct ($forwarding = false)
	{
		$this->forwarding = $forwarding;
		$this->code = 200;
		$this->headers = array();
	}
	
	// Setters
	public function setStatusCode ($status)
	{
		$this->code = $status;
	}

	public function setBody ($body)
	{
		$this->body = $body;
	}

	public function setHeader ($key, $value)
	{
		$this->headers[$key] = $value;
	}
	
	// Getters
	public function isForwarding ()
	{
		return $this->forwarding;
	}

	public function getStatusCode ()
	{
		return $this->code;
	}

	public function getBody ()
	{
		return $this->body;
	}

	public function getHeader ($key)
	{
		foreach ($this->headers as $hk => $hv) {
			if (strcasecmp($key, $hk) == 0)
				return $hv;
		}
		return null;
	}

	public function getHeaders ()
	{
		return $this->headers;
	}

	private $forwarding;

	private $code, $headers, $body;
}