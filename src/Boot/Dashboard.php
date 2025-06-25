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
use Tobento\App\Backend\Action\DashboardShowAction;
use Tobento\App\Language\RouteLocalizerInterface;
use Tobento\Service\Menu\MenusInterface;
use Tobento\Service\Routing\RouterInterface;
use function Tobento\App\Translation\{trans};

class Dashboard extends Boot
{
    public const INFO = [
        'boot' => [
            'Dashboard',
        ],
    ];

    public const BOOT = [
        \Tobento\App\Card\Boot\Card::class,
    ];
    
    /**
     * Boot application services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Routes:
        $router = $this->app->get(RouterInterface::class);
        $route = $router->get(uri: '{?locale}/', handler: DashboardShowAction::class)->name('home');
        $this->app->get(RouteLocalizerInterface::class)->localizeRoute($route);
        
        // Menu:
        $this->app->on(
            MenusInterface::class,
            static function(MenusInterface $menus, RouterInterface $router) {
                $menus->menu('main')
                    ->link($router->url('home'), trans('Dashboard'))
                    ->id('home')
                    ->icon('home')
                    ->order(10000);
            }
        );
    }
}