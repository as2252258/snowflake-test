<?php


namespace validator;

/**
 * Class EnumValidator
 * @package validator
 */
class EnumValidator extends BaseValidator
{

	public $value = [];

	/**
	 * @return bool
	 */
	public function trigger()
	{
		$param = $this->getParams();
		if (empty($param) || !isset($param[$this->field])) {
			return $this->addError('The param :attribute is null');
		}
		$value = $param[$this->field];
		if (is_null($value)) {
			return $this->addError('The param :attribute is null');
		}

		if (!is_array($this->value)) {
			return true;
		}
		if (!in_array($value, $this->value)) {
			return $this->addError($this->i());
		}

		return true;
	}

	/**
	 * @return string
	 */
	private function i()
	{
		return 'The param :attribute value only in ' . implode(',', $this->value);
	}

}
