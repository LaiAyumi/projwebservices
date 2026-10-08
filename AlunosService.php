<?php

    require_once __DIR__ . '/Alunos.php';
    require_once __DIR__ . '/ApiException.php';

    class AlunosService
    {

        public function get(?int $id = null): array
        {
            if ($id === null) {
                return Alunos::selectAll();
            }

            $aluno = Alunos::select($id);

            if ($aluno === null) {
                throw new ApiException('Aluno não encontrado.', 404);
            }

            return $aluno;
        }


        public function post(array $dados): array
        {
            $id = Alunos::insert($this->validar($dados));

            return Alunos::select($id);
        }


        // atualização completa: nome, email e telefone são substituídos
        public function put(int $id, array $dados): array
        {
            $aluno = $this->validar($dados);

            if (Alunos::select($id) === null) {
                throw new ApiException('Aluno não encontrado.', 404);
            }

            Alunos::update($id, $aluno);

            return Alunos::select($id);
        }


        public function delete(int $id): string
        {
            if (!Alunos::delete($id)) {
                throw new ApiException('Aluno não encontrado.', 404);
            }

            return 'Aluno removido com sucesso.';
        }


        // valida e normaliza os campos; lança 422 com a lista de erros
        private function validar(array $dados): array
        {
            $erros = [];

            $nome     = $this->texto($dados, 'nome', $erros);
            $email    = $this->texto($dados, 'email', $erros);
            $telefone = $this->texto($dados, 'telefone', $erros);

            if ($nome === null || $nome === '') {
                $erros['nome'] = $erros['nome'] ?? 'nome é obrigatório';
            } elseif ($this->tamanho($nome) > 50) {
                $erros['nome'] = 'nome deve ter no máximo 50 caracteres';
            }

            if ($email === null || $email === '') {
                $erros['email'] = $erros['email'] ?? 'email é obrigatório';
            } elseif ($this->tamanho($email) > 50) {
                $erros['email'] = 'email deve ter no máximo 50 caracteres';
            } elseif (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                $erros['email'] = 'email inválido';
            }

            if ($telefone === '') {
                $telefone = null;
            } elseif ($telefone !== null && $this->tamanho($telefone) > 25) {
                $erros['telefone'] = 'telefone deve ter no máximo 25 caracteres';
            }

            if ($erros) {
                throw new ApiException('Dados inválidos: ' . implode('; ', $erros) . '.', 422);
            }

            return ['nome' => $nome, 'email' => $email, 'telefone' => $telefone];
        }


        // lê um campo como texto (aceita número inteiro), já sem espaços nas pontas
        private function texto(array $dados, string $campo, array &$erros): ?string
        {
            if (!isset($dados[$campo])) {
                return null;
            }

            $valor = $dados[$campo];

            if (is_int($valor)) {
                $valor = (string) $valor;
            }

            if (!is_string($valor)) {
                $erros[$campo] = $campo . ' deve ser um texto';
                return null;
            }

            if (preg_match('//u', $valor) !== 1) {
                $erros[$campo] = $campo . ' deve estar em UTF-8';
                return null;
            }

            $valor = trim($valor);

            // caracteres de controle (NUL, quebra de linha, tab...) no meio do texto
            if (preg_match('/[\x00-\x1F\x7F]/', $valor) === 1) {
                $erros[$campo] = $campo . ' contém caracteres inválidos';
                return null;
            }

            return $valor;
        }


        private function tamanho(string $valor): int
        {
            return (int) preg_match_all('/./us', $valor);
        }

    }
