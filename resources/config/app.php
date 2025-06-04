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
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | Possible application environments:
    | production, development, local or any custom named.
    |
    */
    
    'environment' => 'local',
    
    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, 
    | detailed error messages will be shown on every error that occurs.
    | If disabled, a simple generic error page is shown.
    |
    */

    'debug' => true,

    /*
    |--------------------------------------------------------------------------
    | Application Timezone and Locale
    |--------------------------------------------------------------------------
    |
    | The application timezone and locale.
    |
    */
        
    'timezone' => 'Europe/Berlin',
    
    'locale' => 'de-DE',
    
    /*
    |--------------------------------------------------------------------------
    | Application Boots
    |--------------------------------------------------------------------------
    |
    | The application boots.
    |
    */
        
    'boots' => [
        // Adds backend ACL rule and backend menu items:
        \Tobento\App\Backend\Boot\BackendWeb::class,
        
        // Apps support:
        \Tobento\App\Backend\Boot\Apps::class,
                
        // Resources:
        \Tobento\App\Backend\Boot\Roles::class,
        \Tobento\App\Backend\Boot\Users::class,
        
        // Misc:
        \Tobento\App\Backend\Boot\Dashboard::class,
        \Tobento\App\Backend\Boot\ColorScheme::class,
        \Tobento\App\Search\Boot\Search::class,
        
        // You may uncomment it after first user is created:
        \Tobento\App\Backend\Boot\CreateFirstUser::class,
    ],
];