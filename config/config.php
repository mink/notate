<?php

return [

    /*
    |--------------------------------------------------------------------------
    | JSON Conversion Method
    |--------------------------------------------------------------------------
    |
    | The method used to automatically convert JSON into a usable format.
    | You can provide a class name that needs an array as a parameter,
    | a string of "array" or "object" to return their counterparts
    | or simply return null to opt out of this functionality.
    |
    | Supported: any fully qualified class name, "array", "object", null
    |
    */

    'method' => Illuminate\Support\Collection::class
];