<?php

return [
    'routes' => [
        ['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
    ],
    'ocs' => [
        ['name' => 'list#index',   'url' => '/api/v1/lists',     'verb' => 'GET'],
        ['name' => 'list#create',  'url' => '/api/v1/lists',     'verb' => 'POST'],
        ['name' => 'list#show',    'url' => '/api/v1/lists/{id}', 'verb' => 'GET'],
        ['name' => 'list#update',  'url' => '/api/v1/lists/{id}', 'verb' => 'PUT'],
        ['name' => 'list#destroy', 'url' => '/api/v1/lists/{id}', 'verb' => 'DELETE'],
    ],
];
