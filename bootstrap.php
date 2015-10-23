<?php

// Configure autoloader
$f3->set('AUTOLOAD', array('app/; vendor/;', function($class){
    $match = [];
    preg_match_all("/(.*)\\/(.*)$/", $class, $match, PREG_SET_ORDER);
    
    if ( isset($match[0][1]) && isset($match[0][2]) )
        return strtolower($match[0][1]) . '/' . $match[0][2];
    else
        return $class;
}));

// Admin panel routes
$f3->route('GET|POST /mytcg/@controller/@action/@id','Filler00\Router->routeAdmin');

$f3->route('GET|POST /mytcg/@controller/@action', 'Filler00\Router->routeAdmin');

$f3->route('GET|POST /mytcg/@controller', 'Filler00\Router->routeAdmin');

$f3->route('GET|POST /mytcg', 'Filler00\Router->routeAdmin');

// Front end routes
$f3->route('GET|POST /@controller/@action/@id','Filler00\Router->route');

$f3->route('GET|POST /@controller/@action', 'Filler00\Router->route');

$f3->route('GET|POST /@controller', 'Filler00\Router->route');

$f3->route('GET|POST /', 'Filler00\Router->route');