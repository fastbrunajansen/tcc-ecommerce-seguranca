<?php
// Página de consulta de produto por ID — VERSÃO VULNERÁVEL (Cenário A)
$conexao = new mysqli("db", "loja_user", "senha_teste_local", "loja");

if ($conexao->connect_error) {
    die("Falha na conexão: " . $conexao->connect_error);
}

$id = $_GET['id'];

// PONTO VULNERÁVEL: id concatenado direto, sem sanitização
$sql = "SELECT ID, post_title, post_content FROM wp_posts WHERE ID = " . $id;

$resultado = $conexao->query($sql);

if ($resultado && $resultado->num_rows > 0) {
    while ($linha = $resultado->fetch_assoc()) {
        echo "<h2>" . htmlspecialchars($linha['post_title']) . "</h2>";
        echo "<p>ID: " . htmlspecialchars($linha['ID']) . "</p>";
    }
} else {
    echo "Nenhum produto encontrado. ";
    if ($conexao->error) {
        echo "Erro: " . $conexao->error;
    }
}

$conexao->close();
?>
