<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/25 0025
 * Time: 18:38
 */

namespace Snowflake;


use Console\ConsoleProviders;
use Database\DatabasesProviders;
use Exception;
use HttpServer\Server;
use HttpServer\ServerProviders;
use Snowflake\Abstracts\BaseApplication;
use Snowflake\Abstracts\Config;
use Snowflake\Abstracts\Input;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Exception\ComponentException;

/**
 * Class Init
 *
 * @package Snowflake
 *
 * @property-read Config $config
 */
class Application extends BaseApplication
{

	/**
	 * @var string
	 */
	public $id = 'uniqueId';


	/**
	 * @throws NotFindClassException
	 */
	public function init()
	{
		$this->import(ConsoleProviders::class);
		$this->import(DatabasesProviders::class);
		$this->import(ServerProviders::class);
	}


	/**
	 * @param string $service
	 * @return $this
	 * @throws
	 */
	public function import(string $service)
	{
		if (!class_exists($service)) {
			throw new NotFindClassException($service);
		}
		$class = Snowflake::createObject($service);
		if (method_exists($class, 'onImport')) {
			$class->onImport($this);
		}
		return $this;
	}


	/**
	 * @param string $command
	 * @throws ComponentException
	 */
	public function command(string $command)
	{
		/** @var \Console\Application $abstracts */
		$abstracts = $this->get('console');
		$abstracts->register($command);
	}


	/**
	 * @param $argv
	 * @throws
	 */
	public function start(Input $argv)
	{
		$this->set('input', $argv);
		$manager = Snowflake::app()->server;
		$manager->setDaemon($argv->get('daemon', 0));
		switch ($argv->get('action')) {
			case 'stop':
				$manager->shutdown();
				break;
			case 'restart':
				$manager->shutdown();
				$manager->start();
				break;
			case 'start':
				$manager->start();
				break;
			default:
				$this->error('I don\'t know what I want to do.');
		}
	}


	/**
	 * @param $className
	 * @param null $abstracts
	 * @return mixed
	 * @throws Exception
	 */
	public function make($className, $abstracts = null)
	{
		return make($className, $abstracts);
	}
}
