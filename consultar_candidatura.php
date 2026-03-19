<?php
include "db.php";

$resultado  = null;
$erro       = null;
$aluno_info = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $nif   = trim($_POST['nif']   ?? '');

    if (empty($email) || empty($nif)) {
        $erro = "Preenche todos os campos.";
    } else {

        // ── 1. Buscar candidatura por email de contacto + NIF
        $stmt = $conn->prepare("
            SELECT nome, email, estado
            FROM candidaturas
            WHERE email = ? AND nif = ?
        ");
        $stmt->bind_param("ss", $email, $nif);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows === 0) {
            $erro = "Dados não encontrados. Verifica o teu email e NIF.";
        } else {
            $resultado = $res->fetch_assoc();

            // ── 2. Se aprovado, buscar número de aluno + password_temp da candidatura
            if ($resultado['estado'] === 'aprovado') {
                $stmt2 = $conn->prepare("
                    SELECT a.numero_aluno, c.password_temp
                    FROM candidaturas c
                    JOIN alunos a ON a.nome = c.nome AND a.curso_id = c.curso_id
                    WHERE c.email = ? AND c.nif = ?
                    LIMIT 1
                ");
                $stmt2->bind_param("ss", $email, $nif);
                $stmt2->execute();
                $res2 = $stmt2->get_result();

                if ($res2->num_rows > 0) {
                    $aluno_info = $res2->fetch_assoc();
                    $aluno_info['email_institucional'] = "a" . $aluno_info['numero_aluno'] . "@alunos.ipca.pt";
                    $aluno_info['password']            = $aluno_info['password_temp'] ?? null;
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Consultar Candidatura — IPCA</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/consultar_candidatura.css">


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
    <a href="login_aluno.php" style="color:var(--muted);text-decoration:none;font-size:13px;display:flex;align-items:center;gap:6px;">
      <i class="bi bi-box-arrow-in-right"></i> Área do Aluno
    </a>
  </div>
</nav>

<!-- MAIN -->
<div class="page-wrap">
  <div class="card-wrap">

    <!-- Cabeçalho -->
    <div class="page-header">
      <div class="page-badge">
        <i class="bi bi-search"></i>
        Consulta de Candidatura
      </div>
      <h1 class="page-title">Estado da<br>tua Candidatura</h1>
      <p class="page-desc">
        Introduz o teu email de contacto e NIF para<br>
        verificar o estado da tua candidatura ao IPCA.
      </p>
    </div>

    <!-- Card principal -->
    <div class="form-card">

      <!-- Formulário -->
      <form method="POST" novalidate>

        <div class="mb-3">
          <label class="field-label">Email de Contacto</label>
          <div class="field-icon-wrap">
            <i class="bi bi-envelope"></i>
            <input type="email" name="email" class="field-input"
                   placeholder="exemplo@gmail.com"
                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                   required>
          </div>
        </div>

        <div class="mb-4">
          <label class="field-label">NIF</label>
          <div class="field-icon-wrap">
            <i class="bi bi-credit-card-2-front"></i>
            <input type="text" name="nif" class="field-input"
                   placeholder="123456789"
                   value="<?php echo htmlspecialchars($_POST['nif'] ?? ''); ?>"
                   maxlength="20" required>
          </div>
        </div>

        <button type="submit" class="btn-submit">
          <i class="bi bi-search"></i> Consultar Estado
        </button>

      </form>

      <!-- ── RESULTADOS ── -->
      <?php if ($erro): ?>

        <hr class="divider">
        <div class="state-error">
          <div class="state-icon"><i class="bi bi-x-circle"></i></div>
          <div>
            <h5>Dados não encontrados</h5>
            <p><?php echo htmlspecialchars($erro); ?></p>
          </div>
        </div>

      <?php elseif ($resultado): ?>

        <hr class="divider">

        <?php if ($resultado['estado'] === 'pendente'): ?>

          <!-- PENDENTE -->
          <div class="state-pending">
            <div class="state-icon"><i class="bi bi-hourglass-split"></i></div>
            <div style="flex:1;">
              <h5>Candidatura em análise</h5>
              <p>
                Olá, <strong style="color:var(--text);"><?php echo htmlspecialchars($resultado['nome']); ?></strong>!
                A tua candidatura está a ser analisada pelos Serviços Académicos. Verifica esta página regularmente.
              </p>
              <div class="progress-steps">
                <div class="step">
                  <div class="step-dot done"><i class="bi bi-check"></i></div>
                  <div class="step-label done">Submetida</div>
                </div>
                <div class="step-line done"></div>
                <div class="step">
                  <div class="step-dot active"><i class="bi bi-three-dots"></i></div>
                  <div class="step-label active">Em Análise</div>
                </div>
                <div class="step-line"></div>
                <div class="step">
                  <div class="step-dot todo">3</div>
                  <div class="step-label">Decisão</div>
                </div>
                <div class="step-line"></div>
                <div class="step">
                  <div class="step-dot todo">4</div>
                  <div class="step-label">Acesso</div>
                </div>
              </div>
            </div>
          </div>

        <?php elseif ($resultado['estado'] === 'aprovado' && $aluno_info): ?>

          <!-- APROVADO -->
          <div class="state-approved">

            <div class="approved-header">
              <div class="approved-icon"><i class="bi bi-patch-check-fill"></i></div>
              <div>
                <h5>Candidatura Aprovada!</h5>
                <p>Bem-vindo ao IPCA, <?php echo htmlspecialchars($resultado['nome']); ?></p>
              </div>
            </div>

            <div class="cred-grid">

              <div class="cred-row">
                <span class="cred-key">Nº de Processo</span>
                <div class="d-flex align-items-center gap-2">
                  <span class="cred-val highlight"><?php echo $aluno_info['numero_aluno']; ?></span>
                  <button class="copy-btn" onclick="copiar('<?php echo $aluno_info['numero_aluno']; ?>', this)">
                    <i class="bi bi-copy"></i>
                  </button>
                </div>
              </div>

              <div class="cred-row">
                <span class="cred-key">Email Institucional</span>
                <div class="d-flex align-items-center gap-2">
                  <span class="cred-val highlight"><?php echo $aluno_info['email_institucional']; ?></span>
                  <button class="copy-btn" onclick="copiar('<?php echo $aluno_info['email_institucional']; ?>', this)">
                    <i class="bi bi-copy"></i>
                  </button>
                </div>
              </div>

              <div class="cred-row">
                <span class="cred-key">Password Provisória</span>
                <div class="d-flex align-items-center gap-2">
                  <?php if ($aluno_info['password']): ?>
                    <span class="cred-val" id="pwd-val">••••••••••</span>
                    <button class="copy-btn" onclick="togglePassword()" title="Mostrar">
                      <i class="bi bi-eye" id="pwd-icon"></i>
                    </button>
                  <?php else: ?>
                    <span class="cred-val" style="color:var(--muted);font-size:12px;">
                      Já acedeste ao sistema — usa a tua password definida
                    </span>
                  <?php endif; ?>
                </div>
              </div>

            </div>

            <div class="approved-warning">
              <strong>⚠️ Importante:</strong> Guarda estas credenciais num local seguro.
              A password provisória deixa de estar visível após o primeiro acesso.
            </div>

            <a href="login_aluno.php" class="btn-login-link">
              <i class="bi bi-box-arrow-in-right"></i>
              Entrar na Área do Aluno
            </a>

          </div>

        <?php elseif ($resultado['estado'] === 'rejeitado'): ?>

          <div class="state-error">
            <div class="state-icon"><i class="bi bi-x-circle-fill"></i></div>
            <div>
              <h5>Candidatura Rejeitada</h5>
              <p>A tua candidatura não foi aceite. Para mais informações, contacta os Serviços Académicos.</p>
            </div>
          </div>

        <?php else: ?>

          <div class="state-error">
            <div class="state-icon"><i class="bi bi-exclamation-triangle"></i></div>
            <div>
              <h5>Dados incompletos</h5>
              <p>A candidatura foi aprovada mas os dados de acesso ainda não estão disponíveis. Aguarda alguns minutos.</p>
            </div>
          </div>

        <?php endif; ?>

      <?php endif; ?>

    </div><!-- /form-card -->

    <p class="text-center mt-4" style="font-size:13px;color:var(--muted);">
      Ainda não submeteste candidatura?
      <a href="candidatura.php" style="color:var(--gold);text-decoration:none;font-weight:500;">
        Submeter agora <i class="bi bi-arrow-right" style="font-size:11px;"></i>
      </a>
    </p>

  </div>
</div>

<footer>
  © <?php echo date('Y'); ?> Instituto Politécnico do Cávado e do Ave · Barcelos, Portugal
</footer>

<div id="copy-toast"><i class="bi bi-check2 me-1"></i> Copiado!</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  const pwdPlain = <?php echo ($aluno_info && $aluno_info['password']) ? json_encode($aluno_info['password']) : 'null'; ?>;
  let pwdVisible = false;

  function togglePassword() {
    if (!pwdPlain) return;
    pwdVisible = !pwdVisible;
    document.getElementById('pwd-val').textContent  = pwdVisible ? pwdPlain : '••••••••••';
    document.getElementById('pwd-icon').className   = pwdVisible ? 'bi bi-eye-slash' : 'bi bi-eye';
  }

  function copiar(texto, btn) {
    navigator.clipboard.writeText(texto).then(() => {
      const toast = document.getElementById('copy-toast');
      toast.classList.add('show');
      setTimeout(() => toast.classList.remove('show'), 2000);
      const icon = btn.querySelector('i');
      icon.className = 'bi bi-check2';
      setTimeout(() => icon.className = 'bi bi-copy', 2000);
    });
  }
</script>
</body>
</html>