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

namespace Tobento\App\Backend\Middleware;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Tobento\Service\Acl\AclInterface;
use Tobento\App\User\Exception\PermissionDeniedException;
use Tobento\App\User\Middleware\VerifyPermission;

/**
 * Prevents users accessing the backend without the right permission.
 */
class VerifyBackendPermission extends VerifyPermission
{
    /**
     * Create a new VerifyBackendPermission.
     *
     * @param AclInterface $acl
     * @param array<array-key, string> $exceptRoutes
     * @param null|string $permission
     * @param string $message
     * @param string $messageLevel
     * @param null|string $redirectUri
     * @param null|string $redirectRoute
     */
    public function __construct(
        protected AclInterface $acl,
        protected array $exceptRoutes = [],
        protected null|string $permission = 'backend',
        protected string $message = '',
        protected string $messageLevel = '',
        protected null|string $redirectUri = null,
        protected null|string $redirectRoute = null,
    ) {
        if (empty($exceptRoutes)) {
            $this->exceptRoutes = [
                'login', 'login.store', 'logout',
                'twofactor.code.show', 'twofactor.code.resend', 'twofactor.code.verify', 
            ];
        }
    }
    
    /**
     * Process the middleware.
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws PermissionDeniedException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $routeName = $request->getAttribute('route.name');
        
        if (in_array($routeName, $this->exceptRoutes)) {
            return $handler->handle($request);
        }

        return parent::process($request, $handler);
    }
}