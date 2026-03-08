<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn(): bool
{
    return isset($_SESSION['user']) && is_array($_SESSION['user']);
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: ../login.php');
        exit;
    }
}

function currentUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

function isAdmin(): bool
{
    return isLoggedIn() && (($_SESSION['user']['role'] ?? '') === 'admin');
}

function requireAdmin(): void
{
    if (!isAdmin()) {
        http_response_code(403);
        echo '403 Forbidden';
        exit;
    }
}