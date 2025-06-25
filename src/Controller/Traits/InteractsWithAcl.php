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
 
namespace Tobento\App\Backend\Controller\Traits;

use ArrayAccess;
use Tobento\App\User\RoleInterface;
use Tobento\Service\Acl\AclInterface;
use Tobento\Service\Collection\Arr;

trait InteractsWithAcl
{
    /**
     * Returns the verified permissions.
     *
     * @param AclInterface $acl
     * @param array $permissions
     * @param array<array-key, string> $allowedAreas
     * @return array
     */
    protected function verifyPermissions(AclInterface $acl, array $permissions, array $allowedAreas): array
    {
        $verified = [];
        
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
            
            if (!in_array($rule->getArea(), $allowedAreas)) {
                continue;
            }
            
            $verified[] = $rule->getKey();
        }
        
        return array_unique($verified);
    }
    
    /**
     * Returns the acl rules sorted and mapped by area.
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
            
            $rulesByArea[$rule->getArea()][$rule->getKey()] = $rule;
        }

        ksort($rulesByArea);

        $rulesByArea = Arr::only($rulesByArea, $role->areas(), []);
        
        foreach(array_keys($rulesByArea) as $area) {
            ksort($rulesByArea[$area]);
        }
        
        return $rulesByArea;
    }    
}