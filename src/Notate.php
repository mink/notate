<?php

namespace Notate;

use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Notate\Relations\{HasOne,HasMany,BelongsTo};

trait Notate
{
    /**
     * Return a Notate HasOne instance.
     *
     * @param $related
     * @param null $foreignKey
     * @param null $localKey
     * @return HasOne
     */
    public function hasOne($related, $foreignKey = null, $localKey = null): HasOne
    {
        return $this->hasOneOrMany($related, $foreignKey, $localKey, HasOne::class);
    }

    /**
     * Return a Notate HasMany instance.
     *
     * @param $related
     * @param null $foreignKey
     * @param null $localKey
     * @return HasMany
     */
    public function hasMany($related, $foreignKey = null, $localKey = null): HasMany
    {
        return $this->hasOneOrMany($related, $foreignKey, $localKey, HasMany::class);
    }

    /**
     * Return either a Notate HasOne or HasMany instance.
     *
     * @param $related
     * @param null $foreignKey
     * @param null $localKey
     * @param string $class
     * @return mixed
     */
    private function hasOneOrMany($related, $foreignKey = null, $localKey = null, $class = HasOne::class): HasOneOrMany
    {
        $instance = $this->newRelatedInstance($related);
        $foreignKey = $foreignKey ?? $this->getForeignKey();
        $localKey = $localKey ?? $this->getKeyName();
        return new $class($instance->newQuery(), $this, $instance->getTable() . '.' . $foreignKey, $localKey);
    }

    /**
     * Return a BelongsTo instance.
     *
     * @param $related
     * @param null $foreignKey
     * @param null $ownerKey
     * @param null $relation
     * @return BelongsTo
     */
    public function belongsTo($related, $foreignKey = null, $ownerKey = null, $relation = null)
    {
        $instance = $this->newRelatedInstance($related);
        $foreignKey = $foreignKey ?? snake_case($relation).'_'.$instance->getKeyName();
        $ownerKey = $ownerKey ?? $instance->getKeyName();
        $relation = $relation ?? $this->guessBelongsToRelation();
        return new BelongsTo($instance->newQuery(), $this, $foreignKey, $ownerKey, $relation);
    }

    /**
     * Create a new model instance that is existing.
     *
     * @param array $attributes
     * @param null $connection
     * @return mixed
     */
    public function newFromBuilder($attributes = [], $connection = null)
    {
        $model = parent::newFromBuilder($attributes, $connection);
        $model->convertJson();
        return $model;
    }

    /**
     * Perform a model update operation.
     *
     * @param Builder $query
     * @return bool
     */
    protected function performUpdate(Builder $query)
    {
        $this->convertJson();
        parent::performUpdate($query);
        $this->convertJson();
        return true;
    }

    /**
     * Perform a model insert operation.
     *
     * @param Builder $query
     * @return bool
     */
    protected function performInsert(Builder $query)
    {
        $this->convertJson();
        parent::performInsert($query);
        $this->convertJson();
        return true;
    }

    /**
     * Convert a model's JSON columns into a specified data type.
     *
     * @param Model|null $model
     * @return Model|Notate
     */
    protected function convertJson(Model $model = null)
    {
        if(!$model) { $model = $this; }
        $class = Config::getClass();
        if($class)
        {
            foreach($model->attributes as $key => $value)
            {
                if(is_string($value))
                {
                    if($this->isJson($value))
                    {
                        $this->{$key} = new $class(json_decode($value, true));
                    }
                } else {
                    if($value instanceof Jsonable)
                    {
                        $this->{$key} = $this->{$key}->toJson();
                    }
                    /*elseif(is_array($value) || is_object($value))
                    {
                        $this->{$key} = json_encode($value);
                    }*/
                }
            }
        }
        return $model;
    }

    /**
     * Determines if a sequence is JSON.
     *
     * @param $str
     * @return bool
     */
    private function isJson($str): bool
    {
        if(!is_string($str))
        {
            return false;
        }
        $json = json_decode($str);
        return $json && $str != $json;
    }
}