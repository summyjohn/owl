<?php
defined('DEBUG') or define('DEBUG', false);
defined('TEST') or define('TEST', false);

require __DIR__.'/Application.php';

\Owl\Application::registerNamespace('\Owl', __DIR__);
