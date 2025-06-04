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
     * @param string $actionName
     * @return iterable<FieldInterface>|FieldsInterface
     */
    protected function configureFields(ActionInterface $action): iterable|FieldsInterface
    {
        return [
            Field\PrimaryId::new('id'),
            Field\Radios::new(name: 'active', label: trans('Active'))
                ->group(trans('General'))
                ->options(['0' => trans('Inactive'), '1' => trans('Active')])
                ->selected('1')
                ->validate('required|bool')
                ->displayInline(),
            Field\Text::new(name: 'key', label: trans('Key'))
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
            Field\Text::new(name: 'name', label: trans('Name'))
                ->group(trans('General'))
                ->validate('required|string|minLen:2|maxLen:100'),
            Field\Checkboxes::new('areas', trans('Areas'))
                ->group(trans('General'))
                ->options(['backend' => 'Backend'])
                ->selected(['backend']),
                //->indexable(false),
            Field\Text::new('permissions')
                ->indexable(false)
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
        $editPermissions = Button\Link::new(label: trans('Edit Permissions'), group: 'entity')
            ->name('editPermissions')
            ->linkToRoute('roles.permissions.edit', function(EntityInterface $entity): array {
                return ['id' => $entity->id()];
            });
        
        return [
            Action\Index::new(title: trans('Roles'))
                ->addButton($editPermissions)
                ->removeButton('copy')
                ->reorderButtons('editPermissions')
                ->groupButtons(
                    except: ['edit'],
                    button: Button\Dropdown::new(label: '', icon: 'dots', group: 'entity')
                        ->name('more')
                        ->raw(),
                )
                ->displayButtonIf('editPermissions', $this->acl->can('roles.permissions'))
                ->displayButtonIf('delete', fn (EntityInterface $entity): bool =>
                    !in_array($entity->get('key'), ['administrator'])
                ),
            Action\Create::new(title: trans('New Role')),
            Action\Store::new(),
            Action\Edit::new(title: trans('Edit Role')),
            //Action\Copy::new(title: trans('Copy Role')),
            Action\Update::new(),
            Action\Delete::new()
                ->undeletable(fn (EntityInterface $entity): bool => in_array($entity->get('key'), ['administrator'])),
            Action\BulkDelete::new(),
            //Action\BulkEdit::new(),
            Action\Show::new(title: trans('Show Role')),
            Action\ShowJson::new(),
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
            ...Filter\Fields::new()->fields($action->fields())->toFilters(),
            Filter\FieldsSortOrder::new(),
            Filter\ModalButton::new()->group('header'),
            Filter\Group::new(name: 'group-columns')->group('modal')->label(trans('Columns'))->open(false),
            Filter\Columns::new()->group('group-columns'),
            Filter\Group::new(name: 'group-pagination')->group('modal')->label(trans('Pagination'))->open(false),
            Filter\PaginationItemsPerPage::new()
                ->group('group-pagination')
                ->open(false),
            //Filter\Pagination::new()->group('header'),
            Filter\Pagination::new()->group('footer'),
        ];
    }
}