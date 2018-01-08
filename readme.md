# Notate

Notate is a tool for Eloquent that allows you to work with JSON columns efficiently. Notate allows you to create complete Eloquent relationships using JSON column data, as well as converting your model JSON columns into objects automatically.

At this time, Notate currently supports HasOne, HasMany and BelongsTo relationships. JSON columns can be automatically converted into an stdClass or an instance of `Illuminate\Support\Collection` and will be converted back upon saving changes to the database.

### Installation

Run the following command in your terminal in the directory of your project.

```
composer require notate/notate
```

### Usage

Add the trait `Notate\Notate` to your Eloquent model and you will be able to reference your JSON columns as the local key when creating relationships.

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
$user = new User;

echo $user->guild->id;
$equippedItems = $user->items()->where('equipped,0)->get();
```

#### JSON Conversions

```php
use Notate\Notate;

class User extends Model
{
    use Notate;

    // columns to convert to json
    public $jsonColumns = [
        'stats'
    ];
}
```

```php
User::setJsonType('collection'); // stdClass by default at this time

$user = new User;
$user->stats->forget('last_name');
$user->stats->groupBy('first_name');
$user->stats->isEmpty();
$user->stats->random();
$user->stats->avg->votes;
```