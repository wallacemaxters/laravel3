<?php

namespace WallaceMaxters\Laravel3\Database\Incandescent;

/**
* @package Laravel3
* @author Wallace de Souza Vizerra <wallacemaxters@gmail.com>
* Classe criada para contornas as limitações do Eloquent do Laravel 3
*/

use Laravel\Database\Eloquent\Relationships;
use Laravel\Database\Eloquent\Model;

abstract class Incandescent extends Model implements JsonSerializable 
{

	protected $appends = array();

	/**
	* Facilita a utilização de json_encode e Response::json
	* Implementation for JsonSerializable Interface
	*/

	public function jsonSerialize()
	{
		return array_except($this->to_array(), static::$hidden);
	}

	public function to_array()
	{
		$attributes = parent::to_array();

		foreach ($this->appends as $attribute) {

			$accessor = "get_{$attribute}";

			if (method_exists($this, $accessor)) {

				$attributes[$attribute] = $this->$accessor();
			}
		}

		$attributes;
	}

	public function set_appends(array $appends)
	{
		$this->appends = $appends;

		return $this;
	}

	public function add_appends($valueOrValues)
	{

		$this->appends = array_merge($this->appends, (array)$valueOrValues);

		return $this;
	}

	public function to_json()
	{
		return json_encode($this, JSON_PRETTY_PRINT);
	}

	public function __toString()
	{
		return $this->to_json();
	}

	/**
	* @param string $relation_method
	*/
	public static function has($relation_method)
	{
		return static::where_relation($relation_method, null, false);
	}

	/**
	* @param string $relation_method
	* @param Closure $closure
	*/
	public function where_has($relation_method, Closure $closure)
	{
		return static::where_relation($relation_method, $closure, false);
	}

	/**
	* @param $relation_method
	*/

	public static function doesnt_have($relation_method)
	{
		return static::where_relation($relation_method, null, true);
	}

	/**
	* @param $relation_method
	*/

	public function where_doesnt_have($relation_method, Closure $closure)
	{
		return static::where_relation($relation_method, $closure, true);
	}

	private static function where_relation($relation_method, Closure $closure = null, $not = false)
	{
	    $instance = new static; 

	    if (! method_exists($instance, $relation_method)) {

	        throw new InvalidArgumentException(
	        	"Não existe o método de relacionamento {$relation_method}"
	        );
	    }

	    return $instance->where(function ($query) use($instance, $relation_method, $closure, $not)
	    {

	    	$relation = $instance->$relation_method();

	    	$associated = $relation->model;

	        if ($relation instanceof Relationships\Has_One_Or_Many) {

	        	$foreign = $relation->foreign_key();

	        	$key = $instance->key();



	        } elseif($relation instanceof Relationships\Has_Many_And_Belongs_To)  {

	        	$key = $instance->key();

	        	/*
	        		Utilizamos essa artimanha, pois o laravel 
	        	 	definiu "Has_Many_And_Belongs_To::other_key"
	        		como protected
	        	*/

	        	$method = new ReflectionMethod(get_class($relation), 'other_key');	

	        	$method->setAccessible(true);

	        	// Chama o método "other_key"

	        	$foreign = $method->invoke($relation); 

	        	// Seleciona a tabela "pivot" e reseta qualquer relacionamento pré-determinado
	        	$associated = $relation->pivot()->reset_where();

	        } else {

	        	$foreign = $associated->key(); 

	        	$key = $relation->foreign_key();
	        	
	        }

	        // Retorna um array contendo a lista das chaves para relacionamento

	        if ($closure !== null) {

	            $associated = $associated->where($closure);
	        }

	        $list = $associated->where_null($foreign, 'AND', !$not)
	                           ->group_by($foreign)
	        	               ->order_by($foreign, 'ASC')
	        	               ->lists($foreign);

	        if (count($list) == 0) {
	        	
	        	$list = [0];	        	
	        }

	        // Aplica nosso "where_has"

	        return $query->where_in($key, $list, 'AND', $not);

	    });
	}

}