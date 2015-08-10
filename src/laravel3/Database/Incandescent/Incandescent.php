<?php

namespace WallaceMaxters\Laravel3\Database\Incandescent;

use JsonSerializable; 
use Laravel\Database\Eloquent\Model;
use WallaceMaxters\Laravel3\Support\Collection;
use WallaceMaxters\Laravel3\Database\Incandescent\Relationships;

/**
* @package Laravel3
* @author Wallace de Souza Vizerra <wallacemaxters@gmail.com>
* Classe criada para contornas as limitações do Eloquent do Laravel 3
*/

abstract class Incandescent extends Model implements JsonSerializable 
{

    protected $appends = array();

    /**
    * Implementation for JsonSerializable Interface
    * @return array
    */
    
    public function jsonSerialize()
    {
        return array_except($this->to_array(), static::$hidden);
    }

    /**
    * new implementation for to_array
    * add append elements for conversion for array
    * @return array
    */
    public function to_array()
    {
        $attributes = parent::to_array();

        foreach ($this->appends as $attribute) {

            $accessor = "get_{$attribute}";

            if (method_exists($this, $accessor)) {

                $attributes[$attribute] = $this->$accessor();
            }
        }

        return $attributes;
    }

    /**
    * Define a new value for appends
    * @return $this
    */
    public function set_appends(array $appends)
    {
        $this->appends = $appends;

        return $this;
    }

    /**
    * Add elements for appends
    * @return $this
    */
    public function add_appends($valueOrValues)
    {

        $this->appends = array_merge($this->appends, (array)$valueOrValues);

        return $this;
    }

    /**
    * Convert model to JSON
    */
    public function to_json()
    {
        return json_encode($this, JSON_PRETTY_PRINT);
    }

    /**
    * Convert model to JSON where called as string
    */
    public function __toString()
    {
        return $this->to_json();
    }

    /**
    * Return new Incandescent Query
    * @return \WallaceMaxters\Laravel3\Database\Incandescent\Query
    */
    protected function _query()
    {
        return new Query($this);
    }


    public function has_many_and_belongs_to($model, $table = NULL, $foreign = NULL, $other = NULL)
    {
        return new Relationships\BelongsToMany($this, $model, $table, $foreign, $other);
    }

    /**
     * Get the query for a one-to-one (inverse) relationship.
     *
     * @param  string        $model
     * @param  string        $foreign
     * @return Relationship
     */
    public function belongs_to($model, $foreign = null)
    {
        // If no foreign key is specified for the relationship, we will assume that the
        // name of the calling function matches the foreign key. For example, if the
        // calling function is "manager", we'll assume the key is "manager_id".
        if (is_null($foreign))
        {
            list(, $caller) = debug_backtrace(false);

            $foreign = "{$caller['function']}_id";
        }

        return new Relationships\BelongsTo($this, $model, $foreign);
    }

    /**
     * Get the query for a one-to-one / many association.
     *
     * @param  string        $type
     * @param  string        $model
     * @param  string        $foreign
     * @return Relationship
     */
    protected function has_one_or_many($type, $model, $foreign)
    {
        
        if ($type == 'has_one')
        {
            return new Relationships\HasOne($this, $model, $foreign);
        }
        else
        {
            return new Relationships\HasMany($this, $model, $foreign);
        }
    }

}