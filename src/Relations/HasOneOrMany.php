<?php

namespace Notate\Relations;

use Illuminate\Database\Eloquent\{Builder,Collection,Model,Relations\Relation};

trait HasOneOrMany
{
    /**
     * Create a HasOne or HasMany instance from Notate.
     *
     * @param Builder $query
     * @param Model $parent
     * @param $foreignKey
     * @param $localKey
     */
    public function __construct(Builder $query, Model $parent, $foreignKey, $localKey)
    {
        if($this->isRelation())
        {
            $this->localKey = $localKey;
            $this->foreignKey = $foreignKey;
            $this->query = $query;
            $this->parent = $parent;
            $this->related = $query->getModel();
            $this->addConstraints();
        }
    }

    /**
     * Determine if the class is a Relation instance.
     *
     * @return bool
     */
    private function isRelation(): bool
    {
        return $this instanceof Relation;
    }

    /**
     * Get the keys used to match against relations.
     *
     * @param array $models
     * @param null $key
     * @return array
     */
    protected function getKeys(array $models, $key = null): array
    {
        if(!str_contains($this->localKey,'->'))
        {
            return parent::getKeys($models,$key);
        }

        $keys = [];
        foreach($models as $model)
        {
            $column = $this->getColumnFromKey($this->localKey);
            if(isset($model->{$column}))
            {
                $search = $this->createSearchString($column, $this->localKey);
                if($json = json_decode($model->{$column}, true))
                {
                    if($this->isKeySearchable($json, $search))
                    {
                        $keys[] = array_get(array_dot($json),$search);
                    }
                }
            }
        }

        return $keys;
    }

    /**
     * Returns the models that match the relation constraints.
     *
     * @param array $models
     * @param Collection $results
     * @param string $relation
     * @param string $type
     * @return array
     */
    protected function matchOneOrMany(array $models, Collection $results, $relation, $type): array
    {
        $dictionary = $this->buildDictionary($results);

        foreach ($models as $model) {
            if(!$this->isKeyJsonSearch($this->localKey))
            {
                // parent behaviour
                if (isset($dictionary[$key = $model->getAttribute($this->localKey)])) {
                    $model->setRelation(
                        $relation, $this->getRelationValue($dictionary, $key, $type)
                    );
                }
            }
            else
            {
                $column = $this->getColumnFromKey($this->localKey);
                if(isset($model->{$column}))
                {
                    $search = $this->createSearchString($column, $this->localKey);
                    if($json = json_decode($model->{$column}, true))
                    {
                        if($this->isKeySearchable($json, $search))
                        {
                            $model->setRelation(
                                $relation, $this->getRelationValue($dictionary, array_get(array_dot($json),$search), $type)
                            );
                        }
                    }
                }
            }
        }

        return $models;
    }

    /**
     * Get the model's column result for the query where clause.
     *
     * @return mixed
     */
    public function getParentKey()
    {
        if(!$this->isKeyJsonSearch($this->localKey))
        {
            return $this->parent->{$this->localKey};
        }

        $column = $this->getColumnFromKey($this->localKey);

        if(isset($this->parent->{$column}))
        {
            $search = $this->createSearchString($column, $this->localKey);
            if($json = json_decode($this->parent->{$column}, true))
            {
                if($this->isKeySearchable($json, $search))
                {
                    return array_get(array_dot($json),$search);
                }
            }
        }
    }

    /**
     * Add constraints to the query to find relation matches.
     */
    public function addConstraints(): void
    {
        if (static::$constraints) {
            $this->query->where($this->foreignKey, '=', $this->getParentKey());
            $this->query->whereNotNull($this->foreignKey);
        }
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
        return str_contains($key, '->');
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
        return !is_object(array_get(array_dot($json),$search)) && !is_array(array_get(array_dot($json),$search));
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
        return str_replace('->', '.', str_replace_first($column . '->', '', $key));
    }
}