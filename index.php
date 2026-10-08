<?php

    // Ponto de entrada da API. Funciona de três jeitos:
    //   - Apache com o .htaccess (reescreve para index.php?url=...)
    //   - servidor embutido do PHP usando este arquivo como roteador: php -S localhost:8000 index.php
    //   - chamando direto: index.php?url=api/alunos/1

    require_once __DIR__ . '/AlunosService.php';
    require_once __DIR__ . '/ApiException.php';

    ini_set('display_errors', '0');

    // recursos liberados -> classe de serviço (nunca usamos nome de classe vindo da URL)
    const RECURSOS = [
        'alunos' => 'AlunosService',
    ];

    // método HTTP -> método do serviço
    const METODOS = [
        'GET'    => 'get',
        'HEAD'   => 'get',   // igual ao GET; o PHP não envia o corpo
        'POST'   => 'post',
        'PUT'    => 'put',
        'DELETE' => 'delete',
    ];


    function responder(int $statusHttp, string $status, $dados, array $cabecalhos = []): void
    {
        http_response_code($statusHttp);
        header('Content-Type: application/json; charset=UTF-8');

        foreach ($cabecalhos as $cabecalho) {
            header($cabecalho);
        }

        echo json_encode(['status' => $status, 'data' => $dados], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    }


    // pasta onde a API está publicada (vazio quando está na raiz).
    // No servidor embutido o SCRIPT_NAME às vezes é o próprio caminho pedido, por isso só vale se apontar para o index.php
    function caminhoBase(): string
    {
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

        if (basename($script) !== 'index.php') {
            return '';
        }

        return rtrim(dirname($script), '/');
    }


    // descobre a rota pelo caminho da requisição; ?url=... só vale quando o caminho é o próprio index.php
    function lerRota(): string
    {
        $caminho = rawurldecode(explode('?', $_SERVER['REQUEST_URI'] ?? '/', 2)[0]);

        $base = caminhoBase();
        if ($base !== '' && strpos($caminho, $base . '/') === 0) {
            $caminho = substr($caminho, strlen($base));
        }

        if ($caminho === '' || $caminho === '/' || $caminho === '/index.php') {
            return (isset($_GET['url']) && is_string($_GET['url'])) ? $_GET['url'] : '';
        }

        if (strpos($caminho, '/index.php/') === 0) {
            $caminho = substr($caminho, strlen('/index.php'));
        }

        return $caminho;
    }


    // lê o corpo da requisição (JSON ou formulário)
    function lerCorpo(string $metodoHttp): array
    {
        // só o tipo, sem parâmetros como "; charset=UTF-8"
        $tipo  = strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0]));
        $bruto = (string) file_get_contents('php://input');

        if ($tipo === 'application/json') {

            $objeto = json_decode($bruto);

            if (json_last_error() !== JSON_ERROR_NONE || !($objeto instanceof stdClass)) {
                throw new ApiException('JSON inválido. Envie um objeto JSON.', 400);
            }

            return json_decode($bruto, true);
        }

        // o PHP só lê multipart no POST
        if ($tipo === 'multipart/form-data' && $metodoHttp === 'POST') {
            return $_POST;
        }

        if ($tipo === 'application/x-www-form-urlencoded') {

            if ($metodoHttp === 'POST') {
                return $_POST;
            }

            // no PUT o PHP não preenche $_POST
            parse_str($bruto, $dados);
            return $dados;
        }

        if ($tipo === '' && trim($bruto) === '') {
            return [];
        }

        throw new ApiException('Content-Type não suportado. Use application/json ou application/x-www-form-urlencoded.', 415);
    }


    try {

        $partes = array_values(array_filter(explode('/', lerRota()), 'strlen'));

        if (count($partes) < 2 || count($partes) > 3 || $partes[0] !== 'api' || !isset(RECURSOS[$partes[1]])) {
            throw new ApiException('Rota não encontrada.', 404);
        }

        $recurso = $partes[1];
        $temId   = count($partes) === 3;

        // métodos aceitos na coleção (/api/alunos) e no item (/api/alunos/{id})
        $permitidos = $temId ? ['GET', 'HEAD', 'PUT', 'DELETE'] : ['GET', 'HEAD', 'POST'];
        $metodoHttp = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        if ($metodoHttp === 'OPTIONS') {
            http_response_code(204);
            header('Allow: OPTIONS, ' . implode(', ', $permitidos));
            exit;
        }

        if (!in_array($metodoHttp, $permitidos, true)) {
            header('Allow: ' . implode(', ', $permitidos));
            throw new ApiException('Método não permitido.', 405);
        }

        $id = null;
        if ($temId) {
            // só dígitos, sem sinal, espaços ou zero à esquerda; o filter_var barra números grandes demais
            $id = preg_match('/^[1-9][0-9]*$/D', $partes[2]) === 1
                ? filter_var($partes[2], FILTER_VALIDATE_INT)
                : false;

            if ($id === false) {
                throw new ApiException('O id deve ser um número inteiro positivo.', 400);
            }
        }

        $classe  = RECURSOS[$recurso];
        $servico = new $classe();
        $metodo  = METODOS[$metodoHttp];

        switch ($metodo) {
            case 'get':
                responder(200, 'success', $servico->get($id));
                break;

            case 'post':
                $criado = $servico->post(lerCorpo($metodoHttp));
                $local  = caminhoBase() . '/api/' . $recurso . '/' . $criado['codigo'];
                responder(201, 'success', $criado, ['Location: ' . $local]);
                break;

            case 'put':
                responder(200, 'success', $servico->put($id, lerCorpo($metodoHttp)));
                break;

            case 'delete':
                responder(200, 'success', $servico->delete($id));
                break;
        }

    } catch (ApiException $e) {
        responder($e->getCode(), 'error', $e->getMessage());

    } catch (Throwable $e) {
        // detalhes só no log do servidor, nunca na resposta
        error_log('[projwebservices] ' . get_class($e) . ': ' . $e->getMessage() . ' em ' . $e->getFile() . ':' . $e->getLine());
        responder(500, 'error', 'Erro interno no servidor.');
    }
