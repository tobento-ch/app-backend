<?php
/**
 * TOBENTO
 *
 * @copyright   Tobias Strub, TOBENTO
 * @license     MIT License, see LICENSE file distributed with this source code.
 * @author      Tobias Strub
 * @link        https://www.tobento.ch
 */

use Tobento\App\Backend\User\LoginFeature;
use Tobento\App\User\Web;
use Tobento\App\User\Web\Feature;
use Tobento\App\RateLimiter\Symfony\Registry\SlidingWindow;
use Tobento\Service\Storage\StorageInterface;
use Tobento\Service\Routing\RouterInterface;
use Psr\Container\ContainerInterface;
use function Tobento\App\Translation\{trans};

return [
    
    /*
    |--------------------------------------------------------------------------
    | Features
    |--------------------------------------------------------------------------
    |
    | Specify and configure the features you wish to use or remove uneeded.
    |
    | See: https://github.com/tobento-ch/app-user-web#features
    |
    */
    
    'features' => [
        new LoginFeature(
            // The view to render:
            view: 'user/login',
            
            // A menu name to show the login link or null if none.
            menu: null,
            menuLabel: 'Log in',
            // A menu parent name (e.g. 'user') or null if none.
            menuParent: null,
            
            // Specify the rate limiter:
            rateLimiter: new SlidingWindow(limit: 10, interval: '5 Minutes'),
            // see: https://github.com/tobento-ch/app-rate-limiter#available-rate-limiter-registries
            
            // Specify the identity attributes to be checked on login.
            identifyBy: ['email', 'username', 'smartphone', 'password'],
            // You may set a user verifier(s), see: https://github.com/tobento-ch/app-user#user-verifiers
            userVerifier: function() {
                return new \Tobento\App\User\Authenticator\UserVerifiers(
                    new \Tobento\App\User\Authenticator\UserPermissionVerifier('backend'),
                    new \Tobento\App\User\Authenticator\UserVerifier(
                        verified: fn ($user): bool => $user->active(),
                        message: 'User must be activated',
                    ),
                );
            },
            
            // The period of time from the present after which the auth token MUST be considered expired.
            expiresAfter: new \DateInterval('PT2H'), // int|\DateInterval
            
            // If you want to support remember. If set and the user wants to be remembered,
            // this value replaces the expiresAfter parameter.
            remember: new \DateInterval('P6M'), // null|int|\DateInterval
            
            // The message and redirect route if a user is authenticated.
            authenticatedMessage: 'You are logged in!',
            authenticatedRedirectRoute: 'home', // or null (no redirection)
            
            // The message shown if a login attempt fails.
            failedMessage: 'Invalid user or password.',
            
            // The redirect route after a successful login.
            successRoute: 'home',
            
            // The message shown after a user successfully log in.
            successMessage: 'Welcome back :greeting.', // or null
            
            // If set, it shows the forgot password link. Make sure the Feature\ForgotPassword is set too.
            forgotPasswordRoute: null, // or 'forgot-password.identity'
            
            // The two factor authentication route.
            twoFactorRoute: 'twofactor.code.show',
            
            // If true, routes are being localized.
            localizeRoute: true,
        ),
        
        new Feature\TwoFactorAuthenticationCode(
            // The view to render:
            view: 'user/twofactor-code',
            
            // The period of time from the present after which the verification code MUST be considered expired.
            codeExpiresAfter: 300, // int|\DateInterval

            // The seconds after a new code can be reissued.
            canReissueCodeAfter: 60,
            
            // The message and redirect route if a user is unauthenticated.
            unauthenticatedMessage: 'You have insufficient rights to access the requested resource!',
            unauthenticatedRedirectRoute: 'home', // or null (no redirection)
            
            // The redirect route after a successful code verification.
            successRoute: 'home',
            
            // The message shown after a successful code verification.
            successMessage: 'Welcome back :greeting.', // or null
            
            // If true, routes are being localized.
            localizeRoute: true,
        ),
        
        new Feature\Notifications(
            // The view to render:
            view: 'user/notifications',
            
            // The notifier storage channel used to retrieve notifications.
            notifierStorageChannel: 'storage',
            
            // A menu name to show the notifications link or null if none.
            menu: 'header',
            menuLabel: 'Notifications',
            // A menu parent name (e.g. 'user') or null if none.
            menuParent: 'profile',

            // The message and redirect route if a user is unauthenticated.            
            unauthenticatedMessage: 'You have insufficient rights to access the requested resource!',
            unauthenticatedRedirectRoute: 'login', // or null (no redirection)
            
            // If true, routes are being localized.
            localizeRoute: true,
        ),
        
        new Feature\Verification(
            // The view to render:
            viewAccount: 'user/verification/account',
            viewChannel: 'user/verification/channel',
            
            // The period of time from the present after which the verification code MUST be considered expired.
            codeExpiresAfter: 300, // int|\DateInterval
            
            // The seconds after a new code can be reissued.
            canReissueCodeAfter: 60,
            
            // The message and redirect route if a user is unauthenticated.            
            unauthenticatedMessage: 'You have insufficient rights to access the requested resource!',
            unauthenticatedRedirectRoute: 'login', // or null (no redirection)
            
            // The redirect route after a verified channel.
            verifiedRedirectRoute: 'home',
            
            // If true, routes are being localized.
            localizeRoute: true,
        ),
        
        new Feature\Logout(
            // A menu name to show the logout link or null if none.
            menu: 'header',
            menuLabel: 'Log out',
            // A menu parent name (e.g. 'user') or null if none.
            menuParent: 'profile',
            
            // The redirect route after a successful logout.
            redirectRoute: 'home',
            
            // The message and redirect route if a user is unauthenticated.
            unauthenticatedMessage: 'You have insufficient rights to access the requested resource!',
            unauthenticatedRedirectRoute: 'home', // or null (no redirection)
            
            // If true, routes are being localized.
            localizeRoute: true,
        ),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Interfaces
    |--------------------------------------------------------------------------
    |
    | Do not change the interface's names as it may be used in other app bundles!
    |
    */
    
    'interfaces' => [
        // Verificators:
        Web\TokenVerificatorInterface::class => Web\TokenVerificator::class,
        
        Web\PinCodeVerificatorInterface::class => Web\PinCodeVerificator::class,
        
        // Token:
        Web\TokenFactoryInterface::class => Web\TokenFactory::class,
        
        Web\TokenRepository::class => static function(ContainerInterface $c) {
            return new Web\TokenRepository(
                storage: $c->get(StorageInterface::class)->new(),
                table: 'verification_tokens',
                entityFactory: $c->get(Web\TokenFactoryInterface::class),
            );
        },
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Verificator Hash Key
    |--------------------------------------------------------------------------
    |
    | This key should be set to a random, 32 character string.
    |
    */
    
    'verificator_hash_key' => '{verificator_hash_key}',
];