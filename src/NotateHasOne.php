<?php

namespace Notate;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class NotateHasOne extends HasOne
{

    public function __construct(Builder $query, Model $parent, $foreignKey, $localKey)
    {
        $this->localKey = $localKey;
        $this->foreignKey = $foreignKey;
        $this->query = $query;
        $this->parent = $parent;
        $this->related = $query->getModel();
        $this->addConstraints();
    }

    public function getParentKey()
    {
        if(!str_contains($this->localKey,'->'))
        {
            return $this->localKey;
        }

        $column = explode('->', $this->localKey)[0];

        if(isset($this->parent->{$column}))
        {
            $search = str_replace('->', '.', str_replace_first($column . '->', '', $this->localKey));
            if($json = json_decode($this->parent->{$column}, true))
            {
                if(!is_object(array_get(array_dot($json),$search)) && !is_array(array_get(array_dot($json),$search)))
                {
                    return array_get(array_dot($json),$search);
                }
            }
        }
    }

    public function addConstraints()
    {
        if (static::$constraints) {
            $this->query->where($this->foreignKey, '=', $this->getParentKey());
            $this->query->whereNotNull($this->foreignKey);
        }
    }

}