<?php


namespace Snowflake\Annotation;

use Exception;
use ReflectionClass;
use ReflectionException;
use Snowflake\Abstracts\BaseAnnotation;
use Snowflake\Annotation\Definition\Http;
use Snowflake\Annotation\Definition\Websocket;
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
	 * @param $class
	 * @throws ReflectionException
	 */
	public function registration_notes($path, $namespace, $class)
	{
		$this->scanning(rtrim($path, '/'), $namespace, $class);
	}


	/**
	 * @return string|Http
	 * @throws
	 */
	public function getHttp()
	{
		if (is_object($this->_classMap['http'])) {
			return $this->_classMap['http'];
		}

		return $this->_classMap['http'] = Snowflake::createObject($this->_classMap['http']);
	}


	/**
	 * @return string|Websocket
	 * @throws
	 */
	public function getWebsocket()
	{
		if (is_object($this->_classMap['websocket'])) {
			return $this->_classMap['websocket'];
		}

		return $this->_classMap['websocket'] = Snowflake::createObject($this->_classMap['websocket']);


		return make($this->_classMap['websocket'], $this->_classMap['websocket']);
	}

	/**
	 * @param ReflectionClass $reflect
	 * @param string $className
	 * @throws ReflectionException
	 * @throws Exception
	 * @Message(updatePosition)
	 */
	public function resolve(ReflectionClass $reflect, string $className)
	{
		$controller = $reflect->newInstance();

		$annotations = $this->getAnnotation($className);
		$methods = $reflect->getMethods(\ReflectionMethod::IS_PUBLIC);
		foreach ($methods as $function) {
			foreach ($annotations as $annotation) {
				$comment = $function->getDocComment();
				$methodName = $function->getName();

				preg_match('/@(' . $annotation . ')\((.*?)\)/', $comment, $events);
				if (!isset($events[1])) {
					continue;
				}
				if (!$this->isLegitimate($events)) {
					continue;
				}
				$_key = $this->getName($annotation, $events);
				if (empty($events[2])) {
					$this->push($_key, [$controller, $methodName]);
				} else {
					$handler = $this->createHandler($controller, $methodName, $events[2]);

					$this->push($_key, $handler, [request(), [$controller, $methodName]]);
				}
			}
		}
	}


	/**
	 * @param $events
	 * @throws Exception
	 */
	public function isLegitimate($events)
	{
		throw new Exception('Undefined analytic function isLegitimate.');
	}


	/**
	 * @param $function
	 * @param $events
	 * @throws Exception
	 */
	public function getName($function, $events)
	{
		throw new Exception('Undefined analytic function getName.');
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
	 * @param $className
	 * @throws ReflectionException
	 */
	protected function scanning($path, $namespace, $className)
	{
		$di = Snowflake::getDi();
		foreach (glob($path . '/*') as $file) {
			if (is_dir($file)) {
				$this->scanning($path, $namespace, $className);
			}

			$explode = explode('/', $file);

			$class = str_replace('.php', '', end($explode));

			$this->resolve($di->getReflect($namespace . '\\' . $class), $className);
		}
	}

	/**
	 * @param $path
	 * @return bool|mixed
	 */
	public function runWith($path)
	{
		if (!$this->has($path)) {
			return null;
		}
		$callback = $this->_Scan_directory[$path];
		if (!isset($this->params[$path])) {
			return $callback();
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
