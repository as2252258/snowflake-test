<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/5/2 0002
 * Time: 14:51
 */

namespace Snowflake\Cache;


use Exception;
use Snowflake\Abstracts\Component;
use Swoole\Coroutine\System;

/**
 * Class File
 * @package Snowflake\Snowflake\Cache
 */
class File extends Component implements ICache
{
	public $path;

	/**
	 * @throws Exception
	 */
	public function init()
	{
		parent::init(); // TODO: Change the autogenerated stub
	}

	/**
	 * @param $key
	 * @param $val
	 */
	public function set($key, $val)
	{
		if (is_array($val) || is_object($val)) {
			$val = serialize($val);
		}
		$tmpFile = $this->getCacheKey($key);
		if (!$this->exists($tmpFile)) {
			touch($tmpFile);
		}
		System::writeFile($tmpFile, $val, LOCK_EX);
	}

	/**
	 * @param $key
	 * @param array $hashKeys
	 * @return mixed|void
	 */
	public function hMget($key, array $hashKeys)
	{
		$hash = $this->get($key);
		if (!is_array($hash)) {
			return false;
		}

		$nowHash = [];
		foreach ($hashKeys as $hashKey) {
			$nowHash[$hashKey] = $hash[$hashKey] ?? null;
		}
		return $nowHash;
	}

	/**
	 * @param $key
	 * @param array $val
	 * @return mixed|void
	 */
	public function hMset($key, array $val)
	{
		$hash = $this->get($key);
		if (!is_array($hash)) {
			return false;
		}

		$merge = array_merge($hash, $val);
		return $this->set($key, $merge);
	}

	/**
	 * @param $key
	 * @param $hashKey
	 * @return mixed|void
	 */
	public function hget($key, $hashKey)
	{
		$hash = $this->get($key);
		if (!is_array($hash)) {
			return false;
		}
		return $hash[$hashKey] ?? null;
	}

	/**
	 * @param $key
	 * @param $hashKey
	 * @param $hashValue
	 * @return mixed|void
	 */
	public function hset($key, $hashKey, $hashValue)
	{
		$hash = $this->get($key);
		if (!is_array($hash)) {
			return false;
		}

		$hash[$hashKey] = $hashValue;

		return $this->set($key, $hash);
	}

	/**
	 * @param $key
	 * @return bool
	 */
	public function exists($key)
	{
		return file_exists($key);
	}

	/**
	 * @param $key
	 * @return mixed|null
	 */
	public function get($key)
	{
		$tmpFile = $this->getCacheKey($key);
		if (!$this->exists($tmpFile)) {
			return NULL;
		}
		$content = file_get_contents($tmpFile);
		return unserialize($content);
	}

	/**
	 * @param $key
	 * @return string
	 * @throws
	 */
	private function getCacheKey($key)
	{
		return storage($key,'cache');
	}
}