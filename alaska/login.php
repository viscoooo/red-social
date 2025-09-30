<?php
/** Página de inicio de sesión mejorada */
require_once 'includes/auth.php';
require_once 'includes/config.php';
redirigirSiAutenticado();

$mensaje=''; $tipo_mensaje=''; $email='';
if (isset($_GET['registro']) && $_GET['registro'] === 'ok' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $mensaje = 'Registro exitoso. Ahora puedes iniciar sesión en ALASKA';
    $tipo_mensaje = 'success';
}
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $email = trim($_POST['email']??'');
    $password = $_POST['password']??'';
    if ($email==='' || $password==='') {
        $mensaje='Por favor completa todos los campos'; $tipo_mensaje='error';
    } else {
        $resultado = verificarCredenciales($pdo,$email,$password);
        if ($resultado['success']) {
            iniciarSesionUsuario($resultado['usuario']['id'],$resultado['usuario']['nombre'],$resultado['usuario']['username'],$resultado['usuario']['email']);
            $redirect = $_GET['redirect'] ?? 'index.php';
            // Sanitizar redirect (no permitir externos)
            if (preg_match('~^https?://~i',$redirect)) { $redirect = 'index.php'; }
            $redirect = trim($redirect);
            $redirect = preg_replace('~^[./]+~','',$redirect);
            redirect($redirect);
        } else { $mensaje=$resultado['message']; $tipo_mensaje='error'; }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Iniciar Sesión - ALASKA</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="<?= app_url('css/estilo.css') ?>">
<style>
    .auth-shell{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:2rem;background:linear-gradient(135deg,var(--primary-50),var(--secondary-50));}
    [data-theme="dark"] .auth-shell{background:linear-gradient(135deg,var(--gray-900),var(--gray-800));}
    .auth-card{width:100%;max-width:440px;background:var(--bg-primary);border:1px solid var(--border-color);border-radius:24px;padding:2.5rem 2.25rem;box-shadow:var(--shadow-lg);display:flex;flex-direction:column;gap:1.25rem;}
    .auth-header{display:flex;flex-direction:column;align-items:center;gap:.75rem;margin-bottom:.5rem;}
    .auth-logo{display:flex;align-items:center;gap:.65rem;font-size:2rem;font-family:var(--font-display);font-weight:700;color:var(--primary-600);}
    .auth-logo i{color:var(--secondary-500);}
    .auth-title{font-size:1.9rem;margin:0;color:var(--text-primary);}
    .alert{padding:.85rem 1rem;border-radius:12px;font-size:.9rem;line-height:1.4;}
    .alert-error{background:rgba(239,68,68,.15);border:1px solid #ef4444;color:#b91c1c;}
    .alert-success{background:rgba(16,185,129,.15);border:1px solid var(--primary-500);color:var(--primary-700);}
    .auth-links{margin-top:.5rem;text-align:center;font-size:.85rem;color:var(--text-secondary);}
    .auth-links a{color:var(--primary-600);font-weight:600;text-decoration:none;}
    .auth-links a:hover{text-decoration:underline;}
    .submit-btn{width:100%;}
    @media (max-width:540px){ .auth-card{margin:1rem;padding:2rem 1.5rem;border-radius:18px;} .auth-title{font-size:1.65rem;} }
</style>
</head>
<body>
<div class="auth-shell">
    <div class="auth-card" role="main">
        <header class="auth-header">
            <div class="auth-logo"><i class="fas fa-paw" aria-hidden="true"></i><span>ALASKA</span></div>
            <h1 class="auth-title">Iniciar Sesión</h1>
        </header>
        <?php if($mensaje): ?><div class="alert alert-<?= $tipo_mensaje ?>" role="alert"><?= htmlspecialchars($mensaje) ?></div><?php endif; ?>
        <form method="POST" novalidate class="element-spacing">
                <div class="form-group"><label for="email">Correo Electrónico</label><input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($email) ?>" required autocomplete="email"></div>
                <div class="form-group"><label for="password">Contraseña</label><input type="password" id="password" name="password" class="form-control" required autocomplete="current-password"></div>
                <button type="submit" class="btn btn-primary submit-btn">Iniciar Sesión</button>
        </form>
    <div class="auth-links">¿No tienes cuenta? <a href="<?= app_url('registro.php') ?>">Regístrate en ALASKA</a></div>
    </div>
</div>
<script>
document.querySelector('form').addEventListener('submit',e=>{
 const email=document.getElementById('email').value.trim();
 const pass=document.getElementById('password').value; if(!email||!pass){e.preventDefault();alert('Completa todos los campos');return;} const re=/^[^\s@]+@[^\s@]+\.[^\s@]+$/; if(!re.test(email)){e.preventDefault();alert('Correo inválido');}
});
</script>
</body>
</html>
