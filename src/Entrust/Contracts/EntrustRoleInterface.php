<?php namespace Adesr\Entrust\Contracts;

/**
 * This file is part of Entrust,
 * a role & permission management solution for Laravel.
 *
 * @license MIT
 * @package Adesr\Entrust
 */

interface EntrustRoleInterface
{
    /**
     * Many-to-Many relations with the user model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users();

    /**
     * Many-to-Many relations with the permission model.
     * Named "perms" for backwards compatibility. Also because "perms" is short and sweet.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function perms();

    /**
     * Many-to-Many relations with the menu model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function menus();

    /**
     * Save the inputted permissions.
     *
     * @param mixed $inputPermissions
     *
     * @return void
     */
    public function savePermissions($inputPermissions);

     /**
     * Attach permission to current role.
     *
     * @param object|array $permission
     *
     * @return void
     */
    public function attachPermission($permission);

    /**
     * Detach permission form current role.
     *
     * @param object|array $permission
     *
     * @return void
     */
    public function detachPermission($permission);

    /**
     * Attach multiple permissions to current role.
     *
     * @param mixed $permissions
     *
     * @return void
     */
    public function attachPermissions($permissions);

    /**
     * Detach multiple permissions from current role
     *
     * @param mixed $permissions
     *
     * @return void
     */
    public function detachPermissions($permissions);

    /**
     * Sync multiple permissions to current role.
     *
     * @param mixed $permissions
     *
     * @return void
     */
    public function syncPermissions($permissions);

    /**
    * Attach menu to current role.
    *
    * @param object|array $menu
    *
    * @return void
    */
    public function attachMenu($menu);

    /**
     * Attach multiple menus to current role.
     *
     * @param mixed $menus
     *
     * @return void
     */
    public function attachMenus($menus);

    /**
     * Sync multiple menus to current role.
     *
     * @param mixed $menus
     *
     * @return void
     */
    public function syncMenus($menus);

}
