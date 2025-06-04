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
 
namespace Tobento\App\Backend\Testing;

use Tobento\App\Boot;
use Tobento\App\Migration\Boot\Migration;

class UserAndRolesBoot extends Boot
{
    public const INFO = [
        'boot' => [
            'User and roles migration',
        ],
    ];

    public const BOOT = [
        Migration::class,
    ];
    
    /**
     * Boot application services.
     *
     * @param Migration $migration
     * @return void
     */
    public function boot(Migration $migration): void
    {
        $migration->install(UserAndRolesMigration::class);
    }
}