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

use Tobento\App\User\Authentication\Token\TokenInterface;
use Tobento\App\User\Authenticator\TokenAuthenticator as BaseTokenAuthenticator;
use Tobento\App\User\Authenticator\TokenPasswordHashVerifier;
use Tobento\App\User\Authenticator\TokenVerifierInterface;
use Tobento\App\User\Exception\AuthenticationException;
use Tobento\App\User\Exception\InvalidateTokenException;
use Tobento\App\User\UserInterface;
use Tobento\App\User\UserRepositoryInterface;
use Tobento\Service\Acl\AclInterface;

class TokenAuthenticator extends BaseTokenAuthenticator
{
    public function __construct(
        protected AclInterface $acl,
        protected UserRepositoryInterface $userRepository,
        protected null|TokenVerifierInterface $tokenVerifier = null,
    ) {
        if (is_null($tokenVerifier)) {
            $this->tokenVerifier = new TokenPasswordHashVerifier(
                issuers: [],
                name: 'passwordHash',
            );
        }
    }
    
    /**
     * Authenticate token.
     *
     * @param TokenInterface $token
     * @return UserInterface
     * @throws AuthenticationException If authentication fails.
     */
    public function authenticate(TokenInterface $token): UserInterface
    {
        $user = parent::authenticate($token);
        
        if (! $user->active()) {
            throw new InvalidateTokenException(message: 'User must be active', token: $token);
        }
        
        if ($token->authenticatedVia() === 'loginform-twofactor') {
            
            $role = $this->acl->getRole('guest');
            
            if (is_null($role)) {
                throw new AuthenticationException('Guest role not set up');
            }
            
            $user->setRole($role);
            $user->setRoleKey($role->key());
            $user->setPermissions([]); // clear user specific permissions too.
        }
        
        return $user;
    }
}