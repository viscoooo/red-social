<?php
/** Registro de usuarios mejorado */
require_once 'includes/auth.php';
require_once 'includes/config.php';
redirigirSiAutenticado();

$errores=[]; $nombre=''; $username=''; $email='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $nombre=trim($_POST['nombre']??'');
    $username=trim($_POST['username']??'');
    $email=trim($_POST['email']??'');
    $password=$_POST['password']??''; $password2=$_POST['password2']??'';
    if ($password !== $password2) {
        $errores[] = 'Las contraseñas no coinciden';
    } else {
        $resultado = registrarUsuario($pdo,$nombre,$username,$email,$password);
    if($resultado['success']){ redirect('login.php?registro=ok'); }
        else {
            if(isset($resultado['errores'])) { $errores = array_merge($errores,$resultado['errores']); }
            elseif(isset($resultado['message'])) { $errores[] = $resultado['message']; }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Registro - ALASKA</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="<?= app_url('css/estilo.css') ?>">
<style>
    .auth-shell{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:2rem;background:linear-gradient(135deg,var(--primary-50),var(--secondary-50));}
    [data-theme="dark"] .auth-shell{background:linear-gradient(135deg,var(--gray-900),var(--gray-800));}
    .auth-card{width:100%;max-width:560px;background:var(--bg-primary);border:1px solid var(--border-color);border-radius:28px;padding:2.75rem 2.4rem;box-shadow:var(--shadow-lg);display:flex;flex-direction:column;gap:1.25rem;}
    .auth-header{text-align:center;display:flex;flex-direction:column;gap:.35rem;margin-bottom:.5rem;}
    .auth-title{font-size:2rem;color:var(--text-primary);margin:0;font-family:var(--font-display);}
    .subtitle{font-size:1rem;font-weight:600;color:var(--secondary-600);margin:0 0 1.2rem;letter-spacing:.5px;text-transform:uppercase;}
    .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:1.1rem;} .form-grid .full{grid-column:1/-1;}
    .alert{padding:.9rem 1rem;border-radius:14px;font-size:.9rem;}
    .alert-error{background:rgba(239,68,68,.15);border:1px solid #ef4444;color:#b91c1c;}
    ul.error-list{margin:0;padding-left:18px;}
    .auth-links{margin-top:.75rem;text-align:center;font-size:.85rem;color:var(--text-secondary);padding-top:1.2rem;border-top:1px solid var(--border-color);} .auth-links a{color:var(--primary-600);font-weight:600;text-decoration:none;} .auth-links a:hover{text-decoration:underline;}
    .btn-primary{width:100%;}
    @media (max-width:640px){ .auth-card{margin:1rem;padding:2.25rem 1.6rem;border-radius:22px;} .auth-title{font-size:1.75rem;} .form-grid{grid-template-columns:1fr;} }
</style>
</head>
<body>
<div class="auth-shell">
    <div class="auth-card" role="main">
        <div class="auth-header">
            <h1 class="auth-title"><i class="fas fa-paw" aria-hidden="true"></i> ALASKA</h1>
            <p class="subtitle">Crear Cuenta</p>
        </div>
        <?php if($errores): ?>
            <div class="alert alert-error" role="alert"><ul class="error-list"><?php foreach($errores as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>
        <form method="POST" novalidate class="element-spacing">
            <div class="form-grid">
                <div class="form-group"><label for="nombre">Nombre Completo</label><input type="text" id="nombre" name="nombre" class="form-control" required value="<?= htmlspecialchars($nombre) ?>" minlength="2" maxlength="120" autocomplete="name"></div>
                <div class="form-group"><label for="username">Nombre de Usuario</label><input type="text" id="username" name="username" class="form-control" required value="<?= htmlspecialchars($username) ?>" pattern="^[A-Za-z0-9_]{3,30}$" title="Solo letras, números y guiones bajos (3-30)" autocomplete="username"></div>
                <div class="form-group full"><label for="email">Correo Electrónico</label><input type="email" id="email" name="email" class="form-control" required value="<?= htmlspecialchars($email) ?>" autocomplete="email"></div>
                <div class="form-group"><label for="password">Contraseña</label><input type="password" id="password" name="password" class="form-control" required minlength="6" autocomplete="new-password"></div>
                <div class="form-group"><label for="password2">Repetir Contraseña</label><input type="password" id="password2" name="password2" class="form-control" required minlength="6" autocomplete="new-password"></div>
                <div class="form-group full"><button type="submit" class="btn btn-primary">Registrarse</button></div>
            </div>
        </form>
    <div class="auth-links">¿Ya tienes cuenta? <a href="<?= app_url('login.php') ?>">Inicia sesión en ALASKA</a></div>
    </div>
</div>
<script>
const f=document.querySelector('form');
f.addEventListener('submit',e=>{
 const errs=[]; const nombre=f.nombre.value.trim(); const user=f.username.value.trim(); const email=f.email.value.trim(); const p1=f.password.value; const p2=f.password2.value;
 if(nombre.length<2) errs.push('Nombre muy corto');
 if(!/^[A-Za-z0-9_]{3,30}$/.test(user)) errs.push('Usuario inválido');
 if(!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) errs.push('Correo inválido');
 if(p1.length<6) errs.push('Contraseña mínima 6 caracteres');
 if(p1!==p2) errs.push('Las contraseñas no coinciden');
 if(errs.length){ e.preventDefault(); alert(errs.join('\n')); }
});
</script>
</body>
</html>
