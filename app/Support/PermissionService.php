<?php

namespace App\Support;

use App\Models\User;

class PermissionService
{
    private static array $rolePermissions = [
        User::ROLE_ADMIN => [
            'manage_hotels',
            'manage_users',
            'manage_bookings',
            'manage_reviews',
            'manage_services',
            'view_reports',
            'system_settings',
        ],
        User::ROLE_STAFF => [
            'manage_bookings',
            'manage_reviews',
            'manage_services',
            'view_assigned_hotels',
        ],
        User::ROLE_CUSTOMER => [
            'create_booking',
            'cancel_booking',
            'create_review',
            'update_profile',
        ],
    ];

    /**
     * Get permissions for a given role.
     */
    public static function getPermissionsForRole(string $role): array
    {
        return self::$rolePermissions[$role] ?? [];
    }

    /**
     * Get permissions for a user.
     */
    public static function getPermissions(User $user): array
    {
        if ($user->isAdmin()) {
            return self::getPermissionsForRole(User::ROLE_ADMIN);
        }
        if ($user->isStaffRole()) {
            return self::getPermissionsForRole(User::ROLE_STAFF);
        }
        return self::getPermissionsForRole(User::ROLE_CUSTOMER);
    }

    /**
     * Check if user has a specific permission.
     */
    public static function hasPermission(User $user, string $permission): bool
    {
        return in_array($permission, self::getPermissions($user), true);
    }
}
