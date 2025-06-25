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
use Tobento\App\User\UserRepositoryInterface;
use Tobento\App\Http\Exception\NotFoundException;
use Tobento\Service\Acl\AclInterface;
use Tobento\Service\Requester\RequesterInterface;
use Tobento\Service\Responser\ResponserInterface;
use Tobento\Service\Routing\RouterInterface;
use Tobento\Service\Translation\TranslatorInterface;

class UserPermissionsController
{
    use Traits\InteractsWithAcl;
    
    /**
     * Returns the permissions edit response.
     *
     * @param int|string $id
     * @param RequesterInterface $requester
     * @param ResponserInterface $responser
     * @param UserRepositoryInterface $userRepository
     * @param AclInterface $acl
     * @param RouterInterface $router
     * @param TranslatorInterface $translator
     * @return ResponseInterface
     */
    public function edit(
        int|string $id,
        RequesterInterface $requester,
        ResponserInterface $responser,
        UserRepositoryInterface $userRepository,
        AclInterface $acl,
        RouterInterface $router,
        TranslatorInterface $translator,        
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
                'title' => $translator->trans(':name permissions', [':name' => $user->greeting()]),
                'formAction' => (string)$router->url('users.permissions.update', ['id' => $user->id()]),
                'cancelUrl' => (string)$router->url('users.index'),
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

        $verifiedPermissions = $this->verifyPermissions(
            acl: $acl,
            permissions: $requester->input()->get('permissions', []),
            allowedAreas: $user->role()->areas(),
        );

        $applyPermissions = $requester->input()->get('apply_permissions') ? '1' : '0';

        $settings = $user->getSettings();
        $settings['apply_permissions'] = $applyPermissions;
        
        if ($applyPermissions) {
            $settings['permissions'] = [];
        } else {
            $settings['permissions'] = $verifiedPermissions;
            $verifiedPermissions = [];
        }
        
        $userRepository->updateById(
            id: $user->id(),
            attributes: [
                'permissions' => $verifiedPermissions,
                'settings' => $settings,
            ],
        );
        
        return $responser->redirect(uri: $router->url('users.index'));
    }
}