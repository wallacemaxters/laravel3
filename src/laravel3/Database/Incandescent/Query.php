<?php

namespace WallaceMaxters\Laravel3\Database\Incandescent;


use Closure;
use DateTime;
use Laravel\Database\Expression;
use WallaceMaxters\Laravel3\Database\Incandescent\Relationships;
use WallaceMaxters\Laravel3\Support\Collection;
use Laravel\Database\Eloquent\Query as EloquentQuery;
use WallaceMaxters\Laravel3\Database\Exceptions\ModelNotFoundException;

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
        return $this->has($relation, '<', 1, $callback);
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

        if ($relationship instanceof Relationships\HasOneOrMany) {

            $foreign =  $associated->table(). '.' .$relationship->foreign_key();

            $key = $this->model->table() . '.' . $this->model->key();

        } elseif ($relationship instanceof Relationships\BelongsToMany) {

            // Change the table name of Subquery
            
            $query->table->from = $relationship->pivot()->table->from;

            $associated = $relationship->model;

            $key = $this->model->table() . '.' . $this->model->key();

            $foreign = $relationship->pivot()->model->table() . '.' . $relationship->foreign_key();

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

    public function setup_self_relation(self $query)
    {
        $from = $query->table->from;

        $alias = 'self_' . md5(microtime(true));

        $query->table->from .= ' as ' . $alias;

        $query->table->alias = $alias;
    }

    public function select_aggregate($aggregator, $columns)
    {
        $this->table->aggregate = array(
            'aggregator' => 'count',
            'columns'    => (array) $columns
        );

        return $this;
    }

    /**
     * @deprecated
     * */

    public function get_collection()
    {
        return new Collection($this->get());
    }

    
    public function collection()
    {
        return new Collection($this->get());
    }


    /**
     * Hydrate an array of models from the given results.
     *
     * @param  Model  $model
     * @param  array  $results
     * @return array
     */
    public function hydrate($model, $results)
    {

        $class = get_class($model);

        $models = array();

        // We'll spin through the array of database results and hydrate a model
        // for each one of the records. We will also set the "exists" flag to
        // "true" so that the model will be updated when it is saved.
        foreach ((array) $results as $result)
        {
            $result = (array) $result;

            $new = new $class(array(), true);

            // We need to set the attributes manually in case the accessible property is
            // set on the array which will prevent the mass assignemnt of attributes if
            // we were to pass them in using the constructor or fill methods.
            $new->fill_raw($result);

            $models[] = $new;
        }

        if (count($results) > 0)
        {
            foreach ($this->model_includes() as $relationship => $constraints)
            {
                // If the relationship is nested, we will skip loading it here and let
                // the load method parse and set the nested eager loads on the right
                // relationship when it is getting ready to eager load.
                if (str_contains($relationship, '.'))
                {
                    continue;
                }

                $this->load($models, $relationship, $constraints);
            }
        }

        // The many to many relationships may have pivot table column on them
        // so we will call the "clean" method on the relationship to remove
        // any pivot columns that are on the model.
        if ($this instanceof Relationships\BelongsToMany)
        {

            $this->hydrate_pivot($models);
        }

        return $models;
    }

    public function find_or_fail($id, array $columns = array('*'))
    {
        $result = $this->find($id, $columns);

        if ($result === null) {

            $message = sprintf('No result for model [%s]', get_class($this->model));

            throw new ModelNotFoundException($message);
        }

        return $result;
    }

    public function first_or_fail(array $columns = array('*'))
    {
        $result = $this->first($columns);

        if  ($result === null) {

            $message = sprintf('No result for model [%s]', get_class($this->model));

            throw new ModelNotFoundException($message);
        }

        return $result;
    }

    public function find_or_new($id, array $columns = array('*'))
    {
        $result =  $this->find($id, $columns);

        if ($result === null) {

            $result = new $this->model;
        }

        return $result;
    }

    public function call_scope($scope, $parameters)
    {        
        array_unshift($parameters, $this);

        return call_user_func_array(array($this->model, $scope), $parameters) ?: $this;
    }

    public function __call($method, $parameters)
    {

        $scope = 'scope_' . $method;

        if (method_exists($this->model, $scope)) {

            return $this->call_scope($scope, $parameters);
        }

        return parent::__call($method, $parameters);
    }

}