<?php

namespace App\Services;

use App\Core\Database;
use App\Core\Session;

class AuthService
{
    public static function attempt(string $username, string $password): bool
    {
        $db = Database::pdo();
        $stmt = $db->prepare(
            'SELECT u.*, r.name AS role_name
             FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE u.username = :username AND u.deleted_at IS NULL
             LIMIT 1'
        );
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        if (!$user || !$user['is_active']) {
            self::logFailed($username);
            return false;
        }

        if (!password_verify($password, $user['password_hash'])) {
            self::logFailed($username);
            return false;
        }

        $permissions = self::permissionsForRole((int) $user['role_id']);
        Session::set('user', [
            'id' => (int) $user['id'],
            'username' => $user['username'],
            'full_name' => $user['full_name'],
            'role_id' => (int) $user['role_id'],
            'role_name' => $user['role_name'],
            'permissions' => $permissions,
        ]);

        $update = $db->prepare('UPDATE users SET last_login_at = datetime(\'now\'), last_login_ip = :ip WHERE id = :id');
        $update->execute([
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'id' => $user['id'],
        ]);

        LogService::activity((int) $user['id'], 'login', 'تسجيل دخول ناجح');

        return true;
    }

    public static function logout(): void
    {
        $user = Session::get('user');
        if ($user) {
            LogService::activity((int) $user['id'], 'logout', 'تسجيل خروج');
        }
        Session::destroy();
    }

    public static function check(): bool
    {
        return Session::has('user');
    }

    public static function user(): ?array
    {
        return Session::get('user');
    }

    public static function syncSessionUser(): void
    {
        $sessionUser = Session::get('user');
        if (!is_array($sessionUser) || empty($sessionUser['id'])) {
            return;
        }

        $fullName = (string) ($sessionUser['full_name'] ?? '');
        if ($fullName !== '' && strpos($fullName, '?') === false) {
            return;
        }

        $db = Database::pdo();
        $stmt = $db->prepare(
            'SELECT u.*, r.name AS role_name
             FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE u.id = :id AND u.deleted_at IS NULL
             LIMIT 1'
        );
        $stmt->execute(['id' => (int) $sessionUser['id']]);
        $user = $stmt->fetch();

        if (!$user || !(int) $user['is_active']) {
            Session::remove('user');
            return;
        }

        $permissions = self::permissionsForRole((int) $user['role_id']);
        Session::set('user', [
            'id' => (int) $user['id'],
            'username' => $user['username'],
            'full_name' => $user['full_name'],
            'role_id' => (int) $user['role_id'],
            'role_name' => $user['role_name'],
            'permissions' => $permissions,
        ]);
    }

    public static function id(): ?int
    {
        return Session::get('user')['id'] ?? null;
    }

    public static function can(string $permission): bool
    {
        $user = self::user();
        if (!$user) {
            return false;
        }

        if (($user['role_name'] ?? '') === 'admin') {
            return true;
        }

        return in_array($permission, $user['permissions'] ?? [], true);
    }

    public static function verifyCurrentUserPassword(string $password): bool
    {
        $userId = self::id();
        if (!$userId || $password === '') {
            return false;
        }

        $db = Database::pdo();
        $stmt = $db->prepare(
            'SELECT password_hash, is_active
             FROM users
             WHERE id = :id AND deleted_at IS NULL
             LIMIT 1'
        );
        $stmt->execute(['id' => $userId]);
        $row = $stmt->fetch();

        if (!$row || !(int) $row['is_active']) {
            return false;
        }

        return password_verify($password, (string) $row['password_hash']);
    }

    private static function permissionsForRole(int $roleId): array
    {
        $db = Database::pdo();
        $stmt = $db->prepare(
            'SELECT p.code
             FROM role_permissions rp
             JOIN permissions p ON p.id = rp.permission_id
             WHERE rp.role_id = :role_id'
        );
        $stmt->execute(['role_id' => $roleId]);

        return array_column($stmt->fetchAll(), 'code');
    }

    private static function logFailed(string $username): void
    {
        $db = Database::pdo();
        $stmt = $db->prepare(
            'INSERT INTO failed_logins (username, ip_address, user_agent) VALUES (:username, :ip, :agent)'
        );
        $stmt->execute([
            'username' => $username,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);
    }
}
