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

use Symfony\Component\DomCrawler\Crawler;
use Tobento\App\AppInterface;
use Tobento\App\User\UserRepositoryInterface;
use Tobento\Apps\AppsInterface;

class ColorSchemeTest extends \Tobento\App\Testing\TestCase
{
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/../..', folder: 'app');
        $app->boot(\Tobento\App\Backend\Boot\Backend::class);
        $app->booting();
        
        $app = $app->get(AppsInterface::class)->get('backend')->app();
        $app->boot(\Tobento\App\Backend\Testing\UserAndRolesBoot::class);
        return $app;
    }

    public function testColorSchemeIsAutomaticByDefault()
    {
        $auth = $this->fakeAuth();
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: '');
        
        $app = $this->bootingApp();
        $user = $app->get(UserRepositoryInterface::class)->findByIdentity(email: 'admin@example.com');
        $auth->authenticatedAs($user);

        $response = $http->response()
            ->assertStatus(200)
            ->assertBodyContains('Color Scheme');
        
        $this->assertStringNotContainsString('dark', $response->crawl()->filter('body')->attr('class'));
    }
    
    public function testSwitchToDarkColorScheme()
    {
        $auth = $this->fakeAuth();
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: '?color-scheme=dark');
        
        $app = $this->bootingApp();
        $user = $app->get(UserRepositoryInterface::class)->findByIdentity(email: 'admin@example.com');
        $auth->authenticatedAs($user);

        $response = $http->response()->assertStatus(200);
        $this->assertStringContainsString('dark', $response->crawl()->filter('body')->attr('class'));
        
        $http->request(method: 'GET', uri: '');
        $response = $http->response()->assertStatus(200);
        $this->assertStringContainsString('dark', $response->crawl()->filter('body')->attr('class'));
    }
    
    public function testSwitchToLightColorScheme()
    {
        $auth = $this->fakeAuth();
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: '?color-scheme=light');
        
        $app = $this->bootingApp();
        $user = $app->get(UserRepositoryInterface::class)->findByIdentity(email: 'admin@example.com');
        $auth->authenticatedAs($user);

        $response = $http->response()->assertStatus(200);
        $this->assertStringContainsString('light', $response->crawl()->filter('body')->attr('class'));
        
        $http->request(method: 'GET', uri: '');
        $response = $http->response()->assertStatus(200);
        $this->assertStringContainsString('light', $response->crawl()->filter('body')->attr('class'));
    }
    
    public function testSwitchBackToAutomaticColorScheme()
    {
        $auth = $this->fakeAuth();
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: '?color-scheme=light');
        
        $app = $this->bootingApp();
        $user = $app->get(UserRepositoryInterface::class)->findByIdentity(email: 'admin@example.com');
        $auth->authenticatedAs($user);

        $response = $http->response()->assertStatus(200);
        $this->assertStringContainsString('light', $response->crawl()->filter('body')->attr('class'));
        
        $http->request(method: 'GET', uri: '?color-scheme=auto');
        $response = $http->response()->assertStatus(200);
        $this->assertStringNotContainsString('light', $response->crawl()->filter('body')->attr('class'));
        
        $http->request(method: 'GET', uri: '');
        $response = $http->response()->assertStatus(200);
        $this->assertStringNotContainsString('light', $response->crawl()->filter('body')->attr('class'));
    }
}