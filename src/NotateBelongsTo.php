<?php

namespace App;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotateBelongsTo extends BelongsTo
{
    public function __construct(Builder $query, Model $child, $foreignKey, $ownerKey, $relation)
    {
        parent::__construct($query, $child, $foreignKey, $ownerKey, $relation);
    }

    public function getChildKey()
    {
        if(!str_contains($this->ownerKey,'->'))
        {
            return $this->child->{$this->ownerKey};
        }

        $column = explode('->', $this->ownerKey)[0];

        if(isset($this->child->{$column}))
        {
            $search = str_replace('->', '.', str_replace_first($column . '->', '', $this->ownerKey));

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
            $this->getQuery()->where($this->foreignKey, '=', $this->getChildKey());
            $this->getQuery()->whereNotNull($this->foreignKey);
        }
    }
}