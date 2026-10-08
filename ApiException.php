<?php

    // Erro "esperado" da API: a mensagem vai para o cliente e o código vira o status HTTP
    class ApiException extends Exception
    {
        public function __construct(string $mensagem, int $statusHttp = 400)
        {
            parent::__construct($mensagem, $statusHttp);
        }
    }
