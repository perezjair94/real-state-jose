<?php
/**
 * Logout - Admin
 * Cierra sesión del administrador
 */

define('APP_ACCESS', true);

require_once '../config/constants.php';
require_once '../includes/functions.php';

initSession();
logout();
