<?php

namespace Notate\Relations;

use Illuminate\Database\Eloquent\Relations;

class HasMany extends Relations\HasMany
{
    use HasOneOrMany;
}