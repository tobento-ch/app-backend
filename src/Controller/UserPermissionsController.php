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
use Tobento\App\User\UserRepositoryInterface;
use Tobento\App\Http\Exception\NotFoundException;
use Tobento\Service\Acl\AclInterface;
use Tobento\Service\Collection\Arr;
use Tobento\Service\Requester\RequesterInterface;
use Tobento\Service\Responser\ResponserInterface;
use Tobento\Service\Routing\RouterInterface;

class UserPermissionsController
{
    /**
     * Returns the permissions edit response.
     *
     * @param int|string $id
     * @param RequesterInterface $requester
     * @param ResponserInterface $responser
     * @param UserRepositoryInterface $userRepository
     * @param AclInterface $acl
     * @return ResponseInterface
     */
    public function edit(
        int|string $id,
        RequesterInterface $requester,
        ResponserInterface $responser,
        UserRepositoryInterface $userRepository,
        AclInterface $acl,
    ): ResponseInterface {
        if (is_null($user = $userRepository->findById($id))) {
            throw new NotFoundException();
        }
        
        $userPermissions = $user->getPermissions();
        
        if (empty($userPermissions)) {
            $settingsPermissions = $user->setting('permissions', []);
            $userPermissions = is_array($settingsPermissions) ? $settingsPermissions : [];
        }
        
        return $responser->render(
            view: 'user/permissions/edit',
            data: [
                'user' => $user,
                'areas' => $this->sortAclRules($acl, $user->role()),
                'userPermissions' => $userPermissions,
            ],
        );
    }
    
    /**
     * Returns the permissions update response.
     *
     * @param int|string $id
     * @param RequesterInterface $requester
     * @param ResponserInterface $responser
     * @param UserRepositoryInterface $userRepository
     * @param AclInterface $acl
     * @param RouterInterface $router
     * @return ResponseInterface
     */
    public function update(
        int|string $id,
        RequesterInterface $requester,
        ResponserInterface $responser,
        UserRepositoryInterface $userRepository,
        AclInterface $acl,
        RouterInterface $router,
    ): ResponseInterface {
        if (is_null($user = $userRepository->findById($id))) {
            throw new NotFoundException();
        }
        
        // Verify permissions:
        $verified = [];
        $allowedAreaKeys = $user->role()->areas();
        $applyPermissions = $requester->input()->get('apply_permissions') ? '1' : '0';
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
        $settings = $user->getSettings();
        $settings['apply_permissions'] = $applyPermissions;
        
        if ($applyPermissions) {
            $settings['permissions'] = [];
        } else {
            $settings['permissions'] = $verified;
            $verified = [];
        }
        
        $userRepository->updateById(
            id: $user->id(),
            attributes: [
                'permissions' => $verified,
                'settings' => $settings,
            ],
        );
        
        // Return the response:
        return $responser->redirect(uri: $router->url('users.index'));
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