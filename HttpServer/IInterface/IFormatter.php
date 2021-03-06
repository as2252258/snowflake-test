<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/8 0008
 * Time: 17:29
 */

namespace HttpServer\IInterface;


/**
 * Interface IFormatter
 * @package Snowflake\Snowflake\Http\Formatter
 */
interface IFormatter
{

	/**
	 * @param $context
	 * @return static
	 */
	public function send($context);

	public function getData();

	public function clear();
}
