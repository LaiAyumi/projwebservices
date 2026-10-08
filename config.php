<?php

    // Configuração do banco de dados.
    // Os valores vêm das variáveis de ambiente; se não existirem, usa os padrões locais.
    // O script de criação do banco/tabela está em database/schema.sql

    function configEnv(string $nome, string $padrao): string
    {
        $valor = getenv($nome);

        return ($valor === false || $valor === '') ? $padrao : $valor;
    }

    define('DB_HOST', configEnv('DB_HOST', 'localhost'));
    define('DB_PORT', configEnv('DB_PORT', '3306'));
    define('DB_NAME', configEnv('DB_NAME', 'projwebservices'));
    define('DB_USER', configEnv('DB_USER', 'root'));
    define('DB_PASS', configEnv('DB_PASS', ''));
