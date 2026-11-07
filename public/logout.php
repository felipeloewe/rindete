<?php
require dirname(__DIR__) . '/includes/init.php';
cerrar_sesion();
header('Location: ' . url('login.php'));
exit;
