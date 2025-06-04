<?php

/**
 * TOBENTO
 *
 * @copyright   Tobias Strub, TOBENTO
 * @license     MIT License, see LICENSE file distributed with this source code.
 * @author      Tobias Strub
 * @link        https://www.tobento.ch
 */

declare(strict_types=1);
 
namespace Tobento\App\Backend\Boot;

use Tobento\Apps\AppBoot;

/**
 * Backend App Boot
 */
class Backend extends AppBoot
{
    public const INFO = [
        'boot' => [
            'Backend App',
        ],
    ];
    
    /**
     * Specify your app boots:
     */
    protected const APP_BOOT = [
        \Tobento\App\Boot\App::class,
        \Tobento\App\Boot\ErrorHandling::class,
        \Tobento\App\User\Web\Boot\UserWeb::class,
        \Tobento\App\Media\Boot\Media::class,
        BackendMigration::class,
    ];
    
    /**
     * Set a unique app id. Must be lowercase and
     * only contain [a-z0-9-] characters.
     * Furthermore, do not set ids with two dashes such as 'foo--bar'
     * as supapps id will be separated by two dashes.
     */
    protected const APP_ID = 'backend';

    /**
     * You may set a slug for the routing e.g. example.com/slug/
     * Or you may set the slug to an empty string e.g. example.com/
     */
    protected const SLUG = 'admin';
    
    /**
     * You may set a domains for the routing e.g. ['api.example.com']
     * In addition, you may set the slug to an empty string,
     * otherwise it gets appended e.g. api.example.com/slug
     */
    protected const DOMAINS = [];
    
    /**
     * You may set a migration to be installed on booting e.g Migration::class
     */
    protected const MIGRATION = '';
}