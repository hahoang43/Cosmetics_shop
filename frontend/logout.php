<?php
session_start();
session_destroy();
header("Location: /Cosmetics_shop/frontend/index.php");
exit;