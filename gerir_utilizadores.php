<?php
include "db.php";

// ── Proteção: apenas admin
if (!isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
    header("Location: dashboard_admin.php");
    exit();
}

$msg = $msg_tipo = '';

// ══ AÇÕES POST ══
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    // ── CRIAR PROFESSOR
    if ($acao === 'criar') {
        $nome       = trim($_POST['nome'] ?? '');
        $email      = trim($_POST['email'] ?? '');
        $senha      = $_POST['senha'] ?? '';
        $disciplinas = $_POST['disciplinas'] ?? [];

        if (empty($nome) || empty($email) || empty($senha)) {
            $msg = "Preenche todos os campos obrigatórios.";
            $msg_tipo = "erro";
        } elseif (strlen($senha) < 6) {
            $msg = "A password deve ter pelo menos 6 caracteres.";
            $msg_tipo = "erro";
        } else {
            // Verificar email duplicado
            $chk = $conn->prepare("SELECT id FROM professores WHERE email = ?");
            $chk->execute([$email]);
            if ($chk->fetch()) {
                $msg = "Já existe um professor com esse email.";
                $msg_tipo = "erro";
            } else {
                $hash = password_hash($senha, PASSWORD_BCRYPT);
                $stmt = $conn->prepare("INSERT INTO professores (nome, email, password) VALUES (?, ?, ?)");
                if ($stmt->execute([$nome, $email, $hash])) {
                    $prof_id = $conn->lastInsertId();
                    // Associar disciplinas
                    if (!empty($disciplinas)) {
                        $sd = $conn->prepare("INSERT INTO prof_disciplinas (professor_id, disciplina_id) VALUES (?, ?)");
                        foreach ($disciplinas as $did) {
                            $sd->execute([$prof_id, (int)$did]);
                        }
                    }
                    $msg = "Professor \"$nome\" criado com sucesso.";
                    $msg_tipo = "sucesso";
                } else {
                    $msg = "Erro ao criar professor.";
                    $msg_tipo = "erro";
                }
            }
        }
    }

    // ── EDITAR PROFESSOR
    if ($acao === 'editar') {
        $id          = (int)$_POST['prof_id'];
        $nome        = trim($_POST['nome'] ?? '');
        $email       = trim($_POST['email'] ?? '');
        $senha       = $_POST['senha'] ?? '';
        $disciplinas  = $_POST['disciplinas'] ?? [];

        $stmt = $conn->prepare("UPDATE professores SET nome = ?, email = ? WHERE id = ?");
        $stmt->execute([$nome, $email, $id]);

        // Atualizar password só se preenchida
        if (!empty($senha)) {
            if (strlen($senha) < 6) {
                $msg = "A nova password deve ter pelo menos 6 caracteres.";
                $msg_tipo = "erro";
            } else {
                $hash = password_hash($senha, PASSWORD_BCRYPT);
                $sp = $conn->prepare("UPDATE professores SET password = ? WHERE id = ?");
                $sp->execute([$hash, $id]);
            }
        }

        if ($msg_tipo !== 'erro') {
            // Recriar associações de disciplinas
            $del = $conn->prepare("DELETE FROM prof_disciplinas WHERE professor_id = ?");
            $del->execute([$id]);

            if (!empty($disciplinas)) {
                $sd = $conn->prepare("INSERT INTO prof_disciplinas (professor_id, disciplina_id) VALUES (?, ?)");
                foreach ($disciplinas as $did) {
                    $sd->execute([$id, (int)$did]);
                }
            }
            $msg = "Professor atualizado com sucesso.";
            $msg_tipo = "sucesso";
        }
    }

    // ── ELIMINAR PROFESSOR
    if ($acao === 'eliminar') {
        $id = (int)$_POST['prof_id'];
        $del_pd = $conn->prepare("DELETE FROM prof_disciplinas WHERE professor_id = ?");
        $del_pd->execute([$id]);
        $del_p = $conn->prepare("DELETE FROM professores WHERE id = ?");
        $msg = $del_p->execute([$id]) ? "Professor eliminado." : "Erro ao eliminar.";
        $msg_tipo = strpos($msg, 'Erro') === false ? "sucesso" : "erro";
    }
}

// ══ BUSCAR DADOS ══
$professores_stmt = $conn->query("
    SELECT p.id, p.nome, p.email,
           GROUP_CONCAT(d.Nome_disc ORDER BY d.Nome_disc SEPARATOR '|||') AS disciplinas_nomes,
           GROUP_CONCAT(d.ID ORDER BY d.Nome_disc SEPARATOR ',') AS disciplinas_ids,
           COUNT(d.ID) AS num_disciplinas
    FROM professores p
    LEFT JOIN prof_disciplinas pd ON pd.professor_id = p.id
    LEFT JOIN disciplinas d ON d.ID = pd.disciplina_id
    GROUP BY p.id, p.nome, p.email
    ORDER BY p.nome ASC
");

$todas_disc = $conn->query("SELECT ID, Nome_disc FROM disciplinas ORDER BY Nome_disc ASC")->fetchAll();

// Badges sidebar
$total_cand   = $conn->query("SELECT COUNT(*) as n FROM candidaturas")->fetchColumn();
$total_alunos = $conn->query("SELECT COUNT(*) as n FROM alunos")->fetchColumn();
$total_profs  = $conn->query("SELECT COUNT(*) as n FROM professores")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Utilizadores — IPCA Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/dashboard.css">
<link rel="stylesheet" href="css/gerir_utilizadores.css">

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
  <a href="adicionar_curso.php" class="sidebar-link">
    <i class="bi bi-book"></i> Cursos
  </a>

  <div class="sidebar-section">Administração</div>
  <a href="gerir_utilizadores.php" class="sidebar-link active">
    <i class="bi bi-people-fill"></i> Utilizadores
  </a>

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
        <div class="topbar-title">Gestão de Utilizadores</div>
        <div class="topbar-breadcrumb">IPCA › <span>Admin</span> › Utilizadores</div>
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
          <div class="stat-icon"><i class="bi bi-person-workspace"></i></div>
          <div class="stat-value"><?php echo $total_profs; ?></div>
          <div class="stat-label">Professores</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card" style="--card-color:#3498db;--card-rgb:52,152,219">
          <div class="stat-icon"><i class="bi bi-journal-richtext"></i></div>
          <div class="stat-value"><?php echo count($todas_disc); ?></div>
          <div class="stat-label">Disciplinas</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card" style="--card-color:#2ecc71;--card-rgb:46,204,113">
          <div class="stat-icon"><i class="bi bi-mortarboard"></i></div>
          <div class="stat-value"><?php echo $total_alunos; ?></div>
          <div class="stat-label">Alunos Ativos</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card" style="--card-color:#9b59b6;--card-rgb:155,89,182">
          <div class="stat-icon"><i class="bi bi-shield-lock"></i></div>
          <div class="stat-value">1</div>
          <div class="stat-label">Administradores</div>
        </div>
      </div>
    </div>

    <!-- HEADER + BOTÃO CRIAR -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
      <div>
        <p style="font-size:11px;color:var(--gold);letter-spacing:2px;text-transform:uppercase;margin:0;">
          Corpo Docente
        </p>
        <h2 style="font-family:'Playfair Display',serif;font-size:22px;font-weight:700;color:var(--cream);margin:0;">
          Professores &amp; Docentes
        </h2>
      </div>
      <button class="btn-gold" onclick="abrirCriar()">
        <i class="bi bi-plus-lg me-1"></i> Novo Professor
      </button>
    </div>

    <!-- FILTRO -->
    <div class="filter-bar mb-4">
      <div class="row g-2 align-items-center">
        <div class="col-12 col-md-5">
          <input type="text" class="search-input" id="searchInput"
                 placeholder="Pesquisar por nome ou email..."
                 oninput="filtrar()">
        </div>
        <div class="col-12 col-md-4">
          <select class="filter-select w-100" id="filterDisc" onchange="filtrar()">
            <option value="">Todas as disciplinas</option>
            <?php foreach ($todas_disc as $d): ?>
            <option value="<?php echo htmlspecialchars($d['Nome_disc']); ?>">
              <?php echo htmlspecialchars($d['Nome_disc']); ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </div>

    <!-- GRID DE PROFESSORES -->
    <div class="row g-4" id="profGrid">

      <?php if ($professores_stmt->rowCount() === 0): ?>
      <div class="col-12">
        <div class="empty-state">
          <i class="bi bi-person-workspace"></i>
          <p>Ainda não há professores registados.<br>Clica em <strong style="color:var(--gold);">Novo Professor</strong> para começar.</p>
        </div>
      </div>

      <?php else:
        while ($prof = $professores_stmt->fetch()):
          $initials = strtoupper(substr($prof['nome'], 0, 1) .
            (strpos($prof['nome'], ' ') !== false ? substr(strrchr($prof['nome'], ' '), 1, 1) : ''));
          $disc_nomes = $prof['disciplinas_nomes'] ? explode('|||', $prof['disciplinas_nomes']) : [];
          $disc_ids   = $prof['disciplinas_ids']   ? array_map('intval', explode(',', $prof['disciplinas_ids'])) : [];
      ?>
      <div class="col-md-6 col-xl-4 prof-item"
           data-nome="<?php echo strtolower($prof['nome']); ?>"
           data-email="<?php echo strtolower($prof['email']); ?>"
           data-discs="<?php echo strtolower($prof['disciplinas_nomes'] ?? ''); ?>">

        <div class="prof-card">

          <div class="prof-avatar"><?php echo $initials; ?></div>

          <div class="prof-info">
            <div class="prof-nome"><?php echo htmlspecialchars($prof['nome']); ?></div>
            <div class="prof-email">
              <i class="bi bi-envelope me-1"></i>
              <?php echo htmlspecialchars($prof['email']); ?>
            </div>

            <div class="disc-chips">
              <?php if (!empty($disc_nomes)):
                foreach ($disc_nomes as $dn): ?>
                  <span class="disc-chip"><?php echo htmlspecialchars($dn); ?></span>
              <?php endforeach;
              else: ?>
                <span class="disc-chip empty">Sem disciplinas</span>
              <?php endif; ?>
            </div>
          </div>

          <!-- Ações -->
          <div class="prof-actions">
            <button class="action-btn action-view" title="Editar"
                    onclick="abrirEditar(
                      <?php echo $prof['id']; ?>,
                      '<?php echo addslashes($prof['nome']); ?>',
                      '<?php echo addslashes($prof['email']); ?>',
                      <?php echo json_encode($disc_ids); ?>
                    )">
              <i class="bi bi-pencil"></i>
            </button>
            <button class="action-btn action-delete" title="Eliminar"
                    onclick="abrirEliminar(<?php echo $prof['id']; ?>, '<?php echo addslashes($prof['nome']); ?>')">
              <i class="bi bi-trash"></i>
            </button>
          </div>

        </div>
      </div>
      <?php endwhile; endif; ?>

    </div><!-- /profGrid -->

  </div><!-- /page-content -->
</div><!-- /main-wrap -->


<!-- ════════ MODAL CRIAR ════════ -->
<div class="modal-overlay" id="modalCriar" onclick="fecharModal('modalCriar')">
  <div class="modal-box" onclick="event.stopPropagation()">
    <div class="modal-header-custom">
      <div>
        <div class="modal-title-custom">Novo Professor</div>
        <div class="modal-sub">Preenche os dados e associa as disciplinas</div>
      </div>
      <button class="modal-close" onclick="fecharModal('modalCriar')"><i class="bi bi-x-lg"></i></button>
    </div>

    <form method="POST">
      <input type="hidden" name="acao" value="criar">

      <div class="modal-field">
        <label class="field-label-m">Nome Completo</label>
        <input type="text" name="nome" class="field-input-m" placeholder="Prof. Nome Apelido" required>
      </div>

      <div class="modal-field">
        <label class="field-label-m">Email</label>
        <input type="email" name="email" class="field-input-m" placeholder="professor@ipca.pt" required>
      </div>

      <div class="modal-field">
        <label class="field-label-m">Password</label>
        <div style="position:relative;">
          <input type="password" name="senha" id="criaSenha" class="field-input-m"
                 placeholder="Mínimo 6 caracteres" required style="padding-right:42px;">
          <button type="button" onclick="togglePwd('criaSenha','criaEye')"
                  style="position:absolute;top:50%;right:14px;transform:translateY(-50%);
                         background:none;border:none;color:var(--muted);cursor:pointer;font-size:15px;">
            <i class="bi bi-eye" id="criaEye"></i>
          </button>
        </div>
      </div>

      <div class="modal-field">
        <label class="field-label-m">Disciplinas que Leciona</label>
        <div class="disc-check-grid">
          <?php foreach ($todas_disc as $d): ?>
          <label class="disc-check-item">
            <input type="checkbox" name="disciplinas[]" value="<?php echo $d['ID']; ?>">
            <?php echo htmlspecialchars($d['Nome_disc']); ?>
          </label>
          <?php endforeach; ?>
        </div>
        <?php if (empty($todas_disc)): ?>
        <p style="font-size:12px;color:var(--muted);margin-top:8px;">
          <i class="bi bi-info-circle me-1"></i>
          Não há disciplinas. Cria primeiro em <a href="gerir_cursos.php" style="color:var(--gold);">Gerir Cursos</a>.
        </p>
        <?php endif; ?>
      </div>

      <div class="modal-actions">
        <button type="button" class="btn-modal-cancel" onclick="fecharModal('modalCriar')">Cancelar</button>
        <button type="submit" class="btn-modal-confirm">
          <i class="bi bi-plus-lg me-1"></i> Criar Professor
        </button>
      </div>
    </form>
  </div>
</div>


<!-- ════════ MODAL EDITAR ════════ -->
<div class="modal-overlay" id="modalEditar" onclick="fecharModal('modalEditar')">
  <div class="modal-box" onclick="event.stopPropagation()">
    <div class="modal-header-custom">
      <div>
        <div class="modal-title-custom">Editar Professor</div>
        <div class="modal-sub">Altera os dados e as disciplinas associadas</div>
      </div>
      <button class="modal-close" onclick="fecharModal('modalEditar')"><i class="bi bi-x-lg"></i></button>
    </div>

    <form method="POST">
      <input type="hidden" name="acao" value="editar">
      <input type="hidden" name="prof_id" id="editId">

      <div class="modal-field">
        <label class="field-label-m">Nome Completo</label>
        <input type="text" name="nome" id="editNome" class="field-input-m" required>
      </div>

      <div class="modal-field">
        <label class="field-label-m">Email</label>
        <input type="email" name="email" id="editEmail" class="field-input-m" required>
      </div>

      <div class="modal-field">
        <label class="field-label-m">Nova Password</label>
        <div style="position:relative;">
          <input type="password" name="senha" id="editSenha" class="field-input-m"
                 placeholder="Deixa em branco para não alterar" style="padding-right:42px;">
          <button type="button" onclick="togglePwd('editSenha','editEye')"
                  style="position:absolute;top:50%;right:14px;transform:translateY(-50%);
                         background:none;border:none;color:var(--muted);cursor:pointer;font-size:15px;">
            <i class="bi bi-eye" id="editEye"></i>
          </button>
        </div>
        <div class="pwd-hint"><i class="bi bi-info-circle"></i> Deixa em branco para manter a password atual.</div>
      </div>

      <div class="modal-field">
        <label class="field-label-m">Disciplinas que Leciona</label>
        <div class="disc-check-grid" id="editDiscGrid">
          <?php foreach ($todas_disc as $d): ?>
          <label class="disc-check-item">
            <input type="checkbox" name="disciplinas[]"
                   class="edit-disc-check" data-id="<?php echo $d['ID']; ?>"
                   value="<?php echo $d['ID']; ?>">
            <?php echo htmlspecialchars($d['Nome_disc']); ?>
          </label>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="modal-actions">
        <button type="button" class="btn-modal-cancel" onclick="fecharModal('modalEditar')">Cancelar</button>
        <button type="submit" class="btn-modal-confirm">
          <i class="bi bi-check-lg me-1"></i> Guardar Alterações
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
        <div class="modal-title-custom" style="color:#e74c3c;">Eliminar Professor</div>
        <div class="modal-sub">Esta ação é irreversível</div>
      </div>
      <button class="modal-close" onclick="fecharModal('modalEliminar')"><i class="bi bi-x-lg"></i></button>
    </div>

    <p style="color:var(--muted);font-size:14px;margin-bottom:8px;">
      Tens a certeza que queres eliminar o professor
      <strong id="elimNome" style="color:var(--cream);"></strong>?
    </p>
    <p style="font-size:12px;color:#e74c3c;margin-bottom:24px;">
      <i class="bi bi-exclamation-triangle me-1"></i>
      As disciplinas associadas serão desvinculadas.
    </p>

    <form method="POST">
      <input type="hidden" name="acao" value="eliminar">
      <input type="hidden" name="prof_id" id="elimId">
      <div class="modal-actions">
        <button type="button" class="btn-modal-cancel" onclick="fecharModal('modalEliminar')">Cancelar</button>
        <button type="submit" class="btn-modal-danger">
          <i class="bi bi-trash me-1"></i> Eliminar
        </button>
      </div>
    </form>
  </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // ── Modais
  function abrirCriar() { document.getElementById('modalCriar').classList.add('open'); }

  function abrirEditar(id, nome, email, discIds) {
    document.getElementById('editId').value    = id;
    document.getElementById('editNome').value  = nome;
    document.getElementById('editEmail').value = email;
    document.getElementById('editSenha').value = '';
    document.querySelectorAll('.edit-disc-check').forEach(cb => {
      cb.checked = discIds.includes(parseInt(cb.dataset.id));
    });
    document.getElementById('modalEditar').classList.add('open');
  }

  function abrirEliminar(id, nome) {
    document.getElementById('elimId').value          = id;
    document.getElementById('elimNome').textContent  = nome;
    document.getElementById('modalEliminar').classList.add('open');
  }

  function fecharModal(id) { document.getElementById(id).classList.remove('open'); }

  document.addEventListener('keydown', e => {
    if (e.key === 'Escape')
      document.querySelectorAll('.modal-overlay.open').forEach(m => m.classList.remove('open'));
  });

  // ── Toggle password visibility
  function togglePwd(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon  = document.getElementById(iconId);
    input.type  = input.type === 'password' ? 'text' : 'password';
    icon.className = input.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
  }

  // ── Filtro
  function filtrar() {
    const q    = document.getElementById('searchInput').value.toLowerCase();
    const disc = document.getElementById('filterDisc').value.toLowerCase();
    document.querySelectorAll('.prof-item').forEach(item => {
      const matchQ    = !q    || item.dataset.nome.includes(q) || item.dataset.email.includes(q);
      const matchDisc = !disc || item.dataset.discs.toLowerCase().includes(disc);
      item.style.display = (matchQ && matchDisc) ? '' : 'none';
    });
  }

  // ── Auto-dismiss toast
  setTimeout(() => {
    const tw = document.getElementById('toastWrap');
    if (tw) { tw.style.opacity = '0'; tw.style.transition = 'opacity 0.4s'; setTimeout(() => tw?.remove(), 400); }
  }, 4000);

  // ── Sidebar mobile
  document.addEventListener('click', e => {
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