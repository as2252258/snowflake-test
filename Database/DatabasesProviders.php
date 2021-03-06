<?php


namespace Database;


use Exception;
use Snowflake\Abstracts\Providers;
use Snowflake\Application;
use Snowflake\Exception\ConfigException;
use Snowflake\Snowflake;
use Snowflake\Abstracts\Config;

/**
 * Class DatabasesProviders
 * @package Database
 */
class DatabasesProviders extends Providers
{


	/**
	 * @param Application $application
	 * @throws Exception
	 */
	public function onImport(Application $application)
	{
		$application->set('db', $this);
	}


	/**
	 * @param $name
	 * @return Connection
	 * @throws ConfigException
	 * @throws Exception
	 */
	public function get($name)
	{
		$application = Snowflake::app();
		if ($application->has('databases.' . $name)) {
			return $application->get('databases.' . $name);
		}
		$config = $this->getConfig($name);
		return $application->set('databases.' . $name, [
			'class'       => Connection::class,
			'id'          => $config['id'],
			'cds'         => $config['cds'],
			'username'    => $config['username'],
			'password'    => $config['password'],
			'tablePrefix' => $config['tablePrefix'],
			'maxNumber'   => $config['maxNumber'],
			'charset'     => $config['charset'] ?? 'utf8mb4',
			'slaveConfig' => $config['slaveConfig']
		]);
	}


	/**
	 * @throws ConfigException
	 * @throws Exception
	 */
	public function createPool()
	{
		$databases = Config::get('databases', false, []);
		if (empty($databases)) {
			return;
		}
		$application = Snowflake::app();
		foreach ($databases as $name => $database) {
			/** @var Connection $connection */
			$connection = $application->set('databases.' . $name, [
				'class'       => Connection::class,
				'id'          => $database['id'],
				'cds'         => $database['cds'],
				'username'    => $database['username'],
				'password'    => $database['password'],
				'tablePrefix' => $database['tablePrefix'],
				'maxNumber'   => $database['maxNumber'],
				'charset'     => $database['charset'] ?? 'utf8mb4',
				'slaveConfig' => $database['slaveConfig']
			]);
			$connection->fill();
		}
	}


	/**
	 * @param $name
	 * @return array|mixed|null
	 * @throws ConfigException
	 */
	public function getConfig($name)
	{
		return Config::get('databases.' . $name, true);
	}


}
