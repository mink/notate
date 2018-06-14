<?php

namespace Notate\Relations;

use Illuminate\Database\Eloquent\Relations;

class HasOne extends Relations\HasOne
{
    use HasOneOrMany;

}