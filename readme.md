# Notate

Notate is a tool for Eloquent that allows you to work with JSON columns efficiently. Notate allows you to create complete `HasOne`, `HasMany` and `BelongsTo` relationships using the properties of your column's JSON data.

Notate also allows you to automatically convert JSON columns of your models into a format you can work with, and conver it back into JSON when you need to update the database. By default, any specified JSON columns will be converted into a `Collection` instance, but can be configured to be convert into an object, an array or any class that takes an array as a parameter.

### Installation

Run the following command in your terminal in the directory of your project.

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
        return $this->hasOne('Guild', 'id', 'stats->guild_id');
    }
    
    public function items()
    {
        return $this->hasMany('Item', 'data->id', 'nested->some->property');
    }
    
    public function role()
    {
        return $this->belongsTo('Role', 'name', 'stats->role->name');
    }
}
```

```php
$user = User::find(1);

echo $user->guild->id;

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