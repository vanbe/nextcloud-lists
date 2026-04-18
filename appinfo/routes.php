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

        ['name' => 'item#suggest', 'url' => '/api/v1/lists/{listId}/items/suggest',     'verb' => 'GET'],
        ['name' => 'item#index',   'url' => '/api/v1/lists/{listId}/items',            'verb' => 'GET'],
        ['name' => 'item#create',  'url' => '/api/v1/lists/{listId}/items',            'verb' => 'POST'],
        ['name' => 'item#update',  'url' => '/api/v1/lists/{listId}/items/{id}',       'verb' => 'PUT'],
        ['name' => 'item#toggle',  'url' => '/api/v1/lists/{listId}/items/{id}/toggle','verb' => 'POST'],
        ['name' => 'item#destroy', 'url' => '/api/v1/lists/{listId}/items/{id}',       'verb' => 'DELETE'],

        ['name' => 'share#index',   'url' => '/api/v1/lists/{listId}/shares',      'verb' => 'GET'],
        ['name' => 'share#create',  'url' => '/api/v1/lists/{listId}/shares',      'verb' => 'POST'],
        ['name' => 'share#update',  'url' => '/api/v1/lists/{listId}/shares/{id}', 'verb' => 'PUT'],
        ['name' => 'share#destroy', 'url' => '/api/v1/lists/{listId}/shares/{id}', 'verb' => 'DELETE'],
    ],
];
