<?php

    require_once __DIR__ . '/config.php';

    // Conexão PDO única, compartilhada por toda a requisição
    class Conexao
    {
        private static $pdo = null;

        public static function get(): PDO
        {
            if (self::$pdo === null) {

                $dsn = 'mysql:host=' . DB_HOST . ';port=' . (int) DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';

                self::$pdo = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    PDO::ATTR_TIMEOUT            => 5, // segundos para conectar
                ]);
            }

            return self::$pdo;
        }
    }
