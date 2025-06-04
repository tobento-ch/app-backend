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
use Tobento\App\Backend\Middleware\LoginIfNotAuthenticated;
use Tobento\App\Backend\Middleware\RedirectsToRegister;
use Tobento\App\Backend\Middleware\VerifyBackendPermission;
use Tobento\App\Http\Boot\Middleware;
use Tobento\App\User\UserRepositoryInterface;
use Tobento\App\User\Web\Feature;
use Tobento\Service\Middleware\MiddlewareFactoryInterface;

/**
 * CreateFirstUser
 */
class CreateFirstUser extends Boot
{
    public const INFO = [
        'boot' => [
            'creating first user if none exsits yet',
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
        $userRepository = $this->app->get(UserRepositoryInterface::class);

        if ($userRepository->count() > 0) {
            return;
        }

        // Add register feature:
        $registerFeature = new Feature\Register(
            // A menu name to show the register link or null if none.
            menu: null,
            
            // The default role key for the registered user.
            roleKey: 'administrator',
            
            // The redirect route after a successful registration.
            successRedirectRoute: 'login',
            
            // If true, user has the option to subscribe to the newsletter.
            newsletter: false,
            
            // If a terms route is specified, users need to agree terms and conditions.
            termsRoute: null,
            
            // If true, routes are being localized.
            localizeRoute: false,
        );
        
        $this->app->call($registerFeature);
                
        // Allow register routes by replacing middlewares:
        $middlewareFactory = $this->app->get(MiddlewareFactoryInterface::class);
        
        $middlewareFactory->replaceMiddleware(
            middleware: LoginIfNotAuthenticated::class,
            withMiddleware: new LoginIfNotAuthenticated(
                exceptRoutes: ['login', 'login.store', 'register', 'register.store'],
            ),
        );
        
        $middlewareFactory->replaceMiddleware(
            middleware: VerifyBackendPermission::class,
            withMiddleware: [
                VerifyBackendPermission::class,
                'exceptRoutes' => ['register', 'register.store', 'login', 'login.store', 'logout'],
            ],
        );
        
        // Always redirect to register page:
        $middleware->add(RedirectsToRegister::class);
    }
}