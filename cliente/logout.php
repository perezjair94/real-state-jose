<?php
/**
 * Logout - Cliente
 * Cierra sesión del cliente
 */

define('APP_ACCESS', true);

require_once '../config/constants.php';
require_once '../includes/functions.php';

initSession();
logout();
