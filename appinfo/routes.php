<?php

return [
    'routes' => [
        ['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
    ],
    'ocs' => [
        ['name' => 'list#index',   'url' => '/api/v1/lists',          'verb' => 'GET'],
        ['name' => 'list#create',  'url' => '/api/v1/lists',          'verb' => 'POST'],
        ['name' => 'list#reorder', 'url' => '/api/v1/lists/reorder',  'verb' => 'POST'],
        ['name' => 'list#duplicate', 'url' => '/api/v1/lists/{id}/duplicate', 'verb' => 'POST'],
        ['name' => 'list#show',    'url' => '/api/v1/lists/{id}',     'verb' => 'GET'],
        ['name' => 'list#update',  'url' => '/api/v1/lists/{id}',     'verb' => 'PUT'],
        ['name' => 'list#destroy', 'url' => '/api/v1/lists/{id}',     'verb' => 'DELETE'],

        ['name' => 'item#suggest', 'url' => '/api/v1/lists/{listId}/items/suggest',     'verb' => 'GET'],
        ['name' => 'item#index',   'url' => '/api/v1/lists/{listId}/items',            'verb' => 'GET'],
        ['name' => 'item#create',  'url' => '/api/v1/lists/{listId}/items',            'verb' => 'POST'],
        ['name' => 'item#update',  'url' => '/api/v1/lists/{listId}/items/{id}',       'verb' => 'PUT'],
        ['name' => 'item#toggle',  'url' => '/api/v1/lists/{listId}/items/{id}/toggle','verb' => 'POST'],
        ['name' => 'item#destroy', 'url' => '/api/v1/lists/{listId}/items/{id}',       'verb' => 'DELETE'],

        ['name' => 'user#search_users',  'url' => '/api/v1/users/search',  'verb' => 'GET'],
        ['name' => 'user#search_groups', 'url' => '/api/v1/groups/search', 'verb' => 'GET'],

        ['name' => 'share#index',   'url' => '/api/v1/lists/{listId}/shares',      'verb' => 'GET'],
        ['name' => 'share#create',  'url' => '/api/v1/lists/{listId}/shares',      'verb' => 'POST'],
        ['name' => 'share#update',  'url' => '/api/v1/lists/{listId}/shares/{id}', 'verb' => 'PUT'],
        ['name' => 'share#destroy', 'url' => '/api/v1/lists/{listId}/shares/{id}', 'verb' => 'DELETE'],

        ['name' => 'category#index',   'url' => '/api/v1/lists/{listId}/categories',         'verb' => 'GET'],
        ['name' => 'category#create',  'url' => '/api/v1/lists/{listId}/categories',         'verb' => 'POST'],
        ['name' => 'category#reorder', 'url' => '/api/v1/lists/{listId}/categories/reorder', 'verb' => 'POST'],
        ['name' => 'category#update',  'url' => '/api/v1/lists/{listId}/categories/{id}',    'verb' => 'PUT'],
        ['name' => 'category#destroy', 'url' => '/api/v1/lists/{listId}/categories/{id}',    'verb' => 'DELETE'],
    ],
];
