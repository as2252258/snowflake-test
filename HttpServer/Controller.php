<?php


namespace HttpServer;


use HttpServer\Http\HttpHeaders;
use HttpServer\Http\HttpParams;
use HttpServer\Http\Request;
use Exception;
use Snowflake\Abstracts\BaseGoto;
use Snowflake\Snowflake;

/**
 * Class WebController
 * @package Snowflake\Snowflake\Web
 * @property BaseGoto $goto
 * @property \Snowflake\Application $app
 */
class Controller extends Application
{

	/** @var HttpParams $input */
	public $input;


	/** @var HttpHeaders */
	public $headers;


	/** @var Request */
	public $request;


	public $goto;


	public $app;


	public function __construct($config = [])
	{
		$this->app = Snowflake::app();
		$this->goto = $this->app->goto;
		parent::__construct($config);
	}

	/**
	 * @param HttpParams $input
	 */
	public function setInput(HttpParams $input): void
	{
		$this->input = $input;
	}

	/**
	 * @param HttpHeaders $headers
	 */
	public function setHeaders(HttpHeaders $headers): void
	{
		$this->headers = $headers;
	}

	/**
	 * @param Request $request
	 */
	public function setRequest(Request $request): void
	{
		$this->request = $request;
	}

	/**
	 * @return HttpParams
	 * @throws Exception
	 */
	public function getInput(): HttpParams
	{
		if (!$this->input) {
			$this->input = $this->getRequest()->params;
		}
		return $this->input;
	}

	/**
	 * @return HttpHeaders
	 * @throws Exception
	 */
	public function getHeaders(): HttpHeaders
	{
		if (!$this->headers) {
			$this->headers = $this->getRequest()->headers;
		}
		return $this->headers;
	}

	/**
	 * @return Request
	 * @throws Exception
	 */
	public function getRequest(): Request
	{
		if (!$this->request) {
			$this->request = Snowflake::app()->request;
		}
		return $this->request;
	}


}
