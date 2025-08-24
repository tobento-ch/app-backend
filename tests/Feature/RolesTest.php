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
use Tobento\App\User\RoleRepositoryInterface;
use Tobento\App\User\UserRepositoryInterface;
use Tobento\App\Testing\Database\MigrateDatabases;
use Tobento\Apps\AppsInterface;

class RolesTest extends \Tobento\App\Crud\Testing\AbstractCrudTestCase
{
    use MigrateDatabases;
    
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
    
    protected function getCrudController(): string
    {
        return \Tobento\App\Backend\Controller\RoleCrudController::class;
    }
    
    public function testIndexAction()
    {
        $auth = $this->fakeAuth();
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateIndexUri());
        
        $app = $this->bootingApp();
        $user = $app->get(UserRepositoryInterface::class)->findByIdentity(email: 'admin@example.com');
        $auth->authenticatedAs($user);

        $http->response()
            ->assertStatus(200)
            ->assertBodyContains('Roles')
            ->assertCrudIndexHeaderColumnsExists(columns: ['name', 'key', 'active', 'actions'])
            ->assertCrudIndexEntityCount(3);
    }
    
    public function testIndexActionFailsWithoutPermission()
    {
        $auth = $this->fakeAuth();
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateIndexUri());
        
        $app = $this->bootingApp();
        $user = $app->get(UserRepositoryInterface::class)->findByIdentity(email: 'editor@example.com');
        $auth->authenticatedAs($user);

        $http->response()
            ->assertStatus(403)
            ->assertBodyContains('You don\'t have a required "roles" permission.');
    }
    
    public function testStoreActionFailsIfKeyExists()
    {
        $auth = $this->fakeAuth();
        $http = $this->fakeHttp();
        $http->previousUri($this->generateCreateUri());
        $http->request(method: 'POST', uri: $this->generateStoreUri())->body([
            'key' => 'administrator',
        ]);
        
        $app = $this->bootingApp();
        $user = $app->get(UserRepositoryInterface::class)->findByIdentity(email: 'admin@example.com');
        $auth->authenticatedAs($user);

        $http->followRedirects()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'key', errorText: 'Key exists already.');
    }
    
    public function testAdministratorRoleIsUndeletable()
    {
        $auth = $this->fakeAuth();
        $http = $this->fakeHttp();
        $http->previousUri($this->generateIndexUri());
        $http->request(method: 'DELETE', uri: $this->generateDeleteUri(id: 1));

        $app = $this->bootingApp();
        $user = $app->get(UserRepositoryInterface::class)->findByIdentity(email: 'admin@example.com');
        $auth->authenticatedAs($user);

        $http->response()
            ->assertStatus(302)
            ->assertLocation($this->generateIndexUri());

        $http->followRedirects()
            ->assertStatus(200)
            ->assertBodyContains('Record with the ID 1 is undeletable.')
            ->assertCrudIndexEntityCount(3);
    }

    public function testEditPermissionsAction()
    {
        $auth = $this->fakeAuth();
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'roles/2/permissions/edit');
        
        $app = $this->bootingApp();
        $user = $app->get(UserRepositoryInterface::class)->findByIdentity(email: 'admin@example.com');
        $auth->authenticatedAs($user);
        
        $http->response()
            ->assertStatus(200)
            ->assertBodyContains('permissions')
            ->assertBodyContains('[roles.create]');
    }
    
    public function testEditPermissionsActionFailsIfRoleNotFound()
    {
        $auth = $this->fakeAuth();
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'roles/44/permissions/edit');
        
        $app = $this->bootingApp();
        $user = $app->get(UserRepositoryInterface::class)->findByIdentity(email: 'admin@example.com');
        $auth->authenticatedAs($user);
        
        $http->response()->assertStatus(404);
    }
    
    public function testEditPermissionsActionFailsWithoutPermission()
    {
        $auth = $this->fakeAuth();
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'roles/2/permissions/edit');
        
        $app = $this->bootingApp();
        $user = $app->get(UserRepositoryInterface::class)->findByIdentity(email: 'editor@example.com');
        $auth->authenticatedAs($user);
        
        $http->response()
            ->assertStatus(403)
            ->assertBodyContains('You don\'t have a required "roles.permissions" permission.');
    }
    
    public function testUpdatePermissionsAction()
    {
        $auth = $this->fakeAuth();
        $http = $this->fakeHttp();
        $http->request(method: 'PUT', uri: 'roles/2/permissions')->body([
            'permissions' => ['backend', 'roles', 'roles.create', 'invalid'],
        ]);
        
        $app = $this->bootingApp();
        $user = $app->get(UserRepositoryInterface::class)->findByIdentity(email: 'admin@example.com');
        $auth->authenticatedAs($user);
        
        $role = $app->get(RoleRepositoryInterface::class)->findById(2);
        $this->assertSame(['backend'], $role->getPermissions());
        
        $http->response()
            ->assertStatus(302)
            ->assertLocation($this->generateIndexUri());
        
        $role = $app->get(RoleRepositoryInterface::class)->findById(2);
        $this->assertSame(['backend', 'roles', 'roles.create'], $role->getPermissions());
    }
    
    public function testUpdatePermissionsActionFailsIfRoleNotFound()
    {
        $auth = $this->fakeAuth();
        $http = $this->fakeHttp();
        $http->request(method: 'PUT', uri: 'roles/23/permissions')->body([
            'permissions' => ['backend', 'roles', 'roles.create', 'invalid'],
        ]);
        
        $app = $this->bootingApp();
        $user = $app->get(UserRepositoryInterface::class)->findByIdentity(email: 'admin@example.com');
        $auth->authenticatedAs($user);
        
        $http->response()->assertStatus(404);
    }
    
    public function testUpdatePermissionsActionFailsWithoutPermission()
    {
        $auth = $this->fakeAuth();
        $http = $this->fakeHttp();
        $http->request(method: 'PUT', uri: 'roles/1/permissions')->body([
            'permissions' => ['backend', 'roles', 'roles.create', 'invalid'],
        ]);
        
        $app = $this->bootingApp();
        $user = $app->get(UserRepositoryInterface::class)->findByIdentity(email: 'editor@example.com');
        $auth->authenticatedAs($user);
        
        $http->response()
            ->assertStatus(403)
            ->assertBodyContains('You don\'t have a required "roles.permissions" permission.');
    }
}