<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019-03-20
 * Time: 01:04
 */

namespace Snowflake\Core;

/**
 * Class JSON
 * @package Snowflake\Snowflake\Core
 */
class JSON
{

	/**
	 * @param $data
	 * @return false|string
	 * @throws \Exception
	 */
	public static function encode($data)
	{
		if (empty($data)) {
			return $data;
		}
		if (is_array($data)) {
			return self::filter(ArrayAccess::toArray($data));
		}
		return $data;
	}


	/**
	 * @param $data
	 * @return mixed
	 */
	private static function filter($data)
	{
		array_walk_recursive($data, function ($value, $key) use ($data) {
			if (!is_numeric($value)) {
				return;
			}
			if (is_int(+$value)) {
				$value = +$value;
			}
			$data[$key] = $value;
		});
		return json_encode($data, JSON_UNESCAPED_UNICODE);
	}


	/**
	 * @param $data
	 * @param bool $asArray
	 * @return mixed
	 */
	public static function decode($data, $asArray = true)
	{
		if (is_array($data)) {
			return $data;
		}
		return json_decode($data, $asArray);
	}

	/**
	 * @param $code
	 * @param string $message
	 * @param array $data
	 * @param int $count
	 * @param array $exPageInfo
	 * @return mixed
	 * @throws
	 */
	public static function to($code, $message = '', $data = [], $count = 0, $exPageInfo = [])
	{
		$params['code'] = $code;
		if (!is_string($message)) {
			$params['param'] = $message;
			if (!empty($data)) {
				$params['exPageInfo'] = $data;
			}
			$params['message'] = 'System success.';
		} else {
			$params['message'] = $message;
			$params['param'] = $data;
		}
		if (!empty($exPageInfo)) {
			$params['exPageInfo'] = $exPageInfo;
		}
		$params['count'] = $count;
		if (is_numeric($data) || !is_numeric($count)) {
			$params['count'] = $data;
			$params['exPageInfo'] = $count;
		}
		if ((int)$params['count'] == -100) {
			$params['count'] = 1;
		}

		ksort($params, SORT_ASC);

		return static::encode($params);
	}

	/**
	 * @param $state
	 * @param $body
	 * @return false|int|string
	 * @throws \Exception
	 */
	public static function output($state, $body)
	{
		$params['state'] = $state;
		$params['body'] = ArrayAccess::toArray($body);

		return static::encode($params);
	}

}
