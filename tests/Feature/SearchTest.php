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

class SearchTest extends \Tobento\App\Testing\TestCase
{
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/../..', folder: 'app');
        $app->boot(\Tobento\App\Backend\Boot\Backend::class);
        $app->booting();
        
        $app = $app->get(AppsInterface::class)->get('backend')->app();
        $app->boot(\Tobento\App\Backend\Testing\UserAndRolesBoot::class);
        
        $app->on(
            DashboardCards::class,
            static function(DashboardCards $cards): void {
                $cards->add(name: 'foo', card: new Factory\Table(
                    rows: ['FOO', 'BAR'],
                ));
            }
        );
        
        return $app;
    }

    public function testSearching()
    {
        $auth = $this->fakeAuth();
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'search', query: ['search' => ['term' => 'adm']]);
        
        $app = $this->bootingApp();
        $user = $app->get(UserRepositoryInterface::class)->findByIdentity(email: 'admin@example.com');
        $auth->authenticatedAs($user);

        $response = $http->response()
            ->assertStatus(200)
            ->assertBodyContains('Search')
            ->assertBodyContains('1 Roles found')
            ->assertBodyContains('Administrator');
    }
}