<?php
if (function_exists('opcache_reset')) {
    opcache_reset();
}
header('Content-Type: text/plain; charset=utf-8');
echo 'OPcache cleared';
