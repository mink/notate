<?php

namespace Notate\Relations;

use Illuminate\Database\Eloquent\{Builder,Collection,Model,Relations};
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

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
        if(!$this->isKeyJsonSearch($this->foreignKey))
        {
            return $this->child->{$this->foreignKey};
        }

        $column = $this->getColumnFromKey($this->foreignKey);

        if(isset($this->child->{$column}))
        {
            $search = $this->createSearchString($column, $this->foreignKey);

            // futureproof for JSON conversion support
            $json = ($this->isJson($this->child->{$column})) ? json_decode($this->child->{$column}, true) : $this->child->{$column};

            if($this->isKeySearchable($json, $search))
            {
                return Arr::get(Arr::dot($json),$search);
            }
        }
    }

    /**
     * Add constraints to the query to find relation matches.
     */
    public function addConstraints()
    {
        if(!$this->isKeyJsonSearch($this->foreignKey))
        {
            return parent::addConstraints();
        }

        if(static::$constraints) {
            $table = $this->related->getTable();

            $this->getQuery()->where($table.'.'.$this->ownerKey, '=', $this->getChildKey());
        }
    }

    /**
     * Add constraints to the query in an eager loading context.
     *
     * @param array $models
     */
    public function addEagerConstraints(array $models): void
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
    public function match(array $models, Collection $results, $relation): array
    {
        if(!$this->isKeyJsonSearch($this->foreignKey))
        {
            return parent::match($models, $results, $relation);
        }

        foreach($models as $model)
        {
            $column = $this->getColumnFromKey($this->foreignKey);
            if(isset($model->{$column}))
            {
                $search = $this->createSearchString($column, $this->foreignKey);

                // futureproof for JSON conversion support
                $json = ($this->isJson($model->{$column})) ? json_decode($model->{$column}, true) : $model->{$column};

                if($this->isKeySearchable($json, $search))
                {
                    foreach($results as $result)
                    {
                        if(Arr::get(Arr::dot($json), $search) == $result->{$this->ownerKey})
                        {
                            $model->setRelation($relation, $result);
                        }
                        break;
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
    public function getEagerModelKeys(array $models): array
    {
        if(!$this->isKeyJsonSearch($this->foreignKey))
        {
            return parent::getEagerModelKeys($models);
        }

        $keys = [];
        foreach($models as $model)
        {
            $column = $this->getColumnFromKey($this->foreignKey);
            if(isset($model->{$column}))
            {
                $search = $this->createSearchString($column, $this->foreignKey);

                // futureproof for JSON conversion support
                $json = ($this->isJson($model->{$column})) ? json_decode($model->{$column}, true) : $model->{$column};

                if($this->isKeySearchable($json, $search))
                {
                    $keys[] = Arr::get(Arr::dot($json),$search);
                }

            }
        }

        return $keys;
    }

    /**
     * Get the column to lookup from the key.
     *
     * @param $key
     * @return string
     */
    private function getColumnFromKey($key): string
    {
        return explode('->', $key)[0];
    }

    /**
     * Determines if the key suggests a JSON field.
     *
     * @param $key
     * @return bool
     */
    private function isKeyJsonSearch($key): bool
    {
        return Str::contains($key, '->');
    }

    /**
     * Check if the key can be used in a query.
     *
     * @param $json
     * @param $search
     * @return bool
     */
    private function isKeySearchable($json, $search): bool
    {
        return !is_object(Arr::get(Arr::dot($json),$search)) && !is_array(Arr::get(Arr::dot($json),$search));
    }

    /**
     * Create a string used to search the JSON.
     *
     * @param $column
     * @param $key
     * @return string
     */
    private function createSearchString($column, $key): string
    {
        return str_replace('->', '.', Str::replaceFirst($column . '->', '', $key));
    }

    /**
     * Determines if a sequence is JSON.
     *
     * @param $str
     * @return bool
     */
    private function isJson($str): bool
    {
        return is_string($str) && (json_decode($str) && $str != json_decode($str));
    }
}