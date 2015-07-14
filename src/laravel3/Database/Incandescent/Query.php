<?php

namespace WallaceMaxters\Laravel3\Database\Incandescent;


use Closure;
use DateTime;
use Laravel\Database\Expression;
use Laravel\Database\Eloquent\Relationships;
use Laravel\Database\Eloquent\Query as EloquentQuery;
use WallaceMaxters\Laravel3\Support\Collection;

/**
* @author Wallace de Souza Vizerra <wallacemaxters@gmail.com>
*/

class Query extends EloquentQuery
{

	/**
	* Convert to SQL sintax
	* @return string
	*/
	public function to_sql()
	{
		if (! $this->table->selects) {

			$this->table->select();
		}

		$grammar = $this->table->grammar;

		$sql = $this->table->grammar->select($this->table);

		// Remove expressions from "bindings"
		// Only value is accepted

		$bindings = array_filter((array) $this->table->bindings, function ($value)
		{
			return ! $value instanceof Expression;
		});

		// The variable is passed to reference because 
		// closure is called with variable $bindings in each calling

		return preg_replace_callback('/\?/', function($match) use(&$bindings, $grammar)
	    {
	    	$value = array_shift($bindings);

	    	if ($value instanceof DateTime) {
	    		$value = $value->format($grammar->datetime);
	    	}

	        return is_string($value) ? '"' . $value . '"' : $value;

	    }, $sql);
	}

	public function where_has($relation, Closure $callback)
	{
		return $this->has($relation, '>=', 1, $callback);
	}

	public function where_doesnt_have($relation, Closure $callback)
	{
		return $this->has($relation, '<', 1, $closure);
	}

	public function doesnt_have($relation)
	{
		return $this->has($relation, '<', 1);
	}

	public function has($relation, $operator = '>=', $value = 1, Closure $callback = null)
	{
		$relationship = $this->model->$relation();														

		$associated = $relationship->model;

		$query = new static($relationship->model);														

		$query->select_aggregate('count', '*');

	    if ($relationship instanceof Relationships\Has_One_Or_Many) {

	    	$foreign =  $associated->table(). '.' .$relationship->foreign_key();

	    	$key = $this->model->table() . '.' . $this->model->key();

	    } elseif ($relationship instanceof Relationships\Has_Many_And_Belongs_To)  {


	    	$associated = $relationship->model;
	    	
	    	// Gambiarra brasileira
	    	// Não gosto, mas não teve jeito :(

	    	$reflection = new \ReflectionMethod(get_class($relationship), 'other_key');

	    	$reflection->setAccessible(true);

	    	$other = $reflection->invoke($relationship);

	    	$key = $this->model->table() . '.' . $this->model->key();

	    	$join_table = $relationship->pivot()->model->table();

	    	$foreign = $join_table . '.' . $relationship->foreign_key();

	    	$query->join(
	    		$join_table,
	    		$join_table . '.' . $other,
	    		'=',
	    		$associated->table() . '.' . $associated->key()
	    	);


	    } else {

	    	$key = $associated->table() . '.' . $relationship->foreign_key(); 

	    	$foreign = $associated->table(). '.' . $relationship->model->key();
	    	
	    }
 
		$query->where($foreign, '=', new Expression($key));

	    if ($callback !== null) {

	    	$callback($query);
	    }

	    $sql = $query->to_sql();

	    $this->where(new Expression('(' . $sql . ')'), $operator, $value);

	    return $this;
	}

	public function select_aggregate($aggregator, $columns)
	{
		$this->table->aggregate = array(
			'aggregator' => 'count',
			'columns'    => (array) $columns
		);

		return $this;
	}

	public function get_collection()
	{
		return new Collection($this->get());
	}

}