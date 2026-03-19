<?php
include "db.php";

if (!isset($_SESSION['tipo']) || ($_SESSION['tipo'] !== 'admin' && $_SESSION['tipo'] !== 'funcionario')) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: dashboard_admin.php");
    exit();
}

$id = (int)$_GET['id'];

// ── Ações POST
$msg = $msg_tipo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SESSION['tipo'] === 'admin') {
    $acao = $_POST['acao'] ?? '';

    // APROVAR
    if ($acao === 'aprovar') {
        header("Location: aprovar_candidatura.php?id=$id");
        exit();
    }

    // REJEITAR
    if ($acao === 'rejeitar') {
        $obs = trim($_POST['observacoes'] ?? '');
        $stmt = $conn->prepare("UPDATE candidaturas SET estado='rejeitado', observacoes=?, data_decisao=NOW() WHERE id=?");
        $stmt->bind_param("si", $obs, $id);
        $msg      = $stmt->execute() ? "Candidatura rejeitada." : "Erro ao rejeitar.";
        $msg_tipo = strpos($msg, 'Erro') === false ? "sucesso" : "erro";
    }

    // GUARDAR OBSERVAÇÕES
    if ($acao === 'observacoes') {
        $obs = trim($_POST['observacoes'] ?? '');
        $stmt = $conn->prepare("UPDATE candidaturas SET observacoes=? WHERE id=?");
        $stmt->bind_param("si", $obs, $id);
        $msg      = $stmt->execute() ? "Observações guardadas." : "Erro ao guardar.";
        $msg_tipo = strpos($msg, 'Erro') === false ? "sucesso" : "erro";
    }

    // REPOR PARA PENDENTE
    if ($acao === 'repor') {
        $stmt = $conn->prepare("UPDATE candidaturas SET estado='pendente', data_decisao=NULL WHERE id=?");
        $stmt->bind_param("i", $id);
        $msg      = $stmt->execute() ? "Candidatura reposta para pendente." : "Erro.";
        $msg_tipo = strpos($msg, 'Erro') === false ? "sucesso" : "erro";
    }
}

// ── Buscar candidatura
$stmt = $conn->prepare("
    SELECT c.*, cu.Nome AS nome_curso
    FROM candidaturas c
    LEFT JOIN cursos cu ON c.curso_id = cu.ID
    WHERE c.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$cand = $stmt->get_result()->fetch_assoc();

if (!$cand) {
    header("Location: dashboard_admin.php?erro=nao_encontrada");
    exit();
}

// ── Se aprovado, buscar dados do aluno gerado
$aluno = null;
if ($cand['estado'] === 'aprovado') {
    $stmt2 = $conn->prepare("SELECT numero_aluno, email FROM alunos WHERE nome = ? LIMIT 1");
    $stmt2->bind_param("s", $cand['nome']);
    $stmt2->execute();
    $aluno = $stmt2->get_result()->fetch_assoc();
}

// ── Sidebar badges
$total_cand   = $conn->query("SELECT COUNT(*) as n FROM candidaturas")->fetch_assoc()['n'];
$total_alunos = $conn->query("SELECT COUNT(*) as n FROM alunos")->fetch_assoc()['n'];

// ── Helpers
$estado       = $cand['estado'] ?? 'pendente';
$foto_path    = (!empty($cand['foto']) && $cand['foto'] !== 'default.png' && file_exists('uploads/'.$cand['foto']))
                ? 'uploads/'.$cand['foto']
                : null;
$initials     = strtoupper(substr($cand['nome'] ?? 'A', 0, 1) .
                (strpos($cand['nome'], ' ') !== false ? substr(strrchr($cand['nome'], ' '), 1, 1) : ''));
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ficha de Candidatura #<?php echo $id; ?> — IPCA</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/dashboard.css">
<link rel="stylesheet" href="css/ver_candidatura.css">

</head>

<body>

<!-- ════════ SIDEBAR ════════ -->
<aside class="sidebar" id="sidebar">
  <a href="index.php" class="sidebar-brand text-decoration-none">
    <div class="logo-icon">IP</div>
    <div>
      <div class="brand-name">IPCA</div>
      <div class="brand-sub">Painel Admin</div>
    </div>
  </a>

  <div class="sidebar-section">Principal</div>
  <a href="dashboard_admin.php" class="sidebar-link active">
    <i class="bi bi-people"></i> Candidaturas
  
  </a>

  <a href="lista_alunos.php" class="sidebar-link">
    <i class="bi bi-mortarboard"></i> Alunos Ativos

  </a>

  <a href="adicionar_curso.php" class="sidebar-link">
    <i class="bi bi-book"></i> Cursos
  </a>


  <?php if ($_SESSION['tipo'] === 'admin'): ?>
  <div class="sidebar-section">Administração</div>
  <a href="#" class="sidebar-link"><i class="bi bi-people-fill"></i> Utilizadores</a>

  <?php endif; ?>

  <div class="sidebar-bottom">
    <div class="sidebar-user">
      <div class="sidebar-avatar"><?php echo strtoupper(substr($_SESSION['usuario'], 0, 1)); ?></div>
      <div style="overflow:hidden;">
        <div style="font-size:13px;color:var(--cream);font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
          <?php echo $_SESSION['usuario']; ?>
        </div>
        <div style="font-size:10px;color:var(--gold);text-transform:uppercase;letter-spacing:1px;">
          <?php echo ucfirst($_SESSION['tipo']); ?>
        </div>
      </div>
    </div>
    <a href="logout.php" class="sidebar-link" style="color:#e74c3c;">
      <i class="bi bi-box-arrow-left"></i> Terminar Sessão
    </a>
  </div>
</aside>


<!-- ════════ MAIN ════════ -->
<div class="main-wrap">

  <!-- TOPBAR -->
  <div class="topbar">
    <div class="d-flex align-items-center gap-3">
      <button class="sidebar-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')">
        <i class="bi bi-list"></i>
      </button>
      <div>
        <div class="topbar-title">Ficha de Candidatura</div>
        <div class="topbar-breadcrumb">
          IPCA › <span>Admin</span> ›
          <a href="dashboard_admin.php" style="color:var(--muted);text-decoration:none;">Candidaturas</a> ›
          <span>#<?php echo $id; ?></span>
        </div>
      </div>
    </div>
    <div class="topbar-actions">
      <div class="session-pill">
        <div class="session-dot"></div>
        <span style="font-size:12px;color:var(--gold);font-weight:500;">Sessão Ativa</span>
      </div>
    </div>
  </div>


  <!-- TOAST -->
  <?php if ($msg): ?>
  <div class="toast-wrap" id="toastWrap">
    <div class="toast-custom toast-<?php echo $msg_tipo === 'sucesso' ? 'success' : 'error'; ?>">
      <i class="bi bi-<?php echo $msg_tipo === 'sucesso' ? 'check-circle-fill' : 'x-circle-fill'; ?>"></i>
      <div><?php echo htmlspecialchars($msg); ?></div>
      <button onclick="this.parentElement.parentElement.remove()"><i class="bi bi-x"></i></button>
    </div>
  </div>
  <?php endif; ?>



  <!-- PAGE CONTENT -->
  <div class="page-content">

    <!-- BACK + TÍTULO -->
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
      <div class="d-flex align-items-center gap-3">
        <a href="dashboard_admin.php" class="btn-back">
          <i class="bi bi-arrow-left"></i> Voltar
        </a>
        <div>
          <p style="font-size:10px;color:var(--gold);letter-spacing:2px;text-transform:uppercase;margin:0;">
            Candidatura #<?php echo $id; ?>
          </p>
          <h2 style="font-family:'Playfair Display',serif;font-size:22px;font-weight:700;color:var(--cream);margin:0;">
            <?php echo htmlspecialchars($cand['nome']); ?>
          </h2>
        </div>
      </div>

      <!-- Estado badge grande -->
      <span class="status-badge status-<?php echo $estado; ?>" style="font-size:13px;padding:8px 16px;">
        <?php
          $estado_labels = ['pendente' => 'Pendente', 'aprovado' => 'Aprovado', 'rejeitado' => 'Rejeitado'];
          echo $estado_labels[$estado] ?? ucfirst($estado);
        ?>
      </span>
    </div>


    <!-- ACTION BAR (só para admin e estados relevantes) -->
    <?php if ($_SESSION['tipo'] === 'admin'): ?>
    <div class="action-bar">
      <span class="action-bar-label">Ações:</span>

      <?php if ($estado === 'pendente'): ?>
        <form method="POST" style="display:inline;">
          <input type="hidden" name="acao" value="aprovar">
          <button type="submit" class="btn-aprovar">
            <i class="bi bi-check-circle"></i> Aprovar Candidatura
          </button>
        </form>
        <button class="btn-rejeitar" onclick="abrirRejeitar()">
          <i class="bi bi-x-circle"></i> Rejeitar
        </button>

      <?php elseif ($estado === 'aprovado'): ?>
        <span style="font-size:13px;color:#2ecc71;">
          <i class="bi bi-patch-check-fill me-1"></i>
          Aprovada 
        </span>
        
       

      <?php elseif ($estado === 'rejeitado'): ?>
        <span style="font-size:13px;color:#e74c3c;">
          <i class="bi bi-x-circle-fill me-1"></i>
          Rejeitada
        </span>
       
      <?php endif; ?>
    </div>
    <?php endif; ?>


    <div class="row g-4">

      <!-- COLUNA ESQUERDA -->
      <div class="col-lg-8">

        <!-- IDENTIFICAÇÃO -->
        <div class="detail-card">
          <div class="detail-card-header">
            <i class="bi bi-person-vcard"></i>
            <span>Dados Pessoais</span>
          </div>
          <div class="detail-card-body">

            <!-- Foto + nome -->
            <div class="d-flex align-items-center gap-4 mb-4 pb-4"
                 style="border-bottom:1px solid rgba(255,255,255,0.06);">
              <?php if ($foto_path): ?>
                <img src="<?php echo $foto_path; ?>" class="candidato-avatar" alt="Foto">
              <?php else: ?>
                <div class="candidato-initials"><?php echo $initials; ?></div>
              <?php endif; ?>
              <div>
                <div style="font-family:'Playfair Display',serif;font-size:22px;font-weight:700;color:var(--cream);">
                  <?php echo htmlspecialchars($cand['nome']); ?>
                </div>
                <div style="font-size:13px;color:var(--muted);margin-top:3px;">
                  <i class="bi bi-envelope me-1"></i><?php echo htmlspecialchars($cand['email']); ?>
                </div>
              </div>
            </div>

            <div class="field-row">
              <div class="field-key">NIF</div>
              <div class="field-val mono"><?php echo htmlspecialchars($cand['nif'] ?? '—'); ?></div>
            </div>

            <div class="field-row">
              <div class="field-key">Email Contacto</div>
              <div class="field-val">
                <a href="mailto:<?php echo htmlspecialchars($cand['email']); ?>"
                   style="color:var(--gold);text-decoration:none;">
                  <?php echo htmlspecialchars($cand['email']); ?>
                </a>
              </div>
            </div>

            <div class="field-row">
              <div class="field-key">Curso Pretendido</div>
              <div class="field-val gold"><?php echo htmlspecialchars($cand['nome_curso'] ?? '—'); ?></div>
            </div>

            <div class="field-row">
              <div class="field-key">Data Submissão</div>
              <div class="field-val">
                <?php echo $cand['data_submissao'] ? date('d/m/Y \à\s H:i', strtotime($cand['data_submissao'])) : '—'; ?>
              </div>
            </div>

            <?php if ($cand['data_decisao']): ?>
            <div class="field-row">
              <div class="field-key">Data Decisão</div>
              <div class="field-val"><?php echo date('d/m/Y \à\s H:i', strtotime($cand['data_decisao'])); ?></div>
            </div>
            <?php endif; ?>

          </div>
        </div><!-- /identificação -->


        <!-- OBSERVAÇÕES -->
        <div class="detail-card">
          <div class="detail-card-header">
            <i class="bi bi-chat-left-text"></i>
            <span>Observações Internas</span>
          </div>
          <div class="detail-card-body">
            <?php if ($_SESSION['tipo'] === 'admin'): ?>
            <form method="POST">
              <input type="hidden" name="acao" value="observacoes">
              <textarea name="observacoes" class="obs-textarea"
                        placeholder="Adiciona notas internas sobre esta candidatura..."
              ><?php echo htmlspecialchars($cand['observacoes'] ?? ''); ?></textarea>
              <div class="d-flex justify-content-end mt-3">
                <button type="submit" class="btn-obs">
                  <i class="bi bi-floppy me-1"></i> Guardar Observações
                </button>
              </div>
            </form>
            <?php else: ?>
              <p style="font-size:14px;color:var(--muted);line-height:1.6;">
                <?php echo !empty($cand['observacoes'])
                  ? nl2br(htmlspecialchars($cand['observacoes']))
                  : '<em>Sem observações registadas.</em>'; ?>
              </p>
            <?php endif; ?>
          </div>
        </div><!-- /observações -->


        <!-- DADOS DO ALUNO (se aprovado) -->
        <?php if ($estado === 'aprovado' && $aluno): ?>
        <div class="detail-card">
          <div class="detail-card-header">
            <i class="bi bi-mortarboard"></i>
            <span>Credenciais Geradas</span>
          </div>
          <div class="detail-card-body">
            <div class="approved-box">
              <div class="d-flex align-items-center gap-3 mb-3">
                <div style="width:38px;height:38px;border-radius:50%;background:rgba(201,168,76,0.15);
                            display:flex;align-items:center;justify-content:center;color:var(--gold);font-size:18px;">
                  <i class="bi bi-patch-check-fill"></i>
                </div>
                <div>
                  <div style="font-family:'Playfair Display',serif;font-size:15px;font-weight:700;color:var(--cream);">
                    Acesso criado com sucesso
                  </div>
                  <div style="font-size:12px;color:var(--muted);">Credenciais disponíveis na área do aluno</div>
                </div>
              </div>
              <div class="cred-item">
                <span class="k">Nº de Processo</span>
                <span class="v"><?php echo $aluno['numero_aluno']; ?></span>
              </div>
              <div class="cred-item">
                <span class="k">Email Institucional</span>
                <span class="v"><?php echo htmlspecialchars($aluno['email']); ?></span>
              </div>
            </div>
          </div>
        </div>
        <?php endif; ?>

      </div><!-- /col-lg-8 -->


      <!-- COLUNA DIREITA -->
      <div class="col-lg-4">

        <!-- TIMELINE -->
        <div class="detail-card mb-4">
          <div class="detail-card-header">
            <i class="bi bi-clock-history"></i>
            <span>Histórico</span>
          </div>
          <div class="detail-card-body">
            <div class="timeline">

              <div class="timeline-item">
                <div class="tl-dot done"><i class="bi bi-check"></i></div>
                <div class="tl-content">
                  <div class="tl-title">Candidatura submetida</div>
                  <div class="tl-date">
                    <?php echo $cand['data_submissao'] ? date('d/m/Y H:i', strtotime($cand['data_submissao'])) : '—'; ?>
                  </div>
                </div>
              </div>

              <div class="timeline-item">
                <div class="tl-dot <?php echo $estado === 'pendente' ? 'active' : 'done'; ?>">
                  <?php echo $estado === 'pendente' ? '<i class="bi bi-three-dots"></i>' : '<i class="bi bi-check"></i>'; ?>
                </div>
                <div class="tl-content">
                  <div class="tl-title">Em análise</div>
                  <div class="tl-date" style="<?php echo $estado === 'pendente' ? 'color:var(--gold)' : ''; ?>">
                    <?php echo $estado === 'pendente' ? 'A aguardar decisão...' : 'Concluída'; ?>
                  </div>
                </div>
              </div>

              <?php if ($estado === 'aprovado'): ?>
              <div class="timeline-item">
                <div class="tl-dot done"><i class="bi bi-check"></i></div>
                <div class="tl-content">
                  <div class="tl-title" style="color:#2ecc71;">Aprovada</div>
                  <div class="tl-date">
                    <?php echo $cand['data_decisao'] ? date('d/m/Y H:i', strtotime($cand['data_decisao'])) : '—'; ?>
                  </div>
                </div>
              </div>
              <div class="timeline-item">
                <div class="tl-dot done"><i class="bi bi-mortarboard"></i></div>
                <div class="tl-content">
                  <div class="tl-title">Login institucional gerado</div>
                  <div class="tl-date">
                    <?php echo $aluno ? 'a'.$aluno['numero_aluno'].'@alunos.ipca.pt' : '—'; ?>
                  </div>
                </div>
              </div>

              <?php elseif ($estado === 'rejeitado'): ?>
              <div class="timeline-item">
                <div class="tl-dot danger"><i class="bi bi-x"></i></div>
                <div class="tl-content">
                  <div class="tl-title" style="color:#e74c3c;">Rejeitada</div>
                  <div class="tl-date">
                    <?php echo $cand['data_decisao'] ? date('d/m/Y H:i', strtotime($cand['data_decisao'])) : '—'; ?>
                  </div>
                </div>
              </div>

              <?php else: ?>
              <div class="timeline-item">
                <div class="tl-dot pending">3</div>
                <div class="tl-content">
                  <div class="tl-title">Decisão</div>
                  <div class="tl-date">Pendente</div>
                </div>
              </div>
              <div class="timeline-item">
                <div class="tl-dot pending">4</div>
                <div class="tl-content">
                  <div class="tl-title">Gerar acesso</div>
                  <div class="tl-date">Pendente</div>
                </div>
              </div>
              <?php endif; ?>

            </div>
          </div>
        </div><!-- /timeline -->


        <!-- INFO RÁPIDA -->
        <div class="detail-card">
          <div class="detail-card-header">
            <i class="bi bi-info-circle"></i>
            <span>Resumo</span>
          </div>
          <div class="detail-card-body" style="padding:16px 20px;">
            <div class="field-row" style="padding:10px 0;">
              <div class="field-key" style="width:120px;">ID</div>
              <div class="field-val mono">#<?php echo $id; ?></div>
            </div>
            <div class="field-row" style="padding:10px 0;">
              <div class="field-key" style="width:120px;">Estado</div>
              <div class="field-val">
                <span class="status-badge status-<?php echo $estado; ?>">
                  <?php echo $estado_labels[$estado] ?? ucfirst($estado); ?>
                </span>
              </div>
            </div>
            <div class="field-row" style="padding:10px 0;">
              <div class="field-key" style="width:120px;">Curso</div>
              <div class="field-val" style="font-size:13px;color:var(--gold);">
                <?php echo htmlspecialchars($cand['nome_curso'] ?? '—'); ?>
              </div>
            </div>
            <div class="field-row" style="padding:10px 0;border:none;">
              <div class="field-key" style="width:120px;">Foto</div>
              <div class="field-val" style="font-size:12px;color:var(--muted);">
                <?php echo $foto_path ? '<span style="color:#2ecc71;"><i class="bi bi-check me-1"></i>Disponível</span>' : '<em>Sem foto</em>'; ?>
              </div>
            </div>
          </div>
        </div>

      </div><!-- /col-lg-4 -->

    </div><!-- /row -->

  </div><!-- /page-content -->
</div><!-- /main-wrap -->


<!-- ════════ MODAL REJEITAR ════════ -->
<div class="modal-overlay" id="modalRejeitar" onclick="fecharModal('modalRejeitar')">
  <div class="modal-box modal-box-sm" onclick="event.stopPropagation()">
    <div class="modal-header-custom">
      <div>
        <div class="modal-title-custom" style="color:#e74c3c;">Rejeitar Candidatura</div>
        <div class="modal-sub">Indica o motivo da rejeição (opcional)</div>
      </div>
      <button class="modal-close" onclick="fecharModal('modalRejeitar')"><i class="bi bi-x-lg"></i></button>
    </div>

    <form method="POST">
      <input type="hidden" name="acao" value="rejeitar">

      <div class="modal-field">
        <label class="field-label-m">Motivo / Observação</label>
        <textarea name="observacoes" class="obs-textarea" style="min-height:80px;"
                  placeholder="Ex: Documentação incompleta, NIF inválido..."
        ><?php echo htmlspecialchars($cand['observacoes'] ?? ''); ?></textarea>
      </div>

      <div class="modal-actions">
        <button type="button" class="btn-modal-cancel" onclick="fecharModal('modalRejeitar')">Cancelar</button>
        <button type="submit" class="btn-modal-danger">
          <i class="bi bi-x-circle me-1"></i>Confirmar Rejeição
        </button>
      </div>
    </form>
  </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  function abrirRejeitar() {
    document.getElementById('modalRejeitar').classList.add('open');
  }

  function fecharModal(id) {
    document.getElementById(id).classList.remove('open');
  }

  document.addEventListener('keydown', e => {
    if (e.key === 'Escape')
      document.querySelectorAll('.modal-overlay.open').forEach(m => m.classList.remove('open'));
  });

  // Auto-dismiss toast
  setTimeout(() => {
    const tw = document.getElementById('toastWrap');
    if (tw) tw.style.opacity = '0';
    setTimeout(() => { if (tw) tw.remove(); }, 400);
  }, 4000);

  // Sidebar mobile
  document.addEventListener('click', function(e) {
    const sidebar = document.getElementById('sidebar');
    if (window.innerWidth < 768 && sidebar.classList.contains('open')) {
      if (!sidebar.contains(e.target) && !e.target.closest('.sidebar-toggle')) {
        sidebar.classList.remove('open');
      }
    }
  });
</script>
</body>
</html>