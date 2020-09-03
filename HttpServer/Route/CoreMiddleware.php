<?php


namespace HttpServer\Route;


use Closure;
use Exception;
use HttpServer\Http\Context;
use HttpServer\Http\Request;
use HttpServer\Http\Response;

/**
 * Class CoreMiddleware
 * @package Snowflake\Snowflake\Route
 * 跨域中间件
 */
class CoreMiddleware implements \HttpServer\IInterface\Middleware
{

	/**
	 * @param Request $request
	 * @param Closure $next
	 * @return mixed
	 * @throws Exception
	 */
	public function handler(Request $request, Closure $next)
	{
		$header = $request->headers;

		/** @var Response $response */
		$response = Context::getContext('response');
		$request_method = $header->getHeader('access-control-request-method');
		$request_headers = $header->getHeader('access-control-request-headers');
		$response->addHeader('Access-Control-Allow-Headers', $request_headers);
		$response->addHeader('Access-Control-Request-Method', $request_method);

		return $next($request);
	}

}