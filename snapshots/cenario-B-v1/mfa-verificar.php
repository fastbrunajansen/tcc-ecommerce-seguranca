<?php
// Pagina independente de verificacao do codigo MFA (Cenario B)
require_once(__DIR__ . '/../../wp-load.php');
require_once(__DIR__ . '/totp-lib.php');

$token = $_GET['t'] ?? ($_POST['t'] ?? '');
$user_id = $token ? get_transient('mfa_pend_' . $token) : false;
$erro = '';

if (!$user_id) {
    $erro = 'Sessao de verificacao expirada ou invalida. Faca login novamente.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo = trim($_POST['mfa_code'] ?? '');
    $secret = get_user_meta($user_id, 'mfa_secret', true);
    $ga = new PHPGangsta_GoogleAuthenticator();

    if (empty($codigo)) {
        $erro = 'Digite o codigo do aplicativo autenticador.';
    } elseif (!$ga->verifyCode($secret, $codigo, 2)) {
        $erro = 'Codigo invalido. Tente novamente.';
    } else {
        delete_transient('mfa_pend_' . $token);
        $user = get_user_by('id', $user_id);
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id);
        wp_redirect(admin_url());
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Verificação em duas etapas</title>
    <style>
        body { font-family: -apple-system, sans-serif; background: #f0f0f1; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .caixa { background: #fff; padding: 32px; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,.1); width: 320px; }
        h1 { font-size: 20px; margin-top: 0; }
        input[type=text] { width: 100%; padding: 10px; font-size: 18px; letter-spacing: 4px; text-align: center; box-sizing: border-box; margin-bottom: 16px; }
        button { width: 100%; padding: 10px; background: #2271b1; color: #fff; border: none; border-radius: 4px; font-size: 15px; cursor: pointer; }
        .erro { background: #fcf0f1; border-left: 4px solid #d63638; padding: 10px; margin-bottom: 16px; font-size: 14px; }
    </style>
</head>
<body>
    <div class="caixa">
        <h1>Verificação em duas etapas</h1>
        <?php if ($erro): ?>
            <div class="erro"><?php echo esc_html($erro); ?></div>
        <?php endif; ?>
        <?php if ($user_id): ?>
        <form method="post">
            <input type="hidden" name="t" value="<?php echo esc_attr($token); ?>">
            <label>Código do aplicativo autenticador</label>
            <input type="text" name="mfa_code" inputmode="numeric" pattern="[0-9]*" maxlength="6" autofocus autocomplete="off">
            <button type="submit">Verificar</button>
        </form>
        <?php else: ?>
            <p><a href="<?php echo wp_login_url(); ?>">Voltar para o login</a></p>
        <?php endif; ?>
    </div>
</body>
</html>
