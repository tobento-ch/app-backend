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

class UsersTest extends \Tobento\App\Crud\Testing\AbstractCrudTestCase
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
        return \Tobento\App\Backend\Controller\UserCrudController::class;
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
            ->assertBodyContains('Users')
            ->assertCrudIndexHeaderColumnsExists(columns: ['id', 'active', 'role_key', 'address.name', 'email', 'actions'])
            ->assertCrudIndexEntityCount(4);
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
            ->assertBodyContains('You don\'t have a required "users" permission.');
    }
    
    public function testUserCanEditHisProfileWithoutUserEditPermission()
    {
        $auth = $this->fakeAuth();
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateEditUri(id: 3));
        
        $app = $this->bootingApp();
        $user = $app->get(UserRepositoryInterface::class)->findByIdentity(email: 'editor@example.com');
        $auth->authenticatedAs($user);
        
        $http->response()
            ->assertStatus(200)
            ->assertBodyContains('Edit User');
    }
    
    public function testUserCanUpdateHisProfileWithoutUserEditPermission()
    {
        $auth = $this->fakeAuth();
        $http = $this->fakeHttp();
        $http->request(method: 'PATCH', uri: $this->generateUpdateUri(id: 3))->body([
            'email' => 'editor@example.com',
            'address' => [
                'name' => 'NEW',
            ],
            'next_action' => 'edit',
        ]);
        
        $app = $this->bootingApp();
        $user = $app->get(UserRepositoryInterface::class)->findByIdentity(email: 'editor@example.com');
        $auth->authenticatedAs($user);
        
        $oldName = $this->getCrudRepository()->findById(3)->address()->name();
        
        $http->response()
            ->assertStatus(302)
            ->assertLocation($this->generateEditUri(id: 3));
        
        $newName = $this->getCrudRepository()->findById(3)->address()->name();
        
        $this->assertSame('NEW', $newName);
        $this->assertFalse($oldName === $newName);
    }
    
    public function testStoreActionFailsIfEmailExists()
    {
        $auth = $this->fakeAuth();
        $http = $this->fakeHttp();
        $http->previousUri($this->generateCreateUri());
        $http->request(method: 'POST', uri: $this->generateStoreUri())->body([
            'email' => 'editor@example.com',
        ]);
        
        $app = $this->bootingApp();
        $user = $app->get(UserRepositoryInterface::class)->findByIdentity(email: 'admin@example.com');
        $auth->authenticatedAs($user);

        $http->followRedirects()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'email', errorText: 'E-mail exists already.');
    }
    
    public function testStoreActionFailsIfSmartphoneExists()
    {
        $auth = $this->fakeAuth();
        $http = $this->fakeHttp();
        $http->previousUri($this->generateCreateUri());
        $http->request(method: 'POST', uri: $this->generateStoreUri())->body([
            'smartphone' => '12345678',
        ]);
        
        $app = $this->bootingApp();
        $user = $app->get(UserRepositoryInterface::class)->findByIdentity(email: 'admin@example.com');
        $auth->authenticatedAs($user);

        $http->followRedirects()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'smartphone', errorText: 'Smartphone exists already.');
    }
    
    public function testCanUpdatePassword()
    {
        $auth = $this->fakeAuth();
        $http = $this->fakeHttp();
        $http->request(method: 'PATCH', uri: $this->generateUpdateUri(id: 2))->body([
            'email' => 'inactive@example.com',
            'password' => 'new-password',
        ]);
        
        $app = $this->bootingApp();
        $user = $app->get(UserRepositoryInterface::class)->findByIdentity(email: 'admin@example.com');
        $auth->authenticatedAs($user);
        
        $oldPasswordHash = $this->getCrudRepository()->findById(2)->password();
        
        $http->followRedirects()->assertStatus(200);
        
        $newPasswordHash = $this->getCrudRepository()->findById(2)->password();

        $this->assertFalse($oldPasswordHash === $newPasswordHash);
    }
    
    public function testRoleCanNotBeUpdatedWithoutPermission()
    {
        $auth = $this->fakeAuth();
        $http = $this->fakeHttp();
        $http->previousUri($this->generateEditUri(id: 2));
        $http->request(method: 'PATCH', uri: $this->generateUpdateUri(id: 2))->body([
            'email' => 'inactive@example.com',
            'role_key' => 'editor',
        ]);
        
        $app = $this->bootingApp();
        
        $user = $auth->getUserRepository()->create([
            'username' => 'tom',
            'role_key' => 'editor',
            'permissions' => ['backend', 'users', 'users.edit'],
        ]);
        
        $auth->authenticatedAs($user);
        
        $oldRoleKey = $this->getCrudRepository()->findById(2)->role()->key();

        $http->followRedirects()->assertStatus(200);

        $newRoleKey = $this->getCrudRepository()->findById(2)->role()->key();

        $this->assertTrue($oldRoleKey === $newRoleKey);
    }
    
    public function testRoleCanBeUpdatedWithPermission()
    {
        $auth = $this->fakeAuth();
        $http = $this->fakeHttp();
        $http->previousUri($this->generateEditUri(id: 2));
        $http->request(method: 'PATCH', uri: $this->generateUpdateUri(id: 2))->body([
            'email' => 'inactive@example.com',
            'role_key' => 'editor',
        ]);
        
        $app = $this->bootingApp();
        
        $user = $auth->getUserRepository()->create([
            'username' => 'tom',
            'role_key' => 'editor',
            'permissions' => ['backend', 'users', 'users.edit', 'users.role'],
        ]);
        
        $auth->authenticatedAs($user);
        
        $oldRoleKey = $this->getCrudRepository()->findById(2)->role()->key();

        $http->followRedirects()->assertStatus(200);

        $newRoleKey = $this->getCrudRepository()->findById(2)->role()->key();

        $this->assertFalse($oldRoleKey === $newRoleKey);
    }
    
    public function testEditActionDisplaysChannelVerificationsIfUsersProfile()
    {
        $auth = $this->fakeAuth();
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateEditUri(id: 1));
        
        $app = $this->bootingApp();
        $user = $app->get(UserRepositoryInterface::class)->findByIdentity(email: 'admin@example.com');
        $auth->authenticatedAs($user);
        
        $http->response()
            ->assertStatus(200)
            ->assertBodyContains('Channel Verifications');
    }
    
    public function testEditActionNotDisplaysChannelVerificationsIfNotUsersProfile()
    {
        $auth = $this->fakeAuth();
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateEditUri(id: 2));
        
        $app = $this->bootingApp();
        $user = $app->get(UserRepositoryInterface::class)->findByIdentity(email: 'admin@example.com');
        $auth->authenticatedAs($user);
        
        $http->response()
            ->assertStatus(200)
            ->assertBodyNotContains('Channel Verifications');
    }

    public function testEditPermissionsAction()
    {
        $auth = $this->fakeAuth();
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'users/2/permissions/edit');
        
        $app = $this->bootingApp();
        $user = $app->get(UserRepositoryInterface::class)->findByIdentity(email: 'admin@example.com');
        $auth->authenticatedAs($user);
        
        $http->response()
            ->assertStatus(200)
            ->assertBodyContains('permissions')
            ->assertBodyContains('[roles.create]');
    }
    
    public function testEditPermissionsActionFailsIfUserNotFound()
    {
        $auth = $this->fakeAuth();
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'users/44/permissions/edit');
        
        $app = $this->bootingApp();
        $user = $app->get(UserRepositoryInterface::class)->findByIdentity(email: 'admin@example.com');
        $auth->authenticatedAs($user);
        
        $http->response()->assertStatus(404);
    }
    
    public function testEditPermissionsActionFailsWithoutPermission()
    {
        $auth = $this->fakeAuth();
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'users/2/permissions/edit');
        
        $app = $this->bootingApp();
        $user = $app->get(UserRepositoryInterface::class)->findByIdentity(email: 'editor@example.com');
        $auth->authenticatedAs($user);
        
        $http->response()
            ->assertStatus(403)
            ->assertBodyContains('You don\'t have a required "users.permissions" permission.');
    }
    
    public function testUpdatePermissionsAction()
    {
        $auth = $this->fakeAuth();
        $http = $this->fakeHttp();
        $http->request(method: 'PUT', uri: 'users/1/permissions')->body([
            'apply_permissions' => '1',
            'permissions' => ['backend', 'roles', 'roles.create', 'invalid'],
        ]);
        
        $app = $this->bootingApp();
        $user = $app->get(UserRepositoryInterface::class)->findByIdentity(email: 'admin@example.com');
        $auth->authenticatedAs($user);
        
        $user = $app->get(UserRepositoryInterface::class)->findById(1);
        $this->assertSame([], $user->getPermissions());
        
        $http->response()
            ->assertStatus(302)
            ->assertLocation($this->generateIndexUri());
        
        $user = $app->get(UserRepositoryInterface::class)->findById(1);
        $this->assertSame(['backend', 'roles', 'roles.create'], $user->getPermissions());
    }
    
    public function testUpdatePermissionsActionNotAppliedIfNotSet()
    {
        $auth = $this->fakeAuth();
        $http = $this->fakeHttp();
        $http->request(method: 'PUT', uri: 'users/1/permissions')->body([
            'apply_permissions' => '0',
            'permissions' => ['backend', 'roles', 'roles.create', 'invalid'],
        ]);
        
        $app = $this->bootingApp();
        $user = $app->get(UserRepositoryInterface::class)->findByIdentity(email: 'admin@example.com');
        $auth->authenticatedAs($user);
        
        $user = $app->get(UserRepositoryInterface::class)->findById(1);
        $this->assertSame([], $user->getPermissions());
        
        $http->response()
            ->assertStatus(302)
            ->assertLocation($this->generateIndexUri());
        
        $user = $app->get(UserRepositoryInterface::class)->findById(1);
        $this->assertSame([], $user->getPermissions());
    }
    
    public function testUpdatePermissionsActionFailsIfUserNotFound()
    {
        $auth = $this->fakeAuth();
        $http = $this->fakeHttp();
        $http->request(method: 'PUT', uri: 'users/23/permissions')->body([
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
        $http->request(method: 'PUT', uri: 'users/1/permissions')->body([
            'permissions' => ['backend', 'roles', 'roles.create', 'invalid'],
        ]);
        
        $app = $this->bootingApp();
        $user = $app->get(UserRepositoryInterface::class)->findByIdentity(email: 'editor@example.com');
        $auth->authenticatedAs($user);
        
        $http->response()
            ->assertStatus(403)
            ->assertBodyContains('You don\'t have a required "users.permissions" permission.');
    }
}