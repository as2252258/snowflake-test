<?php


namespace Snowflake\Annotation;

use Exception;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use Snowflake\Abstracts\BaseAnnotation;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;
use validator\RequiredValidator;
use validator\RequiredValidator as NotEmptyValidator;

/**
 * Class Annotation
 * @package Snowflake\Snowflake\Annotation
 * @property Websocket $websocket
 * @property Http $http
 */
class Annotation extends BaseAnnotation
{

	protected $_Scan_directory = [];
	protected $params = [];

	public $namespace = '';

	public $prefix = '';

	public $path = '';


	private $rules = [
		'required'  => [
			'class' => RequiredValidator::class
		],
		'not empty' => [
			'class' => NotEmptyValidator::class
		]
	];


	private $_classMap = [
		'websocket' => Websocket::class,
		'http'      => Http::class
	];


	/**
	 * @param $name
	 * @param $class
	 */
	public function register($name, $class)
	{
		$this->_classMap[$name] = $class;
	}


	/**
	 * @param $path
	 * @param $namespace
	 * @throws ReflectionException
	 */
	public function registration_notes($path, $namespace)
	{
		foreach ($path as $item) {
			$this->scanning($item, $namespace);
		}
	}


	/**
	 * @return string
	 * @throws
	 */
	public function getHttp()
	{
		return Snowflake::createObject($this->_classMap['http']);
	}


	/**
	 * @return string
	 * @throws
	 */
	public function getWebsocket()
	{
		return Snowflake::createObject($this->_classMap['websocket']);
	}

	/**
	 * @param ReflectionClass $reflect
	 * @Message(updatePosition)
	 * @throws Exception
	 */
	public function resolve(ReflectionClass $reflect)
	{
		$controller = $reflect->newInstance();

		$methods = $this->getPrivates($reflect);

		foreach ($methods as $function) {
			$comment = $function->getDocComment();
			$methodName = $function->getName();

			preg_match('/@(' . $function . ')\((.*?)\)/', $comment, $events);
			if (!isset($events[1])) {
				continue;
			}
			if (!$this->isLegitimate($events)) {
				continue;
			}
			$_key = $this->getName($function, $events);
			if (empty($events[2])) {
				$this->push($_key, [$controller, $methodName]);
			} else {
				$handler = $this->createHandler($controller, $methodName, $events[2]);

				$this->push($_key, $handler, [request(), [$controller, $methodName]]);
			}
		}
	}


	/**
	 * @param $events
	 * @throws Exception
	 */
	public function isLegitimate($events)
	{
		throw new Exception('Undefined analytic function.');
	}


	/**
	 * @param $function
	 * @param $events
	 * @throws Exception
	 */
	public function getName($function, $events)
	{
		throw new Exception('Undefined analytic function.');
	}


	/**
	 * @param $controller
	 * @param $methodName
	 * @param $events
	 * @throws Exception
	 */
	public function createHandler($controller, $methodName, $events)
	{
		throw new Exception('Undefined analytic function.');
	}


	/**
	 * @param string $path
	 * @param $namespace
	 * @throws ReflectionException
	 * @throws Exception
	 */
	protected function scanning($path, $namespace)
	{
		$di = Snowflake::getDi();
		foreach (glob($path . '/*') as $file) {
			if (is_dir($file)) {
				$this->scanning($path, $namespace);
			}

			$explode = explode('/', $file);

			$class = str_replace('.php', '', end($explode));

			$this->resolve($di->getReflect($namespace . '\\' . $class));
		}
	}

	/**
	 * @param $path
	 * @param mixed ...$param
	 * @return bool|mixed
	 */
	public function runWith($path, $param)
	{
		if (!$this->has($path)) {
			return null;
		}
		$callback = $this->_Scan_directory[$path];
		if (!isset($this->params[$path])) {
			return $callback(...$param);
		}
		return $callback(...$this->params[$path]);
	}


	/**
	 * @param $name
	 * @param $callback
	 * @param array $params
	 */
	public function push($name, $callback, $params = [])
	{
		$this->_Scan_directory[$name] = $callback;
		if (!empty($params)) {
			$this->params[$name] = $params;
		}
	}


	/**
	 * @param $path
	 * @return bool|mixed
	 */
	public function has($path)
	{
		return isset($this->_Scan_directory[$path]);
	}


	/**
	 * @param $name
	 * @return mixed|null
	 * @throws ReflectionException
	 * @throws NotFindClassException
	 * @throws Exception
	 */
	public function __get($name)
	{
		if (isset($this->_classMap[$name])) {
			return Snowflake::createObject($this->_classMap[$name]);
		}
		return parent::__get($name); // TODO: Change the autogenerated stub
	}


}
