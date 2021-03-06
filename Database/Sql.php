<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/6/27 0027
 * Time: 17:49
 */

namespace Database;


use Database\Orm\Select;
use Database\Traits\QueryTrait;

/**
 * Class Sql
 * @package Database
 */
class Sql
{
	
	use QueryTrait;
	
	/**
	 * @return string
	 * @throws \Exception
	 */
	public function getSql()
	{
		return (new Select())->getQuery($this);
	}
}
