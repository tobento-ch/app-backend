<?php

/**
 * TOBENTO
 *
 * @copyright   Tobias Strub, TOBENTO
 * @license     MIT License, see LICENSE file distributed with this source code.
 * @author      Tobias Strub
 * @link        https://www.tobento.ch
 */

return [
    
    /*
    |--------------------------------------------------------------------------
    | Middlewares
    |--------------------------------------------------------------------------
    |
    | These middlewares are applied to all routes and requests.
    |
    */
    
    'middlewares' => [
        // priority => middleware
        8000 => \Tobento\App\Http\Middleware\SecurePolicyHeaders::class,
        
        // important set after pre routing for route name.
        4501 => \Tobento\App\Backend\Middleware\LoginIfNotAuthenticated::class,
        
        4500 => [
            \Tobento\App\Backend\Middleware\VerifyBackendPermission::class,
        ],
    ],
    
    
    /*
    |--------------------------------------------------------------------------
    | Middleware Groups
    |--------------------------------------------------------------------------
    |
    | You may define middleware groups.
    |
    */
    
    'groups' => [
        /*'name' => [
            \App\SomeMiddleware::class,
        ],*/
    ],

    /*
    |--------------------------------------------------------------------------
    | Middleware Aliases
    |--------------------------------------------------------------------------
    |
    | The middleware aliases.
    |
    */
    
    'aliases' => [
        //'alias' => \App\SomeMiddleware::class,
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Replace Middlewares
    |--------------------------------------------------------------------------
    |
    | You may replace any middleware with another or remove it at all.
    |
    */
    
    'replace' => [
        //SomeMiddleware::class => ReplaceMiddleware::class,
        
        // by class instance:
        //SomeMiddleware::class => new ReplaceMiddleware(),
        
        // with build-in parameters:
        //SomeMiddleware::class => [ReplaceMiddleware::class, 'name' => 'Sam'],
        
        // or remove it at all:
        //AnotherMiddleware::class => null,
    ],
];