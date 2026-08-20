<?php
require_once __DIR__ . '/includes/init.php';
requireInstalled();
redirect(currentUser() ? 'dashboard.php' : 'login.php');
