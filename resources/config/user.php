<?php
/**
 * TOBENTO
 *
 * @copyright   Tobias Strub, TOBENTO
 * @license     MIT License, see LICENSE file distributed with this source code.
 * @author      Tobias Strub
 * @link        https://www.tobento.ch
 */

use Tobento\App\Backend\User\TokenAuthenticator;
use Tobento\App\User;
use Tobento\App\User\Authentication;
use Tobento\App\User\Authenticator;
use Tobento\Service\Session\SessionInterface;
use Tobento\Service\Storage\StorageInterface;
use Tobento\Service\Storage\InMemoryStorage;
use Tobento\Service\Database\DatabasesInterface;
use Psr\Container\ContainerInterface;
use Psr\Clock\ClockInterface;

return [

    /*
    |--------------------------------------------------------------------------
    | Migrations
    |--------------------------------------------------------------------------
    |
    | The migrations.
    |
    */
    
    'migrations' => [
        // Creates database tables depending on its storage
        // implemenation specified on the interfaces below.
        \Tobento\App\User\Migration\RoleRepository::class,
        \Tobento\App\User\Migration\UserRepositories::class,
        \Tobento\App\User\Migration\AuthenticationTokenRepository::class,
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Middlewares
    |--------------------------------------------------------------------------
    |
    | The middlewares.
    |
    */
    
    'middlewares' => [
        // You may uncomment it and set it on each route individually
        // using the User\Middleware\AuthenticationWith::class!
        User\Middleware\Authentication::class,
        
        User\Middleware\User::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Middleware Aliases
    |--------------------------------------------------------------------------
    |
    | The middleware aliases.
    |
    */
    
    'middleware_aliases' => [
        // Authentication:
        'auth.with' => User\Middleware\AuthenticationWith::class,
        'auth' => User\Middleware\Authenticated::class,
        'guest' => User\Middleware\Unauthenticated::class,
        'verified' => User\Middleware\Verified::class,
        
        // Authorization:
        'can' => User\Middleware\VerifyPermission::class,
        'role' => User\Middleware\VerifyRole::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Interfaces
    |--------------------------------------------------------------------------
    |
    | Do not change the interface's names!
    |
    */
    
    'interfaces' => [
        // Default auth implementation:
        Authentication\AuthInterface::class => Authentication\Auth::class,
        
        // Define the token storages you wish to support:
        Authentication\Token\TokenStoragesInterface::class => static function(ContainerInterface $c) {
            return new Authentication\Token\TokenStorages(
                new Authentication\Token\RepositoryStorage(
                    clock: $c->get(ClockInterface::class),
                    repository: new Authentication\Token\TokenRepository(
                        storage: $c->get(StorageInterface::class)->new(),
                        table: 'auth_tokens',
                    ),
                    name: 'repository',
                ),
            );
        },
        
        // Define the default token storage used for auth:
        Authentication\Token\TokenStorageInterface::class => static function(ContainerInterface $c) {
            return $c->get(Authentication\Token\TokenStoragesInterface::class)->get('repository');
        },
        
        // Define the token transport you wish to support:
        Authentication\Token\TokenTransportsInterface::class => static function(ContainerInterface $c) {
            return new Authentication\Token\TokenTransports(
                new Authentication\Token\CookieTransport(
                    clock: $c->get(ClockInterface::class),
                    cookieName: 'token'
                ),
                new Authentication\Token\HeaderTransport(name: 'header', headerName: 'X-Auth-Token'),
            );
        },
        
        // Define the default token transport(s) used for auth:
        Authentication\Token\TokenTransportInterface::class => static function(ContainerInterface $c) {
            return $c->get(Authentication\Token\TokenTransportsInterface::class); // all
            //return $c->get(Authentication\Token\TokenTransportsInterface::class)->get('cookie');
            //return $c->get(Authentication\Token\TokenTransportsInterface::class)->only(['header']);
        },
        
        // Default token authenticator:
        Authenticator\TokenAuthenticatorInterface::class => TokenAuthenticator::class,
        
        /*
        // Example with token verifiers:
        Authenticator\TokenAuthenticatorInterface::class => static function(ContainerInterface $c) {
            return new Authenticator\TokenAuthenticator(
                verifier: new Authenticator\TokenVerifiers(
                    new Authenticator\TokenPasswordHashVerifier(
                        // The token issuers (storage names) to verify password hash. 
                        // If empty it gets verified for all issuers.
                        issuers: ['session'],
                        
                        // The attribute name of the payload:
                        name: 'passwordHash',
                    ),
                ),
            );
        },
        */
        
        // You may define a default user verifier used by authenticators:
        Authenticator\UserVerifierInterface::class => static function(ContainerInterface $c) {
            return new Authenticator\UserVerifiers(
                //new Authenticator\UserRoleAreaVerifier('backend'),
            );
        },
        
        // Default password hasher:
        User\PasswordHasherInterface::class => User\PasswordHasher::class,
        
        // User:
        User\UserFactoryInterface::class => User\UserFactory::class,

        User\UserRepositoryInterface::class => static function(ContainerInterface $c) {
            return new User\UserStorageRepository(
                storage: $c->get(StorageInterface::class)->new(),
                table: 'users',
                userFactory: $c->get(User\UserFactoryInterface::class),
                addressRepository: $c->get(User\AddressRepositoryInterface::class),
            );
        },
        
        // Role:
        User\RoleFactoryInterface::class => User\RoleFactory::class,

        User\RoleRepositoryInterface::class => static function(ContainerInterface $c) {
            return new User\RoleStorageRepository(
                storage: $c->get(StorageInterface::class)->new(),
                table: 'roles',
                entityFactory: $c->get(User\RoleFactoryInterface::class),
            );
        },
        
        // Address:
        \Tobento\Service\User\AddressFactoryInterface::class => \Tobento\Service\User\AddressFactory::class,
        
        \Tobento\Service\User\AddressesFactoryInterface::class => \Tobento\Service\User\AddressesFactory::class,
        
        User\AddressFactoryInterface::class => User\AddressFactory::class,
        
        User\AddressRepositoryInterface::class => static function(ContainerInterface $c) {
            return new User\AddressStorageRepository(
                storage: $c->get(StorageInterface::class)->new(),
                table: 'addresses',
                addressFactory: $c->get(User\AddressFactoryInterface::class),
            );
        },
    ],

];