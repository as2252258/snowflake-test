<?php


namespace HttpServer\Events\Trigger;


use Exception;
use HttpServer\Events\Callback;
use HttpServer\Events\Http;
use HttpServer\Events\WebSocket;
use HttpServer\Route\Annotation\Websocket as AWebsocket;
use Snowflake\Error\Logger;
use Snowflake\Event;
use Snowflake\Snowflake;
use Swoole\Server;

/**
 * Class OnWorkerStart
 * @package HttpServer\Events\Trigger
 */
class OnWorkerStart extends Callback
{


	/**
	 * @param Server $server
	 * @param int $worker_id
	 *
	 * @return mixed|void
	 * @throws Exception
	 */
	public function onHandler(Server $server, $worker_id)
	{
		Logger::$worker_id = $worker_id;

		Snowflake::setProcessId($server->worker_pid);

		$get_name = $this->get_process_name($server, $worker_id);
		if (!empty($get_name) && !Snowflake::isMac()) {
			swoole_set_process_name($get_name);
		}
		if ($worker_id >= $server->setting['worker_num']) {
			return;
		}
		$this->setWorkerAction($server, $worker_id);
	}

	/**
	 * @param $worker_id
	 * @param  $socket
	 * @throws Exception
	 */
	private function setWorkerAction($socket, $worker_id)
	{
		try {
			if ($socket instanceof Http) {
				$router = Snowflake::get()->router;
				$router->loadRouterSetting();
			} else if ($socket instanceof WebSocket) {
				$path = APP_PATH . 'app/Websocket';

				/** @var AWebsocket $websocket */
				$websocket = Snowflake::get()->annotation->register('websocket', AWebsocket::class);
				$websocket->registration_notes($path, 'App\\Websocket');
			}
			$event = Snowflake::get()->event;
			if (!$event->exists(Event::SERVER_WORKER_START)) {
				return;
			}
			$event->trigger(Event::SERVER_WORKER_START);
		} catch (\Throwable $exception) {
			Logger::write($exception->getMessage(), 'worker');
		}
	}

	/**
	 * @param $socket
	 * @param $worker_id
	 * @return string
	 */
	private function get_process_name($socket, $worker_id)
	{
		$prefix = 'system:';
		if ($worker_id >= $socket->setting['worker_num']) {
			return $prefix . ': Task: No.' . $worker_id;
		} else {
			return $prefix . ': worker: No.' . $worker_id;
		}
	}


}
