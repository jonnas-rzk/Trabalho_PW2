<?php
include "db.php";

// ── Proteção: tem de estar logado
if (!isset($_SESSION['aluno_id'])) {
    header("Location: login_aluno.php");
    exit();
}

$aluno_id = (int)$_SESSION['aluno_id'];
$erro     = '';
$sucesso  = false;

// ── Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nova    = $_POST['nova_password']     ?? '';
    $confirma = $_POST['confirma_password'] ?? '';

    if (empty($nova) || empty($confirma)) {
        $erro = "Preenche os dois campos.";

    } elseif (strlen($nova) < 8) {
        $erro = "A password deve ter pelo menos 8 caracteres.";

    } elseif ($nova !== $confirma) {
        $erro = "As passwords não coincidem.";

    } else {
        // Hash da nova password
        $novo_hash = password_hash($nova, PASSWORD_BCRYPT);

        // Atualizar na tabela alunos
        $stmt = $conn->prepare("UPDATE alunos SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $novo_hash, $aluno_id);

        if ($stmt->execute()) {
            // Apagar password_temp da candidatura
            $nome_stmt = $conn->prepare("SELECT nome FROM alunos WHERE id = ?");
            $nome_stmt->bind_param("i", $aluno_id);
            $nome_stmt->execute();
            $nome_aluno = $nome_stmt->get_result()->fetch_assoc()['nome'];

            $del = $conn->prepare("UPDATE candidaturas SET password_temp = NULL WHERE nome = ? AND password_temp IS NOT NULL");
            $del->bind_param("s", $nome_aluno);
            $del->execute();

            // Marcar na sessão que já definiu password
            $_SESSION['password_definida'] = true;

            // Redirecionar para o dashboard
            header("Location: dashboard_aluno.php?novoacesso=1");
            exit();
        } else {
            $erro = "Erro ao guardar a password. Tenta novamente.";
        }
    }
}

// ── Buscar nome do aluno para personalizar
$stmt = $conn->prepare("SELECT nome FROM alunos WHERE id = ?");
$stmt->bind_param("i", $aluno_id);
$stmt->execute();
$aluno = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Criar Nova Password — IPCA</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/nova_password.css">
<link rel="stylesheet" href="css/style.css">

</head>
<body>

<div class="pwd-card">

  <!-- Ícone -->
  <div class="lock-icon">
    <i class="bi bi-shield-lock"></i>
  </div>

  <!-- Título -->
  <div class="pwd-title">Cria a tua Password</div>
  <p class="pwd-sub">
    Olá, <strong><?php echo htmlspecialchars(explode(' ', $aluno['nome'])[0]); ?></strong>!<br>
    É o teu primeiro acesso. Define uma password pessoal para continuar.
  </p>

  <!-- Erro -->
  <?php if ($erro): ?>
  <div class="erro-box">
    <i class="bi bi-exclamation-circle"></i>
    <?php echo htmlspecialchars($erro); ?>
  </div>
  <?php endif; ?>

  <!-- Formulário -->
  <form method="POST" id="pwdForm" novalidate>

    <!-- Requisitos -->
    <div class="requisitos">
      <div class="requisito" id="req-len">
        <i class="bi bi-circle"></i> Mínimo 8 caracteres
      </div>
      <div class="requisito" id="req-upper">
        <i class="bi bi-circle"></i> Uma letra maiúscula
      </div>
      <div class="requisito" id="req-num">
        <i class="bi bi-circle"></i> Um número
      </div>
    </div>

    <!-- Nova password -->
    <div class="field-wrap">
      <label class="field-label">Nova Password</label>
      <div class="field-inner">
        <input type="password" name="nova_password" id="novaPwd"
               class="field-input" placeholder="Cria a tua password"
               oninput="verificarForca(this.value)" autocomplete="new-password">
        <button type="button" class="toggle-pwd" onclick="toggleVer('novaPwd', 'eye1')">
          <i class="bi bi-eye" id="eye1"></i>
        </button>
      </div>
      <!-- Barra de força -->
      <div class="strength-wrap">
        <div class="strength-bar" id="sb1"></div>
        <div class="strength-bar" id="sb2"></div>
        <div class="strength-bar" id="sb3"></div>
        <div class="strength-bar" id="sb4"></div>
        <span class="strength-label" id="strength-label"></span>
      </div>
    </div>

    <!-- Confirmar password -->
    <div class="field-wrap">
      <label class="field-label">Confirmar Password</label>
      <div class="field-inner">
        <input type="password" name="confirma_password" id="confirmaPwd"
               class="field-input" placeholder="Repete a password"
               oninput="verificarMatch()" autocomplete="new-password">
        <button type="button" class="toggle-pwd" onclick="toggleVer('confirmaPwd', 'eye2')">
          <i class="bi bi-eye" id="eye2"></i>
        </button>
      </div>
      <div class="match-indicator" id="matchIndicator"></div>
    </div>

    <button type="submit" class="btn-guardar" id="btnGuardar" disabled>
      <i class="bi bi-shield-check"></i> Guardar e Entrar
    </button>

  </form>

</div>

<script>
  let forcaOk = false;
  let matchOk = false;

  // ── Mostrar/esconder password
  function toggleVer(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon  = document.getElementById(iconId);
    input.type  = input.type === 'password' ? 'text' : 'password';
    icon.className = input.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
  }

  // ── Verificar força da password
  function verificarForca(val) {
    const temLen   = val.length >= 8;
    const temUpper = /[A-Z]/.test(val);
    const temNum   = /[0-9]/.test(val);

    // Atualizar requisitos visuais
    setReq('req-len',   temLen);
    setReq('req-upper', temUpper);
    setReq('req-num',   temNum);

    // Calcular força (0-4)
    let forca = 0;
    if (temLen)                forca++;
    if (temUpper)              forca++;
    if (temNum)                forca++;
    if (val.length >= 12)      forca++;

    // Cores das barras
    const cores = ['', '#e74c3c', '#f39c12', '#3498db', '#2ecc71'];
    const labels = ['', 'Fraca', 'Razoável', 'Boa', 'Forte'];

    for (let i = 1; i <= 4; i++) {
      document.getElementById('sb' + i).style.background =
        i <= forca ? cores[forca] : 'rgba(255,255,255,0.1)';
    }

    document.getElementById('strength-label').textContent  = forca > 0 ? labels[forca] : '';
    document.getElementById('strength-label').style.color  = forca > 0 ? cores[forca] : 'var(--muted)';

    forcaOk = temLen && temUpper && temNum;

    // Atualizar campo visual
    const input = document.getElementById('novaPwd');
    input.className = 'field-input' + (val.length > 0 ? (forcaOk ? ' valid' : ' invalid') : '');

    verificarMatch();
    atualizarBotao();
  }

  function setReq(id, ok) {
    const el = document.getElementById(id);
    el.classList.toggle('ok', ok);
    el.querySelector('i').className = ok ? 'bi bi-check-circle-fill' : 'bi bi-circle';
  }

  // ── Verificar se as passwords coincidem
  function verificarMatch() {
    const nova     = document.getElementById('novaPwd').value;
    const confirma = document.getElementById('confirmaPwd').value;
    const indicator = document.getElementById('matchIndicator');
    const input2    = document.getElementById('confirmaPwd');

    if (confirma.length === 0) {
      indicator.innerHTML = '';
      input2.className = 'field-input';
      matchOk = false;
    } else if (nova === confirma) {
      indicator.innerHTML = '<i class="bi bi-check-circle-fill" style="color:#2ecc71;"></i> <span style="color:#2ecc71;">Passwords iguais</span>';
      input2.className = 'field-input valid';
      matchOk = true;
    } else {
      indicator.innerHTML = '<i class="bi bi-x-circle-fill" style="color:#e74c3c;"></i> <span style="color:#e74c3c;">Passwords diferentes</span>';
      input2.className = 'field-input invalid';
      matchOk = false;
    }

    atualizarBotao();
  }

  // ── Activar/desactivar botão
  function atualizarBotao() {
    document.getElementById('btnGuardar').disabled = !(forcaOk && matchOk);
  }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>