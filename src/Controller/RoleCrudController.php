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
use Tobento\App\Crud\AbstractCrudController;
use Tobento\App\Crud\Action\ActionsInterface;
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Action;
use Tobento\App\Crud\Button;
use Tobento\App\Crud\Entity\EntityInterface;
use Tobento\App\Crud\Field\FieldsInterface;
use Tobento\App\Crud\Field\FieldInterface;
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Filter\FiltersInterface;
use Tobento\App\Crud\Filter\FilterInterface;
use Tobento\App\Crud\Filter;
use Tobento\App\Notifier\AvailableChannelsInterface;
use Tobento\App\User\RoleRepositoryInterface;
use Tobento\Service\Acl\AclInterface;
use Tobento\Service\Requester\RequesterInterface;
use Tobento\Service\Responser\ResponserInterface;
use Tobento\Service\Validation\Rule\Passes;
use function Tobento\App\Translation\trans;

class RoleCrudController extends AbstractCrudController
{
    /**
     * Must be unique, lowercase and only of [a-z-] characters.
     */
    public const RESOURCE_NAME = 'roles';
    
    /**
     * Create a new RoleCrudController instance.
     *
     * @param RoleRepositoryInterface $repository
     * @param AclInterface $acl
     */
    public function __construct(
        RoleRepositoryInterface $repository,
        protected AclInterface $acl,
    ) {
        $this->repository = $repository;
    }
    
    /**
     * Returns the configured fields.
     *
     * @param ActionInterface $action
     * @return iterable<FieldInterface>|FieldsInterface
     */
    protected function configureFields(ActionInterface $action): iterable|FieldsInterface
    {
        return [
            new Field\PrimaryId('id'),
            
            new Field\Radios(name: 'active', label: trans('Active'))
                ->group(trans('General'))
                ->options(['0' => trans('Inactive'), '1' => trans('Active')])
                ->selected('1')
                ->validate('required|bool')
                ->displayInline()
                ->formatValue(new Field\Formatter\Badge(
                    classes: ['0' => 'text-error', '1' => 'text-success'],
                )),
            
            new Field\Text(name: 'key', label: trans('Key'))
                ->group(trans('General'))
                ->validate([
                    'required',
                    'alpha',
                    'minLen:2',
                    'maxLen:100',
                    new Passes(
                        passes: function(string $value, RoleRepositoryInterface $repo) use ($action): bool {
                            if ($action->entity()->get('key') === $value) {
                                return true;
                            }
                            return is_null($repo->findByKey(key: $value)) ? true : false;
                        },
                        errorMessage: 'Key exists already.',
                    ),
                ]),
            
            new Field\Text(name: 'name', label: trans('Name'))
                ->group(trans('General'))
                ->validate('required|string|minLen:2|maxLen:100'),
            
            new Field\Checkboxes(name: 'areas', label: trans('Areas'))
                ->group(trans('General'))
                ->options(['backend' => 'Backend'])
                ->selected(['backend']),
            
            new Field\Checkboxes(name: 'permissions', label: trans('Permissions'))
                ->formatValue(new Field\Formatter\Badge(limit: 5), 'index')
                ->formatValue(new Field\Formatter\Badge(), 'show')
                ->creatable(false)
                ->editable(false),
        ];
    }
    
    /**
     * Returns the configured actions.
     *
     * @return iterable<ActionInterface>|ActionsInterface
     */
    protected function configureActions(): iterable|ActionsInterface
    {
        $editPermissions = new Button\Link(label: trans('Edit Permissions'), group: 'entity')
            ->name('editPermissions')
            ->linkToRoute('roles.permissions.edit', function(EntityInterface $entity): array {
                return ['id' => $entity->id()];
            });
        
        return [
            new Action\Index(title: trans('Roles'))
                ->addButton($editPermissions)
                ->removeButton('copy')
                ->reorderButtons('editPermissions')
                ->groupButtons(
                    except: ['edit'],
                    button: new Button\Dropdown(label: '', icon: 'dots', group: 'entity')
                        ->name('more')
                        ->raw(),
                )
                ->displayButtonIf('editPermissions', $this->acl->can('roles.permissions'))
                ->displayButtonIf('delete', fn (EntityInterface $entity): bool =>
                    !in_array($entity->get('key'), ['administrator'])
                ),
            
            new Action\Create(title: trans('New Role')),
            
            new Action\Store(),
            
            new Action\Edit(title: trans('Edit Role')),
            
            new Action\Update(),
            
            new Action\Delete()
                ->undeletable(fn (EntityInterface $entity): bool => in_array($entity->get('key'), ['administrator'])),
            
            new Action\BulkDelete(),
            
            new Action\Show(title: trans('Show Role')),
            
            new Action\ShowJson(),
        ];
    }
    
    /**
     * Returns the configured filters.
     *
     * @param ActionInterface $action
     * @return iterable<FilterInterface>|FiltersInterface
     */
    protected function configureFilters(ActionInterface $action): iterable|FiltersInterface
    {
        return [
            ...new Filter\Fields()->fields($action->fields())->toFilters(),
            
            new Filter\FieldsSortOrder(),
            
            new Filter\ModalButton()->group('header'),
            
            new Filter\Group(name: 'group-columns')->group('modal')->label(trans('Columns'))->open(false),
            
            new Filter\Columns()
                ->group('group-columns')
                ->default('name', 'key', 'active', 'actions'),
            
            new Filter\Group(name: 'group-pagination')->group('modal')->label(trans('Pagination'))->open(false),
            
            new Filter\PaginationItemsPerPage()
                ->group('group-pagination')
                ->open(false),
            
            new Filter\Pagination()->group('footer'),
        ];
    }
}