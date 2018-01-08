<?php

namespace Notate;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotateHasMany extends HasMany
{
    protected function matchOneOrMany(array $models, Collection $results, $relation, $type)
    {
        $dictionary = $this->buildDictionary($results);

        foreach ($models as $model) {
            if(!str_contains($this->localKey, '->'))
            {
                if (isset($dictionary[$key = $model->getAttribute($this->localKey)])) {
                    $model->setRelation(
                        $relation, $this->getRelationValue($dictionary, $key, $type)
                    );
                }
            }
            else
            {
                $column = explode('->', $this->localKey)[0];
                if(isset($model->{$column}))
                {
                    $search = str_replace('->', '.', str_replace_first($column . '->', '', $this->localKey));
                    if($json = json_decode($model->{$column}, true))
                    {
                        if(!is_object(array_get(array_dot($json),$search)) && !is_array(array_get(array_dot($json),$search)))
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

    protected function getKeys(array $models, $key = null)
    {
        if(!str_contains($this->localKey,'->'))
        {
            return parent::getKeys($models,$key);
        }

        $keys = [];
        foreach($models as $model)
        {
            $column = explode('->', $this->localKey)[0];
            if(isset($model->{$column}))
            {
                $search = str_replace('->', '.', str_replace_first($column . '->', '', $this->localKey));
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
            return $this->parent->{$this->localKey};
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

        //dd($this->foreignKey);
        if (static::$constraints) {
            $this->query->where($this->foreignKey, '=', $this->getParentKey());
            $this->query->whereNotNull($this->foreignKey);
        }
    }

}