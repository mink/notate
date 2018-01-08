<?php

namespace Notate;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use stdClass;

trait Notate
{
    public static $jsonType;

    public static function setJsonType($type)
    {
        switch(strtolower($type))
        {
            case "collection":
                self::$jsonType = 'collection';
                break;
            case "object":
            case "stdclass":
            default:
                self::$jsonType = 'object';
                break;
            case "array":
                self::$jsonType = 'array';
                break;
        }
    }

    public function newFromBuilder($attributes = [], $connection = null)
    {
        $model = parent::newFromBuilder($attributes, $connection);
        $model->convertJson();
        return $model;
    }

    protected function performUpdate(Builder $query)
    {
        $this->convertJson();
        parent::performUpdate($query);
        $this->convertJson();
        return true;
    }

    protected function performInsert(Builder $query)
    {
        $this->convertJson();
        parent::performInsert($query);
        $this->convertJson();
        return true;
    }

    protected function convertJson(Model $model = null)
    {
        if(!$model) { $model = $this; }
        foreach ($model->jsonColumns as $column)
        {
            if($model->{$column})
            {
                if(is_string($model->{$column}))
                {
                    switch(self::$jsonType)
                    {
                        case "object":
                        default:
                        case null:
                            $model->{$column} = json_decode($this->{$column});
                            break;
                        case "array":
                        case "collection":
                            $model->{$column} = collect(json_decode($this->{$column},true));
                            break;
                    }
                    continue;
                }
                elseif($model->{$column} instanceof Collection)
                {
                    $model->{$column} = $this->{$column}->toJson();
                }
                elseif($model->{$column} instanceof stdClass)
                {
                    $model->{$column} = json_encode($this->{$column});
                    continue;
                }
            }
        }
        return $model;
    }

    public function hasOne($related, $foreignKey = null, $localKey = null)
    {
        $instance = $this->newRelatedInstance($related);

        $foreignKey = $foreignKey ?: $this->getForeignKey();

        $localKey = $localKey ?: $this->getKeyName();
        return new NotateHasOne($instance->newQuery(), $this, $instance->getTable() . '.' . $foreignKey, $localKey);
    }

    public function hasMany($related, $foreignKey = null, $localKey = null)
    {
        $instance = $this->newRelatedInstance($related);

        $foreignKey = $foreignKey ?: $this->getForeignKey();

        $localKey = $localKey ?: $this->getKeyName();

        return new NotateHasMany(
            $instance->newQuery(), $this, $instance->getTable() . '.' . $foreignKey, $localKey
        );
    }
    public function belongsTo($related, $foreignKey = null, $ownerKey = null, $relation = null)
    {
        if (is_null($relation)) {
            $relation = $this->guessBelongsToRelation();
        }

        $instance = $this->newRelatedInstance($related);

        if (is_null($foreignKey)) {
            $foreignKey = Str::snake($relation).'_'.$instance->getKeyName();
        }

        $ownerKey = $ownerKey ?: $instance->getKeyName();

        return new NotateBelongsTo(
            $instance->newQuery(), $this, $foreignKey, $ownerKey, $relation
        );

    }
}