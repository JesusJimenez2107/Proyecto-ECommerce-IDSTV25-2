<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Genera o devuelve el token CSRF activo de la sesión
 */
function getCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Imprime el campo input hidden con el token CSRF para formularios POST
 */
function csrfField(): string
{
    $token = htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

/**
 * Valida que el token enviado por POST coincida con el de la sesión
 */
function verifyCsrfToken(?string $token): bool
{
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Exige que el usuario haya iniciado sesión; si no, redirige a login.php
 */
function requireLogin(): void
{
    if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {
        header("Location: login.php?error=login_required");
        exit();
    }
}

/**
 * Exige rol estricto de administrador basado únicamente en la sesión del servidor
 */
function requireAdmin(): void
{
    if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
        header("Location: index.php");
        exit();
    }
}