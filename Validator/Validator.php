<?php


namespace validator;


use Snowflake\Snowflake;

/**
 * Class Validator
 * @package validator
 */
class Validator extends BaseValidator
{

	/** @var BaseValidator[] */
	private $validators = [];

	/** @var Validator */
	private static $instance = null;

	protected $classMap = [
		'not empty' => [
			'class'  => 'validator\EmptyValidator',
			'method' => EmptyValidator::CAN_NOT_EMPTY,
		],
		'not null'  => [
			'class'  => 'validator\EmptyValidator',
			'method' => EmptyValidator::CAN_NOT_NULL,
		],
		'required'  => [
			'class' => 'validator\RequiredValidator',
		],
		'enums'  => [
			'class' => 'validator\EnumValidator',
		],
		'unique'    => [
			'class' => 'validator\UniqueValidator',
		],
		'datetime'  => [
			'class'  => 'validator\DatetimeValidator',
			'method' => DateTimeValidator::DATE_TIME,
		],
		'date'      => [
			'class'  => 'validator\DatetimeValidator',
			'method' => DateTimeValidator::DATE,
		],
		'time'      => [
			'class'  => 'validator\DatetimeValidator',
			'method' => DateTimeValidator::TIME,
		],
		'timestamp' => [
			'class'  => 'validator\DatetimeValidator',
			'method' => DateTimeValidator::STR_TO_TIME,
		],
		'string'    => [
			'class'  => 'validator\TypesOfValidator',
			'method' => TypesOfValidator::STRING,
		],
		'int'       => [
			'class'  => 'validator\TypesOfValidator',
			'method' => TypesOfValidator::INTEGER,
		],
		'min'       => [
			'class' => IntegerValidator::class
		],
		'max'       => [
			'class' => IntegerValidator::class
		],
		'json'      => [
			'class'  => 'validator\TypesOfValidator',
			'method' => TypesOfValidator::JSON,
		],
		'float'     => [
			'class'  => 'validator\TypesOfValidator',
			'method' => TypesOfValidator::FLOAT,
		],
		'array'     => [
			'class'  => 'validator\TypesOfValidator',
			'method' => TypesOfValidator::ARRAY,
		],
		'serialize' => [
			'class'  => 'validator\TypesOfValidator',
			'method' => TypesOfValidator::SERIALIZE,
		],
		'maxLength' => [
			'class'  => 'validator\LengthValidator',
			'method' => 'max',
		],
		'minLength' => [
			'class'  => 'validator\LengthValidator',
			'method' => 'min',
		],
		'email'     => [
			'class'  => 'validator\EmailValidator',
			'method' => 'email',
		],
		'length'    => [
			'class'  => 'validator\LengthValidator',
			'method' => 'default',
		],
	];

	/**
	 * @return Validator
	 */
	public static function getInstance()
	{
		if (static::$instance == null) {
			static::$instance = new Validator();
		}
		return static::$instance;
	}

	/**
	 * @param $field
	 * @param $rules
	 * @return $this
	 * @throws \Exception
	 */
	public function make($field, $rules)
	{
		if (!is_array($field)) {
			$field = [$field];
		}

		$param = $this->getParams();
		$model = $this->getModel();
		foreach ($field as $val) {
			$this->createRule($val, $rules, $model, $param);
		}

		return $this;
	}

	/**
	 * @param $field
	 * @param $rule
	 * @param $model
	 * @param $param
	 * @throws \Exception
	 * ['maxLength'=>150, 'required', 'minLength' => 100]
	 */
	public function createRule($field, $rule, $model, $param)
	{
		$define = ['field' => $field];
		foreach ($rule as $key => $val) {
			if (is_string($val) && method_exists($model, $val)) {
				$this->validators[] = [$model, $val];
				continue;
			}
			if (is_string($key)) {
				$type = strtolower($key);
				$define['value'] = $val;
			} else {
				$type = strtolower($val);
			}

			if (!isset($this->classMap[$type])) {
				continue;
			}
			$constr = array_merge($this->classMap[$type], $define);

			/** @var BaseValidator $class */
			$class = Snowflake::createObject($constr);
			$class->setParams($param);
			$class->setModel($model);

			$this->validators[] = $class;
		}
	}

	/**
	 * @return bool
	 * @throws \Exception
	 */
	public function validation()
	{
		if (count($this->validators) < 1) {
			return true;
		}

		foreach ($this->validators as $val) {
			if ($this->check($val)) {
				continue;
			}
			$isTrue = false;
			if ($val instanceof BaseValidator) {
				$this->addError($val->getError());
			}
			break;
		}
		$this->validators = [];
		return !isset($isTrue);
	}

	/**
	 * @param BaseValidator $val
	 * @return mixed
	 * @throws
	 */
	private function check($val)
	{
		if (is_callable($val, true)) {
			return call_user_func($val, $this);
		}
		return $val->trigger();
	}

}
