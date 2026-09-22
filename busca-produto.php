<?php
// Página de consulta de produto por ID — VERSÃO CORRIGIDA (Cenário B)
$conexao = new mysqli("db", "loja_user", "senha_teste_local", "loja");

if ($conexao->connect_error) {
    // DEFESA: não expõe detalhes do erro ao usuário
    http_response_code(500);
    error_log("Erro de conexao: " . $conexao->connect_error);
    die("Erro interno. Tente novamente mais tarde.");
}

// DEFESA 1: valida que o id é um número inteiro
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($id === false || $id === null) {
    echo "Parâmetro inválido.";
    $conexao->close();
    exit;
}

// DEFESA 2: consulta parametrizada (prepared statement)
// O valor NÃO é concatenado; entra como parâmetro tratado pelo banco
$stmt = $conexao->prepare("SELECT ID, post_title, post_content FROM wp_posts WHERE ID = ?");
$stmt->bind_param("i", $id);   // "i" = inteiro
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado && $resultado->num_rows > 0) {
    while ($linha = $resultado->fetch_assoc()) {
        echo "<h2>" . htmlspecialchars($linha['post_title']) . "</h2>";
        echo "<p>ID: " . htmlspecialchars($linha['ID']) . "</p>";
    }
} else {
    echo "Nenhum produto encontrado.";
}

$stmt->close();
$conexao->close();
?>
