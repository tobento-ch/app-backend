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

namespace Tobento\App\Backend\Migration;

use Tobento\App\User\Migration\RolePermissionsAction;
use Tobento\App\User\RoleRepositoryInterface;
use Tobento\Service\Dir\DirsInterface;
use Tobento\Service\Migration\Action\DirCopy;
use Tobento\Service\Migration\Action\DirDelete;
use Tobento\Service\Migration\Actions;
use Tobento\Service\Migration\ActionsInterface;
use Tobento\Service\Migration\Action\FilesCopy;
use Tobento\Service\Migration\Action\FilesDelete;
use Tobento\Service\Migration\Action\FileStringReplacer;
use Tobento\Service\Migration\MigrationInterface;
use Tobento\Service\Repository\Storage\Migration\RepositoryAction;
use Tobento\Service\Repository\Storage\Migration\RepositoryDeleteAction;

/**
 * Backend migration
 */
class Backend implements MigrationInterface
{
    protected array $configFiles;
    
    protected array $viewFiles;
    
    protected array $assetFiles;
    
    protected array $transFiles;
    
    /**
     * Create a new Backend instance.
     *
     * @param DirsInterface $dirs
     */
    public function __construct(
        protected DirsInterface $dirs,
        protected RoleRepositoryInterface $roleRepository,
    ) {
        $resources = realpath(__DIR__.'/../../').'/resources/';
        
        $this->configFiles = [
            $this->dirs->get('config') => [
                $resources.'config/app.php',
                $resources.'config/middleware.php',
                $resources.'config/user.php',
                $resources.'config/user_web.php',
            ],
        ];
        
        $this->viewFiles = [
            $this->dirs->get('views').'exception/' => [
                $resources.'views/exception/error.php',
            ],
            $this->dirs->get('views').'icons/' => [
                $resources.'views/icons/apps.svg',
                $resources.'views/icons/language.svg',
                $resources.'views/icons/menu.svg',
                $resources.'views/icons/moon.svg',
                $resources.'views/icons/sun-moon.svg',
                $resources.'views/icons/sun.svg',
                $resources.'views/icons/user-circle.svg',
            ],
            $this->dirs->get('views').'inc/' => [
                $resources.'views/inc/footer.php',
                $resources.'views/inc/header.php',
                $resources.'views/inc/nav.php',
            ],
            $this->dirs->get('views') => [
                $resources.'views/dashboard.php',
            ],
            $this->dirs->get('views').'user/' => [
                $resources.'views/user/login.php',
                $resources.'views/user/register.php',
                $resources.'views/user/twofactor-code.php',
            ],
            $this->dirs->get('views').'user/permissions/' => [
                $resources.'views/user/permissions/edit.php',
            ],
        ];
        
        $this->assetFiles = [
            $this->dirs->get('public').'assets/css/' => [
                $resources.'/css/app.css',
            ],
        ];
        
        $this->transFiles = [
            $this->dirs->get('trans').'en/' => [
                $resources.'trans/en/en-backend.json',
            ],
            $this->dirs->get('trans').'de/' => [
                $resources.'trans/de/de-backend.json',
            ],
        ];
    }
    
    /**
     * Return a description of the migration.
     *
     * @return string
     */
    public function description(): string
    {
        return 'Backend migration.';
    }
        
    /**
     * Return the actions to be processed on install.
     *
     * @return ActionsInterface
     */
    public function install(): ActionsInterface
    {
        $resources = realpath(__DIR__.'/../../').'/resources/';
        
        return new Actions(
            new FilesCopy(
                files: $this->configFiles,
                type: 'config',
                description: 'Backend config files.',
            ),
            new FilesCopy(
                files: $this->viewFiles,
                type: 'views',
                description: 'Backend view files.',
            ),
            new FilesCopy(
                files: $this->assetFiles,
                type: 'assets',
                description: 'Backend asset files.',
            ),
            new FilesCopy(
                files: $this->transFiles,
                type: 'trans',
                description: 'Backend translation files.',
            ),
            new FileStringReplacer(
                file: $this->dirs->get('config').'user_web.php',
                replace: [
                    '{verificator_hash_key}' => base64_encode(random_bytes(32)),
                ],
                description: 'verificator_hash_key generation.',
                type: 'config',
            ),            
            RepositoryAction::newOrNull(
                repository: $this->roleRepository,
                description: 'Adding default backend roles',
                items: [
                    ['key' => 'administrator', 'areas' => ['backend'], 'active' => true],
                ],
                createItems: function(RoleRepositoryInterface $repo): bool {
                    return is_null($repo->findByKey(key: 'administrator'));
                },
            ),
            new RolePermissionsAction(
                roleRepository: $this->roleRepository,
                add: [
                    'administrator' => [
                        'backend',
                        'users', 'users.create', 'users.edit', 'users.delete', 'users.permissions', 'users.role',
                        'roles', 'roles.create', 'roles.edit', 'roles.delete', 'roles.permissions',
                    ],
                ],
                description: 'Roles permissions added for administrator',
            ),
            new DirCopy(
                dir: $resources.'views/roles/',
                destDir: $this->dirs->get('views').'roles/',
                name: 'Role views',
                type: 'views',
                description: 'Role views.',
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
        return new Actions(
            new FilesDelete(
                files: $this->configFiles,
                type: 'config',
                description: 'Backend config files.',
            ),
            new FilesDelete(
                files: $this->transFiles,
                type: 'trans',
                description: 'Backend translation files.',
            ),
            new DirDelete(
                dir: $this->dirs->get('views').'roles/',
                name: 'Role views',
                type: 'views',
                description: 'Role views.',
            ),
        );
    }
}