# Notate

Notate is a tool for the Laravel Eloquent ORM that offers several utilities to help you interact with JSON fields.
Notate allows you to create complete `HasOne`, `HasMany` and `BelongsTo` relationships using properties defined in a JSON field.

Notate also allows you to automatically convert selected JSON fields into a format you can work with. Unlike using accessors and mutators,
this conversion will allow you to interact with the data directly. When it is time to update the database, the field will be converted
back into a valid JSON object.

By default, any specified JSON columns will be converted into a `Collection` instance, but can be configured to be converted into an object,
array or any class that takes an array as a parameter - allowing you to parse the JSON however you like.

### Installation

Run the following command in your terminal in the directory of your Laravel project.

```
composer require notate/notate
```

### Usage

Add the `Notate\Notate` trait to your Eloquent models and you will be able to reference JSON columns on both ends when creating relationships.

#### Relationships

```php
use Notate\Notate;

class User extends Model
{
    use Notate;

    public function guild()
    {
        return $this->hasOne(Guild::class, 'id', 'stats->guild_id');
    }
    
    public function items()
    {
        return $this->hasMany(Item::class, 'data->id', 'nested->some->property');
    }
    
    public function role()
    {
        return $this->belongsTo(Role::class, 'name', 'stats->role->name');
    }
}
```

```php
$user = User::find(1);

// user->nested->some->property == item->data->id
echo $user->items->first()->data->id;

// can use the query builder as usual
$equippedItems = $user->items()->where('equipped',0)->get();

// eager loading support
$users = User::with(['items','guild'])->get();
```

#### JSON Conversions

```php
use Notate\Notate;

class User extends Model
{
    use Notate;

    // columns you wish to convert from json
    protected $json = ['stats', 'resources'];
}
```

```php
// will be a Collection by default, configure in config/notate.php
$user->resources->forget('stone');
$user->stats->groupBy('first_name');
$user->resources->isEmpty();
$user->stats->random();
$user->stats->avg->votes;
```