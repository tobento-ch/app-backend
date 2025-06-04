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

namespace Tobento\App\Backend\Test\Feature;

use Tobento\App\AppInterface;
use Tobento\App\RateLimiter\Middleware\RateLimitRequests;
use Tobento\App\Seeding\User\UserFactory;
use Tobento\App\Spam\Middleware\ProtectAgainstSpam;
use Tobento\App\Testing\Database\RefreshDatabases;
use Tobento\App\User\Web\Event;
use Tobento\Apps\AppsInterface;

class RegisterTest extends \Tobento\App\Testing\TestCase
{
    use RefreshDatabases;
    
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/../..', folder: 'app-register');
        $app->boot(\Tobento\App\Backend\Boot\Backend::class);
        $app->booting();
        
        $app = $app->get(AppsInterface::class)->get('backend')->app();
        return $app;
    }

    public function testRedirectsToRegisterScreenIfNoUsersExists()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: '');
        
        $http->followRedirects()
            ->assertStatus(200)
            ->assertBodyContains('Register');
    }
    

    public function testRegisterScreenIsRendered()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'register');
        $http->response()
            ->assertStatus(200)
            ->assertBodyContains('Register')
            ->assertBodyContains('E-Mail')
            ->assertBodyContains('Smartphone')
            ->assertBodyContains('Password')
            ->assertBodyContains('Confirm password');
    }
    
    public function testUserCanRegister()
    {
        $events = $this->fakeEvents();
        $auth = $this->fakeAuth();
        $http = $this->fakeHttp();
        $http->withoutMiddleware(RateLimitRequests::class);
        $http->withoutMiddleware(ProtectAgainstSpam::class);
        $http->request(
            method: 'POST',
            uri: 'register',
            body: [
                'address' => ['name' => 'Tom'],
                'email' => 'tom@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
            ],
        );
        
        $app = $this->bootingApp();
        
        $http->response()->assertStatus(302)->assertRedirectToRoute(name: 'login');
        $auth->assertNotAuthenticated();
        
        $events->assertDispatched(Event\Registered::class, static function(Event\Registered $event): bool {
            $user = $event->user();
            return $user->email() === 'tom@example.com'
                && $user->address()->name() === 'Tom';
        });
        
        $events->assertNotDispatched(Event\RegisterFailed::class);
        
        // Once registered, the registering should not be possible again.
        $http->request(method: 'GET', uri: 'register');
        $http->response()->assertStatus(404);
        
        $http->request(
            method: 'POST',
            uri: 'register',
            body: [
                'address' => ['name' => 'Tom'],
                'email' => 'tom@example.com',
                'password' => '12345678',
                'password_confirmation' => '12345678',
            ],
        );
        $http->response()->assertStatus(404);
        
        // Should redirect to login.
        $http->request(method: 'GET', uri: '');
        $http->response()->assertStatus(302)->assertRedirectToRoute(name: 'login');
    }
}