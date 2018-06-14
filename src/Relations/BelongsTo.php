<?php

namespace Notate\Relations;

use Illuminate\Database\Eloquent\{Builder,Collection,Model,Relations};

class BelongsTo extends Relations\BelongsTo
{
    /**
     * Creates a BelongsTo instance from Notate.
     *
     * @param Builder $query
     * @param Model $child
     * @param $foreignKey
     * @param $ownerKey
     * @param $relation
     */
    public function __construct(Builder $query, Model $child, $foreignKey, $ownerKey, $relation)
    {
        parent::__construct($query, $child, $foreignKey, $ownerKey, $relation);
    }

    /**
     * Get the model's column result for the query where clause.
     *
     * @return mixed
     */
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

    /**
     * Add constraints to the query to find relation matches.
     */
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

    /**
     * Add constraints to the query in an eager loading context.
     *
     * @param array $models
     */
    public function addEagerConstraints(array $models)
    {
        $key = $this->related->getTable().'.'.$this->ownerKey;

        $this->getQuery()->whereIn($key, $this->getEagerModelKeys($models));
    }

    /**
     * Returns the models that match the relation constraints.
     *
     * @param array $models
     * @param Collection $results
     * @param string $relation
     * @return array
     */
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

    /**
     * Gather the keys from an array of related models.
     *
     * @param array $models
     * @return array
     */
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