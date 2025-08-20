<?php
// src/bootstrap.php
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require __DIR__ . '/../vendor/autoload.php';
} else {
    // Caso não use composer, você pode incluir arquivos manualmente a partir do public/index.php
}

if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}

// configuração básica
date_default_timezone_set('America/Fortaleza'); // ajuste conforme necessário
