<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/3/30 0030
 * Time: 14:39
 */

namespace Database\Base;


use Snowflake\Abstracts\Component;
use Snowflake\Core\JSON;
use Database\ActiveQuery;
use Database\ActiveRecord;
use Database\Connection;
use Database\HasMany;
use Database\HasOne;
use Database\Mysql\Columns;
use Database\Relation;
use Snowflake\Error\Logger;
use Exception;
use Snowflake\Exception\ComponentException;
use validator\Validator;
use Database\IOrm;
use Snowflake\Snowflake;

/**
 * Class BOrm
 *
 * @package Snowflake\Abstracts
 *
 * @property bool $isCreate
 * @method rules()
 * @method static tableName()
 */
abstract class BaseActiveRecord extends Component implements IOrm, \ArrayAccess
{

	/** @var array */
	protected $_attributes = [];

	/** @var array */
	protected $_oldAttributes = [];

	/** @var array */
	protected $_relate = [];

	/** @var null|string */
	protected $primary = NULL;

	/**
	 * @var bool
	 */
	protected $isNewExample = TRUE;

	protected $actions = [];

	/** @var Relation */
	protected $_relation = [];

	/**
	 * @throws Exception
	 */
	public function init()
	{
		$this->_relation = Snowflake::createObject(Relation::className());
	}

	/**
	 * @param string $column
	 * @param int $value
	 * @return void
	 * @throws Exception
	 */
	public function incrBy(string $column, int $value)
	{
		throw new Exception('Undefined function incrBy in ' . get_called_class());
	}

	/**
	 * @param string $column
	 * @param int $value
	 * @return void
	 * @throws Exception
	 */
	public function decrBy(string $column, int $value)
	{
		throw new Exception('Undefined function decrBy in ' . get_called_class());
	}

	/**
	 * @return array
	 */
	public function getActions()
	{
		return $this->actions;
	}

	/**
	 * @return bool
	 */
	public function getIsCreate()
	{
		return $this->isNewExample === TRUE;
	}

	/**
	 * @param bool $bool
	 * @return $this
	 */
	public function setIsCreate($bool = FALSE)
	{
		$this->isNewExample = $bool;
		return $this;
	}

	/**
	 * @return mixed
	 *
	 * get last exception or other error
	 * @throws ComponentException
	 */
	public function getLastError()
	{
		return Snowflake::app()->getLogger()->getLastError('mysql');
	}

	/**
	 * @return bool
	 * @throws Exception
	 */
	public function hasPrimary()
	{
		if ($this->primary !== NULL) {
			return true;
		}
		$primary = static::getColumns()->getPrimaryKeys();
		if (!empty($primary)) {
			return $this->primary = is_array($primary) ? current($primary) : $primary;
		}
		return false;
	}

	/**
	 * @throws Exception
	 */
	public function hasAutoIncrement()
	{
		$autoIncrement = $this->getAutoIncrement();
		return $autoIncrement !== null;
	}

	/**
	 * @throws Exception
	 */
	public function getAutoIncrement()
	{
		return static::getColumns()->getAutoIncrement();
	}

	/**
	 * @return null|string
	 * @throws Exception
	 */
	public function getPrimary()
	{
		if (!$this->hasPrimary()) {
			return null;
		}
		return $this->primary;
	}

	/**
	 * @return null|string
	 * @throws Exception
	 */
	public function getPrimaryValue()
	{
		if (!$this->hasPrimary()) {
			return null;
		}
		return $this->getAttribute($this->primary);
	}

	/**
	 * @param $condition
	 * @param $db
	 * @return $this
	 * @throws
	 */
	public static function findOne($condition, $db = NULL)
	{
		if (is_numeric($condition)) {
			$primary = static::getColumns()->getPrimaryKeys();
			if (empty($primary)) {
				throw new Exception('Primary key cannot be empty.');
			}
			if (is_array($primary)) {
				$primary = current($primary);
			}
			$condition = [$primary => $condition];
		}
		return static::find()->where($condition)->first();
	}

	/**
	 * @param null $field
	 * @return ActiveRecord|null
	 * @throws Exception
	 * @throws Exception
	 */
	public static function max($field = null)
	{
		$columns = static::getColumns();
		if (empty($field)) {
			$field = $columns->getFirstPrimary();
		}
		$columns = $columns->get_fields();
		if (!isset($columns[$field])) {
			return null;
		}
		$first = static::find()->max($field)->first();
		if (empty($first)) {
			return null;
		}
		return $first[$field];
	}

	/**
	 * @return mixed|ActiveQuery
	 * @throws
	 */
	public static function find()
	{
		return Snowflake::createObject(ActiveQuery::class, [get_called_class()]);
	}

	/**
	 * @param null $condition
	 * @param array $attributes
	 *
	 * @param bool $if_condition_is_null
	 * @return bool
	 * @throws Exception
	 */
	public static function deleteAll($condition = NULL, $attributes = [], $if_condition_is_null = false)
	{
		if (empty($condition)) {
			if (!$if_condition_is_null) {
				return false;
			}
			return static::find()->deleteAll();
		}
		$model = static::find()->ifNotWhere($if_condition_is_null)->where($condition);
		if (!empty($attributes)) {
			$model->bindParams($attributes);
		}
		return $model->deleteAll();
	}


	/**
	 * @return array
	 */
	public function getAttributes()
	{
		return $this->_attributes;
	}

	/**
	 * @return array
	 */
	public function getOldAttributes()
	{
		return $this->_oldAttributes;
	}

	/**
	 * @param $name
	 * @param $value
	 * @return mixed
	 */
	public function setAttribute($name, $value)
	{
		return $this->_attributes[$name] = $value;
	}

	/**
	 * @param $name
	 * @param $value
	 * @return mixed
	 */
	public function setOldAttribute($name, $value)
	{
		return $this->_oldAttributes[$name] = $value;
	}

	/**
	 * @param array $param
	 * @return $this
	 * @throws Exception
	 */
	public function setAttributes(array $param)
	{
		if (empty($param)) {
			return $this;
		}
		foreach ($param as $key => $val) {
			if (!$this->has($key)) {
				$this->setAttribute($key, $val);
			} else {
				$this->$key = $val;
			}
		}
		return $this;
	}

	/**
	 * @param $param
	 * @return $this
	 */
	public function setOldAttributes($param)
	{
		if (empty($param) || !is_array($param)) {
			return $this;
		}
		foreach ($param as $key => $val) {
			$this->setOldAttribute($key, $val);
		}
		return $this;
	}

	/**
	 * @return bool
	 * @throws Exception
	 */
	public function beforeSave()
	{
		return true;
	}

	/**
	 * @param $attributes
	 * @param $param
	 * @return $this|bool
	 * @throws Exception
	 */
	private function insert($param, $attributes)
	{
		if (empty($param)) {
			return FALSE;
		}
		$dbConnection = static::getDb();
		$change = $dbConnection->getSchema()->getChange();
		$sqlBuilder = $change->insert(static::getTable(), $attributes, $param);

		$trance = $dbConnection->beginTransaction();
		try {
			$commandExec = $dbConnection->createCommand($sqlBuilder, $param);
			if (($lastId = $commandExec->save(true, $this->hasAutoIncrement())) === false) {
				throw new Exception('保存失败.' . $sqlBuilder);
			}
			if ($this->hasAutoIncrement()) {
				$this->setAttribute($this->getAutoIncrement(), (int)$lastId);
			} else if ($this->hasPrimary()) {
				$primary = $this->getPrimary();
				if (!isset($param[$primary]) || empty($param[$primary])) {
					$this->setAttribute($primary, (int)$lastId);
				}
			}
			$trance->commit();
			$this->setAttributes($param);
			$this->afterSave($attributes, $param);
			$this->refresh();
		} catch (Exception $exception) {
			$lastId = false;
			$trance->rollback();
		}
		return $lastId;
	}


	/**
	 * @param $param
	 * @param $condition
	 * @param $attributes
	 * @return $this|bool
	 * @throws Exception
	 */
	private function update($attributes, $condition, $param)
	{
		if (empty($param)) {
			return true;
		}
		$command = static::getDb();
		$change = $command->getSchema()->getChange();
		$sql = $change->update(static::getTable(), $attributes, $condition, $param);

		$trance = $command->beginTransaction();
		if (!($command = $command->createCommand($sql, $param)->save(false, $this->hasAutoIncrement()))) {
			$trance->rollback();
			$result = $this->addError($this->getLastError());
		} else {
			$trance->commit();
			$result = $this->refresh();
			$this->afterSave($attributes, $param);
		}
		return $result;
	}

	/**
	 * @param null $data
	 * @return bool|mixed|ActiveRecord
	 * @throws Exception
	 */
	public function save($data = NULL)
	{
		if (is_array($data)) {
			$this->setAttributes($data);
		}
		if (!$this->validator($this->rules()) || !$this->beforeSave()) {
			return false;
		}

		$format = static::getColumns()->format();
		$this->_attributes = array_merge($format, $this->_attributes);
		static::getDb()->enablingTransactions();

		[$attributes, $condition, $param] = $this->filtration_and_separation();
		if (($primary = $this->getPrimary()) !== null) {
			$condition = [$primary => $this->getPrimaryValue()];
		}

		if (!$this->getIsCreate()) {
			return $this->update($param, $condition, $attributes);
		}
		return $this->insert($attributes, $param);
	}


	/**
	 * @param array $rule
	 * @return bool
	 * @throws Exception
	 */
	public function validator(array $rule)
	{
		if (empty($rule)) return true;
		$validate = $this->resolve($rule);
		if (!$validate->validation()) {
			return $this->addError($validate->getError(), 'mysql');
		} else {
			return TRUE;
		}
	}

	/**
	 * @param $rule
	 * @return Validator
	 * @throws Exception
	 */
	private function resolve($rule)
	{
		$validate = Validator::getInstance();
		$validate->setParams($this->_attributes);
		$validate->setModel($this);
		foreach ($rule as $Key => $val) {
			$field = array_shift($val);
			if (empty($val)) {
				continue;
			}
			$validate->make($field, $val);
		}
		return $validate;
	}

	/**
	 * @param string $name
	 * @return null
	 * @throws Exception
	 */
	public function getAttribute(string $name)
	{
		$method = 'get' . ucfirst($name) . 'Attribute';
		if (method_exists($this, $method)) {
			return $this->$method($this->_attributes[$name]);
		}
		return $this->_attributes[$name] ?? null;
	}


	/**
	 * @return array
	 * @throws Exception
	 */
	private function filtration_and_separation()
	{
		$_tmp = [];
		$condition = [];
		$columns = static::getColumns();
		foreach ($this->_attributes as $key => $val) {
			if ($val === NULL) continue;
			$oldValue = $this->_oldAttributes[$key] ?? null;
			if ($val !== $oldValue) {
				$_tmp[$key] = $columns->fieldFormat($key, $val);
			} else {
				$condition[$key] = $val;
			}
		}
		return [$_tmp, $condition, array_keys($_tmp)];
	}


	/**
	 * @param $name
	 * @param $value
	 */
	public function setRelate($name, $value)
	{
		$this->_relate[$name] = $value;
	}

	/**
	 * @param array $relates
	 */
	public function setRelates(array $relates)
	{
		if (empty($relates)) {
			return;
		}
		foreach ($relates as $key => $val) {
			$this->setRelate($key, $val);
		}
	}

	/**
	 * @return array
	 */
	public function getRelates()
	{
		return $this->_relate;
	}


	/**
	 * @return Relation
	 */
	public function getRelation()
	{
		return $this->_relation;
	}


	/**
	 * @param $name
	 * @return mixed|null
	 */
	public function getRelate($name)
	{
		if (!isset($this->_relate[$name])) {
			return NULL;
		}
		return $this->_relate[$name];
	}


	/**
	 * @param $attribute
	 * @return bool
	 * @throws Exception
	 */
	public function has($attribute)
	{
		$format = static::getColumns()->format();

		return array_key_exists($attribute, $format);
	}

	/**ƒ
	 * @return string
	 * @throws Exception
	 */
	public static function getTable()
	{
		$tablePrefix = static::getDb()->tablePrefix;

		$table = static::tableName();

		if (strpos($table, $tablePrefix) === 0) {
			return $table;
		}

		if (empty($table)) {
			$class = preg_replace('/model\\\/', '', get_called_class());
			$table = lcfirst($class);
		}

		$table = trim($table, '{{%}}');
		if ($tablePrefix) {
			$table = $tablePrefix . $table;
		}
		return $table;
	}


	/**
	 * @param $attributes
	 * @param $changeAttributes
	 * @return mixed
	 * @throws Exception
	 */
	public function afterSave($attributes, $changeAttributes)
	{
		return true;
	}

	/**
	 * @return Connection
	 * @throws Exception
	 */
	public static function getDb()
	{
		return static::setDatabaseConnect('db');
	}

	/**
	 * @return static
	 */
	public function refresh()
	{
		$this->_oldAttributes = $this->_attributes;
		return $this;
	}

	/**
	 * @param $name
	 * @param $value
	 * @throws Exception
	 */
	public function __set($name, $value)
	{
		if (!$this->has($name)) {
			parent::__set($name, $value);
		} else {
			$sets = 'set' . ucfirst($name) . 'Attribute';
			if (method_exists($this, $sets)) {
				$value = $this->$sets($value);
			}
			$this->_attributes[$name] = $value;
		}
	}

	/**
	 * @param $name
	 * @return mixed|null
	 * @throws Exception
	 */
	public function __get($name)
	{
		$method = 'get' . ucfirst($name);
		if (method_exists($this, $method . 'Attribute')) {
			return $this->{$method . 'Attribute'}($this->_attributes[$name] ?? null);
		}

		if (isset($this->_attributes[$name]) || array_key_exists($name, $this->_attributes)) {
			return stripcslashes($this->_attributes[$name]);
		}

		if (isset($this->_relate[$name])) {
			$gets = $this->{$this->_relate[$name]}();
		} else if (method_exists($this, $method)) {
			$gets = $this->$method();
		}

		if (isset($gets)) {
			return $this->resolveClass($gets);
		}

		return parent::__get($name);
	}

	/**
	 * @param $name
	 * @return mixed|null
	 */
	public function __isset($name)
	{
		return $this->_attributes[$name] ?? null;
	}

	/**
	 * @param $call
	 * @return array|null|ActiveRecord
	 * @throws Exception
	 */
	private function resolveClass($call)
	{
		if ($call instanceof HasOne) {
			return $call->get();
		} else if ($call instanceof HasMany) {
			return $call->get();
		} else {
			return $call;
		}
	}


	/**
	 * @param mixed $offset
	 * @return bool
	 * @throws Exception
	 */
	public function offsetExists($offset)
	{
		return $this->has($offset);
	}

	/**
	 * @param mixed $offset
	 * @return mixed|null
	 * @throws Exception
	 */
	public function offsetGet($offset)
	{
		return $this->__get($offset);
	}

	/**
	 * @param mixed $offset
	 * @param mixed $value
	 * @throws Exception
	 */
	public function offsetSet($offset, $value)
	{
		return $this->__set($offset, $value);
	}

	/**
	 * @param mixed $offset
	 * @throws Exception
	 */
	public function offsetUnset($offset)
	{
		if (!$this->has($offset)) {
			return;
		}
		unset($this->_attributes[$offset]);
		unset($this->_oldAttributes[$offset]);
		if (isset($this->_relate)) {
			unset($this->_relate[$offset]);
		}
	}

	/**
	 * @return array
	 */
	public function unset()
	{
		$fields = func_get_args();
		$fields = array_shift($fields);
		if (!is_array($fields)) {
			$fields = explode(',', $fields);
		}

		$array = array_combine($fields, $fields);

		return array_diff_assoc($array, $this->_attributes);
	}


	/**
	 * @param $bsName
	 * @return mixed
	 * @throws Exception
	 */
	public static function setDatabaseConnect($bsName)
	{
		return Snowflake::app()->db->get($bsName);
	}

	/**
	 * @return Columns
	 * @throws Exception
	 */
	public static function getColumns()
	{
		return static::getDb()->getSchema()
			->getColumns()
			->table(static::getTable());
	}

	/**
	 * @param array $data
	 * @return static
	 * @throws
	 */
	public static function populate(array $data)
	{
		$model = new static();
		$model->setAttributes(self::parse($data));
		$model->setIsCreate(false);
		$model->refresh();
		return $model;
	}


	/**
	 * @param $data
	 * @return array
	 * @throws Exception
	 */
	private static function parse($data)
	{
		return static::getColumns()->populate($data);
	}
}
