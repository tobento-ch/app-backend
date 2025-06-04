# App Backend

The App Backend is a minimal admin panel using the following main app bundles:

* [Apps](https://github.com/tobento-ch/apps) to run the backend in its own application
* [App User Web](https://github.com/tobento-ch/app-user-web) for authentication
* [App Crud](https://github.com/tobento-ch/app-crud) for users and roles CRUD operations
* [App Media](https://github.com/tobento-ch/app-media) to support media
* [App Search](https://github.com/tobento-ch/app-search) to search apps content
* [App Card](https://github.com/tobento-ch/app-card) to display cards on dashboard

## Table of Contents

- [Getting Started](#getting-started)
    - [Requirements](#requirements)
- [Documentation](#documentation)
    - [App](#app)
    - [Backend Boot](#backend-boot)
    - [Adding Boots](#adding-boots)
    - [Adding Dashboard Cards](#adding-dashboard-cards)
    - [Adding Searchables](#adding-searchables)
    - [Customization](#customization)
        - [Customize Via App](#customize-via-app)
        - [Customize Via App Config](#customize-via-app-config)
    - [Testing](#testing)
- [Credits](#credits)
___

# Getting Started

Add the latest version of the app backend project running this command.

```
composer require tobento/app-backend
```

## Requirements

- PHP 8.0 or greater

# Documentation

## App

Check out the [**App Skeleton**](https://github.com/tobento-ch/app-skeleton) if you are using the skeleton.

You may also check out the [**App**](https://github.com/tobento-ch/app) to learn more about the app in general.

## Backend Boot

The backend boot is extended :

```php
use Tobento\App\AppFactory;

// Create the app
$app = (new AppFactory())->createApp();

// Add directories:
$app->dirs()
    ->dir(realpath(__DIR__.'/../'), 'root')
    ->dir(realpath(__DIR__.'/../app/'), 'app')
    ->dir($app->dir('app').'config', 'config', group: 'config')
    ->dir($app->dir('root').'public', 'public')
    ->dir($app->dir('root').'vendor', 'vendor');

// Adding boots
$app->boot(\Tobento\App\Backend\Boot\Backend::class);

// Run the app
$app->run();
```

Once booted, you can access the backend by the following URL ```http://localhost/{project}/public/admin/```.

You may change the ```backend``` slug ```admin``` or even route to another domain in the ```app/config/apps.php``` file.

## Adding Boots

There are two ways to add backend boots:

**Via App Config**

In the ```apps/backend/config/app.php``` file you can add more boots.

```php
'boots' => [
    // ...
    SomeBoot::class,
],
```

**Via Backend Boot**

```php
use Tobento\App\Boot;
use Tobento\App\Backend\Boot\Backend;

class BackendBoots extends Boot
{
    public const BOOT = [
        Backend::class,
    ];
    
    public function boot(Backend $backend): void
    {
        $backend->addBoot(SomeBoot::class);
    }
}
```

Register your boot:

```php
use Tobento\App\AppFactory;

// Create the app
$app = (new AppFactory())->createApp();

// Add directories:
$app->dirs()
    ->dir(realpath(__DIR__.'/../'), 'root')
    ->dir(realpath(__DIR__.'/../app/'), 'app')
    ->dir($app->dir('app').'config', 'config', group: 'config')
    ->dir($app->dir('root').'public', 'public')
    ->dir($app->dir('root').'vendor', 'vendor');

// Adding boots
$app->boot(\Tobento\App\Backend\Boot\Backend::class);
$app->boot(BackendBoots::class);

// Run the app
$app->run();
```

## Adding Dashboard Cards

Use the ```DashboardCards::class``` and app ```on``` method to add dashboard cards only on demand:

```php
use Tobento\App\Backend\Card\DashboardCards;
use Tobento\App\Card\Factory;

$app->on(
    DashboardCards::class,
    static function(DashboardCards $cards): void {
        // Adding cards:
        $cards->add(name: 'foo', card: new Factory\Table(
            rows: ['foo', 'bar'],
        ));
    }
);
```

Check out the [App Card](https://github.com/tobento-ch/app-card) bundle to learn more.

## Adding Searchables

To add searchables, check out the [App Search - Adding Searchables](https://github.com/tobento-ch/app-search#adding-searchables) section.

## Customization

### Customize Via App

In your [```app/src```](https://github.com/tobento-ch/app-skeleton#src) directory you may create a boot customizing specific controllers for instance.

```php
namespace App;

use use Tobento\App\Backend\Controller\RoleCrudController;

class Customization extends Boot
{
    public function boot(): void
    {
        // Custom controller:
        $this->app->set(RoleCrudController::class, \App\CustomRoleCrudController::class);
    }
}
```

In the ```apps/backend/config/app.php``` file add your boot.

```php
'boots' => [
    // ...
    \App\Customization::class,
],
```

### Customize Via App Config

In the ```apps/backend/config/app.php``` file you can replace existing boots with your customized boots.

```php
'boots' => [
    // ...
    // Resources:
    //\Tobento\App\Backend\Boot\Roles::class,
    \Tobento\App\Backend\Boot\CustomRoles::class,
    //\Tobento\App\Backend\Boot\Users::class,
    \Tobento\App\Backend\Boot\CustomUsers::class,
    // ...
],
```

## Testing

You may boot the ```\Tobento\App\Backend\Testing\UserAndRolesBoot::class``` which will migrate the following users for testing:

| Email | Smartphone | Role | Active |
| --- | --- | --- | --- |
| admin@example.com | - | administrator | 1 |
| inactive@example.com | - | administrator | 0 |
| editor@example.com | 12345678 | editor | 1 |
| registered@example.com | - | registered | 1 |


```php
use Tobento\App\AppInterface;

final class SomeBackendAppTest extends \Tobento\App\Testing\TestCase
{
    use \Tobento\App\Testing\Database\MigrateDatabases;
    
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/../..');
        $app->boot(\Tobento\App\Backend\Boot\Backend::class);
        $app->booting();
        
        $app = $app->get(AppsInterface::class)->get('backend')->app();
        $app->boot(\Tobento\App\Seeding\Boot\Seeding::class);
        $app->boot(\Tobento\App\Backend\Testing\UserAndRolesBoot::class);
        return $app;
    }
}
```

You may check out the following links to learn more about testing.

* [App Testing](https://github.com/tobento-ch/app-testing)
* [Apps - Testing](https://github.com/tobento-ch/apps#testing)
* [App Crud - Testing](https://github.com/tobento-ch/app-crud#testing)
* [App Seeding](https://github.com/tobento-ch/app-seeding)

# Credits

- [Tobias Strub](https://www.tobento.ch)
- [All Contributors](../../contributors)