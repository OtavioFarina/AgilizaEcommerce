<?php

$lifetime = 2592000;

session_set_cookie_params($lifetime, '/', null, false, true); // O 'true' em httponly é uma camada de segurança
ini_set('session.gc_maxlifetime', $lifetime);

session_start();

if (!isset($_SESSION['carrinho'])) {
    $_SESSION['carrinho'] = [];
}
?>