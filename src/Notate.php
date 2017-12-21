<?php

namespace Notate;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

trait Notate
{
    public function newFromBuilder($attributes = [], $connection = null)
    {
        $model = parent::newFromBuilder($attributes, $connection);
        $model = $this->jsonToCollection($model);
        return $model;
    }

    protected function jsonToCollection(Model $model = null)
    {
        if (!$model) { $model = $this; }
        foreach ($model->jsonColumns as $column)
        {
            if($model->{$column})
            {
                $model->{$column} = new Collection(json_decode($model->{$column}));
            }
        }
        return $model;
    }

    protected function collectionToJson(Model $model = null)
    {
        if (!$model) { $model = $this; }
        foreach ($model->jsonColumns as $column)
        {
            if($model->{$column} instanceof Collection)
            {
                $model->{$column} = $model->{$column}->toJson();
            }
        }
        return $model;
    }

    protected function performUpdate(Builder $query)
    {
        $this->collectionToJson($this);
        parent::performUpdate($query);
        $this->jsonToCollection($this);
        return true;
    }

    protected function performInsert(Builder $query)
    {
        $this->collectionToJson($this);
        parent::performUpdate($query);
        $this->jsonToCollection($this);
        return true;
    }

    public function hasOne($related, $foreignKey = null, $localKey = null)
    {
        if(!str_contains($localKey, '->'))
        {
            return parent::hasOne($related, $foreignKey, $localKey);
        }

        $column = explode('->', $localKey)[0];
        $search = str_replace('->', '.', str_replace_first($column . '->', '', $localKey));
        $property = debug_backtrace()[1]['function'];

        if(empty($this->appends[$property])) { $this->appends[] = $property; }

        if(array_has(json_decode($this->{$column}->toJson(), true), $search))
        {
            $this->setAttribute($property, with(new Collection($related::where($foreignKey, '=', array_get(json_decode($this->{$column}->toJson(), true), $search))->get()))->first());
            return $this->getAttribute($property);
        }
        else
        {
            $this->setAttribute($property, new Collection());
            return $this->getAttribute($property);
        }
    }

    public function hasMany($related, $foreignKey = null, $localKey = null)
    {
        if(!str_contains($localKey, '->'))
        {
            return parent::hasOne($related, $foreignKey, $localKey);
        }

        $column = explode('->', $localKey)[0];
        $search = str_replace('->', '.', str_replace_first($column . '->', '', $localKey));
        $property = debug_backtrace()[1]['function'];

        if(empty($this->appends[$property])) { $this->appends[] = $property; }

        if(array_has(json_decode($this->{$column}->toJson(), true), $search))
        {
            $this->setAttribute($property, new Collection($related::where($foreignKey, '=', array_get(json_decode($this->{$column}->toJson(), true), $search))->get()));
            return $this->getAttribute($property);
        }
        else
        {
            $this->setAttribute($property, new Collection());
            return $this->getAttribute($property);
        }
    }

    public function __get($key)
    {
        if(method_exists($this, $key))
        {
            return $this->{$key}();
        }
        return $this->getAttribute($key);
    }

}