<?php


namespace HttpServer\Route;


/**
 * Class Any
 * @package Snowflake\Snowflake\Route
 */
class Any
{

	private $nodes = [];

	/**
	 * Any constructor.
	 * @param array $nodes
	 */
	public function __construct(array $nodes)
	{
		$this->nodes = $nodes;
	}


	/**
	 * @param $name
	 * @param $arguments
	 * @return $this
	 */
	public function __call($name, $arguments)
	{
		foreach ($this->nodes as $node) {
			$node->{$name}(...$arguments);
		}
		return $this;
	}

}
