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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tobento\App\User\Authentication\AuthInterface;
use Tobento\App\User\Exception\PermissionDeniedException;
use Tobento\App\User\Middleware\VerifyPermission;
use Tobento\Service\Acl\AclInterface;
use Tobento\Service\Routing\RouterInterface;

/**
 * Allow user to edit his profile.
 */
class AllowUserToEditProfile implements MiddlewareInterface
{
    /**
     * Create a new AllowUserToEditProfile.
     *
     * @param AclInterface $acl
     * @param RouterInterface $router
     */
    public function __construct(
        protected AclInterface $acl,
        protected RouterInterface $router
    ) {}
    
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
        $user = $request->getAttribute(AuthInterface::class)?->getAuthenticated()?->user();
        $route = $this->router->getMatchedRoute();
        
        if (is_null($route) || is_null($user)) {
            return $handler->handle($request);
        }
        
        if (!in_array($route->getName(), ['users.edit', 'users.update'])) {
            return $handler->handle($request);
        }
        
        $id = $route->getParameter('request_parameters')['id'] ?? 0;
        $id = is_numeric($id) ? (int)$id : 0;
        
        if ($user->id() === $id) {
            $this->acl->addPermissions(['users.edit', 'users.update']);
        }
        
        return $handler->handle($request);
    }
}