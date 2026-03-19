<?php
include "db.php";

if (!isset($_SESSION['tipo']) || ($_SESSION['tipo'] !== 'admin' && $_SESSION['tipo'] !== 'funcionario')) {
    header("Location: login.php");
    exit();
}

// ── Ações POST (editar / eliminar)
$msg = $msg_tipo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // EDITAR
    if (isset($_POST['acao']) && $_POST['acao'] === 'editar') {
        $id       = (int)$_POST['id'];
        $nome     = trim($_POST['nome']);
        $email    = trim($_POST['email']);
        $curso_id = (int)$_POST['curso_id'];

        $stmt = $conn->prepare("UPDATE alunos SET nome=?, email=?, curso_id=? WHERE id=?");
        $stmt->bind_param("ssii", $nome, $email, $curso_id, $id);
        $msg      = $stmt->execute() ? "Aluno atualizado com sucesso." : "Erro ao atualizar.";
        $msg_tipo = $stmt->execute() ? "sucesso" : "erro";
    }

    // ELIMINAR
    if (isset($_POST['acao']) && $_POST['acao'] === 'eliminar') {
        $id   = (int)$_POST['id'];
        $stmt = $conn->prepare("DELETE FROM alunos WHERE id=?");
        $stmt->bind_param("i", $id);
        $msg      = $stmt->execute() ? "Aluno eliminado com sucesso." : "Erro ao eliminar.";
        $msg_tipo = "erro";
        if (strpos($msg, 'sucesso') !== false) $msg_tipo = "sucesso";
    }
}

// ── Buscar todos os alunos com curso
$alunos = $conn->query("
    SELECT a.id, a.numero_aluno, a.nome, a.email, a.curso_id, a.foto,
           c.Nome AS nome_curso
    FROM alunos a
    LEFT JOIN cursos c ON a.curso_id = c.ID
    ORDER BY a.nome ASC
");

// ── Buscar cursos para o select do modal de edição
$cursos = $conn->query("SELECT ID, Nome FROM cursos ORDER BY Nome ASC");
$lista_cursos = [];
while ($c = $cursos->fetch_assoc()) $lista_cursos[] = $c;

// ── Total candidaturas (para badge sidebar)
$total_cand = $conn->query("SELECT COUNT(*) as n FROM candidaturas")->fetch_assoc()['n'];
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Alunos Ativos — IPCA Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/dashboard.css">
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
  <a href="dashboard_admin.php" class="sidebar-link">
    <i class="bi bi-people"></i> Candidaturas
  </a>

  <a href="lista_alunos.php" class="sidebar-link active">
    <i class="bi bi-mortarboard"></i> Alunos Ativos
  </a>

  <a href="adicionar_curso.php" class="sidebar-link">
    <i class="bi bi-book"></i> Cursos
  </a>



  <?php if ($_SESSION['tipo'] === 'admin'): ?>
  <div class="sidebar-section">Administração</div>
  <a href="gerir_utilizadores.php" class="sidebar-link"><i class="bi bi-people-fill"></i> Utilizadores</a>
  <?php endif; ?>

  <div class="sidebar-bottom">
    <div class="sidebar-user">
      <div class="sidebar-avatar"><?php echo strtoupper(substr($_SESSION['usuario'], 0, 1)); ?></div>
      <div style="overflow:hidden;">
        <div style="font-size:13px;color:var(--cream);font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo $_SESSION['usuario']; ?></div>
        <div style="font-size:10px;color:var(--gold);text-transform:uppercase;letter-spacing:1px;"><?php echo ucfirst($_SESSION['tipo']); ?></div>
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
        <div class="topbar-title">Alunos Ativos</div>
        <div class="topbar-breadcrumb">IPCA › <span>Admin</span> › Alunos</div>
      </div>
    </div>

    <div class="topbar-actions">
      <div class="session-pill">
        <div class="session-dot"></div>
        <span style="font-size:12px; color:var(--gold); font-weight:500;">Sessão Ativa</span>
      </div>
    </div>
  </div>


  <!-- TOAST -->
  <?php if ($msg): ?>
  <div class="toast-wrap">
    <div class="toast-custom toast-<?php echo $msg_tipo === 'sucesso' ? 'success' : 'error'; ?>">
      <i class="bi bi-<?php echo $msg_tipo === 'sucesso' ? 'check-circle-fill' : 'x-circle-fill'; ?>"></i>
      <div><?php echo htmlspecialchars($msg); ?></div>
      <button onclick="this.parentElement.parentElement.remove()"><i class="bi bi-x"></i></button>
    </div>
  </div>
  <?php endif; ?>

  <!-- PAGE CONTENT -->
  <div class="page-content">

    <!-- STAT CARDS -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-md-3">
        <div class="stat-card" style="--card-color:#c9a84c;--card-rgb:201,168,76">
          <div class="stat-icon"><i class="bi bi-mortarboard"></i></div>
          <div class="stat-value"><?php echo $alunos->num_rows; ?></div>
          <div class="stat-label">Total Alunos</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card" style="--card-color:#3498db;--card-rgb:52,152,219">
          <div class="stat-icon"><i class="bi bi-book"></i></div>
          <div class="stat-value"><?php echo count($lista_cursos); ?></div>
          <div class="stat-label">Cursos Ativos</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card" style="--card-color:#2ecc71;--card-rgb:46,204,113">
          <div class="stat-icon"><i class="bi bi-envelope-at"></i></div>
          <div class="stat-value" id="count-com-email">—</div>
          <div class="stat-label">Com Email Inst.</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card" style="--card-color:#9b59b6;--card-rgb:155,89,182">
          <div class="stat-icon"><i class="bi bi-person-check"></i></div>
          <div class="stat-value" id="count-sem-curso">—</div>
          <div class="stat-label">Sem Curso</div>
        </div>
      </div>
    </div>

    <!-- FILTER BAR -->
    <div class="filter-bar">
      <div class="row g-2 align-items-center">
        <div class="col-12 col-md-5">
          <input type="text" class="search-input" id="searchInput"
                 placeholder="Pesquisar por nome, nº ou email..."
                 oninput="filterTable()">
        </div>
        <div class="col-6 col-md-3">
          <select class="filter-select w-100" id="filterCurso" onchange="filterTable()">
            <option value="">Todos os cursos</option>
            <?php foreach ($lista_cursos as $c): ?>
            <option value="<?php echo htmlspecialchars($c['Nome']); ?>">
              <?php echo htmlspecialchars($c['Nome']); ?>
            </option>
            <?php endforeach; ?>
            <option value="sem curso">Sem curso</option>
          </select>
        </div>
        <div class="col-6 col-md-4 d-flex gap-2 justify-content-md-end">
          <?php if ($_SESSION['tipo'] === 'admin'): ?>
          <button class="btn-outline-g" onclick="exportar()">
            <i class="bi bi-download me-1"></i>Exportar
          </button>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- TABLE -->
    <div class="table-card">
      <div class="table-card-header">
        <div>
          <div class="table-card-title">Lista de Alunos Matriculados</div>
          <div class="table-card-sub">Alunos com acesso ativo à área do aluno</div>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-hover" id="mainTable">
          <thead>
            <tr>
              <th style="width:46px"></th>
              <th>Nome</th>
              <th>Nº Aluno</th>
              <th>Email Institucional</th>
              <th>Curso</th>
              <?php if ($_SESSION['tipo'] === 'admin'): ?>
              <th class="text-center" style="width:100px">Ações</th>
              <?php endif; ?>
            </tr>
          </thead>
          <tbody>
            <?php if ($alunos->num_rows > 0):
              $alunos->data_seek(0);
              while ($row = $alunos->fetch_assoc()):
                $initials = strtoupper(substr($row['nome'] ?? 'A', 0, 1) .
                  (strpos($row['nome'], ' ') !== false ? substr(strrchr($row['nome'], ' '), 1, 1) : ''));
            ?>
            <tr data-nome="<?php echo strtolower($row['nome']); ?>"
                data-num="<?php echo $row['numero_aluno']; ?>"
                data-email="<?php echo strtolower($row['email'] ?? ''); ?>"
                data-curso="<?php echo htmlspecialchars($row['nome_curso'] ?? 'sem curso'); ?>">

                
            <td>
                <?php if (!empty($row['foto']) && file_exists("uploads/" . $row['foto'])): ?>
                    <img src="uploads/<?php echo $row['foto']; ?>" 
                        style="width: 38px; height: 38px; border-radius: 50%; object-fit: cover; border: 2px solid var(--gold);">
                <?php else: ?>
                    <div class="student-initials"><?php echo $initials; ?></div>
                <?php endif; ?>
            </td>

              <td>
                <div style="font-weight:500;color:var(--cream);"><?php echo htmlspecialchars($row['nome']); ?></div>
              </td>

              <td>
                <span style="font-family:monospace;color:var(--gold);font-size:13px;font-weight:600;">
                  <?php echo $row['numero_aluno'] ?? '—'; ?>
                </span>
              </td>

              <td style="font-size:13px;color:var(--muted);">
                <?php echo htmlspecialchars($row['email'] ?? '—'); ?>
              </td>

              <td>
                <?php if ($row['nome_curso']): ?>
                  <span class="status-badge" style="background:rgba(201,168,76,0.1);color:var(--gold);">
                    <?php echo htmlspecialchars($row['nome_curso']); ?>
                  </span>
                <?php else: ?>
                  <span class="status-badge status-rejeitado">Sem curso</span>
                <?php endif; ?>
              </td>

              <?php if ($_SESSION['tipo'] === 'admin'): ?>
              <td>
                <div class="d-flex gap-1 justify-content-center">
                  <button class="action-btn action-view" title="Editar"
                          onclick="abrirEditar(
                            <?php echo $row['id']; ?>,
                            '<?php echo addslashes($row['nome']); ?>',
                            '<?php echo addslashes($row['email'] ?? ''); ?>',
                            <?php echo $row['curso_id'] ?? 'null'; ?>
                          )">
                    <i class="bi bi-pencil"></i>
                  </button>
                  <button class="action-btn action-delete" title="Eliminar"
                          onclick="abrirEliminar(<?php echo $row['id']; ?>, '<?php echo addslashes($row['nome']); ?>')">
                    <i class="bi bi-trash"></i>
                  </button>
                </div>
              </td>
              <?php endif; ?>

            </tr>
            <?php endwhile; ?>
            <?php else: ?>
            <tr>
              <td colspan="6" class="text-center py-5" style="color:var(--muted);">
                <i class="bi bi-mortarboard" style="font-size:32px;display:block;margin-bottom:10px;opacity:0.3;"></i>
                Nenhum aluno registado.
              </td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div><!-- /page-content -->
</div><!-- /main-wrap -->


<!-- ════════ MODAL EDITAR ════════ -->
<div class="modal-overlay" id="modalEditar" onclick="fecharModal('modalEditar')">
  <div class="modal-box" onclick="event.stopPropagation()">
    <div class="modal-header-custom">
      <div>
        <div class="modal-title-custom">Editar Aluno</div>
        <div class="modal-sub">Atualiza os dados do aluno</div>
      </div>
      <button class="modal-close" onclick="fecharModal('modalEditar')"><i class="bi bi-x-lg"></i></button>
    </div>

    <form method="POST">
      <input type="hidden" name="acao" value="editar">
      <input type="hidden" name="id" id="edit_id">

      <div class="modal-field">
        <label class="field-label-m">Nome Completo</label>
        <input type="text" name="nome" id="edit_nome" class="field-input-m" required>
      </div>

      <div class="modal-field">
        <label class="field-label-m">Email Institucional</label>
        <input type="email" name="email" id="edit_email" class="field-input-m" required>
      </div>

      <div class="modal-field">
        <label class="field-label-m">Curso</label>
        <select name="curso_id" id="edit_curso" class="field-input-m">
          <option value="">— Sem curso —</option>
          <?php foreach ($lista_cursos as $c): ?>
          <option value="<?php echo $c['ID']; ?>"><?php echo htmlspecialchars($c['Nome']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="modal-actions">
        <button type="button" class="btn-modal-cancel" onclick="fecharModal('modalEditar')">Cancelar</button>
        <button type="submit" class="btn-modal-confirm">
          <i class="bi bi-check-lg me-1"></i>Guardar
        </button>
      </div>
    </form>
  </div>
</div>


<!-- ════════ MODAL ELIMINAR ════════ -->
<div class="modal-overlay" id="modalEliminar" onclick="fecharModal('modalEliminar')">
  <div class="modal-box modal-box-sm" onclick="event.stopPropagation()">
    <div class="modal-header-custom">
      <div>
        <div class="modal-title-custom" style="color:#e74c3c;">Eliminar Aluno</div>
        <div class="modal-sub">Esta ação é irreversível</div>
      </div>
      <button class="modal-close" onclick="fecharModal('modalEliminar')"><i class="bi bi-x-lg"></i></button>
    </div>

    <p style="color:var(--muted);font-size:14px;margin-bottom:24px;">
      Tens a certeza que queres eliminar o aluno <strong id="del_nome" style="color:var(--cream);"></strong>?
      O acesso à área do aluno será removido permanentemente.
    </p>

    <form method="POST">
      <input type="hidden" name="acao" value="eliminar">
      <input type="hidden" name="id" id="del_id">
      <div class="modal-actions">
        <button type="button" class="btn-modal-cancel" onclick="fecharModal('modalEliminar')">Cancelar</button>
        <button type="submit" class="btn-modal-danger">
          <i class="bi bi-trash me-1"></i>Eliminar
        </button>
      </div>
    </form>
  </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Stats dinâmicas
  window.addEventListener('DOMContentLoaded', () => {
    const rows = document.querySelectorAll('#mainTable tbody tr[data-nome]');
    let semCurso = 0, comEmail = 0;
    rows.forEach(r => {
      if (r.dataset.curso === 'sem curso') semCurso++;
      if (r.dataset.email.includes('@alunos.ipca.pt')) comEmail++;
    });
    document.getElementById('count-sem-curso').textContent  = semCurso;
    document.getElementById('count-com-email').textContent  = comEmail;
  });

  // Filtro
  function filterTable() {
    const q     = document.getElementById('searchInput').value.toLowerCase();
    const curso = document.getElementById('filterCurso').value.toLowerCase();
    document.querySelectorAll('#mainTable tbody tr[data-nome]').forEach(row => {
      const matchQ    = !q    || row.dataset.nome.includes(q) || row.dataset.num.includes(q) || row.dataset.email.includes(q);
      const matchC    = !curso || row.dataset.curso.toLowerCase() === curso;
      row.style.display = (matchQ && matchC) ? '' : 'none';
    });
  }

  // Modal editar
  function abrirEditar(id, nome, email, cursoId) {
    document.getElementById('edit_id').value    = id;
    document.getElementById('edit_nome').value  = nome;
    document.getElementById('edit_email').value = email;
    const sel = document.getElementById('edit_curso');
    sel.value = cursoId || '';
    document.getElementById('modalEditar').classList.add('open');
  }

  // Modal eliminar
  function abrirEliminar(id, nome) {
    document.getElementById('del_id').value       = id;
    document.getElementById('del_nome').textContent = nome;
    document.getElementById('modalEliminar').classList.add('open');
  }

  function fecharModal(id) {
    document.getElementById(id).classList.remove('open');
  }

  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
      document.querySelectorAll('.modal-overlay.open').forEach(m => m.classList.remove('open'));
    }
  });

  function exportar() { window.location.href = 'exportar_alunos.php'; }

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