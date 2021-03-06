<?php


namespace HttpServer\Service\Abstracts;


use Exception;
use ReflectionException;
use Snowflake\Application;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;

trait Server
{

	/** @var Application */
	public $application;


	/**
	 * Server constructor.
	 * @param $host
	 * @param null $port
	 * @param null $mode
	 * @param null $sock_type
	 */
	public function __construct($host, $port = null, $mode = null, $sock_type = null)
	{
		$this->application = Snowflake::app();
		parent::__construct($host, $port, $mode, $sock_type);
	}


	/**
	 * @param array $settings
	 * @return mixed|void
	 */
	public function set(array $settings)
	{
		parent::set($settings); // TODO: Change the autogenerated stub
		$this->onInit();
	}


	/**
	 * @return mixed|void
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 */
	public function onHandlerListener()
	{
		$this->on('workerStop', $this->createHandler('workerStop'));
		$this->on('workerExit', $this->createHandler('workerExit'));
		$this->on('workerStart', $this->createHandler('workerStart'));
		$this->on('workerError', $this->createHandler('workerError'));
		$this->on('managerStart', $this->createHandler('managerStart'));
		$this->on('managerStop', $this->createHandler('managerStop'));
		$this->on('pipeMessage', $this->createHandler('pipeMessage'));
		$this->on('shutdown', $this->createHandler('shutdown'));
		$this->on('start', $this->createHandler('start'));
		$this->addTask();
	}


	/**
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 */
	protected function addTask()
	{
		$settings = $this->setting;
		if (($taskNumber = $settings['task_worker_num'] ?? 0) > 0) {
			$this->on('finish', $this->createHandler('finish'));
			$this->on('task', $this->createHandler('task'));
		}
	}


	/**
	 * @param $eventName
	 * @return array
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 * @throws Exception
	 */
	protected function createHandler($eventName)
	{
		$classPrefix = 'HttpServer\Events\On' . ucfirst($eventName);
		if (!class_exists($classPrefix)) {
			throw new Exception('class not found.');
		}
		$class = Snowflake::createObject($classPrefix, [Snowflake::app()]);
		return [$class, 'onHandler'];
	}


}
