# Notate

Notate is a tool used to convert your Eloquent JSON columns into `Illuminate\Support\Collection` instances automatically, allowing you to quickly manipulate non relational data for your Eloquent models.

Notate also allows you to use data JSON columns to create *very basic* Eloquent relationships, creating pseudo-relations via the hasOne and hasMany model methods (further support to come soon).

### Installation

```
composer require notate/notate
```

### Usage

```php
use Notate;

class User extends Model
{
    use Notate;
    
    // Will convert specified columns into Collections if contains JSON data
    public $jsonColumns = ['stats'];
    
    public function guild()
    {
        // Using JSON data to create a relationship
        return $this->hasOne('Guild', 'id', 'stats->id');
    }
}

$user = User::find(1);
// Specified columns are populated and can be interacted with instantly
$user->stats->put('guild', 1);
$user->save(); // all JSON/Collection data will be saved

// Relations
echo $user->guild->id; // Guild model ID, will return '1' if the guild exists
```