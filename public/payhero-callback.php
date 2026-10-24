<?php
// Public entry point. PayHero posts here. No session or login is required.
require __DIR__ . '/../app/Controllers/PayHeroController.php';

(new \App\Controllers\PayHeroController())->callback();