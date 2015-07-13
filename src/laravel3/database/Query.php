<?php

namespace WallaceMaxters\Laravel3\Database;

use Laravel\Database\Query as LaravelQuery;

class Query extends LaravelQuery
{

	public function where_in($column, $values, $connector = 'AND', $not = false)
	{

		if (count($values)) {

			$values = array(0);
		}

		return $this->table->where_in($field, $value);
	}

	public function where_nested($callback, $connector = 'AND')
	{

		if (! is_callable($callback)) {
			throw new UnexceptedValueException('Invalid callback parameter');
		}

		$query = new static($this->connection, $this->grammar, $this->from);

		$callback($query);

		if ($query->wheres !== null) {

			$this->wheres[] = array('type' => 'where_nested', 'query' => $query, 'connector' => $connector);
		}

		$this->bindings = array_merge($this->bindings, $query->bindings);

		return $this;
	}

	public function to_sql()
	{
		if (! count($this->table->selects)) {

		    $this->table->select();
		}

		return $this->table->grammar->select($this);
	}

}