<?php
require_once __DIR__ . '/../includes/db.php';
if (session_status()===PHP_SESSION_NONE) session_start();
if (!empty($_SESSION['admin_user'])) { header('Location: index.php'); exit; }
$err='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $u = trim($_POST['username'] ?? '');
  $p = (string)($_POST['password'] ?? '');
  $csrf = $_POST['csrf_token'] ?? '';
  if (!verify_csrf($csrf)) $err='Invalid session — please refresh.';
  else {
    $expectedUser = get_setting('admin_username','admin');
    $hash = get_setting('admin_password_hash','');
    if ($u === $expectedUser && $hash && password_verify($p, $hash)) {
      $_SESSION['admin_user']=$u;
      // rotate CSRF
      $_SESSION['csrf_token']=bin2hex(random_bytes(32));
      header('Location: index.php'); exit;
    } else $err='Invalid username or password.';
  }
}
?>
<!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Login — Nena Studio</title>
<meta name="robots" content="noindex, nofollow">
<link rel="stylesheet" href="../assets/css/style.css">
<style>
  body{display:grid; place-items:center; min-height:100vh; padding:18px}
  .login-card{width:min(420px,96vw); border-radius:22px; padding:22px}
  .login-card h1{font-size:26px; margin:0 0 6px}
  .field input{width:100%; padding:12px; border-radius:12px; border:1px solid rgba(0,109,119,0.14); background:rgba(255,255,255,0.92); font:500 14px 'Plus Jakarta Sans',sans-serif}
  .err{margin:10px 0 0; padding:10px 12px; border-radius:12px; background:rgba(180,40,40,0.08); border:1px solid rgba(180,40,40,0.16); color:#7a1f1f; font-size:13px}
</style>
</head><body>
<div class="bg-auras" aria-hidden="true"><div class="aura aura--1"></div><div class="aura aura--2"></div></div>
<form method="post" class="login-card glass stitch">
  <div style="display:flex; gap:10px; align-items:center; margin-bottom:10px">
    <span style="width:38px; height:38px; border-radius:50%; display:grid; place-items:center; background:rgba(42,157,143,0.10); border:1px solid rgba(42,157,143,0.14)">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#006D77" stroke-width="1.6"><path d="M6 20 L18 8"/><path d="M7 8 Q12 6 17 8"/></svg>
    </span>
    <div><strong style="font-family:'Cormorant Garamond',serif; font-size:18px; color:#006D77">Nena Studio</strong><br><span style="font-size:12px; color:var(--muted)">Admin — sign in</span></div>
  </div>
  <h1>Welcome back</h1>
  <p class="muted" style="margin:0 0 14px; font-size:13px">Default: <code>admin</code> / <code>Nena2026!</code> — change it after first login in Settings.</p>
  <?php if($err): ?><div class="err" role="alert"><?= htmlspecialchars($err) ?></div><?php endif; ?>
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
  <div class="field" style="margin-top:12px"><label for="username">Username</label><input id="username" name="username" required autocomplete="username" value="<?= htmlspecialchars($_POST['username'] ?? 'admin') ?>"></div>
  <div class="field"><label for="password">Password</label><input id="password" name="password" type="password" required autocomplete="current-password"></div>
  <button class="btn btn--primary" style="width:100%; margin-top:12px" type="submit">Sign in →</button>
  <p style="text-align:center; margin:10px 0 0"><a href="../" style="font-size:13px">← Back to website</a></p>
</form>
</body></html>
