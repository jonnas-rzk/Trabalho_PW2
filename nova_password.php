<?php
include "db.php";

if (!isset($_SESSION['aluno_id'])) {
    header("Location: login_aluno.php"); exit();
}

$aluno_id = (int)$_SESSION['aluno_id'];
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nova     = $_POST['nova_password']     ?? '';
    $confirma = $_POST['confirma_password'] ?? '';

    if (empty($nova) || empty($confirma)) {
        $erro = "Preenche os dois campos.";
    } elseif (strlen($nova) < 8) {
        $erro = "A password deve ter pelo menos 8 caracteres.";
    } elseif ($nova !== $confirma) {
        $erro = "As passwords não coincidem.";
    } else {
        $hash = password_hash($nova, PASSWORD_BCRYPT);

        $upd = $conn->prepare("UPDATE alunos SET password = ? WHERE id = ?");
        if ($upd->execute([$hash, $aluno_id])) {
            // Buscar nome para limpar password_temp
            $s = $conn->prepare("SELECT nome FROM alunos WHERE id = ?");
            $s->execute([$aluno_id]);
            $nome = $s->fetchColumn();

            $del = $conn->prepare("UPDATE candidaturas SET password_temp = NULL WHERE nome = ? AND password_temp IS NOT NULL");
            $del->execute([$nome]);

            $_SESSION['password_definida'] = true;
            header("Location: dashboard_aluno.php?novoacesso=1");
            exit();
        } else {
            $erro = "Erro ao guardar a password. Tenta novamente.";
        }
    }
}

$s = $conn->prepare("SELECT nome FROM alunos WHERE id = ?");
$s->execute([$aluno_id]);
$aluno = $s->fetch();
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

  <div class="lock-icon"><i class="bi bi-shield-lock"></i></div>

  <div class="pwd-title">Cria a tua Password</div>
  <p class="pwd-sub">
    Olá, <strong><?php echo htmlspecialchars(explode(' ', $aluno['nome'])[0]); ?></strong>!<br>
    É o teu primeiro acesso. Define uma password pessoal para continuar.
  </p>

  <?php if ($erro): ?>
  <div class="erro-box">
    <i class="bi bi-exclamation-circle"></i>
    <?php echo htmlspecialchars($erro); ?>
  </div>
  <?php endif; ?>

  <form method="POST" id="pwdForm" novalidate>

    <div class="requisitos">
      <div class="requisito" id="req-len"><i class="bi bi-circle"></i> Mínimo 8 caracteres</div>
      <div class="requisito" id="req-upper"><i class="bi bi-circle"></i> Uma letra maiúscula</div>
      <div class="requisito" id="req-num"><i class="bi bi-circle"></i> Um número</div>
    </div>

    <div class="field-wrap">
      <label class="field-label">Nova Password</label>
      <div class="field-inner">
        <input type="password" name="nova_password" id="novaPwd" class="field-input"
               placeholder="Cria a tua password" oninput="verificarForca(this.value)" autocomplete="new-password">
        <button type="button" class="toggle-pwd" onclick="toggleVer('novaPwd','eye1')">
          <i class="bi bi-eye" id="eye1"></i>
        </button>
      </div>
      <div class="strength-wrap">
        <div class="strength-bar" id="sb1"></div>
        <div class="strength-bar" id="sb2"></div>
        <div class="strength-bar" id="sb3"></div>
        <div class="strength-bar" id="sb4"></div>
        <span class="strength-label" id="strength-label"></span>
      </div>
    </div>

    <div class="field-wrap">
      <label class="field-label">Confirmar Password</label>
      <div class="field-inner">
        <input type="password" name="confirma_password" id="confirmaPwd" class="field-input"
               placeholder="Repete a password" oninput="verificarMatch()" autocomplete="new-password">
        <button type="button" class="toggle-pwd" onclick="toggleVer('confirmaPwd','eye2')">
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
  let forcaOk = false, matchOk = false;

  function toggleVer(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon  = document.getElementById(iconId);
    input.type  = input.type === 'password' ? 'text' : 'password';
    icon.className = input.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
  }

  function verificarForca(val) {
    const temLen   = val.length >= 8;
    const temUpper = /[A-Z]/.test(val);
    const temNum   = /[0-9]/.test(val);

    setReq('req-len',   temLen);
    setReq('req-upper', temUpper);
    setReq('req-num',   temNum);

    let forca = 0;
    if (temLen)           forca++;
    if (temUpper)         forca++;
    if (temNum)           forca++;
    if (val.length >= 12) forca++;

    const cores  = ['', '#e74c3c', '#f39c12', '#3498db', '#2ecc71'];
    const labels = ['', 'Fraca', 'Razoável', 'Boa', 'Forte'];

    for (let i = 1; i <= 4; i++) {
      document.getElementById('sb' + i).style.background = i <= forca ? cores[forca] : 'rgba(255,255,255,0.1)';
    }
    document.getElementById('strength-label').textContent = forca > 0 ? labels[forca] : '';
    document.getElementById('strength-label').style.color  = forca > 0 ? cores[forca] : 'var(--muted)';

    forcaOk = temLen && temUpper && temNum;
    document.getElementById('novaPwd').className = 'field-input' + (val.length > 0 ? (forcaOk ? ' valid' : ' invalid') : '');
    verificarMatch();
    atualizarBotao();
  }

  function setReq(id, ok) {
    const el = document.getElementById(id);
    el.classList.toggle('ok', ok);
    el.querySelector('i').className = ok ? 'bi bi-check-circle-fill' : 'bi bi-circle';
  }

  function verificarMatch() {
    const nova     = document.getElementById('novaPwd').value;
    const confirma = document.getElementById('confirmaPwd').value;
    const indicator = document.getElementById('matchIndicator');
    const input2    = document.getElementById('confirmaPwd');

    if (!confirma.length) {
      indicator.innerHTML = ''; input2.className = 'field-input'; matchOk = false;
    } else if (nova === confirma) {
      indicator.innerHTML = '<i class="bi bi-check-circle-fill" style="color:#2ecc71;"></i> <span style="color:#2ecc71;">Passwords iguais</span>';
      input2.className = 'field-input valid'; matchOk = true;
    } else {
      indicator.innerHTML = '<i class="bi bi-x-circle-fill" style="color:#e74c3c;"></i> <span style="color:#e74c3c;">Passwords diferentes</span>';
      input2.className = 'field-input invalid'; matchOk = false;
    }
    atualizarBotao();
  }

  function atualizarBotao() {
    document.getElementById('btnGuardar').disabled = !(forcaOk && matchOk);
  }
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
