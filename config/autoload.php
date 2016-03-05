<?php

// Autoload plugin classes
spl_autoload_register(function($class) {
    $posibleLocations = [
        __DIR__ . '/../' . '/classes/models',
        __DIR__ . '/../' . '/classes/utils',
        __DIR__ . '/../' . '/classes/controllers',
        __DIR__ . '/../' . '/schedule',
        __DIR__ . '/../' . '/classes/services',
        __DIR__ . '/../' . '/classes/wp'
    ];
    foreach ($posibleLocations as $location) {
        if (file_exists($location . '/' . $class . '.php')) {
            require_once $location . '/' . $class . '.php';
            return true;
        }
    }
});