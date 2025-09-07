<?php
require __DIR__."/vendor/autoload.php";
$app = require __DIR__."/bootstrap/app.php";
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Doctrine\ORM\EntityManagerInterface;
/** @var EntityManagerInterface $em */
$em = $app->make(EntityManagerInterface::class);
echo (int) $em->getConnection()->executeQuery("SELECT 1")->fetchOne(), PHP_EOL;
