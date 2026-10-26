<?php
/*
Plugin Name: Limite de Tentativas de Login
Description: Mitigacao de forca bruta - bloqueia IP apos N tentativas falhas (Cenario B)
Version: 1.0
*/

// Configuracoes da defesa
define('LL_MAX_TENTATIVAS', 5);        // numero maximo de tentativas
define('LL_TEMPO_BLOQUEIO', 15 * 60);  // tempo de bloqueio em segundos (15 min)

// Obtem o IP de quem esta tentando logar
function ll_obter_ip() {
    return $_SERVER['REMOTE_ADDR'] ?? 'desconhecido';
}

// Chave unica para guardar o contador daquele IP
function ll_chave_ip() {
    return 'll_tentativas_' . md5(ll_obter_ip());
}

// ANTES de autenticar: verifica se o IP esta bloqueado
add_filter('authenticate', function($user, $username, $password) {
    if (empty($username)) {
        return $user;
    }
    $chave = ll_chave_ip();
    $tentativas = get_transient($chave);

    if ($tentativas !== false && $tentativas >= LL_MAX_TENTATIVAS) {
        return new WP_Error(
            'bloqueado',
            'Acesso temporariamente bloqueado por excesso de tentativas. Tente novamente mais tarde.'
        );
    }
    return $user;
}, 30, 3);

// QUANDO o login falha: incrementa o contador
add_action('wp_login_failed', function($username) {
    $chave = ll_chave_ip();
    $tentativas = get_transient($chave);

    if ($tentativas === false) {
        $tentativas = 0;
    }
    $tentativas++;
    set_transient($chave, $tentativas, LL_TEMPO_BLOQUEIO);
});

// QUANDO o login da certo: zera o contador daquele IP
add_action('wp_login', function($user_login) {
    delete_transient(ll_chave_ip());
});
?>
