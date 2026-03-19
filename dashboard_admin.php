<?php
include "db.php";

if (!isset($_SESSION['tipo']) || ($_SESSION['tipo'] !== 'admin' && $_SESSION['tipo'] !== 'funcionario')) {
    // Se for professor, manda para o painel dele em vez do login
    if (isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'professor') {
        header("Location: dashboard_professor.php");
    } else {
        header("Location: login.php");
    }
    exit();
}

$sql = "SELECT c.*, cu.Nome as nome_curso 
        FROM candidaturas c 
        LEFT JOIN cursos cu ON c.curso_id = cu.ID 
        ORDER BY c.data_submissao DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Painel Admin — IPCA</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/dashboard.css">

</head>
<body>

<!-- ════════════ SIDEBAR ════════════ -->
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
    <i class="bi bi-people"></i>
    Candidaturas

  </a>

  <a href="lista_alunos.php" class="sidebar-link">
    <i class="bi bi-mortarboard"></i>
    Alunos Ativos
  </a>

  <a href="adicionar_curso.php" class="sidebar-link">
    <i class="bi bi-book"></i>
    Cursos
  </a>

 

  <?php if ($_SESSION['tipo'] === 'admin'): ?>
  <div class="sidebar-section">Administração</div>

  <a href="gerir_utilizadores.php" class="sidebar-link">
    <i class="bi bi-people-fill"></i>
    Utilizadores
  </a>

  
  <?php endif; ?>

  <div class="sidebar-bottom">
    <div class="sidebar-user">
      <div class="sidebar-avatar">
        <?php echo strtoupper(substr($_SESSION['usuario'], 0, 1)); ?>
      </div>
      <div style="overflow:hidden;">
        <div style="font-size:13px; color:var(--cream); font-weight:500; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
          <?php echo $_SESSION['usuario']; ?>
        </div>
        <div style="font-size:10px; color:var(--gold); text-transform:uppercase; letter-spacing:1px;">
          <?php echo ucfirst($_SESSION['tipo']); ?>
        </div>
      </div>
    </div>
    <a href="logout.php" class="sidebar-link" style="color:#e74c3c;">
      <i class="bi bi-box-arrow-left"></i>
      Terminar Sessão
    </a>
  </div>

</aside>


<!-- ════════════ MAIN ════════════ -->
<div class="main-wrap">

  <!-- TOP BAR -->
  <div class="topbar">
    <div class="d-flex align-items-center gap-3">
      <button class="sidebar-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')">
        <i class="bi bi-list"></i>
      </button>
      <div>
        <div class="topbar-title">Gestão de Candidaturas</div>
        <div class="topbar-breadcrumb">IPCA › <span>Admin</span> › Candidaturas</div>
      </div>
    </div>

    <div class="topbar-actions">
     
      <div class="session-pill">
        <div class="session-dot"></div>
        <span style="font-size:12px; color:var(--gold); font-weight:500;">Sessão Ativa</span>
      </div>
    </div>
  </div>


  <!-- TOASTS -->
  <?php if (isset($_GET['sucesso']) && $_GET['sucesso'] === 'aprovado'): ?>
  <div class="toast-wrap" id="toastAprovado">
    <div class="toast-custom toast-success">
      <i class="bi bi-check-circle-fill"></i>
      <div>
        <strong><?php echo htmlspecialchars($_GET['aluno'] ?? 'Aluno'); ?></strong> aprovado com sucesso!<br>
        <span>
          Nº <strong style="color:var(--gold)"><?php echo htmlspecialchars($_GET['num'] ?? ''); ?></strong>
          <?php if (!empty($_GET['pw'])): ?>
          · Password: <strong style="color:var(--gold);font-family:monospace;"><?php echo htmlspecialchars($_GET['pw']); ?></strong>
          <?php endif; ?>
        </span>
      </div>
      <button onclick="this.parentElement.parentElement.remove()"><i class="bi bi-x"></i></button>
    </div>
  </div>
  <script>
    // Não auto-fechar este toast — admin precisa de anotar a password
  </script>
  <?php elseif (isset($_GET['aviso']) && $_GET['aviso'] === 'email_falhou'): ?>
  <div class="toast-wrap">
    <div class="toast-custom toast-warning">
      <i class="bi bi-exclamation-triangle-fill"></i>
      <div>
        Aluno criado (Nº <?php echo htmlspecialchars($_GET['num'] ?? ''); ?>) mas o email <strong>não foi enviado</strong>.<br>
        <span>Verifica a configuração SMTP do servidor.</span>
      </div>
      <button onclick="this.parentElement.parentElement.remove()"><i class="bi bi-x"></i></button>
    </div>
  </div>
  <?php elseif (isset($_GET['erro'])): ?>
  <div class="toast-wrap">
    <div class="toast-custom toast-error">
      <i class="bi bi-x-circle-fill"></i>
      <div>
        <?php
          $msgs = [
            'ja_processada' => 'Esta candidatura já foi processada anteriormente.',
            'bd'            => 'Erro ao guardar na base de dados. Tenta novamente.',
          ];
          echo $msgs[$_GET['erro']] ?? 'Ocorreu um erro inesperado.';
        ?>
      </div>
      <button onclick="this.parentElement.parentElement.remove()"><i class="bi bi-x"></i></button>
    </div>
  </div>
  <?php endif; ?>

  <!-- PAGE CONTENT -->
  <div class="page-content">

    <!-- STAT CARDS -->
    <div class="row g-3 mb-4">

      <div class="col-6 col-md-3">
        <div class="stat-card" style="--card-color:#c9a84c; --card-rgb:201,168,76">
          <div class="stat-icon"><i class="bi bi-file-earmark-text"></i></div>
          <div class="stat-value"><?php echo $result->num_rows; ?></div>
          <div class="stat-label">Total Candidaturas</div>
          <div class="stat-delta" style="color:var(--muted);">
            <i class="bi bi-calendar3" style="font-size:11px;"></i> Este ano letivo
          </div>
        </div>
      </div>

      <div class="col-6 col-md-3">
        <div class="stat-card" style="--card-color:#f39c12; --card-rgb:243,156,18">
          <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
          <div class="stat-value" id="count-pendente">—</div>
          <div class="stat-label">Pendentes</div>
          <div class="stat-delta" style="color:#f39c12;">
            <i class="bi bi-exclamation-circle" style="font-size:11px;"></i> Aguardam revisão
          </div>
        </div>
      </div>

      <div class="col-6 col-md-3">
        <div class="stat-card" style="--card-color:#2ecc71; --card-rgb:46,204,113">
          <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
          <div class="stat-value" id="count-aprovado">—</div>
          <div class="stat-label">Aprovadas</div>
          <div class="stat-delta" style="color:#2ecc71;">
            <i class="bi bi-arrow-up" style="font-size:11px;"></i> Login enviado
          </div>
        </div>
      </div>

      <div class="col-6 col-md-3">
        <div class="stat-card" style="--card-color:#e74c3c; --card-rgb:231,76,60">
          <div class="stat-icon"><i class="bi bi-x-circle"></i></div>
          <div class="stat-value" id="count-rejeitado">—</div>
          <div class="stat-label">Rejeitadas</div>
          <div class="stat-delta" style="color:var(--muted);">
            <i class="bi bi-dash" style="font-size:11px;"></i> Sem matrícula
          </div>
        </div>
      </div>

    </div>

    <!-- FILTER BAR -->
    <div class="filter-bar">
      <div class="row g-2 align-items-center">
        <div class="col-12 col-md-4">
          <input type="text" class="search-input" id="searchInput"
                 placeholder="&#xF52A;  Pesquisar por nome, NIF ou email..."
                 oninput="filterTable()">
        </div>
        <div class="col-6 col-md-2">
          <select class="filter-select w-100" id="filterStatus" onchange="filterTable()">
            <option value="">Todos os estados</option>
            <option value="pendente">Pendente</option>
            <option value="aprovado">Aprovado</option>
            <option value="rejeitado">Rejeitado</option>
            <option value="em análise">Em Análise</option>
          </select>
        </div>
        <div class="col-6 col-md-2">
          <select class="filter-select w-100" id="filterCurso" onchange="filterTable()">
            <option value="">Todos os cursos</option>
            <?php
              // Reset result pointer
              if ($result->num_rows > 0) {
                $result->data_seek(0);
                $cursos_vistos = [];
                while ($r = $result->fetch_assoc()) {
                  if ($r['nome_curso'] && !in_array($r['nome_curso'], $cursos_vistos)) {
                    $cursos_vistos[] = $r['nome_curso'];
                    echo "<option value='" . htmlspecialchars($r['nome_curso']) . "'>" . htmlspecialchars($r['nome_curso']) . "</option>";
                  }
                }
                $result->data_seek(0);
              }
            ?>
          </select>
        </div>
        <div class="col-12 col-md-4 d-flex gap-2 justify-content-md-end">
          <button class="btn-outline-g" onclick="exportarExcel()">
            <i class="bi bi-download me-1"></i>Exportar
          </button>
          <?php if ($_SESSION['tipo'] === 'admin'): ?>
          <button class="btn-outline-d" onclick="confirmarLimpar()">
            <i class="bi bi-trash me-1"></i>Limpar
          </button>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- TABLE -->
    <div class="table-card">

      <div class="table-card-header">
        <div>
          <div class="table-card-title">Lista de Candidatos</div>
          <div class="table-card-sub">Clica no <i class="bi bi-eye" style="color:var(--info)"></i> para ver a ficha completa</div>
        </div>
        <a href="nova_candidatura.php" class="btn-gold">
          <i class="bi bi-plus-lg me-1"></i>Nova Ficha
        </a>
      </div>

      <div class="table-responsive">
        <table class="table table-hover" id="mainTable">
          <thead>
            <tr>
              <th style="width:50px"></th>
              <th>Nome</th>
              <th>NIF</th>
              <th>Email Contacto</th>
              <th>Curso Pretendido</th>
              <th>Submetido</th>
              <th>Estado</th>
              <?php if ($_SESSION['tipo'] === 'admin'): ?>
              <th class="text-center">Ações</th>
              <?php endif; ?>
            </tr>
          </thead>
          <tbody>
            <?php if ($result->num_rows > 0):
              $result->data_seek(0);
              while ($row = $result->fetch_assoc()):
                $status = $row['estado'] ?? 'pendente';
                $initials = strtoupper(substr($row['nome'], 0, 1) . (strpos($row['nome'], ' ') !== false ? substr(strrchr($row['nome'], ' '), 1, 1) : ''));
            ?>
            <tr data-nome="<?php echo strtolower($row['nome']); ?>"
                data-nif="<?php echo $row['nif']; ?>"
                data-email="<?php echo strtolower($row['email']); ?>"
                data-status="<?php echo strtolower($status); ?>"
                data-curso="<?php echo htmlspecialchars($row['nome_curso'] ?? ''); ?>">

              <td>
                <?php if (!empty($row['foto']) && file_exists('uploads/'.$row['foto'])): ?>
                  <img src="uploads/<?php echo $row['foto']; ?>" class="student-photo" alt="">
                <?php else: ?>
                  <div class="student-initials"><?php echo $initials; ?></div>
                <?php endif; ?>
              </td>

              <td>
                <div style="font-weight:500; color:var(--cream);"><?php echo htmlspecialchars($row['nome']); ?></div>
                <?php if (!empty($row['data_nascimento'])): ?>
                <div style="font-size:11px; color:var(--muted);">
                  <i class="bi bi-calendar3 me-1"></i><?php echo date('d/m/Y', strtotime($row['data_nascimento'])); ?>
                </div>
                <?php endif; ?>
              </td>

              <td style="font-size:13px; color:var(--muted);"><?php echo htmlspecialchars($row['nif'] ?? '—'); ?></td>

              <td style="font-size:13px;"><?php echo htmlspecialchars($row['email']); ?></td>

              <td>
                <span style="color:var(--gold); font-size:13px;">
                  <?php echo htmlspecialchars($row['nome_curso'] ?? '—'); ?>
                </span>
              </td>

              <td style="font-size:12px; color:var(--muted); white-space:nowrap;">
                <i class="bi bi-clock me-1"></i>
                <?php echo !empty($row['data_submissao']) ? date('d/m/Y', strtotime($row['data_submissao'])) : '—'; ?>
              </td>

              <td>
                <span class="status-badge status-<?php echo strtolower(str_replace(' ', '', $status)); ?>">
                  <?php echo ucfirst($status); ?>
                </span>
              </td>

              <?php if ($_SESSION['tipo'] === 'admin'): ?>
              <td>
                <div class="d-flex gap-1 justify-content-center">
                  <button class="action-btn action-view" title="Ver ficha"
                          onclick="window.location='ver_candidatura.php?id=<?php echo $row['id']; ?>'">
                    <i class="bi bi-eye"></i>
                  </button>
                  <?php if ($status !== 'aprovado'): ?>
                  <button class="action-btn action-approve" title="Aprovar e enviar login"
                          onclick="aprovar(<?php echo $row['id']; ?>, '<?php echo addslashes($row['nome']); ?>')">
                    <i class="bi bi-check-lg"></i>
                  </button>
                  <?php endif; ?>
                  <button class="action-btn action-delete" title="Eliminar"
                          onclick="eliminar(<?php echo $row['id']; ?>)">
                    <i class="bi bi-trash"></i>
                  </button>
                </div>
              </td>
              <?php endif; ?>

            </tr>
            <?php endwhile; ?>
            <?php else: ?>
            <tr>
              <td colspan="8" class="text-center py-5" style="color:var(--muted);">
                <i class="bi bi-inbox" style="font-size:32px; display:block; margin-bottom:10px; opacity:0.4;"></i>
                Nenhuma candidatura encontrada.
              </td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

    </div><!-- /table-card -->

  </div><!-- /page-content -->
</div><!-- /main-wrap -->


<!-- Bootstrap -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
  // ── Count badges por estado
  function updateCounts() {
    const rows = document.querySelectorAll('#mainTable tbody tr[data-status]');
    let p = 0, a = 0, r = 0;
    rows.forEach(row => {
      const s = row.dataset.status;
      if (s === 'pendente') p++;
      else if (s === 'aprovado') a++;
      else if (s === 'rejeitado') r++;
    });
    document.getElementById('count-pendente').textContent  = p;
    document.getElementById('count-aprovado').textContent  = a;
    document.getElementById('count-rejeitado').textContent = r;
  }

  updateCounts();

  // ── Filter table
  function filterTable() {
    const q      = document.getElementById('searchInput').value.toLowerCase();
    const status = document.getElementById('filterStatus').value.toLowerCase();
    const curso  = document.getElementById('filterCurso').value.toLowerCase();
    const rows   = document.querySelectorAll('#mainTable tbody tr[data-nome]');

    rows.forEach(row => {
      const matchQ      = !q      || row.dataset.nome.includes(q) || row.dataset.nif.includes(q) || row.dataset.email.includes(q);
      const matchStatus = !status || row.dataset.status === status;
      const matchCurso  = !curso  || row.dataset.curso.toLowerCase() === curso;
      row.style.display = (matchQ && matchStatus && matchCurso) ? '' : 'none';
    });
  }

  // ── Aprovar candidatura
  function aprovar(id, nome) {
    if (confirm(`Aprovar a candidatura de "${nome}"?\n\nO sistema vai gerar o login institucional e enviar por email.`)) {
      window.location.href = `aprovar_candidatura.php?id=${id}`;
    }
  }

  // ── Eliminar
  function eliminar(id) {
    if (confirm('Tens a certeza que queres eliminar esta candidatura? Esta ação é irreversível.')) {
      window.location.href = `eliminar_candidatura.php?id=${id}`;
    }
  }

  // ── Limpar histórico
  function confirmarLimpar() {
    if (confirm('Atenção: esta ação elimina TODAS as candidaturas aprovadas do histórico. Continuar?')) {
      window.location.href = 'limpar_historico.php';
    }
  }

  // ── Exportar (placeholder)
  function exportarExcel() {
    window.location.href = 'exportar_candidaturas.php';
  }

  // ── Fechar sidebar ao clicar fora (mobile)
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