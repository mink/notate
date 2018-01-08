<?php

namespace Notate;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
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
        if(!str_contains($this->foreignKey,'->'))
        {
            return $this->child->{$this->foreignKey};
        }

        $column = explode('->', $this->foreignKey)[0];

        if(isset($this->child->{$column}))
        {
            $search = str_replace('->', '.', str_replace_first($column . '->', '', $this->foreignKey));
            if($json = json_decode($this->child->{$column}, true))
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
        if(!str_contains($this->foreignKey,'->'))
        {
            return parent::addConstraints();
        }

        if (static::$constraints) {
            $table = $this->related->getTable();

            $this->getQuery()->where($table.'.'.$this->ownerKey, '=', $this->getChildKey());
        }
    }

    public function addEagerConstraints(array $models)
    {
        $key = $this->related->getTable().'.'.$this->ownerKey;

        $this->getQuery()->whereIn($key, $this->getEagerModelKeys($models));
    }

    public function match(array $models, Collection $results, $relation)
    {
        if(!str_contains($this->foreignKey, '->'))
        {
            return parent::match($models, $results, $relation);
        }

        foreach($models as $model)
        {
            $column = explode('->', $this->foreignKey)[0];
            if(isset($model->{$column}))
            {
                $search = str_replace('->', '.', str_replace_first($column . '->', '', $this->foreignKey));
                if ($json = json_decode($model->{$column}, true))
                {
                    if (!is_object(array_get(array_dot($json), $search)) && !is_array(array_get(array_dot($json), $search)))
                    {
                        foreach($results as $result)
                        {
                            if(array_get(array_dot($json), $search) == $result->{$this->ownerKey})
                            {
                                $model->setRelation($relation, $result);
                            }
                            break;
                        }
                    }
                }
            }

        }
        return $models;
    }

    public function getEagerModelKeys(array $models)
    {
        if(!str_contains($this->foreignKey,'->'))
        {
            return parent::getEagerModelKeys($models);
        }

        $keys = [];
        foreach($models as $model)
        {
            $column = explode('->', $this->foreignKey)[0];
            if(isset($model->{$column}))
            {
                $search = str_replace('->', '.', str_replace_first($column . '->', '', $this->foreignKey));
                if($json = json_decode($model->{$column}, true))
                {
                    if(!is_object(array_get(array_dot($json),$search)) && !is_array(array_get(array_dot($json),$search)))
                    {
                        $keys[] = array_get(array_dot($json),$search);
                    }
                }
            }
        }

        return $keys;
    }
}