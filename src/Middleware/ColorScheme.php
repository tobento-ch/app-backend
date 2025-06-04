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
use Tobento\Service\Cookie\CookiesInterface;
use Tobento\Service\Cookie\CookieValuesInterface;
use Tobento\Service\Requester\RequesterInterface;
use Tobento\Service\View\ViewInterface;

/**
 * ColorScheme.
 */
class ColorScheme implements MiddlewareInterface
{
    /**
     * Create a new ColorScheme.
     *
     * @param ViewInterface $view,
     */
    public function __construct(
        protected ViewInterface $view,
        protected RequesterInterface $requester,
    ) {}
    
    /**
     * Process the middleware.
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $cookieValues = $request->getAttribute(CookieValuesInterface::class);
        
        $colorScheme = $cookieValues->get('color-scheme', 'auto');

        $colorScheme = $this->requester->input()->get('color-scheme', $colorScheme);
        
        if (!in_array($colorScheme, ['auto', 'light', 'dark'])) {
            $colorScheme = 'auto';
        }
        
        $cookies = $request->getAttribute(CookiesInterface::class);

        $cookies->add('color-scheme', $colorScheme);
                
        if ($colorScheme !== 'auto') {
            /** @psalm-suppress UndefinedInterfaceMethod */
            $this->view->tagAttributes('body')->add('class', $colorScheme);
        }
        
        $request = $request->withAttribute('color-scheme', $colorScheme);
        
        return $handler->handle($request);
    }
}