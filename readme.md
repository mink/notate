# Notate

Notate is a tool that allows you to create complete Eloquent relationships using JSON column data. At this time, Notate currently supports HasOne, HasMany and BelongsTo relationships.

### Installation

Run the following command in your terminal in the directory of your project.

```
composer require notate/notate
```

### Usage

Add the trait `Notate\Notate` to your Eloquent model and you will be able to reference your JSON columns as the local key when creating relationships.


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