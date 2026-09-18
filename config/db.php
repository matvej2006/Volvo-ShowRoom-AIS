<?php
declare(strict_types=1);

const DB_HOST = 'localhost';
const DB_PORT = 3306;
const DB_NAME = 'volvo_showroom';
const DB_USER = 'root';
const DB_PASSWORD = 'root';

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf('mysql:host=localhost;port=3306;dbname=volvo_showroom;charset=utf8mb4');

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASSWORD, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        exit('Ошибка подключения к БД: ' . $e->getMessage());
    }

    return $pdo;
}