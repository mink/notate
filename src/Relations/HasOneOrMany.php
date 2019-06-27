<?php

namespace Notate\Relations;

use Illuminate\Database\Eloquent\{Builder,Collection,Model};
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

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
        if(!$this->isKeyJsonSearch($this->localKey))
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

                    // futureproof for JSON conversion support
                    $json = ($this->isJson($model->{$column})) ? json_decode($model->{$column}, true) : $model->{$column};

                    if($this->isKeySearchable($json, $search))
                    {
                        $model->setRelation(
                            $relation, $this->getRelationValue($dictionary, Arr::get(Arr::dot($json),$search), $type)
                        );
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

            // futureproof for JSON conversion support
            $json = ($this->isJson($this->parent->{$column})) ? json_decode($this->parent->{$column}, true) : $this->parent->{$column};

            if($this->isKeySearchable($json, $search))
            {
                return Arr::get(Arr::dot($json),$search);
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