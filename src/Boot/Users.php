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

use Tobento\App\Backend\Controller\UserCrudController;
use Tobento\App\Backend\Controller\UserPermissionsController;
use Tobento\App\Boot;
use Tobento\App\Crud\Boot\Crud;
use Tobento\App\Language\RouteLocalizerInterface;
use Tobento\App\Search\Searchable;
use Tobento\App\Search\SearchablesInterface;
use Tobento\App\Search\SearchInterface;
use Tobento\App\Search\SearchResult;
use Tobento\App\Search\SearchResultInterface;
use Tobento\App\User\Authentication\AuthInterface;
use Tobento\App\User\Boot\Acl;
use Tobento\App\User\UserInterface;
use Tobento\App\User\UserRepositoryInterface;
use Tobento\Service\Acl\AclInterface;
use Tobento\Service\Menu\MenusInterface;
use Tobento\Service\Routing\RouterInterface;
use Tobento\Service\Routing\RouteGroupInterface;
use function Tobento\App\Translation\{trans};

class Users extends Boot
{
    public const INFO = [
        'boot' => [
            'Users CRUD',
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
        $acl->rule('users')->description('User can access users.');
        $acl->rule('users.create')->description('User can create users.');
        $acl->rule('users.edit')->description('User can edit users.');
        $acl->rule('users.permissions')->description('User can edit users permissions.');
        $acl->rule('users.role')->description('User can edit users role.');
        $acl->rule('users.delete')->description('User can delete users.');
        
        // Routes:
        $router = $this->app->get(RouterInterface::class);
        
        $crud->routeController(
            UserCrudController::class,
            middleware: [
                \Tobento\App\Backend\Middleware\AllowUserToEditProfile::class,
                [
                    \Tobento\App\User\Middleware\VerifyRoutePermission::class,
                    'permissions' => [
                        'users.index' => 'users',
                        'users.show' => 'users',
                        'users.create' => 'users.create',
                        'users.store' => 'users.create',
                        'users.copy' => 'users.create',
                        'users.edit' => 'users.edit',
                        'users.update' => 'users.edit',
                        'users.delete' => 'users.delete',
                        'users.bulk' => 'users.edit|users.delete',
                    ],
                ]
            ],
            localized: true,
        );

        $route = $router->group('', function(RouteGroupInterface $route) {
            $route->get(
                uri: '{?locale}/users/{id}/permissions/edit',
                handler: [UserPermissionsController::class, 'edit'],
            )->name('users.permissions.edit');

            $route->put(
                uri: '{?locale}/users/{id}/permissions',
                handler: [UserPermissionsController::class, 'update'],
            )->name('users.permissions.update');
        })->middleware(['can', 'permission' => 'users.permissions']);
        
        $this->app->get(RouteLocalizerInterface::class)->localizeRoute($route);
        
        // Menu:
        $this->app->on(
            MenusInterface::class,
            static function(MenusInterface $menus, AclInterface $acl, RouterInterface $router, AuthInterface $auth): void {
                if ($acl->can('users')) {
                    $menus->menu('main')
                        ->link($router->url('users.index'), 'Users')
                        ->parent('settings')
                        ->id('users.index');
                }
                
                $userId = $auth->getAuthenticated()?->user()?->id();

                if ($userId) {
                    $menus->menu('header')
                        ->link($router->url('users.edit', ['id' => $userId]), 'Profile')
                        ->id('profile-edit')
                        ->parent('profile')
                        ->order(1000);                    
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
            static function(SearchInterface $search, UserRepositoryInterface $repository, RouterInterface $router): void {
                $search->searchables()->add(searchable: new Searchable\Repository(
                    repository: $repository,
                    name: 'users',
                    title: trans('Users'),
                    searchAttributes: ['email', 'smartphone'],
                    toSearchResult: function(
                        UserInterface $user,
                        Searchable\Repository $searchable
                    ) use ($router): SearchResultInterface {
                        return new SearchResult(
                            searchable: $searchable->name(),
                            type: $searchable->title(),
                            title: implode(
                                ', ',
                                array_filter([$user->email(), $user->smartphone(), $user->address()->name()])
                            ),
                            url: (string)$router->url('users.edit', ['id' => $user->id()]),
                        );
                    },
                ));
            }
        );
    }
}