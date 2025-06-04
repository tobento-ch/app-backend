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
 
namespace Tobento\App\Backend\User;

use Tobento\App\User\Web\Feature\Login;
use Tobento\App\User\UserInterface;

/**
 * Login feature
 */
class LoginFeature extends Login
{
    /**
     * Returns true if the user is required to perform two factor authentication, otherwise false.
     *
     * @param UserInterface $user
     * @return bool
     */
    protected function isTwoFactorRequiredFor(UserInterface $user): bool
    {
        if ($user->setting('twofactor', '0')) {
            return true;
        }
        
        return false;
    }
}