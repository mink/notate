# Notate

Notate is a tool for the Laravel Eloquent ORM that offers several utilities to help you interact with JSON fields.
Notate allows you to create complete `HasOne`, `HasMany` and `BelongsTo` relationships using properties defined in a JSON field.

### Installation

Run the following command in your terminal in the directory of your Laravel project.

```
composer require notate/notate
```

### Usage

Add the `Notate\Notate` trait to your Eloquent models, and you will be able to reference JSON columns on both ends when creating relationships.

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