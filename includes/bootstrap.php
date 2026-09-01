<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/security.php';

send_security_headers();
secure_session_start();
