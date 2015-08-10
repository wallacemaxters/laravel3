<?php

namespace WallaceMaxters\Laravel3\Support;

use Closure;
use Countable;
use ArrayAccess;
use ArrayIterator;
use JsonSerializable;
use IteratorAggregate;
use Laravel\Database\Eloquent\Model;

class Collection implements ArrayAccess, IteratorAggregate, Countable, JsonSerializable
{
    protected $items = array();

    public function __construct(array $items = array())
    {
        $this->items = $items;
    }

    public function getIterator()
    {
        return new ArrayIterator($this->items);
    }

    public function has($key)
    {
        return array_key_exists($key, $this->items);
    }

    public function get($key)
    {
        return $this->has($key) ? $this->items[$key] : null;
    }

    public function put($key, $value)
    {
        $this->items[$key] = $value;

        return $this;
    }

    public function delete($key)
    {
        unset($this->items[$key]);
    }

    public function all()
    {
        return $this->items;
    }

    public function count()
    {
        return count($this->items);
    }

    public function shift()
    {
        return array_shift($this->items);
    }

    public function pop()
    {
        return array_pop($this->items);
    }

    public function search($key)
    {
        return array_search($key, $this->items);
    }

    public function push($value)
    {
        $this->offsetSet(null, $value);
    }

    public function lists($key1, $key2 = null)
    {

        $list = array_map(function ($value) use($key1) {

            return is_object($value) ? $value->$key1 : $value[$key1];

        }, $this->items);

        if ($key2 !== null) {

            $keys = array_map(function ($value) use($key2) {

                return is_object($value) ? $value->$key2 : $value[$key2];                

            }, $this->items);

            $list = array_combine($keys, $list);
        }

        return $list;
    }

    public function prepend($value)
    {
        array_unshift($this->items, $value);

        return $this;
    }

    public function filter(Closure $callback = null)
    {   

        if ($callback !== null) {

            return new static(array_filter($this->items, $callback));
        }

        return new static(array_filter($this->items));
    }

    public function reject(Closure $callback = null)
    {
        return $this->filter(function ($value) use($callback)
        {
            return !$callback($value);
        });

    }

    public function first()
    {
        return count($this->items) > 0 ? reset($this->items) : null;
    }

    public function last()
    {
        return count($this->items) > 0 ? end($this->items) : null;
    }

    public function each(Closure $callback)
    {
        array_walk($this->items, $callback);

        return $this;
    }

    public function transform(Closure $callback)
    {
        $this->items = array_map($callback, $this->items);

        return $this;
    }

    public function transformed(Closure $callback)
    {
        $new = clone $this;

        $new->transform($callback);

        return $new;
    }

    public function reverse($preserve_key = false)
    {
        return new static(array_reverse($this->items, $preserve_key));
    }

    public function slice($offset, $length = null)
    {
        return new static(array_slice($this->items, $offset, $length));
    }

    public function unique()
    {
        return new static(array_unique($this->items));
    }

    public function shuffle()
    {
        $items = $this->items;

        shuffle($items);

        return new static($items);
    }

    public function chunk($size, $preserve_keys = false)
    {

        $chunks = array();

        foreach (array_chunk($this->items, $size, $preserve_keys) as $chunk) {
            
            $chunks[] = new static($chunk);
        }

        return new static($chunks);
    }

    public function group_by(Closure $callback, $preserve_keys = false)
    {
        $group = array();

        foreach ($this->items as $key => $item) {

            $group_key = $callback($item);

            if (! array_key_exists($group_key, $group)) {

                $group[$group_key] = new static;
            }
            
            $group[$group_key]->offsetSet($preserve_keys ? $key : null, $item);
        }

        return new static($group);
    }

    public function sort(Closure $callback = null)
    {
        $items = $this->items;

        $callback ? uasort($items, $callback) : natcasesort($items);

        return new static($items);

    }

    public function sort_by(Closure $callback, $asc = true)
    {

        $i = $asc ? 1 : -1;

        $items = $this->items;

        uasort($items, function ($value1, $value2) use($callback, $i)
        {
            return $i * strcmp($callback($value1), $callback($value2));
        });

        return new static($items);

    }

    
    public function sum(Closure $callback = null)
    {
        $total = 0;

        foreach ($this->items as $item) {
            
            if ($callback !== null) {

                $total += $callback($item);

            } else {

                $total += $item;
            }
        }

        return $total;
    }

    public function sort_by_desc(Closure $callback)
    {
        return $this->sort_by($callback, false);
    }

    /**
    * @param int|string $key
    * @param mixed $value
    * @return void
    */

    public function offsetSet($key, $value)
    {
        if ($key !== null) {

            $this->put($key, $value);

        } else {

            $this->items[] = $value;
        }
    }

    /**
     * @param int|string $key
     * @return mixed
    */
    public function offsetGet($key)
    {
        return $this->get($key);
    }

    /**
    * @param int|string $key
    * @return void
    */
    public function offsetUnset($key)
    {
        $this->delete($key);
    }

    /**
    * @param int|string $key
    * @return bool
    */
    public function offsetExists($key)
    {
        return $this->has($key);
    }

    public function jsonSerialize()
    {
        return json_encode($this->all());
    }

    public function is_empty()
    {
        return empty($this->items);
    }

    public function keys()
    {
        return array_keys($this->items);
    }

    public function to_array()
    {
        $array = array();

        foreach ($this->items as $key => $item) {

            if ($item instanceof Model || $item instanceof self) {

                $item = $item->to_array();
            }

            $array[$key] = $item;
        }

        return $array;
    }

    public function to_json()
    {
        return $this->jsonSerialize();
    }


}