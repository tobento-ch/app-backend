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

use Psr\Http\Message\ServerRequestInterface;
use Tobento\App\Boot;
use Tobento\App\Http\Boot\Middleware;
use Tobento\Service\Menu\MenusInterface;
use function Tobento\App\Translation\{trans};

class ColorScheme extends Boot
{
    public const INFO = [
        'boot' => [
            'Adding Color Scheme menu and middleware to switch mode',
        ],
    ];

    public const BOOT = [
        Middleware::class,
    ];
    
    /**
     * Boot application services.
     *
     * @param Middleware $middleware
     * @return void
     */
    public function boot(Middleware $middleware): void
    {
        $middleware->add(
            middleware: \Tobento\App\Backend\Middleware\ColorScheme::class,
            priority: 1500,
        );
        
        // Menu:
        $this->app->on(
            MenusInterface::class,
            static function(MenusInterface $menus, ServerRequestInterface $request): void {            
                $menus->menu('footer')
                    ->item(trans('Color Scheme'))
                    ->icon('sun-moon')
                    ->id('color-scheme');
                
                $menus->menu('footer')
                    ->link('?color-scheme=dark', trans('Dark'))
                    ->icon('moon')
                    ->id('color-scheme.dark')
                    ->parent('color-scheme');
                
                $menus->menu('footer')
                    ->link('?color-scheme=light', trans('Light'))
                    ->icon('sun')
                    ->id('color-scheme.light')
                    ->parent('color-scheme');
                
                $menus->menu('footer')
                    ->link('?color-scheme=auto', trans('Automatic'))
                    ->id('color-scheme.auto')
                    ->parent('color-scheme');
                
                $colorScheme = $request->getAttribute('color-scheme', 'auto');
                
                if (in_array($colorScheme, ['auto', 'light', 'dark'])) {
                    $menus->menu('footer')->get('color-scheme.'.$colorScheme)->tag()->class('active');
                }
            }
        );
    }
}