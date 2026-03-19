<?php
include "db.php";

// Se já tem sessão admin ativa vai direto ao painel
if (isset($_SESSION['tipo'])) {
    header("Location: dashboard_admin.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — IPCA</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">
<link rel="stylesheet" href="css/login.css">
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg fixed-top py-2">
  <div class="container-fluid px-4">
    <a class="navbar-brand d-flex align-items-center gap-2 text-decoration-none" href="index.php">
      <div class="logo-icon">IP</div>
      <div>
        <div class="brand-name">IPCA</div>
        <div class="brand-sub">Instituto Politécnico</div>
      </div>
    </a>
  </div>
</nav>

<!-- CONTEÚDO -->
<div class="container" style="position:relative;z-index:1;">
  <div class="row justify-content-center">
    <div class="col-md-5 col-sm-10">

      <div class="login-box">

        <!-- Cabeçalho -->
        <div class="text-center mb-4">
          <div class="logo-icon mb-3 mx-auto" style="width:60px;height:60px;font-size:22px;border-radius:50%;">IP</div>
          <h2 class="section-title mb-1" style="font-size:26px;">Acesso ao Portal</h2>
          <p style="color:var(--muted);font-size:14px;">Administradores &amp; Professores</p>
        </div>

        <!-- Erro de login -->
        <?php if (isset($_GET['erro'])): ?>
        <div class="alert alert-danger py-2 mb-3">
          <i class="bi bi-exclamation-circle me-1"></i>
          Credenciais inválidas. Tenta novamente.
        </div>
        <?php endif; ?>

        <!-- Formulário -->
        <form action="processar_login.php" method="POST">

          <div class="mb-3">
            <label class="form-label">Login ou Email</label>
            <input type="text" name="usuario" class="form-control"
                   placeholder="utilizador ou email@ipca.pt" autocomplete="username" required>
          </div>

          <div class="mb-4">
            <label class="form-label">Palavra-passe</label>
            <div class="position-relative">
              <input type="password" name="senha" id="senhaInput" class="form-control"
                     placeholder="••••••••" autocomplete="current-password" required
                     style="padding-right:44px;">
              <button type="button"
                      onclick="togglePwd()"
                      style="position:absolute;top:50%;right:12px;transform:translateY(-50%);
                             background:none;border:none;color:var(--muted);cursor:pointer;
                             font-size:16px;padding:0;line-height:1;">
                <i class="bi bi-eye" id="eyeIcon"></i>
              </button>
            </div>
          </div>

          <button type="submit" class="btn-cta-primary mb-3">
            Entrar no Sistema <i class="bi bi-box-arrow-in-right ms-1"></i>
          </button>

        </form>

        <!-- Divisor -->
        <div class="login-divider">ou</div>

        <!-- Link aluno -->
        <a href="login_aluno.php" class="btn-cta-secondary">
          <i class="bi bi-mortarboard"></i> Entrar como Aluno
        </a>

        <!-- Voltar -->
        <div class="text-center mt-4">
          <a href="index.php" style="color:var(--muted);text-decoration:none;font-size:13px;">
            <i class="bi bi-arrow-left me-1"></i>Voltar ao site
          </a>
        </div>

      </div><!-- /login-box -->
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  function togglePwd() {
    const input = document.getElementById('senhaInput');
    const icon  = document.getElementById('eyeIcon');
    if (input.type === 'password') {
      input.type  = 'text';
      icon.className = 'bi bi-eye-slash';
    } else {
      input.type  = 'password';
      icon.className = 'bi bi-eye';
    }
  }
</script>
</body>
</html>