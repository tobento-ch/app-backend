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

use Tobento\App\Seeding\User\UserFactory;
use Tobento\App\User\Migration\RolePermissionsAction;
use Tobento\App\User\RoleRepositoryInterface;
use Tobento\Service\Migration\Action\CallableAction;
use Tobento\Service\Migration\Actions;
use Tobento\Service\Migration\ActionsInterface;
use Tobento\Service\Migration\MigrationInterface;
use Tobento\Service\Repository\Storage\Migration\RepositoryAction;
use Tobento\Service\Repository\Storage\Migration\RepositoryDeleteAction;

/**
 * UserAndRolesMigration migration
 */
class UserAndRolesMigration implements MigrationInterface
{
    /**
     * Create a new Roles instance.
     *
     * @param RoleRepositoryInterface $roleRepository
     */
    public function __construct(
        protected RoleRepositoryInterface $roleRepository,
    ) {}
    
    /**
     * Return a description of the migration.
     *
     * @return string
     */
    public function description(): string
    {
        return 'Roles Backend migration.';
    }
        
    /**
     * Return the actions to be processed on install.
     *
     * @return ActionsInterface
     */
    public function install(): ActionsInterface
    {
        return new Actions(
            RepositoryAction::newOrNull(
                repository: $this->roleRepository,
                description: 'Adding editor and registered backend roles',
                items: [
                    ['key' => 'editor', 'areas' => ['backend'], 'active' => true],
                    ['key' => 'registered', 'areas' => ['backend'], 'active' => true],
                ],
            ),
            new RolePermissionsAction(
                roleRepository: $this->roleRepository,
                add: [
                    'editor' => ['backend'],
                ],
                description: 'Adding roles permissions for editor',
            ),
            new CallableAction(
                callable: function () {
                    UserFactory::new()
                        ->withEmail('admin@example.com')
                        ->withRoleKey('administrator')
                        ->withPassword('password')
                        ->withAddress(['name' => 'Admin User'])
                        ->createOne();

                    UserFactory::new(['active' => false])
                        ->withEmail('inactive@example.com')
                        ->withRoleKey('administrator')
                        ->withPassword('password')
                        ->withAddress(['name' => 'Inactive User'])
                        ->createOne();

                    UserFactory::new()
                        ->withEmail('editor@example.com')
                        ->withSmartphone('12345678')
                        ->withRoleKey('editor')
                        ->withPassword('password')
                        ->withAddress(['name' => 'Editor User'])
                        ->createOne();

                    UserFactory::new()
                        ->withEmail('registered@example.com')
                        ->withRoleKey('registered')
                        ->withPassword('password')
                        ->withAddress(['name' => 'Registered User'])
                        ->createOne();
                },
                // you may set parameters passed to the callable:
                parameters: [],
                name: 'Users for testing',
                description: 'Adding users for testing.',
                type: 'database',
            ),
        );
    }

    /**
     * Return the actions to be processed on uninstall.
     *
     * @return ActionsInterface
     */
    public function uninstall(): ActionsInterface
    {
        return new Actions();
    }
}