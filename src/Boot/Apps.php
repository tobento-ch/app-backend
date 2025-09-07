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

use Psr\SimpleCache\CacheInterface;
use Tobento\Apps\AppsInterface;
use Tobento\App\AppInterface;
use Tobento\App\Boot;
use Tobento\Service\Menu\MenusInterface;
use function Tobento\App\Translation\trans;

/**
 * Apps
 */
class Apps extends Boot
{
    public const INFO = [
        'boot' => [
            'Adding apps menu',
        ],
    ];

    /**
     * Boot application services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Menu:
        $this->app->on(
            MenusInterface::class,
            static function(MenusInterface $menus, AppInterface $application, AppsInterface $apps, CacheInterface $cache): void {
                $menus->menu('header')->item(trans('Apps'))
                    ->icon('apps')
                    ->id('apps-header')
                    ->order(1000);
                
                $appsData = $cache->get('apps');

                if ($appsData === null) {
                    $appsData = [];
                    
                    foreach($apps->all() as $app) {
                        $app->app()->booting();
                    }

                    foreach($apps->all() as $app) {
                        if ($app->type() !== 'web') {
                            continue;
                        }
                        
                        $appsData[$app->id()] = [
                            'name' => $app->name(),
                            'url' => (string)$app->url(),
                        ];
                    }
                    
                    $cache->set(key: 'apps', value: $appsData, ttl: new \DateInterval('P1D'));
                    
                    $apps->bootingApp($application);
                }
                
                foreach($appsData as $appId => $app) {
                    $menus->menu('header')
                        ->link($app['url'], $app['name'])
                        ->id($appId)
                        ->parent('apps-header');
                }
                
                $menus->menu('header')->get('backend')->tag()->class('active');
            }
        );
    }
}