<?php


namespace HttpServer\Exception;


use Throwable;

/**
 * Class ExitException
 * @package HttpServer\Exception
 */
class ExitException extends \Exception
{

	/**
	 * ExitException constructor.
	 * @param string $message
	 * @param int $code
	 * @param Throwable|null $previous
	 */
	public function __construct($message = "", $code = 0, Throwable $previous = null)
	{
		parent::__construct($message, $code, $previous);
	}

}
