<?php


namespace HttpServer\Events;


use HttpServer\Events\Abstracts\Callback;
use HttpServer\Route\Annotation\Annotation;
use HttpServer\Route\Annotation\Tcp;
use HttpServer\Route\Annotation\Websocket as AWebsocket;
use Snowflake\Event;
use Snowflake\Snowflake;
use Swoole\Server;
use Exception;
use Swoole\Http\Server as HServer;
use Swoole\WebSocket\Server as WServer;

/**
 * Class OnClose
 * @package HttpServer\Events
 */
class OnClose extends Callback
{


	/**
	 * @param Server $server
	 * @param int $fd
	 * @throws Exception
	 */
	public function onHandler(Server $server, int $fd)
	{
		try {
			if ($server instanceof WServer) {
				if (!$server->isEstablished($fd)) {
					return;
				}
				$manager = Snowflake::get()->annotation->get('websocket');
				$name = $manager->getName(AWebsocket::CLOSE);
			} else if ($server instanceof HServer) {
				$manager = Snowflake::get()->annotation->get('http');
				$name = $manager->getName(Annotation::CLOSE);
			} else {
				$manager = Snowflake::get()->annotation->get('tcp');
				$name = $manager->getName(Tcp::CLOSE);
			}
			if (!$manager->has($name)) {
				return;
			}
			$manager->runWith($name, [$fd]);
		} catch (\Throwable $exception) {
			$this->addError($exception->getMessage());
		} finally {
			$event = Snowflake::get()->event;
			$event->trigger(Event::RELEASE_ALL);

			$logger = Snowflake::get()->getLogger();
			$logger->insert();
		}
	}


}