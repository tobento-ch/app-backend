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

use Tobento\App\Backend\Controller\RoleCrudController;
use Tobento\App\Backend\Controller\RolePermissionsController;
use Tobento\App\Boot;
use Tobento\App\Crud\Boot\Crud;
use Tobento\App\Language\RouteLocalizerInterface;
use Tobento\App\Search\Searchable;
use Tobento\App\Search\SearchablesInterface;
use Tobento\App\Search\SearchInterface;
use Tobento\App\Search\SearchResult;
use Tobento\App\Search\SearchResultInterface;
use Tobento\App\User\Boot\Acl;
use Tobento\App\User\RoleInterface;
use Tobento\App\User\RoleRepositoryInterface;
use Tobento\Service\Acl\AclInterface;
use Tobento\Service\Menu\MenusInterface;
use Tobento\Service\Routing\RouterInterface;
use Tobento\Service\Routing\RouteGroupInterface;
use function Tobento\App\Translation\{trans};

class Roles extends Boot
{
    public const INFO = [
        'boot' => [
            'Roles CRUD',
        ],
    ];

    public const BOOT = [
        Acl::class,
        Crud::class,
    ];
    
    /**
     * Boot application services.
     *
     * @param Crud $crud
     * @return void
     */
    public function boot(Crud $crud): void
    {
        // Acl:
        $acl = $this->app->get(AclInterface::class);
        $acl->rule('roles')->description('User can access roles.');
        $acl->rule('roles.create')->description('User can create roles.');
        $acl->rule('roles.edit')->description('User can edit roles.');
        $acl->rule('roles.permissions')->description('User can edit permissions roles.');
        $acl->rule('roles.delete')->description('User can delete roles.');
        
        // Routes:
        $router = $this->app->get(RouterInterface::class);
        
        $crud->routeController(
            RoleCrudController::class,
            middleware: [
                [
                    \Tobento\App\User\Middleware\VerifyRoutePermission::class,
                    'permissions' => [
                        'roles.index' => 'roles',
                        'roles.show' => 'roles',
                        'roles.create' => 'roles.create',
                        'roles.store' => 'roles.create',
                        'roles.copy' => 'roles.create',
                        'roles.edit' => 'roles.edit',
                        'roles.update' => 'roles.edit',
                        'roles.delete' => 'roles.delete',
                        'roles.bulk' => 'roles.edit|roles.delete',
                    ],
                ]
            ],
            except: ['copy'],
            localized: true,
        );
        
        $route = $router->group('', function(RouteGroupInterface $route) {
            $route->get(
                uri: '{?locale}/roles/{id}/permissions/edit',
                handler: [RolePermissionsController::class, 'edit'],
            )->name('roles.permissions.edit');
            
            $route->put(
                uri: '{?locale}/roles/{id}/permissions',
                handler: [RolePermissionsController::class, 'update'],
            )->name('roles.permissions.update');
        })->middleware(['can', 'permission' => 'roles.permissions']);
        
        $this->app->get(RouteLocalizerInterface::class)->localizeRoute($route);
        
        // Menu:
        $this->app->on(
            MenusInterface::class,
            static function(MenusInterface $menus, AclInterface $acl, RouterInterface $router) {
                if ($acl->can('roles')) {
                    $menus->menu('main')
                        ->link($router->url('roles.index'), trans('Roles'))
                        ->parent('administration')
                        ->id('roles.index');
                }
            }
        );
        
        $this->configureSearchable();
    }
    
    /**
     * Configure searchable.
     *
     * @return void
     */
    protected function configureSearchable(): void
    {
        $this->app->on(
            SearchInterface::class,
            static function(SearchInterface $search, RoleRepositoryInterface $repository, RouterInterface $router): void {
                $search->searchables()->add(searchable: new Searchable\Repository(
                    repository: $repository,
                    name: 'roles',
                    title: trans('Roles'),
                    searchAttributes: ['key', 'name'],
                    toSearchResult: function(
                        RoleInterface $role,
                        Searchable\Repository $searchable
                    ) use ($router): SearchResultInterface {
                        return new SearchResult(
                            searchable: $searchable->name(),
                            type: $searchable->title(),
                            title: $role->name(),
                            url: (string)$router->url('roles.edit', ['id' => $role->id()]),
                        );
                    },
                ));
            }
        );
    }
}