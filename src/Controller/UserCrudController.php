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

use Psr\Clock\ClockInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tobento\App\Crud\AbstractCrudController;
use Tobento\App\Crud\Action;
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Action\ActionsInterface;
use Tobento\App\Crud\Button;
use Tobento\App\Crud\Entity\Entity;
use Tobento\App\Crud\Entity\EntityInterface;
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Field\FieldInterface;
use Tobento\App\Crud\Field\FieldsInterface;
use Tobento\App\Crud\Filter;
use Tobento\App\Crud\Filter\FiltersInterface;
use Tobento\App\Crud\Filter\FilterInterface;
use Tobento\App\Crud\Input\InputInterface;
use Tobento\App\Notifier\AvailableChannelsInterface;
use Tobento\App\User\AddressRepositoryInterface;
use Tobento\App\User\Authentication\AuthInterface;
use Tobento\App\User\PasswordHasherInterface;
use Tobento\App\User\UserRepositoryInterface;
use Tobento\Service\Acl\AclInterface;
use Tobento\Service\Language\LanguagesInterface;
use Tobento\Service\Support\Str;
use Tobento\Service\Validation\Rule\Passes;
use Tobento\Service\View\ViewInterface;
use function Tobento\App\Translation\trans;

class UserCrudController extends AbstractCrudController
{
    /**
     * Must be unique, lowercase and only of [a-z-] characters.
     */
    public const RESOURCE_NAME = 'users';
    
    /**
     * Create a new UserCrudController instance.
     *
     * @param UserRepositoryInterface $repository
     * @param AddressRepositoryInterface $addressRepository
     * @param AclInterface $acl
     * @param ServerRequestInterface $request
     */
    public function __construct(
        UserRepositoryInterface $repository,
        protected AddressRepositoryInterface $addressRepository,
        protected AclInterface $acl,
        protected ServerRequestInterface $request,
    ) {
        $this->repository = $repository;
    }
    
    /**
     * Returns the repository.
     *
     * @return UserRepositoryInterface
     */
    public function repository(): UserRepositoryInterface
    {
        return $this->repository;
    }
    
    /**
     * Create entity from object.
     *
     * @param object $object
     * @return EntityInterface
     */
    public function createEntityFromObject(object $object): EntityInterface
    {
        $user = $object->toArray();
        $user['address'] = $object->address('primary')->toArray();
        return new Entity($user);
    }
    
    /**
     * Returns the configured fields.
     *
     * @param string $actionName
     * @return iterable<FieldInterface>|FieldsInterface
     */
    protected function configureFields(ActionInterface $action): iterable|FieldsInterface
    {
        $fields = [
            Field\PrimaryId::new('id'),
            Field\Radios::new(name: 'active', label: trans('Active'))
                ->group(trans('Account'))
                ->options(['0' => trans('Inactive'), '1' => trans('Active')])
                ->selected(value: '0', action: 'create')
                ->validate(store: 'required|bool', update: 'sometimes|required|bool')
                ->displayInline(),
            Field\Select::new(name: 'role_key', label: trans('Role'))
                ->group(trans('Account'))
                ->options($this->acl->roles()->area('backend')->except(['guest'])->column('name', 'key'))
                ->disabled(disabled: fn(AclInterface $acl) => $acl->cant('users.role'), action: 'edit|update')
                ->validate(store: 'required', update: 'sometimes|required'),
            Field\Text::new(name: 'address.name', label: trans('Name'))
                ->group(trans('Account'))
                ->validate(store: 'required|string|maxLen:150', update: 'sometimes|required|string|maxLen:150'),
            Field\Text::new(name: 'email', label: trans('E-Mail'))
                ->group(trans('Account'))
                ->type('email')
                ->validate([
                    'required_without:smartphone',
                    'email',
                    'maxLen:150',
                    new Passes(
                        passes: function(mixed $value, UserRepositoryInterface $repo) use ($action): bool {
                            if ($action->entity()->get('email') === $value) {
                                return true;
                            }
                            return is_null($repo->findByIdentity(email: $value)) ? true : false;
                        },
                        errorMessage: 'E-mail exists already.',
                    ),
                ])
                ->requiredText('required without smartphone'),
            Field\Text::new(name: 'smartphone', label: trans('Smartphone'))
                ->group(trans('Account'))
                ->validate([
                    'required_without:email',
                    'digit',
                    'minLen:8',
                    'maxLen:150',
                    new Passes(
                        passes: function(mixed $value, UserRepositoryInterface $repo) use ($action): bool {
                            if ($action->entity()->get('smartphone') === $value) {
                                return true;
                            }
                            return is_null($repo->findByIdentity(smartphone: $value)) ? true : false;
                        },
                        errorMessage: 'Smartphone exists already.',
                    ),
                ])
                ->requiredText('required without e-mail')
                ->infoText('Country code followed by the phone number, e.g. 41791234567'),
            Field\Text::new('password', $action->name() === 'edit' ? trans('New Password') : trans('Password'))
                ->group(trans('Account'))
                ->type('password')
                ->process(
                    action: 'store|update',
                    processor: function (
                        FieldInterface $field,
                        InputInterface $input,
                        PasswordHasherInterface $passwordHasher
                    ): void {
                        if (! $input->has($field->name())) {
                            return;
                        }
                        
                        if (empty($input->get($field->name()))) {
                            $input->delete($field->name());
                            return;
                        }
                        
                        $hashedPassword = $passwordHasher->hash(plainPassword: $input->get($field->name()));
                        $input->set($field->name(), $hashedPassword);
                    }
                )
                ->validate(
                    store: 'required|string|minLen:8|maxLen:150',
                    update: 'string|minLen:8|maxLen:150',
                )
                ->indexable(false)
                ->showable(false)
                ->value('')
                ->attributes(['autocomplete' => 'new-password']),
            Field\File::new(name: 'image', label: 'Avatar')
                ->group(trans('Account'))
                ->fileSource(function(Field\FileSource $fs): void {
                    $fs->allowedExtensions('jpg', 'png');
                       //->imageEditor(template: 'default');
                })
                ->fields(
                    Field\Text::new('alt', 'Alternative Text'),
                )
                ->storeFilenameTo('alt'),
            Field\Select::new(name: 'locale', label: trans('Preferred Language'))
                ->group(trans('General'))
                ->options(fn(LanguagesInterface $languages): array => $languages->column('name', 'locale')),
            Field\Text::new(name: 'date_created', label: trans('Registration Date'))
                ->group(trans('General'))
                ->type('datetime-local')
                ->creatable(false),
            Field\Checkboxes::new('settings.preferred_notification_channels', 'Preferred Channels')
                ->group(trans('Notifications'))
                ->options(fn(AvailableChannelsInterface $channels): AvailableChannelsInterface =>
                    $channels
                          ->only(['mail', 'sms', 'storage'])
                          ->withTitle('storage', trans('Account'))
                          ->sortByTitle()
                )
                ->process(
                    action: 'index|show',
                    processor: function (FieldInterface $field, AvailableChannelsInterface $channels): void {
                        $channelNames = $field->entity()->get($field->name(), []);
                        $titles = $channels->only($channelNames)->withTitle('storage', trans('Account'))->titlesToString();
                        $field->html(Str::esc($titles));
                    }
                ),
            Field\Radios::new(name: 'settings.twofactor', label: trans('Two-Factor Authentication'))
                ->group(trans('Security'))
                ->options(['0' => trans('Disabled'), '1' => trans('Enabled')])
                ->selected(value: '0', action: 'create')
                ->displayInline()
                ->infoText('When enabled, your account is secured with Two-Factor Authentication.'),
        ];
        
        $user = $this->request->getAttribute(AuthInterface::class)?->getAuthenticated()?->user();
        
        if ($action->entity()->id() === $user?->id()) {
            $fields[] = Field\Html::new(name: 'channels')
                ->content(function(ViewInterface $view, AvailableChannelsInterface $channels) use ($user): string {
                    return $view->render('user/verification/channels', [
                        'channels' => $channels,
                        'user' => $user,
                    ]);
                })
                ->group(trans('Channel Verifications'));
        }
        
        return $fields;
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
            ->linkToRoute('users.permissions.edit', function(EntityInterface $entity): array {
                return ['id' => $entity->id()];
            });
        
        return [
            Action\Index::new(title: trans('Users'))
                ->addButton($editPermissions)
                ->reorderButtons('editPermissions')
                ->removeButton('copy')
                ->groupButtons(
                    except: ['edit'],
                    button: Button\Dropdown::new(label: '', icon: 'dots', group: 'entity')
                        ->name('more')
                        ->raw(),
                )
                ->displayButtonIf('editPermissions', $this->acl->can('users.permissions'))
                ->displayButtonIf('delete', fn (EntityInterface $entity): bool => !in_array($entity->id(), [1])),
            Action\Create::new(title: trans('New User'))
                ->removeButton('copy'),
            Action\Store::new(),
            Action\Edit::new(title: trans('Edit User'))
                ->removeButton('copy')
                ->displayButtonIf('new', $this->acl->can('users.create'))
                ->displayButtonIf('close', $this->acl->can('users'))
                ->displayButtonIf('cancel', $this->acl->can('users')),
            //Action\Copy::new(title: 'Copy user'),
            Action\Update::new(),
            Action\Delete::new()
                ->undeletable([1]),
            Action\BulkDelete::new(),
            //Action\BulkEdit::new(),
            Action\Show::new(title: trans('Show User')),
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
            Filter\EditableColumns::new('active' ,'role_key', 'address.name')->group('group-columns'),
            Filter\Group::new(name: 'group-pagination')->group('modal')->label(trans('Pagination'))->open(false),
            Filter\PaginationItemsPerPage::new()
                ->group('group-pagination')
                ->open(false),
            //Filter\Pagination::new()->group('header'),
            Filter\Pagination::new()->group('footer'),
        ];
    }

    /**
     * Find entities.
     *
     * @param FiltersInterface $filters
     * @return iterable The found entities.
     */
    public function findEntities(FiltersInterface $filters): iterable
    {
        $userFilters = [];
        $addressFilters = [];
        $idName = $this->entityIdName();
        
        foreach($filters->getWhereParameters() as $name => $where) {
            if (is_string($name) && str_starts_with($name, 'address.')) {
                $addressFilters[substr($name, 8)] = $where;
            } else {
                $userFilters[$name] = $where;
            }
        }
        
        if (!empty($addressFilters)) {
            $ids = $this->addressRepository->findColumn(
                column: $idName,
                where: $addressFilters,
                orderBy: $filters->getOrderByParameters(),
                limit: $filters->getLimitParameter(),
            );
            
            if (isset($userFilters[$idName]) && is_array($userFilters[$idName])) {
                $userFilters[$idName] = array_merge($userFilters[$idName], ['in' => $ids]);
            } else {
                $userFilters[$idName] = ['in' => $ids];
            }
        }
        
        return $this->repository()->findAll(
            where: $userFilters,
            orderBy: $filters->getOrderByParameters(),
            limit: $filters->getLimitParameter(),
        );
    }

    /**
     * Store entity.
     *
     * @param array $attributes
     * @return object The created entity
     */
    public function storeEntity(array $attributes): object
    {
        return $this->repository()->createWithAddress(
            user: $attributes,
            address: $attributes['address'] ?? [],
        );
    }
    
    /**
     * Update entity.
     *
     * @param int|string $id
     * @param array $attributes
     * @param EntityInterface $entity
     * @return object The updated entity
     */
    public function updateEntity(int|string $id, array $attributes, EntityInterface $entity): object
    {
        if (isset($attributes['settings']) && is_array($attributes['settings'])) {
            $settings = $entity->get('settings', []);
            $attributes['settings'] = array_merge($settings, $attributes['settings']);
        }
        
        return $this->repository()->updateWithAddress(
            id: $id,
            user: $attributes,
            address: $attributes['address'] ?? [],
        );
    }
}