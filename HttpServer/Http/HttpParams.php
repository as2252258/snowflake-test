<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019-03-18
 * Time: 14:54
 */

namespace HttpServer\Http;

use Exception;
use HttpServer\Exception\RequestException;
use Snowflake\Core\Help;
use Snowflake\Snowflake;

/**
 * Class HttpParams
 * @package Snowflake\Snowflake\Http
 */
class HttpParams
{

	/** @var array */
	private $body = [];

	/** @var array */
	private $gets = [];

	/** @var array */
	private $files = [];

	/**
	 * HttpParams constructor.
	 * @param $body
	 * @param $get
	 * @param $files
	 */
	public function __construct($body, $get, $files)
	{
		$this->body = $body;
		$this->gets = $get ?? [];
		$this->files = $files ?? [];
		if (!is_array($this->body)) {
			$this->body = Help::toArray($this->body);
		}

	}

	/**
	 * @return int
	 */
	public function offset()
	{
		return ($this->page() - 1) * $this->size();
	}

	/**
	 * @param array $data
	 * 批量添加数据
	 */
	public function setPosts(array $data)
	{
		if (!is_array($data)) {
			return;
		}
		foreach ($data as $key => $vla) {
			$this->body[$key] = $vla;
		}
	}

	/**
	 * @param string $key
	 * @param string $value
	 */
	public function addGetParam(string $key, string $value)
	{
		$this->gets[$key] = $value;
	}

	/**
	 * @return int
	 */
	private function page()
	{
		return (int)$this->get('page', 1);
	}

	/**
	 * @return int
	 */
	public function size()
	{
		return (int)$this->get('size', 20);
	}


	/**
	 * @param $name
	 * @param $defaultValue
	 * @param $call
	 * @return mixed|null
	 */
	public function get($name, $defaultValue = null, $call = null)
	{
		return $this->gets[$name] ?? $defaultValue;
	}

	/**
	 * @param $name
	 * @param null $defaultValue
	 * @param $call
	 * @return mixed|null
	 */
	public function post($name, $defaultValue = null, $call = null)
	{
		$data = $this->body[$name] ?? $defaultValue;
		if ($call !== null) {
			$data = call_user_func($call, $data);
		}
		return $data;
	}

	/**
	 * @param $name
	 * @return false|string
	 * @throws Exception
	 */
	public function json($name)
	{
		$data = $this->array($name);
		if (empty($data)) {
			return JSON::encode([]);
		} else if (!is_array($data)) {
			return JSON::encode([]);
		}
		return JSON::encode($data);
	}

	/**
	 * @return array
	 */
	public function gets()
	{
		return $this->gets;
	}

	/**
	 * @return array
	 */
	public function params()
	{
		return array_merge($this->body ?? [], $this->files ?? []);
	}

	/**
	 * @return array
	 */
	public function load()
	{
		return array_merge($this->files, $this->body, $this->gets);
	}

	/**
	 * @param $name
	 * @param array $defaultValue
	 * @return array|mixed
	 */
	public function array($name, $defaultValue = [])
	{
		return $this->body[$name] ?? $defaultValue;
	}

	/**
	 * @param $name
	 * @return mixed|File|null
	 * @throws Exception
	 */
	public function file($name)
	{
		if (!isset($this->files[$name])) {
			return null;
		}
		$param = $this->files[$name];
		$param['class'] = File::class;
		return Snowflake::createObject($param);
	}

	/**
	 * @param $name
	 * @param bool $isNeed
	 * @return mixed|null
	 * @throws RequestException
	 */
	private function required($name, $isNeed = false)
	{
		$int = $this->body[$name] ?? NULL;
		if (is_null($int) && $isNeed === true) {
			throw new RequestException("You need to add request parameter $name");
		}
		return $int;
	}

	/**
	 * @param      $name
	 * @param bool $isNeed
	 * @param null $min
	 * @param null $max
	 * @return int
	 * @throws Exception
	 */
	public function int($name, $isNeed = FALSE, $min = NULL, $max = NULL)
	{
		$int = $this->required($name, $isNeed);
		if ($int === null) return null;
		if (is_array($min)) {
			list($min, $max) = $min;
		}
		if (is_null($int)) {
			$length = 0;
		} else {
			$length = strlen(floatval($int));
		}
		if (!is_numeric($int) || intval($int) != $int) {
			throw new RequestException("The request parameter $name must integer.");
		}
		$this->between($length, $min, $max);
		return (int)$int;
	}

	/**
	 * @param      $name
	 * @param bool $isNeed
	 * @param int $round
	 * @return float
	 * @throws Exception
	 */
	public function float($name, $isNeed = FALSE, $round = 0)
	{
		$int = $this->required($name, $isNeed);
		if ($int === null) {
			return null;
		}
		if ($round > 0) {
			return round(floatval($int), $round);
		} else {
			return floatval($int);
		}
	}

	/**
	 * @param      $name
	 * @param bool $isNeed
	 * @param null $length
	 *
	 * @return string
	 * @throws
	 */
	public function string($name, $isNeed = FALSE, $length = NULL)
	{
		$string = $this->required($name, $isNeed);
		if ($string === null || $length === null) {
			return $string;
		}
		if (!is_string($string)) {
			$string = json_encode($string, JSON_UNESCAPED_UNICODE);
		}
		$_length = strlen($string);
		if (is_array($length)) {
			if (count($length) < 2) {
				array_unshift($length, 0);
			}
			$this->between($_length, ...$length);
		} else if (is_numeric($length) && $_length != $length) {
			throw new RequestException("The length of the string must be $length characters");
		}
		return $string;
	}

	/**
	 * @param $_length
	 * @param $min
	 * @param $max
	 * @throws RequestException
	 */
	private function between($_length, $min, $max)
	{
		if ($min !== NULL && $_length < $min) {
			throw new RequestException("The minimum value cannot be lower than $min");
		}
		if ($max !== NULL && $_length > $max) {
			throw new RequestException("Maximum cannot exceed $max, has length " . $_length);
		}
	}

	/**
	 * @param      $name
	 * @param bool $isNeed
	 *
	 * @return string
	 * @throws RequestException
	 */
	public function email($name, $isNeed = FALSE)
	{
		$email = $this->required($name, $isNeed);
		if ($email === null) {
			return null;
		}
		if (!preg_match('/^\w+([.-_]\w+)+@\w+(\.\w+)+$/', $email)) {
			throw new RequestException("Request parameter $name is in the wrong format", 4001);
		}
		return $email;
	}


	/**
	 * @param      $name
	 * @param bool $isNeed
	 *
	 * @return string
	 * @throws RequestException
	 */
	public function bool($name, $isNeed = FALSE)
	{
		$email = $this->required($name, $isNeed);
		if ($email === null) {
			return false;
		}
		return (bool)$email;
	}

	/**
	 * @param      $name
	 * @param null $default
	 *
	 * @return mixed|null
	 * @throws RequestException
	 */
	public function timestamp($name, $default = NULL)
	{
		$value = $this->required($name, false);
		if ($value === null) {
			return $default;
		}
		if (!is_numeric($value)) {
			throw new RequestException('The request param :attribute not is a timestamp value');
		}
		if (strlen((string)$value) != 10) {
			throw new RequestException('The request param :attribute not is a timestamp value');
		}
		if (!date('YmdHis', $value)) {
			throw new RequestException('The request param :attribute format error', 4001);
		}
		return $value;
	}

	/**
	 * @param      $name
	 * @param null $default
	 *
	 * @return mixed|null
	 * @throws RequestException
	 */
	public function datetime($name, $default = NULL)
	{
		$value = $this->required($name, false);
		if ($value === null) {
			return $default;
		}
		$match = '/^\d{4}.*?([1-12]).*([1-31]).*?[0-23].*?[0-59].*?[0-59].*?$/';
		$match = preg_match($match, $value, $result);
		if (!$match || $result[0] != $value) {
			throw new RequestException('The request param :attribute format error', 4001);
		}
		return $value;
	}


	/**
	 * @param $name
	 * @param null $default
	 * @return mixed|null
	 * @throws RequestException
	 */
	public function ip($name, $default = NULL)
	{
		$value = $this->required($name, false);
		if ($value == NULL) {
			return $default;
		}
		$match = preg_match('/^\d{1,3}(\.\d{1,3}){3}$/', $value, $result);
		if (!$match || $result[0] != $value) {
			throw new RequestException('The request param :attribute format error', 4001);
		}
		return $value;
	}


	/**
	 * @param $name
	 * @return mixed|null
	 */
	public function __get($name)
	{
		$load = $this->load();

		return $load[$name] ?? null;
	}

}
