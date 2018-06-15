<?php

namespace Notate;

class Config
{
    private static $class;

    public static function getClass()
    {
        return self::$class;
    }
    
    public static function setClass($class): void
    {
        // will add stdClass/array support later
        if(class_exists($class))
        {
            self::$class = $class;
        }
    }
}