<?php
// Página de consulta de produto por ID — VERSÃO CORRIGIDA (Cenário B, versão 2)

// DEFESA 1: valida que o id é um número inteiro positivo
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

if ($id === false || $id === null) {
    echo "Parâmetro inválido.";
    exit;
}

// v2: a partir do PHP 8.1, o mysqli lança exceções em caso de erro; elas são capturadas
// para que nenhum detalhe interno (mensagem do banco, caminho de arquivo) chegue ao usuário.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // v2: credenciais lidas das variáveis de ambiente definidas no compose.yml,
    // em vez de escritas no código (CWE-798)
    $conexao = new mysqli(
        getenv('WORDPRESS_DB_HOST'),
        getenv('WORDPRESS_DB_USER'),
        getenv('WORDPRESS_DB_PASSWORD'),
        getenv('WORDPRESS_DB_NAME')
    );

    // DEFESA 2: consulta parametrizada (prepared statement)
    // O valor NÃO é concatenado; entra como parâmetro tratado pelo banco.
    // v2: só retorna produtos publicados (antes, qualquer ID de wp_posts devolvia o título,
    // inclusive rascunhos e páginas privadas).
    $stmt = $conexao->prepare(
        "SELECT ID, post_title FROM wp_posts WHERE ID = ? AND post_type = 'product' AND post_status = 'publish'"
    );
    $stmt->bind_param("i", $id);   // "i" = inteiro
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        while ($linha = $resultado->fetch_assoc()) {
            echo "<h2>" . htmlspecialchars($linha['post_title']) . "</h2>";
            echo "<p>ID: " . htmlspecialchars($linha['ID']) . "</p>";
        }
    } else {
        echo "Nenhum produto encontrado.";
    }

    $stmt->close();
    $conexao->close();
} catch (mysqli_sql_exception $e) {
    // DEFESA 3: tratamento de erro seguro — registra no log, mostra mensagem genérica
    error_log("busca-produto: " . $e->getMessage());
    http_response_code(500);
    echo "Erro interno. Tente novamente mais tarde.";
}
?>
