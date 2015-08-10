<?php

namespace WallaceMaxters\Laravel3\Database\Incandescent;

use JsonSerializable; 
use Laravel\Database\Eloquent\Model;
use WallaceMaxters\Laravel3\Support\Collection;
use WallaceMaxters\Laravel3\Database\Incandescent\Relationships\BelongsToMany;

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
        return new BelongsToMany($this, $model, $table, $foreign, $other);
    }

}