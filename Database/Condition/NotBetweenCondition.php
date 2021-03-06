<?php


namespace Database\Condition;

/**
 * Class NotBetweenCondition
 * @package Database\Condition
 */
class NotBetweenCondition extends Condition
{


	/**
	 * @return string
	 */
	public function builder()
	{
		return $this->column . ' NOT BETWEEN ' . $this->value[0] . ' AND ' . $this->value[1];
	}

}
