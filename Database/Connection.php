<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/3/30 0030
 * Time: 14:09
 */

namespace Database;


use Snowflake\Abstracts\Component;
use Database\Mysql\Schema;
use Database\Orm\Select;
use Exception;
use PDO;
use Snowflake\Event;
use Snowflake\Exception\ComponentException;
use Snowflake\Snowflake;

/**
 * Class Connection
 * @package Database
 */
class Connection extends Component
{
	const TRANSACTION_COMMIT = 'transaction::commit';
	const TRANSACTION_ROLLBACK = 'transaction::rollback';

	public $id = 'db';
	public $cds = '';
	public $password = '';
	public $username = '';
	public $charset = 'utf-8';
	public $tablePrefix = '';

	public $timeout = 1900;

	public $maxNumber = 200;

	/**
	 * @var bool
	 * enable database cache
	 */
	public $enableCache = false;
	public $cacheDriver = 'redis';

	/**
	 * @var array
	 *
	 * @example [
	 *    'cds'      => 'mysql:dbname=dbname;host=127.0.0.1',
	 *    'username' => 'root',
	 *    'password' => 'root'
	 * ]
	 */
	public $slaveConfig = [];

	/** @var Schema $_schema */
	private $_schema = null;


	/**
	 * @throws Exception
	 */
	public function init()
	{
		$event = Snowflake::app()->event;
		$event->on(Event::RELEASE_ALL, [$this, 'disconnect']);
		$event->on(Event::EVENT_AFTER_REQUEST, [$this, 'clear_connection']);
	}


	/**
	 * @throws Exception
	 */
	public function enablingTransactions()
	{
		if (!Db::transactionsActive()) {
			return;
		}
		$this->beginTransaction();

		$event = Snowflake::app()->event;
		$event->on(Connection::TRANSACTION_COMMIT, [$this, 'commit'], false, true);
		$event->on(Connection::TRANSACTION_ROLLBACK, [$this, 'rollback'], false, true);
	}

	/**
	 * @param null $sql
	 * @return PDO
	 * @throws Exception
	 */
	public function getConnect($sql = NULL)
	{
		$connections = Snowflake::app()->connections;
		$connections->initConnections($this->cds, true, $this->maxNumber);
		$connections->initConnections($this->slaveConfig['cds'], false, $this->maxNumber);
		$connections->setTimeout($this->timeout);

		return $this->getPdo($sql);
	}


	/**
	 * 初始化 Channel
	 */
	public function fill()
	{
		$connections = Snowflake::app()->connections;
		$connections->initConnections($this->cds, true, $this->maxNumber);
		$connections->initConnections($this->slaveConfig['cds'], false, $this->maxNumber);
	}


	/**
	 * @param $sql
	 * @return PDO
	 * @throws Exception
	 */
	private function getPdo($sql)
	{
		if ($this->isWrite($sql)) {
			$connect = $this->masterInstance();
		} else {
			$connect = $this->slaveInstance();
		}
		return $connect;
	}

	/**
	 * @return mixed|object|Schema
	 * @throws Exception
	 */
	public function getSchema()
	{
		if ($this->_schema === null) {
			$this->_schema = Snowflake::createObject([
				'class' => Schema::class,
				'db'    => $this
			]);
		}
		return $this->_schema;
	}

	/**
	 * @param $sql
	 * @return bool
	 */
	public function isWrite($sql)
	{
		if (empty($sql)) return false;

		$prefix = strtolower(mb_substr($sql, 0, 6));

		return in_array($prefix, ['insert', 'update', 'delete']);
	}

	/**
	 * @return mixed|null
	 * @throws ComponentException
	 */
	public function getCacheDriver()
	{
		if (!$this->enableCache) {
			return null;
		}
		return Snowflake::app()->get($this->cacheDriver);
	}

	/**
	 * @return PDO
	 * @throws Exception
	 */
	public function masterInstance()
	{
		$config = [
			'cds'      => $this->cds,
			'username' => $this->username,
			'password' => $this->password
		];
		$pool = Snowflake::app()->connections;
		return $pool->getConnection($config, true);
	}

	/**
	 * @return PDO
	 * @throws Exception
	 */
	public function slaveInstance()
	{
		if (empty($this->slaveConfig)) {
			return $this->masterInstance();
		}

		$connections = Snowflake::app()->connections;
		return $connections->getConnection($this->slaveConfig, false);
	}


	/**
	 * @return $this
	 * @throws Exception
	 */
	public function beginTransaction()
	{
		$connections = Snowflake::app()->connections;
		$connections->beginTransaction($this->cds);
		return $this;
	}

	/**
	 * @return $this|bool
	 * @throws Exception
	 */
	public function inTransaction()
	{
		$connections = Snowflake::app()->connections;
		return $connections->inTransaction($this->cds);
	}

	/**
	 * @throws Exception
	 * 事务回滚
	 */
	public function rollback()
	{
		$connections = Snowflake::app()->connections;
		return $connections->rollback($this->cds);
	}

	/**
	 * @throws Exception
	 * 事务提交
	 */
	public function commit()
	{
		$connections = Snowflake::app()->connections;
		return $connections->commit($this->cds);
	}

	/**
	 * @param $sql
	 * @return PDO
	 * @throws Exception
	 */
	public function refresh($sql)
	{
		if ($this->isWrite($sql)) {
			$instance = $this->masterInstance();
		} else {
			$instance = $this->slaveInstance();
		}
		return $instance;
	}

	/**
	 * @param $sql
	 * @param array $attributes
	 * @return Command
	 * @throws
	 */
	public function createCommand($sql = null, $attributes = [])
	{
		$command = new Command(['db' => $this, 'sql' => $sql]);
		return $command->bindValues($attributes);
	}

	/**
	 * @return Select
	 * @throws Exception
	 */
	public function getBuild()
	{
		return $this->getSchema()->getQueryBuilder();
	}

	/**
	 *
	 * 回收链接
	 * @throws
	 */
	public function release()
	{
		$connections = Snowflake::app()->connections;

		$connections->release($this->cds, true);
		$connections->release($this->slaveConfig['cds'], false);
	}


	/**
	 * 临时回收
	 */
	public function recovery()
	{
		$connections = Snowflake::app()->connections;

		$connections->release($this->cds, true);
		$connections->release($this->slaveConfig['cds'], false);
	}

	/**
	 *
	 * 回收链接
	 * @throws
	 */
	public function clear_connection()
	{
		$connections = Snowflake::app()->connections;

		$connections->release($this->cds, true);
		$connections->release($this->slaveConfig['cds'], false);
	}


	/**
	 * @throws Exception
	 */
	public function disconnect()
	{
		$connections = Snowflake::app()->connections;
		$connections->disconnect($this->cds);
		$connections->disconnect($this->slaveConfig['cds']);
	}

}
