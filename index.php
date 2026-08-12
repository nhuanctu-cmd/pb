<?php

// Fallback front controller for Laragon when the virtual host points at the
// repository root instead of the public directory.
require __DIR__ . '/public/index.php';
