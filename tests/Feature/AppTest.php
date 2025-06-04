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
use Tobento\App\User\UserRepositoryInterface;
use Tobento\Apps\AppsInterface;

class AppTest extends \Tobento\App\Testing\TestCase
{
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/../..', folder: 'app');
        $app->boot(\Tobento\App\Backend\Boot\Backend::class);
        $app->booting();
        
        $app = $app->get(AppsInterface::class)->get('backend')->app();
        $app->boot(\Tobento\App\Seeding\Boot\Seeding::class);
        $app->boot(\Tobento\App\Backend\Testing\UserAndRolesBoot::class);
        return $app;
    }

    public function testRedirectsToLoginIfNotAuthenticated()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'roles');
        
        $http->response()->assertStatus(302)->assertRedirectToRoute(name: 'login');
        
        $http->followRedirects()
            ->assertStatus(200)
            ->assertBodyContains('Login');
    }

    public function testUserCanNotLoginIfNotActive()
    {
        $auth = $this->fakeAuth();
        $http = $this->fakeHttp();
        $http->request(
            method: 'POST',
            uri: 'login',
            body: [
                'user' => 'inactive@example.com',
                'password' => 'password',
            ],
        );
                
        $http->response()->assertStatus(302)->assertRedirectToRoute(name: 'login');
        $auth->assertNotAuthenticated();
    }
    
    public function testUserGetsUnauthenticatedIfUserIsSetInactive()
    {
        $auth = $this->fakeAuth();
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'roles');
        
        $app = $this->bootingApp();
        $app->get(UserRepositoryInterface::class)->updateById(1, ['active' => false]);
        $user = $app->get(UserRepositoryInterface::class)->findById(1);
        $auth->authenticatedAs($user);
        
        $http->response()->assertStatus(403)->assertBodyContains('419 | Resource Expired');
        
        $app->get(UserRepositoryInterface::class)->updateById(1, ['active' => true]);
    }
    
    public function testUserCanNotLoginWithoutBackendPermission()
    {
        $auth = $this->fakeAuth();
        $http = $this->fakeHttp();
        $http->request(
            method: 'POST',
            uri: 'login',
            body: [
                'user' => 'registered@example.com',
                'password' => 'password',
            ],
        );
                
        $http->response()->assertStatus(302)->assertRedirectToRoute(name: 'login');
        $auth->assertNotAuthenticated();
    }
    
    public function testLoginRedirectsToTwoFactorCodeSreenIfEnabled()
    {
        $auth = $this->fakeAuth();
        $http = $this->fakeHttp();
        $http->request(
            method: 'POST',
            uri: 'login',
            body: [
                'user' => 'admin@example.com',
                'password' => 'password',
            ],
        );
        
        $app = $this->bootingApp();
        $app->get(UserRepositoryInterface::class)->updateById(1, ['settings' => ['twofactor' => '1']]);
        
        $http->response()
            ->assertStatus(302)
            ->assertRedirectToRoute(name: 'twofactor.code.show');
        
        $app->get(UserRepositoryInterface::class)->updateById(1, ['settings' => ['twofactor' => '0']]);
    }
}