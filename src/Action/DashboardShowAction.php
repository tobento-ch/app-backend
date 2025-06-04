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
 
namespace Tobento\App\Backend\Action;

use Psr\Http\Message\ResponseInterface;
use Tobento\App\Backend\Card\DashboardCards;
use Tobento\Service\Responser\ResponserInterface;

class DashboardShowAction
{
    /**
     * Returns the dashboard response.
     *
     * @param DashboardCards $cards
     * @param ResponserInterface $responser
     * @return ResponseInterface
     */
    public function __invoke(
        DashboardCards $cards,
        ResponserInterface $responser,
    ): ResponseInterface {
        return $responser->render(
            view: 'dashboard',
            data: [
                'cards' => $cards,
            ],
        );
    }
}