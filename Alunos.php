<?php

    require_once __DIR__ . '/Conexao.php';

    // Acesso à tabela "alunos". Todo SQL usa prepared statements.
    class Alunos
    {

        public static function select(int $id): ?array
        {
            $stmt = Conexao::get()->prepare('SELECT codigo, nome, email, telefone FROM alunos WHERE codigo = :id');
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $aluno = $stmt->fetch();

            return $aluno === false ? null : $aluno;
        }


        public static function selectAll(): array
        {
            $stmt = Conexao::get()->prepare('SELECT codigo, nome, email, telefone FROM alunos ORDER BY codigo');
            $stmt->execute();

            return $stmt->fetchAll();
        }


        public static function insert(array $dados): int
        {
            $pdo = Conexao::get();

            $stmt = $pdo->prepare('INSERT INTO alunos (nome, email, telefone) VALUES (:nome, :email, :telefone)');
            self::bindDados($stmt, $dados);
            $stmt->execute();

            return (int) $pdo->lastInsertId();
        }


        public static function update(int $id, array $dados): void
        {
            $stmt = Conexao::get()->prepare('UPDATE alunos SET nome = :nome, email = :email, telefone = :telefone WHERE codigo = :id');
            self::bindDados($stmt, $dados);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
        }


        // retorna false se o aluno não existir
        public static function delete(int $id): bool
        {
            $stmt = Conexao::get()->prepare('DELETE FROM alunos WHERE codigo = :id');
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->rowCount() > 0;
        }


        private static function bindDados(PDOStatement $stmt, array $dados): void
        {
            $stmt->bindValue(':nome', $dados['nome'], PDO::PARAM_STR);
            $stmt->bindValue(':email', $dados['email'], PDO::PARAM_STR);

            if ($dados['telefone'] === null) {
                $stmt->bindValue(':telefone', null, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(':telefone', $dados['telefone'], PDO::PARAM_STR);
            }
        }

    }
