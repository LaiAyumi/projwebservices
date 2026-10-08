#!/usr/bin/env bash
# Teste rápido (smoke test) da API com curl.
# Uso: BASE_URL=http://localhost:8000 bash tests/smoke.sh
# Atenção: cria, altera e apaga registros na tabela alunos do banco configurado.

BASE_URL="${BASE_URL:-http://localhost:8000}"
BASE_URL="${BASE_URL%/}"

TMP_DIR="$(mktemp -d)"
trap 'rm -rf "$TMP_DIR"' EXIT

PASSOU=0
FALHOU=0
STATUS=""
CORPO=""
CABECALHOS=""

# requisicao METODO CAMINHO [CORPO] [CONTENT-TYPE]
requisicao() {
    local metodo="$1" caminho="$2" corpo="${3-}" tipo="${4:-application/json}"
    local args=(-s -X "$metodo" -o "$TMP_DIR/corpo" -D "$TMP_DIR/cabecalhos" -w '%{http_code}')

    # não deixa a resposta anterior sobrar se o curl falhar
    rm -f "$TMP_DIR/corpo" "$TMP_DIR/cabecalhos"

    if [ "$metodo" = HEAD ]; then
        args=(-s -I -o "$TMP_DIR/corpo" -D "$TMP_DIR/cabecalhos" -w '%{http_code}')
    fi

    if [ $# -ge 3 ]; then
        args+=(-H "Content-Type: $tipo" --data-binary "$corpo")
    fi

    STATUS="$(curl "${args[@]}" "$BASE_URL$caminho")" || STATUS="000"
    CORPO="$(cat "$TMP_DIR/corpo" 2>/dev/null)"
    CABECALHOS="$(tr -d '\r' < "$TMP_DIR/cabecalhos" 2>/dev/null)"
}

ok()    { PASSOU=$((PASSOU + 1)); echo "  ok    $1"; }
falha() { FALHOU=$((FALHOU + 1)); echo "  FALHA $1"; echo "        status: $STATUS"; echo "        corpo:  $CORPO"; }

# verifica DESCRICAO STATUS_ESPERADO [TEXTO_ESPERADO_NO_CORPO]
verifica() {
    local descricao="$1" esperado="$2" texto="${3-}"

    if [ "$STATUS" != "$esperado" ]; then
        falha "$descricao (esperava $esperado)"
    elif [ -n "$texto" ] && ! grep -qF -- "$texto" <<< "$CORPO"; then
        falha "$descricao (corpo sem: $texto)"
    else
        ok "$descricao"
    fi
}

# verifica_cabecalho DESCRICAO REGEX
verifica_cabecalho() {
    if grep -qiE -- "$2" <<< "$CABECALHOS"; then
        ok "$1"
    else
        falha "$1 (cabeçalho não bate com: $2)"
    fi
}

extrai_codigo() {
    sed -n 's/.*"codigo":\([0-9][0-9]*\).*/\1/p' <<< "$CORPO" | head -n 1
}

echo "Testando $BASE_URL"

echo "Listagem"
requisicao GET /api/alunos
verifica "GET /api/alunos" 200 '"status":"success","data":['
verifica_cabecalho "Content-Type JSON" '^content-type: application/json; charset=utf-8'

echo "Criação (JSON)"
requisicao POST /api/alunos '{"nome":"  João Teste  ","email":"joao@teste.com","telefone":"11 99999-0000"}'
verifica "POST /api/alunos" 201 '"nome":"João Teste"'
ID="$(extrai_codigo)"
verifica_cabecalho "Location do novo aluno" "^location: .*/api/alunos/${ID:-x}$"

if [ -z "$ID" ]; then
    echo "Não foi possível obter o código do aluno criado; abortando."
    exit 1
fi

echo "Consulta"
requisicao GET "/api/alunos/$ID"
verifica "GET /api/alunos/$ID" 200 '"email":"joao@teste.com"'
requisicao GET "/index.php?url=api/alunos/$ID"
verifica "GET /index.php?url=api/alunos/$ID" 200 '"email":"joao@teste.com"'
requisicao GET "/api/alunos"
verifica "listagem contém o novo aluno" 200 "\"codigo\":$ID,"

echo "Atualização (PUT)"
requisicao PUT "/api/alunos/$ID" '{"nome":"João Alterado","email":"joao.alterado@teste.com"}'
verifica "PUT /api/alunos/$ID" 200 '"nome":"João Alterado"'
verifica "PUT sem telefone deixa telefone nulo" 200 '"telefone":null'
requisicao GET "/api/alunos/$ID"
verifica "GET depois do PUT" 200 '"email":"joao.alterado@teste.com"'
requisicao PUT "/api/alunos/$ID" '{"nome":"João Alterado","email":"joao.alterado@teste.com"}'
verifica "PUT com os mesmos dados" 200 '"nome":"João Alterado"'
requisicao PUT "/api/alunos/$ID" 'nome=Joao+Form&email=jf%40teste.com' application/x-www-form-urlencoded
verifica "PUT form-urlencoded" 200 '"nome":"Joao Form"'

echo "Criação (formulário)"
requisicao POST /api/alunos 'nome=Maria+Form&email=maria%40teste.com&telefone=123' application/x-www-form-urlencoded
verifica "POST form-urlencoded" 201 '"nome":"Maria Form"'
ID_FORM="$(extrai_codigo)"

echo "Erros"
requisicao POST /api/alunos '{"nome":"Sem fim"'
verifica "POST com JSON inválido" 400 '"status":"error"'
requisicao POST /api/alunos '[1,2,3]'
verifica "POST com JSON que não é objeto" 400 '"status":"error"'
requisicao POST /api/alunos '{"email":"x@teste.com"}'
verifica "POST sem nome" 422 'nome é obrigatório'
requisicao POST /api/alunos '{"nome":"Fulano","email":"email-invalido"}'
verifica "POST com email inválido" 422 'email inválido'
requisicao POST /api/alunos "{\"nome\":\"$(printf 'a%.0s' {1..51})\",\"email\":\"a@teste.com\"}"
verifica "POST com nome de 51 caracteres" 422 'nome deve ter no máximo 50 caracteres'
requisicao POST /api/alunos "{\"nome\":\"Fulano\",\"email\":\"$(printf 'a%.0s' {1..41})@teste.com\"}"
verifica "POST com email de 51 caracteres" 422 'email deve ter no máximo 50 caracteres'
requisicao POST /api/alunos '{"nome":"Fulano","email":"a@teste.com","telefone":"12345678901234567890123456"}'
verifica "POST com telefone longo" 422 'telefone deve ter no máximo 25 caracteres'
requisicao POST /api/alunos '{"nome":1.5,"email":"a@teste.com"}'
verifica "POST com nome numérico decimal" 422 'nome deve ser um texto'
requisicao POST /api/alunos '{"nome":"Ana\u0000Nula","email":"a@teste.com"}'
verifica "POST com caractere de controle" 422 'nome contém caracteres inválidos'
requisicao POST /api/alunos 'texto' text/plain
verifica "POST com Content-Type não suportado" 415 '"status":"error"'
requisicao POST /api/alunos '{"nome":"A","email":"a@teste.com"}' text/application/jsonx
verifica "POST com Content-Type parecido com JSON" 415 '"status":"error"'
requisicao PUT "/api/alunos/$ID" 'nome=X&email=x%40teste.com' 'multipart/form-data; boundary=x'
verifica "PUT com multipart" 415 '"status":"error"'
requisicao PUT "/api/alunos/$ID" '{"nome":"","email":"a@teste.com"}'
verifica "PUT com nome vazio" 422 'nome é obrigatório'
requisicao GET /api/alunos/abc
verifica "GET com id não numérico" 400 '"status":"error"'
requisicao GET /api/alunos/0
verifica "GET com id zero" 400 '"status":"error"'
requisicao GET /api/alunos/1.5
verifica "GET com id decimal" 400 '"status":"error"'
requisicao GET /api/alunos/+1
verifica "GET com id com sinal" 400 '"status":"error"'
requisicao GET /api/alunos/%201
verifica "GET com id com espaço" 400 '"status":"error"'
requisicao GET /api/alunos/99999999999999999999
verifica "GET com id grande demais" 400 '"status":"error"'
requisicao GET "/api/alunos/999999999?url=api/alunos/$ID"
verifica "parâmetro url não troca a rota" 404 'Aluno não encontrado'
requisicao GET /api/alunos/999999999
verifica "GET de aluno inexistente" 404 'Aluno não encontrado'
requisicao PUT /api/alunos/999999999 '{"nome":"X","email":"x@teste.com"}'
verifica "PUT de aluno inexistente" 404 'Aluno não encontrado'
requisicao DELETE /api/alunos/999999999
verifica "DELETE de aluno inexistente" 404 'Aluno não encontrado'
requisicao PATCH "/api/alunos/$ID" '{}'
verifica "PATCH não é aceito" 405 'Método não permitido'
verifica_cabecalho "Allow no 405 do item" '^allow: GET, HEAD, PUT, DELETE$'
requisicao PUT /api/alunos '{}'
verifica "PUT sem id" 405 'Método não permitido'
verifica_cabecalho "Allow no 405 da coleção" '^allow: GET, HEAD, POST$'
requisicao HEAD "/api/alunos/$ID"
verifica "HEAD /api/alunos/$ID" 200
requisicao OPTIONS /api/alunos
verifica "OPTIONS /api/alunos" 204
verifica_cabecalho "Allow no OPTIONS" '^allow: OPTIONS, GET, HEAD, POST$'
requisicao GET /api/inexistente
verifica "recurso inexistente" 404 'Rota não encontrada'
requisicao GET /api/AlunosService
verifica "nome de classe na URL" 404 'Rota não encontrada'
requisicao GET "/api/alunos/$ID/extra"
verifica "rota com segmentos a mais" 404 'Rota não encontrada'

echo "Remoção"
requisicao DELETE "/api/alunos/$ID"
verifica "DELETE /api/alunos/$ID" 200 '"status":"success"'
requisicao GET "/api/alunos/$ID"
verifica "GET depois do DELETE" 404 'Aluno não encontrado'
if [ -n "$ID_FORM" ]; then
    requisicao DELETE "/index.php?url=api/alunos/$ID_FORM"
    verifica "DELETE /index.php?url=api/alunos/$ID_FORM" 200 '"status":"success"'
fi

echo
echo "Resultado: $PASSOU ok, $FALHOU falha(s)"
[ "$FALHOU" -eq 0 ]
