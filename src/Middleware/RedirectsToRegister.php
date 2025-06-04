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
use Tobento\App\User\Authentication\AuthInterface;
use Tobento\App\User\Authentication\AuthenticatedInterface;
use Tobento\App\User\Exception\AuthorizationException;

/**
 * Redirects user to register page.
 */
class RedirectsToRegister implements MiddlewareInterface
{
    /**
     * Create a new RedirectsToRegister.
     *
     * @param array $exceptRoutes
     */
    public function __construct(
        protected array $exceptRoutes = [],
    ) {
        if (empty($exceptRoutes)) {
            $this->exceptRoutes = ['register', 'register.store'];
        }
    }
    
    /**
     * Process the middleware.
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws AuthorizationException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $routeName = $request->getAttribute('route.name');
        
        if (!in_array($routeName, $this->exceptRoutes)) {
            throw new AuthorizationException(
                message: '',
                redirectRoute: 'register',
            );
        }

        return $handler->handle($request);
    }
}