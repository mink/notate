<?php

namespace Notate;
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
}