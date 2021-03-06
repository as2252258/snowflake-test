<?php


namespace HttpServer\Events;


use Exception;
use HttpServer\Abstracts\Callback;
use Snowflake\Event;
use Snowflake\Exception\ComponentException;
use Snowflake\Snowflake;
use Swoole\Server;

/**
 * Class OnPipeMessage
 * @package HttpServer\Events
 */
class OnPipeMessage extends Callback
{

	/**
	 * @param Server $server
	 * @param int $src_worker_id
	 * @param $message
	 * @throws ComponentException
	 * @throws Exception
	 */
	public function onHandler(Server $server, int $src_worker_id, $message)
	{
		// TODO: Implement onHandler() method.
		$events = Snowflake::app()->getEvent();
		$events->trigger(Event::PIPE_MESSAGE, [$server, $src_worker_id, $message]);
	}

}
