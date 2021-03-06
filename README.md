# ENTRUST (Laravel 5 Package)

This repo is a fork for  [zizaco/entrust](https://github.com/Zizaco/entrust). It supports dynamic navigation based on role and permissions registered.

## Contents

- [Installation](#installation)
- [Configuration](#configuration)
    - [User relation to roles](#user-relation-to-roles)
    - [Models](#models)
        - [Role](#role)
        - [Permission](#permission)
        - [Menu](#menu)
        - [User](#user)
        - [Soft Deleting](#soft-deleting)
- [Usage](#usage)
    - [Concepts](#concepts)
        - [Checking for Roles & Permissions](#checking-for-roles--permissions)
        - [User ability](#user-ability)
    - [Blade templates](#blade-templates)
    - [Middleware](#middleware)
    - [Short syntax route filter](#short-syntax-route-filter)
    - [Route filter](#route-filter)

## Installation

1) In order to install Laravel 5 Entrust, just run following command:

```bash
composer require adesr/entrust
```

2) Open your `config/app.php` and add the following to the `providers` array:

```php
Adesr\Entrust\EntrustServiceProvider::class,
```

3) In the same `config/app.php` and add the following to the `aliases ` array:

```php
'Entrust'   => Adesr\Entrust\EntrustFacade::class,
```

4) Run the command below to publish the package config file `config/entrust.php`:

```shell
php artisan vendor:publish
```

5) Open your `config/auth.php` and add the following to it:

```php
'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model' => Namespace\Of\Your\User\Model\User::class,
        'table' => 'users',
    ],
],
```

6)  If you want to use [Middleware](#middleware) (requires Laravel 5.1 or later) you also need to add the following:

```php
    'role' => \Adesr\Entrust\Middleware\EntrustRole::class,
    'permission' => \Adesr\Entrust\Middleware\EntrustPermission::class,
    'ability' => \Adesr\Entrust\Middleware\EntrustAbility::class,
```

to `routeMiddleware` array in `app/Http/Kernel.php`.

## Configuration

Set the property values in the `config/auth.php`.
These values will be used by entrust to refer to the correct user table and model.

To further customize table names and model namespaces, edit the `config/entrust.php`.

### User relation to roles

Now generate the Entrust migration:

```bash
php artisan entrust:migration
```

It will generate the `<timestamp>_entrust_setup_tables.php` migration.
You may now run it with the artisan migrate command:

```bash
php artisan migrate
```

After the migration, four new tables will be present:
- `roles` &mdash; stores role records
- `permissions` &mdash; stores permission records
- `role_user` &mdash; stores [many-to-many](http://laravel.com/docs/5.4/eloquent#many-to-many) relations between roles and users
- `permission_role` &mdash; stores [many-to-many](http://laravel.com/docs/5.4/eloquent#many-to-many) relations between roles and permissions
- `menus` &mdash; stores menu records
- `menu_role` &mdash; stores [many-to-many](http://laravel.com/docs/5.4/eloquent#many-to-many) relations between menus and roles
- `menu_permission` &mdash; stores [many-to-many](http://laravel.com/docs/5.4/eloquent#many-to-many) relations between menus and permissions

### Models

RUn following command:

```bash
php artisan entrust:models
```

It will generate 3 model files inside `app` directory:
- `Role`
- `Permission`
- `Menu`

or, you can create your own model:

#### Role

Create a Role model inside `app/Role.php` using the following example:

```php
<?php namespace App;

use Adesr\Entrust\EntrustRole;

class Role extends EntrustRole
{
}
```

The `Role` model has two main attributes:
- `name` &mdash; Unique name for the Role, used for looking up role information in the application layer. For example: "admin", "owner", "employee".
- `description` &mdash; A more detailed explanation of what the Role does. Also optional.

`description` is optional; their fields are nullable in the database.

#### Permission

Create a Permission model inside `app/Permission.php` using the following example:

```php
<?php namespace App;

use Adesr\Entrust\EntrustPermission;

class Permission extends EntrustPermission
{
}
```

The `Permission` model has the same three attributes as the `Role`:
- `name` &mdash; Unique name for the permission, used for looking up permission information in the application layer. For example: "create-post", "edit-user", "post-payment", "mailing-list-subscribe".
- `description` &mdash; A more detailed explanation of the Permission.

In general, it may be helpful to think of the last two attributes in the form of a sentence: "The permission `name` allows a user to `description`."

#### Menu

Create a Menu model inside `app/Menu.php` using following example:

```php
<?php
namespace App;

use Adesr\Entrust\EntrustMenu;

class Menu extends EntrustMenu
{

    protected $casts = [ 'is_active' ];

}
```

The `Menu` model has six main attributes:
- `name` &mdash; Name for the Menu, used as a display name of the menu.
- `slug` &mdash; Unique name of Menu used as URL target.
- `parent` &mdash; Name of parent Menu, used as relations between menus (parent-child relation).
- `icon` &mdash; Icon class name for the Menu, used as an icon display of the menu (based on bootstrap/ glyphicons or other css framework available).
- `is_active` &mdash; Active flag of the menu.
- `order_no` &mdash; Order number for the menu.

#### User

Next, use the `EntrustUserTrait` trait in your existing `User` model. For example:

```php
<?php

use Adesr\Entrust\Traits\EntrustUserTrait;

class User extends Eloquent
{
    use EntrustUserTrait; // add this trait to your user model

    ...
}
```

This will enable the relation with `Role` and add the following methods `roles()`, `hasRole($name)`, `can($permission)`, and `ability($roles, $permissions, $options)` within your `User` model.

Don't forget to dump composer autoload

```bash
composer dump-autoload
```

**And you are ready to go.**

#### Soft Deleting

The default migration takes advantage of `onDelete('cascade')` clauses within the pivot tables to remove relations when a parent record is deleted. If for some reason you cannot use cascading deletes in your database, the EntrustRole and EntrustPermission classes, and the HasRole trait include event listeners to manually delete records in relevant pivot tables. In the interest of not accidentally deleting data, the event listeners will **not** delete pivot data if the model uses soft deleting. However, due to limitations in Laravel's event listeners, there is no way to distinguish between a call to `delete()` versus a call to `forceDelete()`. For this reason, **before you force delete a model, you must manually delete any of the relationship data** (unless your pivot tables uses cascading deletes). For example:

```php
$role = Role::findOrFail(1); // Pull back a given role

// Regular Delete
$role->delete(); // This will work no matter what

// Force Delete
$role->users()->sync([]); // Delete relationship data
$role->perms()->sync([]); // Delete relationship data
$role->menus()->sync([]); // Delete relationship data

$role->forceDelete(); // Now force delete will work regardless of whether the pivot table has cascading delete
```

## Usage

### Concepts
Let's start by creating the following `Role`s and `Permission`s:

```php
$owner = new Role();
$owner->name         = 'owner';
$owner->display_name = 'Project Owner'; // optional
$owner->description  = 'User is the owner of a given project'; // optional
$owner->save();

$admin = new Role();
$admin->name         = 'admin';
$admin->display_name = 'User Administrator'; // optional
$admin->description  = 'User is allowed to manage and edit other users'; // optional
$admin->save();
```

Next, with both roles created let's assign them to the users.
Thanks to the `HasRole` trait this is as easy as:

```php
$user = User::where('username', '=', 'michele')->first();

// role attach alias
$user->attachRole($admin); // parameter can be an Role object, array, or id

// or eloquent's original technique
$user->roles()->attach($admin->id); // id only
```

Now we just need to add permissions to those Roles:

```php
$createPost = new Permission();
$createPost->name         = 'create-post';
$createPost->display_name = 'Create Posts'; // optional
// Allow a user to...
$createPost->description  = 'create new blog posts'; // optional
$createPost->save();

$editUser = new Permission();
$editUser->name         = 'edit-user';
$editUser->display_name = 'Edit Users'; // optional
// Allow a user to...
$editUser->description  = 'edit existing users'; // optional
$editUser->save();

$admin->attachPermission($createPost);
// equivalent to $admin->perms()->sync(array($createPost->id));

$owner->attachPermissions(array($createPost, $editUser));
// equivalent to $owner->perms()->sync(array($createPost->id, $editUser->id));
```

#### Checking for Roles & Permissions

Now we can check for roles and permissions simply by doing:

```php
$user->hasRole('owner');   // false
$user->hasRole('admin');   // true
$user->can('edit-user');   // false
$user->can('create-post'); // true
```

Both `hasRole()` and `can()` can receive an array of roles & permissions to check:

```php
$user->hasRole(['owner', 'admin']);       // true
$user->can(['edit-user', 'create-post']); // true
```

By default, if any of the roles or permissions are present for a user then the method will return true.
Passing `true` as a second parameter instructs the method to require **all** of the items:

```php
$user->hasRole(['owner', 'admin']);             // true
$user->hasRole(['owner', 'admin'], true);       // false, user does not have admin role
$user->can(['edit-user', 'create-post']);       // true
$user->can(['edit-user', 'create-post'], true); // false, user does not have edit-user permission
```

You can have as many `Role`s as you want for each `User` and vice versa.

The `Entrust` class has shortcuts to both `can()` and `hasRole()` for the currently logged in user:

```php
Entrust::hasRole('role-name');
Entrust::can('permission-name');

// is identical to

Auth::user()->hasRole('role-name');
Auth::user()->can('permission-name');
```

You can also use placeholders (wildcards) to check any matching permission by doing:

```php
// match any admin permission
$user->can("admin.*"); // true

// match any permission about users
$user->can("*_users"); // true
```


#### User ability

More advanced checking can be done using the awesome `ability` function.
It takes in three parameters (roles, permissions, options):
- `roles` is a set of roles to check.
- `permissions` is a set of permissions to check.

Either of the roles or permissions variable can be a comma separated string or array:

```php
$user->ability(array('admin', 'owner'), array('create-post', 'edit-user'));

// or

$user->ability('admin,owner', 'create-post,edit-user');
```

This will check whether the user has any of the provided roles and permissions.
In this case it will return true since the user is an `admin` and has the `create-post` permission.

The third parameter is an options array:

```php
$options = array(
    'validate_all' => true | false (Default: false),
    'return_type'  => boolean | array | both (Default: boolean)
);
```

- `validate_all` is a boolean flag to set whether to check all the values for true, or to return true if at least one role or permission is matched.
- `return_type` specifies whether to return a boolean, array of checked values, or both in an array.

Here is an example output:

```php
$options = array(
    'validate_all' => true,
    'return_type' => 'both'
);

list($validate, $allValidations) = $user->ability(
    array('admin', 'owner'),
    array('create-post', 'edit-user'),
    $options
);

var_dump($validate);
// bool(false)

var_dump($allValidations);
// array(4) {
//     ['role'] => bool(true)
//     ['role_2'] => bool(false)
//     ['create-post'] => bool(true)
//     ['edit-user'] => bool(false)
// }

```
The `Entrust` class has a shortcut to `ability()` for the currently logged in user:

```php
Entrust::ability('admin,owner', 'create-post,edit-user');

// is identical to

Auth::user()->ability('admin,owner', 'create-post,edit-user');
```

### Blade templates

Three directives are available for use within your Blade templates. What you give as the directive arguments will be directly passed to the corresponding `Entrust` function.

```php
@role('admin')
    <p>This is visible to users with the admin role. Gets translated to
    \Entrust::role('admin')</p>
@endrole

@permission('manage-admins')
    <p>This is visible to users with the given permissions. Gets translated to
    \Entrust::can('manage-admins'). The @can directive is already taken by core
    laravel authorization package, hence the @permission directive instead.</p>
@endpermission

@ability('admin,owner', 'create-post,edit-user')
    <p>This is visible to users with the given abilities. Gets translated to
    \Entrust::ability('admin,owner', 'create-post,edit-user')</p>
@endability
```

### Middleware

You can use a middleware to filter routes and route groups by permission or role
```php
Route::group(['prefix' => 'admin', 'middleware' => ['role:admin']], function() {
    Route::get('/', 'AdminController@welcome');
    Route::get('/manage', ['middleware' => ['permission:manage-admins'], 'uses' => 'AdminController@manageAdmins']);
});
```

It is possible to use pipe symbol as *OR* operator:
```php
'middleware' => ['role:admin|root']
```

To emulate *AND* functionality just use multiple instances of middleware
```php
'middleware' => ['role:owner', 'role:writer']
```

For more complex situations use `ability` middleware which accepts 3 parameters: roles, permissions, validate_all
```php
'middleware' => ['ability:admin|owner,create-post|edit-user,true']
```

### Short syntax route filter

To filter a route by permission or role you can call the following in your `app/Http/routes.php`:

```php
// only users with roles that have the 'manage_posts' permission will be able to access any route within admin/post
Entrust::routeNeedsPermission('admin/post*', 'create-post');

// only owners will have access to routes within admin/advanced
Entrust::routeNeedsRole('admin/advanced*', 'owner');

// optionally the second parameter can be an array of permissions or roles
// user would need to match all roles or permissions for that route
Entrust::routeNeedsPermission('admin/post*', array('create-post', 'edit-comment'));
Entrust::routeNeedsRole('admin/advanced*', array('owner','writer'));
```

Both of these methods accept a third parameter.
If the third parameter is null then the return of a prohibited access will be `App::abort(403)`, otherwise the third parameter will be returned.
So you can use it like:

```php
Entrust::routeNeedsRole('admin/advanced*', 'owner', Redirect::to('/home'));
```

Furthermore both of these methods accept a fourth parameter.
It defaults to true and checks all roles/permissions given.
If you set it to false, the function will only fail if all roles/permissions fail for that user.
Useful for admin applications where you want to allow access for multiple groups.

```php
// if a user has 'create-post', 'edit-comment', or both they will have access
Entrust::routeNeedsPermission('admin/post*', array('create-post', 'edit-comment'), null, false);

// if a user is a member of 'owner', 'writer', or both they will have access
Entrust::routeNeedsRole('admin/advanced*', array('owner','writer'), null, false);

// if a user is a member of 'owner', 'writer', or both, or user has 'create-post', 'edit-comment' they will have access
// if the 4th parameter is true then the user must be a member of Role and must have Permission
Entrust::routeNeedsRoleOrPermission(
    'admin/advanced*',
    array('owner', 'writer'),
    array('create-post', 'edit-comment'),
    null,
    false
);
```

### Route filter

Entrust roles/permissions can be used in filters by simply using the `can` and `hasRole` methods from within the Facade:

```php
Route::filter('manage_posts', function()
{
    // check the current user
    if (!Entrust::can('create-post')) {
        return Redirect::to('admin');
    }
});

// only users with roles that have the 'manage_posts' permission will be able to access any admin/post route
Route::when('admin/post*', 'manage_posts');
```

Using a filter to check for a role:

```php
Route::filter('owner_role', function()
{
    // check the current user
    if (!Entrust::hasRole('Owner')) {
        App::abort(403);
    }
});

// only owners will have access to routes within admin/advanced
Route::when('admin/advanced*', 'owner_role');
```

As you can see `Entrust::hasRole()` and `Entrust::can()` checks if the user is logged in, and then if he or she has the role or permission.
If the user is not logged the return will also be `false`.
