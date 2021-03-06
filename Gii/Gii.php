<?php
/**
 * Created by PhpStorm.
 * User: 向林
 * Date: 2016/8/9 0009
 * Time: 17:43
 */

namespace Gii;

use Database\Connection;
use Database\Db;
use Exception;
use Snowflake\Abstracts\Input;
use Snowflake\Exception\ComponentException;
use Snowflake\Exception\ConfigException;
use Snowflake\Snowflake;

/**
 * Class gii
 *
 * @package Inter\utility
 */
class Gii
{
	private $tableName = NULL;

	/** @var Connection */
	private $db;

	/** @var Input */
	private $input;

	public $modelPath = APP_PATH . '/app/Models/';
	public $modelNamespace = 'App\\Models\\';

	public $controllerPath = APP_PATH . '/app/Http/Controllers/';
	public $controllerNamespace = 'App\\Http\\Controllers\\';


	public $keyword = [
		'ADD', 'ALL', 'ALTER', 'AND', 'AS', 'ASC', 'ASENSITIVE', 'BEFORE', 'BETWEEN', 'BIGINT', 'BINARY', 'BLOB', 'BOTH', 'BY', 'CALL', 'CASCADE', 'CASE', 'CHANGE', 'CHAR', 'CHARACTER', 'CHECK', 'COLLATE', 'COLUMN', 'CONDITION', 'CONNECTION', 'CONSTRAINT', 'CONTINUE', 'CONVERT', 'CREATE', 'CROSS', 'CURRENT_DATE', 'CURRENT_TIME', 'CURRENT_TIMESTAMP', 'CURRENT_USER', 'CURSOR', 'DATABASE', 'DATABASES', 'DAY_HOUR', 'DAY_MICROSECOND', 'DAY_MINUTE', 'DAY_SECOND', 'DEC', 'DECIMAL', 'DECLARE', 'DEFAULT', 'DELAYED', 'DELETE', 'DESC', 'DESCRIBE', 'DETERMINISTIC', 'DISTINCT', 'DISTINCTROW', 'DIV', 'DOUBLE', 'DROP', 'DUAL', 'EACH', 'ELSE', 'ELSEIF', 'ENCLOSED', 'ESCAPED', 'EXISTS', 'EXIT', 'EXPLAIN', 'FALSE', 'FETCH', 'FLOAT', 'FLOAT4', 'FLOAT8', 'FOR', 'FORCE', 'FOREIGN', 'FROM', 'FULLTEXT', 'GOTO', 'GRANT', 'GROUP', 'HAVING', 'HIGH_PRIORITY', 'HOUR_MICROSECOND', 'HOUR_MINUTE', 'HOUR_SECOND', 'IF', 'IGNORE', 'IN', 'INDEX', 'INFILE', 'INNER', 'INOUT', 'INSENSITIVE', 'INSERT', 'INT', 'INT1', 'INT2', 'INT3', 'INT4', 'INT8', 'INTEGER', 'INTERVAL', 'INTO', 'IS', 'ITERATE', 'JOIN', 'KEY', 'KEYS', 'KILL', 'LABEL', 'LEADING', 'LEAVE', 'LEFT', 'LIKE', 'LIMIT', 'LINEAR', 'LINES', 'LOAD', 'LOCALTIME', 'LOCALTIMESTAMP', 'LOCK', 'LONG', 'LONGBLOB', 'LONGTEXT', 'LOOP', 'LOW_PRIORITY', 'MATCH', 'MEDIUMBLOB', 'MEDIUMINT', 'MEDIUMTEXT', 'MIDDLEINT', 'MINUTE_MICROSECOND', 'MINUTE_SECOND', 'MOD', 'MODIFIES', 'NATURAL', 'NOT', 'NO_WRITE_TO_BINLOG', 'NULL', 'NUMERIC', 'ON', 'OPTIMIZE', 'OPTION', 'OPTIONALLY', 'OR', 'ORDER', 'OUT', 'OUTER', 'OUTFILE', 'PRECISION', 'PRIMARY', 'PROCEDURE', 'PURGE', 'RAID0', 'RANGE', 'READ', 'READS', 'REAL', 'REFERENCES', 'REGEXP', 'RELEASE', 'RENAME', 'REPEAT', 'REPLACE', 'REQUIRE', 'RESTRICT', 'RETURN', 'REVOKE', 'RIGHT', 'RLIKE', 'SCHEMA', 'SCHEMAS', 'SECOND_MICROSECOND', 'SELECT', 'SENSITIVE', 'SEPARATOR', 'SET', 'SHOW', 'SMALLINT', 'SPATIAL', 'SPECIFIC', 'SQL', 'SQLEXCEPTION', 'SQLSTATE', 'SQLWARNING', 'SQL_BIG_RESULT', 'SQL_CALC_FOUND_ROWS', 'SQL_SMALL_RESULT', 'SSL', 'STARTING', 'STRAIGHT_JOIN', 'TABLE', 'TERMINATED', 'THEN', 'TINYBLOB', 'TINYINT', 'TINYTEXT', 'TO', 'TRAILING', 'TRIGGER', 'TRUE', 'UNDO', 'UNION', 'UNIQUE', 'UNLOCK', 'UNSIGNED', 'UPDATE', 'USAGE', 'USE', 'USING', 'UTC_DATE', 'UTC_TIME', 'UTC_TIMESTAMP', 'VALUES', 'VARBINARY', 'VARCHAR', 'VARCHARACTER', 'VARYING', 'WHEN', 'WHERE', 'WHILE', 'WITH', 'WRITE', 'X509', 'XOR', 'YEAR_MONTH', 'ZEROFILL'
	];

	/**
	 * @param Connection|null $db
	 *
	 * @param $input
	 * @return array
	 * @throws ComponentException
	 * @throws ConfigException
	 * @throws Exception
	 */
	public function run(?Connection $db, $input)
	{
		$this->input = $input;
		if (!empty($db)) $this->db = $db;

		$make = $this->input->get('make', null);
		if (empty($make)) {
			throw new Exception('构建类型不能为空~');
		}
		switch (strtolower($make)) {
			case 'task':
				$task = new GiiTask();
				$task->setInput($this->input);
				return $task->generate();
			case 'interceptor':
				$task = new GiiInterceptor();
				$task->setInput($this->input);
				return $task->generate();
			case 'limits':
				$task = new GiiLimits();
				$task->setInput($this->input);
				return $task->generate();
			case 'middleware':
				$task = new GiiMiddleware();
				$task->setInput($this->input);
				return $task->generate();
			default:
				return $this->getModel($make, $input);
		}
	}


	/**
	 * @param $make
	 * @param $input
	 * @return array
	 * @throws ComponentException
	 * @throws ConfigException
	 */
	private function getModel($make, $input)
	{
		if (!$this->db) {
			$db = $this->input->get('databases', 'db');
			$this->db = Snowflake::app()->db->get($db);
		}
		$redis = Snowflake::app()->getRedis();
		if (!empty($input->get('table'))) {
			$this->tableName = $input->get('table');
			$redis->del('column:' . $this->tableName);
		}
		switch ($make) {
			case 'controller':
				return $this->getTable(1, 0);
			case 'model':
				return $this->getTable(0, 1);
			default:
				return [];
		}
	}


	/**
	 * @param $controller
	 * @param $model
	 * @return array
	 *
	 * @throws Exception
	 */
	private function getTable($controller, $model)
	{
		$tables = $this->getFields($this->getTables());
		if (empty($tables)) {
			return [];
		}

		$fileList = [];
		foreach ($tables as $key => $val) {
			$data = $this->createModelFile($key, $val);
			if ($controller == 1) {
				$fileList[] = $this->generateController($data);
			}
			if ($model == 1) {
				$fileList[] = $this->generateModel($data);
			}
		}
		return $fileList;
	}

	/**
	 * @param array $data
	 * @return string
	 * @throws Exception
	 */
	private function generateModel(array $data)
	{
		$controller = new GiiModel($data['classFileName'], $data['tableName'], $data['visible'], $data['res'], $data['fields']);
		$controller->setConnection($this->db);
		$controller->setModelPath($this->modelPath);
		$controller->setModelNamespace($this->modelNamespace);
		$controller->setInput($this->input);
		$controller->setModule($this->input->get('module', null));
		$controller->setControllerPath($this->controllerPath);
		$controller->setControllerNamespace($this->controllerNamespace);
		return $controller->generate();
	}

	/**
	 * @param array $data
	 * @return string
	 * @throws Exception
	 */
	private function generateController(array $data)
	{
		$controller = new GiiController($data['classFileName'], $data['fields']);
		$controller->setConnection($this->db);
		$controller->setModelPath($this->modelPath);
		$controller->setInput($this->input);
		$controller->setModelNamespace($this->modelNamespace);
		$controller->setControllerPath($this->controllerPath);
		$controller->setModule($this->input->get('module', null));
		$controller->setControllerNamespace($this->controllerNamespace);
		return $controller->generate();
	}

	/**
	 * @return array|null
	 * @throws Exception
	 */
	private function getTables()
	{
		if (empty($this->tableName)) {
			return $this->showAll();
		}
		$res = $this->tableName;
		if (is_string($res)) {
			$res = explode(',', $this->tableName);
		}
		if (empty($res)) {
			return [];
		}
		return $res;
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	private function showAll()
	{
		$res = [];
		$_tables = Db::findAllBySql('show tables', [], $this->db);
		if (empty($_tables)) {
			return $res;
		}
		foreach ($_tables as $key => $val) {
			$res[] = array_shift($val);
		}
		return $res;
	}

	/**
	 * @param $table
	 * @return bool|int
	 * @throws Exception
	 */
	private function getIndex($table)
	{
		$data = Db::findAllBySql('SHOW INDEX FROM ' . $table, [], $this->db);

		return empty($data) ? NULL : $data[0];
	}

	/**
	 * @param $tables
	 *
	 * @return array
	 * @throws
	 */
	private function getFields($tables)
	{
		$res = [];
		if (!is_array($tables)) {
			$tables = [$tables];
		}
		foreach ($tables as $key => $val) {
			if (empty($val)) continue;
			$_tmp = Db::findAllBySql('SHOW FULL FIELDS FROM ' . $val, [], $this->db);
			if (empty($_tmp)) {
				continue;
			}
			$res[$val] = $_tmp;
		}
		return $res;
	}

	/**
	 * @param $tableName
	 * @param $tables
	 *
	 * @return array
	 * @throws Exception
	 */
	public function createModelFile($tableName, $tables)
	{
		$res = $visible = $fields = $keys = [];
		foreach ($tables as $_key => $_val) {
			$keys = $tableName;
			if ($_val['Extra'] == 'auto_increment' || $_val['Key'] == 'PRI') {
				$keys = $tableName;
			}
			if (!isset($keys) && !($index = $this->getIndex($tableName))) {
				$keys = $index['Column_name'];
			}
			if (in_array(strtoupper($_val['Field']), $this->keyword)) {
				throw new Exception('You can not use keyword "' . $_val['Field'] . '" as field at table "' . $tableName . '"');
			}
			array_push($visible, $this->createVisible($_val['Field']));
			array_push($fields, $_val);
			$res[] = $this->createSetFunc($_val['Field'], $_val['Comment']);
		}

		$classFileName = $this->getClassName($tableName);

		return [
			'classFileName' => $classFileName,
			'tableName'     => $keys,
			'visible'       => $visible,
			'fields'        => $fields,
			'res'           => $res,
		];
	}

	/**
	 * @param $field
	 * @return string
	 * 创建变量注释
	 */
	private function createVisible($field)
	{
		return '
 * @property $' . $field;
	}

	/**
	 * @param $field
	 * @param $comment
	 * @return string
	 * 暂时不知道干嘛用的
	 */
	private function createSetFunc($field, $comment)
	{
		return '
            ' . str_pad('\'' . $field . '\'', 20, ' ', STR_PAD_RIGHT) . '=> \'' . (empty($comment) ? ucfirst($field) : $comment) . '\',';
	}

	/**
	 * @param $tableName
	 * @return string
	 * 构建类名称
	 */
	private function getClassName($tableName)
	{
		$res = [];
		foreach (explode('_', $tableName) as $n => $val) {
			$res[] = ucfirst($val);
		}

		$name = ucfirst(rtrim($this->db->tablePrefix, '_'));

		return implode('', $res);

		return str_replace($name, '', implode('', $res)) . 'Comply';
	}

}
