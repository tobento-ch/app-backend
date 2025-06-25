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
 
namespace Tobento\App\Backend\Controller;

use Psr\Http\Message\ResponseInterface;
use Tobento\App\User\RoleInterface;
use Tobento\App\User\RoleRepositoryInterface;
use Tobento\App\Http\Exception\NotFoundException;
use Tobento\Service\Acl\AclInterface;
use Tobento\Service\Requester\RequesterInterface;
use Tobento\Service\Responser\ResponserInterface;
use Tobento\Service\Routing\RouterInterface;
use Tobento\Service\Translation\TranslatorInterface;

class RolePermissionsController
{
    use Traits\InteractsWithAcl;
    
    /**
     * Returns the permissions edit response.
     *
     * @param int|string $id
     * @param RequesterInterface $requester
     * @param ResponserInterface $responser
     * @param RoleRepositoryInterface $roleRepository
     * @param AclInterface $acl
     * @param RouterInterface $router
     * @param TranslatorInterface $translator
     * @return ResponseInterface
     */
    public function edit(
        int|string $id,
        RequesterInterface $requester,
        ResponserInterface $responser,
        RoleRepositoryInterface $roleRepository,
        AclInterface $acl,
        RouterInterface $router,
        TranslatorInterface $translator,
    ): ResponseInterface {
        if (is_null($role = $roleRepository->findById($id))) {
            throw new NotFoundException();
        }
        
        return $responser->render(
            view: 'roles/permissions/edit',
            data: [
                'title' => $translator->trans(':name permissions', [':name' => $role->name()]),
                'formAction' => (string)$router->url('roles.permissions.update', ['id' => $role->id()]),
                'cancelUrl' => (string)$router->url('roles.index'),
                'role' => $role,
                'areas' => $this->sortAclRules($acl, $role),
            ],
        );
    }
    
    /**
     * Returns the permissions update response.
     *
     * @param int|string $id
     * @param RequesterInterface $requester
     * @param ResponserInterface $responser
     * @param RoleRepositoryInterface $roleRepository
     * @param AclInterface $acl
     * @param RouterInterface $router
     * @return ResponseInterface
     */
    public function update(
        int|string $id,
        RequesterInterface $requester,
        ResponserInterface $responser,
        RoleRepositoryInterface $roleRepository,
        AclInterface $acl,
        RouterInterface $router,
    ): ResponseInterface {
        if (is_null($role = $roleRepository->findById($id))) {
            throw new NotFoundException();
        }
        
        $verifiedPermissions = $this->verifyPermissions(
            acl: $acl,
            permissions: $requester->input()->get('permissions', []),
            allowedAreas: $role->areas(),
        );

        $roleRepository->updateById(
            id: $role->id(),
            attributes: [
                'permissions' => $verifiedPermissions,
            ],
        );
        
        return $responser->redirect(uri: $router->url('roles.index'));
    }
}