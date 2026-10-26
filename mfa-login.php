<?php
/*
Plugin Name: MFA - Autenticacao em Duas Etapas
Description: Autenticacao multifator via TOTP, com pagina de verificacao propria (Cenario B)
Version: 3.1
*/

require_once(__DIR__ . '/totp-lib.php');

// Campo de MFA no perfil do usuario
add_action('show_user_profile', 'mfa_mostrar_campo');
add_action('edit_user_profile', 'mfa_mostrar_campo');
function mfa_mostrar_campo($user) {
    $ga = new PHPGangsta_GoogleAuthenticator();
    $secret = get_user_meta($user->ID, 'mfa_secret', true);
    $ativo = get_user_meta($user->ID, 'mfa_ativo', true);
    // Segredo de 32 caracteres base32 = 160 bits (RFC 4226, R6: minimo de 128, recomendado 160).
    // Chaves antigas (16 caracteres = 80 bits) sao trocadas quando o MFA esta desativado.
    if (!$secret || (strlen($secret) < 32 && $ativo !== '1')) {
        $secret = $ga->createSecret(32);
        update_user_meta($user->ID, 'mfa_secret', $secret);
        delete_user_meta($user->ID, 'mfa_ultimo_passo');
    }
    ?>
    <h2>Autenticacao em Duas Etapas (MFA)</h2>
    <table class="form-table">
        <tr><th>Status</th><td><?php echo $ativo ? '<strong style="color:green">Ativo</strong>' : '<strong style="color:red">Inativo</strong>'; ?></td></tr>
        <tr><th>Chave secreta</th><td><code style="font-size:16px"><?php echo esc_html($secret); ?></code>
            <?php if (strlen($secret) < 32): ?><p style="color:#b32d2e">Chave antiga (80 bits). Desative o MFA e salve o perfil para gerar uma nova chave de 160 bits.</p><?php endif; ?></td></tr>
        <tr><th>Ativar MFA</th><td><label><input type="checkbox" name="mfa_ativo" value="1" <?php checked($ativo, '1'); ?> /> Exigir codigo no login</label></td></tr>
    </table>
    <?php
}
add_action('personal_options_update', 'mfa_salvar_campo');
add_action('edit_user_profile_update', 'mfa_salvar_campo');
function mfa_salvar_campo($user_id) {
    update_user_meta($user_id, 'mfa_ativo', isset($_POST['mfa_ativo']) ? '1' : '');
}

// Intercepta apos validar usuario+senha: se MFA ativo, redireciona para pagina propria
add_filter('authenticate', 'mfa_interceptar', 40, 3);
function mfa_interceptar($user, $username, $password) {
    if (!($user instanceof WP_User) || empty($password)) {
        return $user; // senha errada, deixa o WordPress mostrar o erro normal
    }

    $ativo = get_user_meta($user->ID, 'mfa_ativo', true);
    if ($ativo !== '1') {
        return $user; // sem MFA, login segue normal
    }

    // Senha certa + MFA ativo: cria um token temporario e redireciona
    $token = wp_generate_password(32, false);
    set_transient('mfa_pend_' . $token, $user->ID, 5 * 60); // valido 5 minutos

    wp_redirect(site_url('/wp-content/plugins/mfa-verificar.php?t=' . $token));
    exit;
}
?>
