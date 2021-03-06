<?php


namespace Database\Condition;

use Snowflake\Core\Str;

/**
 * Class RLikeCondition
 * @package Database\Condition
 */
class RLikeCondition extends Condition
{

	public $pos = '';

	/**
	 * @return string
	 */
	public function builder()
	{
		if (!is_string($this->value)) {
			$this->value = array_shift($this->value);
		}
		$this->value = Str::encode($this->value);
		return $this->column . ' LIKE \'' . $this->value . '%\'';
	}

}
