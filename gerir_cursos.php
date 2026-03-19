<?php
include "db.php";

if (!isset($_SESSION['tipo']) || ($_SESSION['tipo'] !== 'admin' && $_SESSION['tipo'] !== 'funcionario')) {
    header("Location: login.php");
    exit();
}

$msg = $msg_tipo = '';

// ══ AÇÕES POST ══
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    // ── CRIAR CURSO
    if ($acao === 'criar_curso') {
        $nome  = trim($_POST['nome_curso'] ?? '');
        $discs = $_POST['disciplinas'] ?? [];

        if (empty($nome)) {
            $msg = "O nome do curso não pode ficar vazio.";
            $msg_tipo = "erro";
        } else {
            $check = $conn->prepare("SELECT ID FROM cursos WHERE Nome = ?");
            $check->execute([$nome]);

            if ($check->fetch()) {
                $msg = "Já existe um curso com esse nome.";
                $msg_tipo = "erro";
            } else {
                $stmt = $conn->prepare("INSERT INTO cursos (Nome) VALUES (?)");

                if ($stmt->execute([$nome])) {
                    $curso_id = $conn->lastInsertId();

                    if (!empty($discs)) {
                        $sp = $conn->prepare("INSERT INTO plano_estudos (CURSOS, DISCIPLINA) VALUES (?, ?)");
                        foreach ($discs as $d) {
                            $did = (int)$d;
                            $sp->execute([$curso_id, $did]);
                        }
                    }

                    $msg = "Curso \"$nome\" criado com sucesso!";
                    $msg_tipo = "sucesso";
                } else {
                    $msg = "Erro ao criar curso.";
                    $msg_tipo = "erro";
                }
            }
        }
    }

    // ── EDITAR CURSO
    if ($acao === 'editar_curso') {
        $id    = (int)($_POST['curso_id'] ?? 0);
        $nome  = trim($_POST['nome_curso'] ?? '');
        $discs = $_POST['disciplinas'] ?? [];

        if (empty($nome)) {
            $msg = "O nome do curso não pode ficar vazio.";
            $msg_tipo = "erro";
        } elseif ($id <= 0) {
            $msg = "Curso inválido.";
            $msg_tipo = "erro";
        } else {
            // Atualizar nome
            $stmt = $conn->prepare("UPDATE cursos SET Nome = ? WHERE ID = ?");

            if ($stmt->execute([$nome, $id])) {
                // Apagar disciplinas antigas
                $del = $conn->prepare("DELETE FROM plano_estudos WHERE CURSOS = ?");
                $del->execute([$id]);

                // Inserir disciplinas novas
                if (!empty($discs)) {
                    $sp = $conn->prepare("INSERT INTO plano_estudos (CURSOS, DISCIPLINA) VALUES (?, ?)");
                    foreach ($discs as $d) {
                        $did = (int)$d;
                        $sp->execute([$id, $did]);
                    }
                }

                $msg = "Curso atualizado com sucesso.";
                $msg_tipo = "sucesso";
            } else {
                $msg = "Erro ao atualizar o curso.";
                $msg_tipo = "erro";
            }
        }
    }

    // ── ELIMINAR CURSO
    if ($acao === 'eliminar_curso') {
        $id = (int)($_POST['curso_id'] ?? 0);

        if ($id > 0) {
            $del_pe = $conn->prepare("DELETE FROM plano_estudos WHERE CURSOS = ?");
            $del_pe->execute([$id]);

            $del_c = $conn->prepare("DELETE FROM cursos WHERE ID = ?");
            if ($del_c->execute([$id])) {
                $msg = "Curso eliminado com sucesso.";
                $msg_tipo = "sucesso";
            } else {
                $msg = "Erro ao eliminar o curso.";
                $msg_tipo = "erro";
            }
        }
    }
}

// ══ BUSCAR DADOS ══
$cursos_result = $conn->query("
    SELECT c.ID, c.Nome,
           COUNT(DISTINCT pe.DISCIPLINA) AS num_disciplinas,
           COUNT(DISTINCT a.id)          AS num_alunos
    FROM cursos c
    LEFT JOIN plano_estudos pe ON pe.CURSOS = c.ID
    LEFT JOIN alunos a         ON a.curso_id = c.ID
    GROUP BY c.ID, c.Nome
    ORDER BY c.Nome ASC
");

$discs_result = $conn->query("SELECT ID, Nome_disc FROM disciplinas ORDER BY Nome_disc ASC");
$todas_disc   = [];
// PDO fetchAll below

$plano_map  = [];
$plano_rows = $conn->query("SELECT CURSOS, DISCIPLINA FROM plano_estudos");
foreach ($plano_rows->fetchAll() as $p) {
    $plano_map[$p['CURSOS']][] = (int)$p['DISCIPLINA'];
}

$total_cand   = $conn->query("SELECT COUNT(*) as n FROM candidaturas")->fetchColumn();
$total_alunos = $conn->query("SELECT COUNT(*) as n FROM alunos")->fetchColumn();
$cursos_all = $cursos_result->fetchAll();
$total_cursos = count($cursos_all);
$total_discs  = count($todas_disc);
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Gerir Cursos — IPCA Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/dashboard.css">
<link rel="stylesheet" href="css/adicionar_curso.css">
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
  <a href="lista_alunos.php" class="sidebar-link">
    <i class="bi bi-mortarboard"></i> Alunos Ativos
  </a>
  <a href="adicionar_curso.php" class="sidebar-link active">
    <i class="bi bi-book"></i> Cursos
  </a>

  <?php if ($_SESSION['tipo'] === 'admin'): ?>
  <div class="sidebar-section">Administração</div>
  <a href="gerir_utilizadores.php" class="sidebar-link">
    <i class="bi bi-people-fill"></i> Utilizadores
  </a>
  <?php endif; ?>

  <div class="sidebar-bottom">
    <div class="sidebar-user">
      <div class="sidebar-avatar"><?php echo strtoupper(substr($_SESSION['usuario'], 0, 1)); ?></div>
      <div style="overflow:hidden;">
        <div style="font-size:13px;color:var(--cream);font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
          <?php echo htmlspecialchars($_SESSION['usuario']); ?>
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
        <div class="topbar-title">Gestão de Cursos</div>
        <div class="topbar-breadcrumb">IPCA › <span>Admin</span> › Cursos</div>
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

    <!-- STAT CARDS -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-md-3">
        <div class="stat-card" style="--card-color:#c9a84c;--card-rgb:201,168,76">
          <div class="stat-icon"><i class="bi bi-book"></i></div>
          <div class="stat-value"><?php echo $total_cursos; ?></div>
          <div class="stat-label">Cursos Ativos</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card" style="--card-color:#3498db;--card-rgb:52,152,219">
          <div class="stat-icon"><i class="bi bi-journal-richtext"></i></div>
          <div class="stat-value"><?php echo $total_discs; ?></div>
          <div class="stat-label">Disciplinas</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card" style="--card-color:#2ecc71;--card-rgb:46,204,113">
          <div class="stat-icon"><i class="bi bi-mortarboard"></i></div>
          <div class="stat-value"><?php echo $total_alunos; ?></div>
          <div class="stat-label">Alunos Matriculados</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card" style="--card-color:#9b59b6;--card-rgb:155,89,182">
          <div class="stat-icon"><i class="bi bi-diagram-3"></i></div>
          <div class="stat-value"><?php echo $conn->query("SELECT COUNT(*) as n FROM plano_estudos")->fetchColumn(); ?></div>
          <div class="stat-label">Associações Plano</div>
        </div>
      </div>
    </div>

    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <p class="mb-0" style="font-size:11px;color:var(--gold);letter-spacing:2px;text-transform:uppercase;">Oferta Formativa</p>
        <h2 style="font-family:'Playfair Display',serif;font-size:22px;font-weight:700;color:var(--cream);margin:0;">
          Cursos &amp; Disciplinas
        </h2>
      </div>
      <button class="btn-gold" onclick="abrirCriar()">
        <i class="bi bi-plus-lg me-1"></i>Novo Curso
      </button>
    </div>

    <!-- GRID DE CURSOS -->
    <div class="row g-4" id="cursosGrid">
      <?php
        foreach ($cursos_all as $curso):
          $disc_ids   = $plano_map[$curso['ID']] ?? [];
          $disc_nomes = array_filter($todas_disc, fn($d) => in_array((int)$d['ID'], $disc_ids));
      ?>
      <div class="col-md-6 col-xl-4">
        <div class="curso-card">

          <div class="curso-nome"><?php echo htmlspecialchars($curso['Nome']); ?></div>

          <div class="curso-meta">
            <div class="curso-meta-item">
              <i class="bi bi-journal-text"></i>
              <?php echo $curso['num_disciplinas']; ?> disciplina<?php echo $curso['num_disciplinas'] != 1 ? 's' : ''; ?>
            </div>
            <div class="curso-meta-item">
              <i class="bi bi-mortarboard"></i>
              <?php echo $curso['num_alunos']; ?> aluno<?php echo $curso['num_alunos'] != 1 ? 's' : ''; ?>
            </div>
          </div>

          <div class="disc-chips">
            <?php if (!empty($disc_nomes)):
              foreach ($disc_nomes as $d): ?>
                <span class="disc-chip"><?php echo htmlspecialchars($d['Nome_disc']); ?></span>
            <?php endforeach;
            else: ?>
              <span class="disc-chip empty">Sem disciplinas associadas</span>
            <?php endif; ?>
          </div>

          <div class="curso-actions">
            <button class="action-btn action-view" style="flex:1;width:auto;padding:6px 0;"
                    onclick="abrirEditar(
                      <?php echo $curso['ID']; ?>,
                      '<?php echo addslashes($curso['Nome']); ?>',
                      <?php echo json_encode(array_values($disc_ids)); ?>
                    )">
              <i class="bi bi-pencil me-1"></i> Editar
            </button>
            <?php if ($_SESSION['tipo'] === 'admin'): ?>
            <button class="action-btn action-delete" style="flex:1;width:auto;padding:6px 0;"
                    onclick="abrirEliminar(<?php echo $curso['ID']; ?>, '<?php echo addslashes($curso['Nome']); ?>')">
              <i class="bi bi-trash me-1"></i> Eliminar
            </button>
            <?php endif; ?>
          </div>

        </div>
      </div>
      <?php endforeach; ?>

      <?php if ($total_cursos === 0): ?>
      <div class="col-12 text-center py-5" style="color:var(--muted);">
        <i class="bi bi-book" style="font-size:40px;display:block;margin-bottom:12px;opacity:0.3;"></i>
        Nenhum curso registado. Cria o primeiro!
      </div>
      <?php endif; ?>
    </div>

  </div><!-- /page-content -->
</div><!-- /main-wrap -->


<!-- ════════ MODAL CRIAR ════════ -->
<div class="modal-overlay" id="modalCriar" onclick="fecharModal('modalCriar')">
  <div class="modal-box" onclick="event.stopPropagation()">
    <div class="modal-header-custom">
      <div>
        <div class="modal-title-custom">Novo Curso</div>
        <div class="modal-sub">Preenche os dados e seleciona as disciplinas</div>
      </div>
      <button class="modal-close" onclick="fecharModal('modalCriar')"><i class="bi bi-x-lg"></i></button>
    </div>
    <form method="POST">
      <input type="hidden" name="acao" value="criar_curso">
      <div class="modal-field">
        <label class="field-label-m">Nome do Curso</label>
        <input type="text" name="nome_curso" class="field-input-m" placeholder="Ex: Engenharia Informática" required>
      </div>
      <div class="modal-field">
        <label class="field-label-m">Disciplinas do Plano de Estudos</label>
        <div class="disc-check-grid">
          <?php foreach ($todas_disc as $d): ?>
          <label class="disc-check-item">
            <input type="checkbox" name="disciplinas[]" value="<?php echo $d['ID']; ?>">
            <?php echo htmlspecialchars($d['Nome_disc']); ?>
          </label>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn-modal-cancel" onclick="fecharModal('modalCriar')">Cancelar</button>
        <button type="submit" class="btn-modal-confirm"><i class="bi bi-plus-lg me-1"></i>Criar Curso</button>
      </div>
    </form>
  </div>
</div>


<!-- ════════ MODAL EDITAR ════════ -->
<div class="modal-overlay" id="modalEditar" onclick="fecharModal('modalEditar')">
  <div class="modal-box" onclick="event.stopPropagation()">
    <div class="modal-header-custom">
      <div>
        <div class="modal-title-custom">Editar Curso</div>
        <div class="modal-sub">Altera o nome e as disciplinas associadas</div>
      </div>
      <button class="modal-close" onclick="fecharModal('modalEditar')"><i class="bi bi-x-lg"></i></button>
    </div>
    <form method="POST">
      <input type="hidden" name="acao" value="editar_curso">
      <input type="hidden" name="curso_id" id="edit_curso_id">
      <div class="modal-field">
        <label class="field-label-m">Nome do Curso</label>
        <input type="text" name="nome_curso" id="edit_nome_curso" class="field-input-m" required>
      </div>
      <div class="modal-field">
        <label class="field-label-m">Disciplinas</label>
        <div class="disc-check-grid">
          <?php foreach ($todas_disc as $d): ?>
          <label class="disc-check-item">
            <input type="checkbox"
                   name="disciplinas[]"
                   value="<?php echo $d['ID']; ?>"
                   class="edit-disc-check"
                   data-id="<?php echo $d['ID']; ?>">
            <?php echo htmlspecialchars($d['Nome_disc']); ?>
          </label>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn-modal-cancel" onclick="fecharModal('modalEditar')">Cancelar</button>
        <button type="submit" class="btn-modal-confirm"><i class="bi bi-check-lg me-1"></i>Guardar Alterações</button>
      </div>
    </form>
  </div>
</div>


<!-- ════════ MODAL ELIMINAR ════════ -->
<div class="modal-overlay" id="modalEliminar" onclick="fecharModal('modalEliminar')">
  <div class="modal-box modal-box-sm" onclick="event.stopPropagation()">
    <div class="modal-header-custom">
      <div>
        <div class="modal-title-custom" style="color:#e74c3c;">Eliminar Curso</div>
        <div class="modal-sub">Esta ação é irreversível</div>
      </div>
      <button class="modal-close" onclick="fecharModal('modalEliminar')"><i class="bi bi-x-lg"></i></button>
    </div>
    <p style="color:var(--muted);font-size:14px;margin-bottom:8px;">
      Tens a certeza que queres eliminar o curso <strong id="del_nome_curso" style="color:var(--cream);"></strong>?
    </p>
    <p style="color:#e74c3c;font-size:12px;margin-bottom:24px;">
      <i class="bi bi-exclamation-triangle me-1"></i>
      As disciplinas associadas no plano de estudos também serão removidas.
    </p>
    <form method="POST">
      <input type="hidden" name="acao" value="eliminar_curso">
      <input type="hidden" name="curso_id" id="del_curso_id">
      <div class="modal-actions">
        <button type="button" class="btn-modal-cancel" onclick="fecharModal('modalEliminar')">Cancelar</button>
        <button type="submit" class="btn-modal-danger"><i class="bi bi-trash me-1"></i>Eliminar Curso</button>
      </div>
    </form>
  </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  function abrirCriar() {
    document.getElementById('modalCriar').classList.add('open');
  }

  function abrirEditar(id, nome, discIds) {
    document.getElementById('edit_curso_id').value   = id;
    document.getElementById('edit_nome_curso').value = nome;

    // Converter para array de números e marcar checkboxes
    const ids = discIds.map(Number);
    document.querySelectorAll('.edit-disc-check').forEach(cb => {
      cb.checked = ids.includes(parseInt(cb.dataset.id));
    });

    document.getElementById('modalEditar').classList.add('open');
  }

  function abrirEliminar(id, nome) {
    document.getElementById('del_curso_id').value         = id;
    document.getElementById('del_nome_curso').textContent = nome;
    document.getElementById('modalEliminar').classList.add('open');
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
    if (tw) { tw.style.opacity = '0'; tw.style.transition = 'opacity 0.4s'; setTimeout(() => tw?.remove(), 400); }
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
