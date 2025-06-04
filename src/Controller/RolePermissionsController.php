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

use ArrayAccess;
use Psr\Http\Message\ResponseInterface;
use Tobento\App\User\RoleInterface;
use Tobento\App\User\RoleRepositoryInterface;
use Tobento\App\Http\Exception\NotFoundException;
use Tobento\Service\Acl\AclInterface;
use Tobento\Service\Collection\Arr;
use Tobento\Service\Requester\RequesterInterface;
use Tobento\Service\Responser\ResponserInterface;
use Tobento\Service\Routing\RouterInterface;

class RolePermissionsController
{
    /**
     * Returns the permissions edit response.
     *
     * @param int|string $id
     * @param RequesterInterface $requester
     * @param ResponserInterface $responser
     * @param RoleRepositoryInterface $roleRepository
     * @param AclInterface $acl
     * @return ResponseInterface
     */
    public function edit(
        int|string $id,
        RequesterInterface $requester,
        ResponserInterface $responser,
        RoleRepositoryInterface $roleRepository,
        AclInterface $acl,
    ): ResponseInterface {
        if (is_null($role = $roleRepository->findById($id))) {
            throw new NotFoundException();
        }
        
        return $responser->render(
            view: 'roles/permissions/edit',
            data: [
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
        
        // Verify permissions:
        $verified = [];
        $allowedAreaKeys = $role->areas();
        $permissions = $requester->input()->get('permissions', []);
        
        foreach($permissions as $permission) {
            if (!is_string($permission)) {
                continue;
            }
            
            if (is_null($rule = $acl->getRule($permission))) {
                continue;
            }
            
            if (! $rule->requiresPermission()) {
                continue;
            }
            
            if (!in_array($rule->getArea(), $allowedAreaKeys)) {
                continue;
            }
            
            $verified[] = $rule->getKey();
        }
        
        $verified = array_unique($verified);

        // Update permissions:
        $roleRepository->updateById(
            id: $role->id(),
            attributes: [
                'permissions' => $verified,
            ],
        );
        
        // Return the response:
        return $responser->redirect(uri: $router->url('roles.index'));
    }
    
    /**
     * Returns the the acl rules sorted and mapped by area.
     *
     * @param AclInterface $acl
     * @param RoleInterface $role
     * @return ArrayAccess|array
     */
    protected function sortAclRules(AclInterface $acl, RoleInterface $role): ArrayAccess|array
    {
        $rulesByArea = [];
        
        foreach($acl->getRules() as $rule) {
            if (! $rule->requiresPermission()) {
                continue;
            }
            
            if ($rule->getTitle() === $rule->getKey()) {
                $rule->title('');
            }
            
            $rulesByArea[$rule->getArea()][] = $rule;
        }

        ksort($rulesByArea);

        $rulesByArea = Arr::only($rulesByArea, $role->areas(), []);
        
        foreach(array_keys($rulesByArea) as $area) {
            ksort($rulesByArea[$area]);
        }

        return $rulesByArea;
    }    
}