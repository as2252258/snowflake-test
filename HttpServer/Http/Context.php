<?php


namespace HttpServer\Http;

use HttpServer\Abstracts\BaseContext;
use Swoole\Coroutine;

/**
 * Class Context
 * @package Yoc\http
 */
class Context extends BaseContext
{

	protected static $_requests = [];

	protected static $_response = [];


	/**
	 * @param $id
	 * @param $context
	 * @param null $key
	 * @return mixed
	 */
	public static function setContext($id, $context, $key = null)
	{
		if (static::inCoroutine()) {
			return self::setCoroutine($id, $context, $key);
		} else {
			return self::setStatic($id, $context, $key);
		}
	}

	/**
	 * @param $id
	 * @param $context
	 * @param null $key
	 * @return mixed
	 */
	private static function setStatic($id, $context, $key = null)
	{
		if (!empty($key)) {
			if (!is_array(static::$_requests[$id])) {
				static::$_requests[$id] = [$key => $context];
			} else {
				static::$_requests[$id][$key] = $context;
			}
		} else {
			static::$_requests[$id] = $context;
		}
		return $context;
	}

	/**
	 * @param $id
	 * @param $context
	 * @param null $key
	 * @return
	 */
	private static function setCoroutine($id, $context, $key = null)
	{
		if (!static::hasContext($id)) {
			Coroutine::getContext()[$id] = [];
		}
		if (!empty($key)) {
			if (!is_array(Coroutine::getContext()[$id])) {
				return Coroutine::getContext()[$id] = [$key => $context];
			} else {
				return Coroutine::getContext()[$id][$key] = $context;
			}
		} else {
			return Coroutine::getContext()[$id] = $context;
		}
	}

	/**
	 * @param $id
	 * @param null $key
	 * @return false|mixed
	 */
	public static function autoIncr($id, $key = null)
	{
		if (!static::inCoroutine()) {
			return false;
		}
		if (!isset(Coroutine::getContext()[$id][$key])) {
			return false;
		}
		return Coroutine::getContext()[$id][$key] += 1;
	}

	/**
	 * @param $id
	 * @param null $key
	 * @return false|mixed
	 */
	public static function autoDecr($id, $key = null)
	{
		if (!static::inCoroutine() || !static::hasContext($id)) {
			return false;
		}
		if (!isset(Coroutine::getContext()[$id][$key])) {
			return false;
		}
		return Coroutine::getContext()[$id][$key] -= 1;
	}

	/**
	 * @param $id
	 * @param null $key
	 * @return mixed
	 */
	public static function getContext($id, $key = null)
	{
		if (!static::hasContext($id)) {
			return null;
		}
		if (static::inCoroutine()) {
			if ($key === null) {
				return Coroutine::getContext()[$id];
			} else {
				return Coroutine::getContext()[$id][$key] ?? null;
			}
		} else {
			if ($key === null) {
				return static::$_requests[$id];
			} else {
				return static::$_requests[$id][$key] ?? null;
			}
		}
	}

	/**
	 * @return mixed
	 */
	public static function getAllContext()
	{
		if (static::inCoroutine()) {
			return Coroutine::getContext() ?? [];
		} else {
			return static::$_requests ?? [];
		}
	}

	/**
	 * @param $id
	 * @param null $key
	 */
	public static function deleteId($id, $key = null)
	{
		if (!static::hasContext($id, $key)) {
			return;
		}
		if (static::inCoroutine()) {
			if (!empty($key)) {
				unset(Coroutine::getContext()[$id][$key]);
			} else {
				unset(Coroutine::getContext()[$id]);
			}
		} else {
			unset(static::$_requests[$id]);
		}
	}

	/**
	 * @param $id
	 * @param null $key
	 * @return mixed
	 */
	public static function hasContext($id, $key = null)
	{
		if (!static::inCoroutine()) {
			return false;
		}
		if (!isset(Coroutine::getContext()[$id])) {
			return false;
		}
		if (Coroutine::getContext()[$id] !== null) {
			if ($key === null) {
				return true;
			}
			return isset(Coroutine::getContext()[$id][$key]);
		}
		return false;
	}


	/**
	 * @return bool
	 */
	public static function inCoroutine()
	{
		return Coroutine::getCid() > 0;
	}

}



