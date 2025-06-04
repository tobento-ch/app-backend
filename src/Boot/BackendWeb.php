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

use Tobento\App\Boot;
use Tobento\App\Crud\Boot\Crud;
use Tobento\App\User\Boot\Acl;
use Tobento\App\User\UserInterface;
use Tobento\Service\Acl\AclInterface;
use Tobento\Service\Language\LanguagesInterface;
use Tobento\Service\Menu\LinkToFirstChild;
use Tobento\Service\Menu\MenusInterface;
use Tobento\Service\Routing\RouterInterface;
use Tobento\Service\Routing\UrlException;
use function Tobento\App\Translation\trans;

/**
 * BackendWeb
 */
class BackendWeb extends Boot
{
    public const INFO = [
        'boot' => [
            'Adding backend ACL rule',
            'Adding backend menu items',
        ],
    ];

    public const BOOT = [
        Acl::class,
        Crud::class,
    ];

    /**
     * Boot application services.
     *
     * @param Acl $acl
     * @param Crud $crud
     * @return void
     */
    public function boot(Acl $acl, Crud $crud): void
    {
        // Acl:
        $acl->acl()->setDefaultRuleArea('backend');
        $acl->rule('backend')->description('User can access the backend.');
        
        // Menu:
        $this->app->on(
            MenusInterface::class,
            static function(
                MenusInterface $menus,
                AclInterface $acl,
                RouterInterface $router,
                LanguagesInterface $languages
            ): void {
                if ($acl->can('backend')) {
                    $menus->menu('main')
                        ->add((new LinkToFirstChild($menus->menu('main'), trans('Settings')))
                        ->id('settings')
                        ->order(100));
                    
                    // Language:           
                    $menus->menu('footer')->item(trans('Language'))->icon('language')->id('language');
                    $route = $router->getMatchedRoute();
                    $currentLanguage = $languages->current();
                    $routeParams = is_null($route) ? [] : $route->getParameter('request_parameters', []);
                    unset($routeParams['locale']);
                    
                    foreach($languages as $language) {
                        try {
                            if (!is_null($route)) {
                                $url = $router->url($route->getName(), $routeParams)->locale($language->locale());
                            } else {
                                $url = $router->url('home')->locale($language->locale());
                            }
                        } catch (UrlException $e) {
                            $url = '';
                        }
                        
                        $menus->menu('footer')
                            ->link($url, $language->name())
                            ->id($language->locale())
                            ->parent('language');
                        
                        if ($currentLanguage->locale() === $language->locale()) {
                            $menus->menu('footer')->get($language->locale())->tag()->class('active');
                        }
                    }
                    
                    // User:
                    $user = $acl->getCurrentUser();
                        
                    if ($user instanceof UserInterface) {
                        $greeting = $user->greeting();
                        $greeting = $greeting ?: trans('Profile');
                        $menus->menu('header')->item($greeting)->icon('user-circle')->id('profile');
                    }
                }
            }
        );
    }
}