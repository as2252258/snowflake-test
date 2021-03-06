<?php


namespace HttpServer;


use Exception;
use Snowflake\Abstracts\Input;
use Snowflake\Exception\ConfigException;
use Snowflake\Snowflake;

/**
 * Class Command
 * @package HttpServer
 */
class Command extends \Console\Command
{

	public $command = 'sw:server';


	public $description = 'server start|stop|reload|restart';


	const ACTIONS = ['start', 'stop', 'restart'];


	/**
	 * @param Input $dtl
	 * @return mixed|void
	 * @throws Exception
	 * @throws ConfigException
	 */
	public function onHandler(Input $dtl)
	{
		$manager = Snowflake::app()->server;
		$manager->setDaemon($dtl->get('daemon', 0));

		if (!in_array($dtl->get('action'), self::ACTIONS)) {
			return 'I don\'t know what I want to do.';
		}

		if ($manager->isRunner() && $dtl->get('action') == 'start') {
			return 'Service is running. Please use restart.';
		}

		$manager->shutdown();
		if ($dtl->get('action') == 'stop') {
			return 'shutdown success.';
		}
		$manager->start();
	}

}
