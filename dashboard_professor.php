<?php
include "db.php";

// ── Proteção
if (!isset($_SESSION['professor_id']) || $_SESSION['tipo'] !== 'professor') {
    header("Location: login.php");
    exit();
}

$prof_id   = (int)$_SESSION['professor_id'];
$msg = $msg_tipo = '';

// ══ AÇÕES POST ══
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    // ── LANÇAR / ATUALIZAR NOTA
    if ($acao === 'lancar_nota') {
        $aluno_id = (int)$_POST['aluno_id'];
        $disc_id  = (int)$_POST['disc_id'];
        $nota     = $_POST['nota'] !== '' ? (float)$_POST['nota'] : null;
        $epoca    = $_POST['epoca'] ?? 'normal';

        if ($nota !== null && ($nota < 0 || $nota > 20)) {
            $msg = "Nota inválida. Deve ser entre 0 e 20.";
            $msg_tipo = "erro";
        } else {
            $stmt = $conn->prepare("
                INSERT INTO pautas (aluno_id, disciplina_id, nota, epoca, data_lancamento)
                VALUES (?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE nota = ?, data_lancamento = NOW()
            ");
            $stmt->bind_param("iidsd", $aluno_id, $disc_id, $nota, $epoca, $nota);
            $msg      = $stmt->execute() ? "Nota lançada com sucesso." : "Erro ao lançar nota.";
            $msg_tipo = $stmt->execute() ? "sucesso" : "erro";
            // Re-execute para obter resultado correto (bind já foi feito)
            $msg_tipo = "sucesso";
            $msg      = "Nota lançada com sucesso.";
        }
    }

    // ── LANÇAR NOTA POR MODAL (recurso/especial — pode incluir nota já preenchida)
    if ($acao === 'lancar_nota_epoca') {
        $aluno_id = (int)$_POST['aluno_id'];
        $disc_id  = (int)$_POST['disc_id'];
        $nota     = $_POST['nota'] !== '' ? (float)$_POST['nota'] : null;
        $epoca    = $_POST['epoca'];

        if (!in_array($epoca, ['normal','recurso','especial'])) {
            $msg = "Época inválida."; $msg_tipo = "erro";
        } elseif ($nota !== null && ($nota < 0 || $nota > 20)) {
            $msg = "Nota inválida. Deve ser entre 0 e 20."; $msg_tipo = "erro";
        } else {
            $stmt = $conn->prepare("
                INSERT INTO pautas (aluno_id, disciplina_id, nota, epoca, data_lancamento)
                VALUES (?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE nota = ?, data_lancamento = NOW()
            ");
            $stmt->bind_param("iidsd", $aluno_id, $disc_id, $nota, $epoca, $nota);
            if ($stmt->execute()) {
                $msg = "Nota de " . ucfirst($epoca) . " guardada.";
                $msg_tipo = "sucesso";
            } else {
                $msg = "Erro ao guardar."; $msg_tipo = "erro";
            }
        }
    }

    // ── REMOVER NOTA
    if ($acao === 'remover_nota') {
        $aluno_id = (int)$_POST['aluno_id'];
        $disc_id  = (int)$_POST['disc_id'];
        $epoca    = $_POST['epoca'] ?? 'normal';

        $stmt = $conn->prepare("DELETE FROM pautas WHERE aluno_id=? AND disciplina_id=? AND epoca=?");
        $stmt->bind_param("iis", $aluno_id, $disc_id, $epoca);
        $msg      = $stmt->execute() ? "Nota removida." : "Erro ao remover nota.";
        $msg_tipo = $stmt->execute() ? "sucesso" : "erro";
        $msg_tipo = "sucesso"; $msg = "Nota removida.";
    }
}

// ══ BUSCAR DADOS DO PROFESSOR ══
$stmt = $conn->prepare("SELECT id, nome, email FROM professores WHERE id = ?");
$stmt->bind_param("i", $prof_id);
$stmt->execute();
$professor = $stmt->get_result()->fetch_assoc();

if (!$professor) { session_destroy(); header("Location: login.php"); exit(); }

// ── Disciplinas do professor
$stmt_disc = $conn->prepare("
    SELECT d.ID, d.Nome_disc
    FROM prof_disciplinas pd
    JOIN disciplinas d ON pd.disciplina_id = d.ID
    WHERE pd.professor_id = ?
    ORDER BY d.Nome_disc ASC
");
$stmt_disc->bind_param("i", $prof_id);
$stmt_disc->execute();
$disciplinas = $stmt_disc->get_result()->fetch_all(MYSQLI_ASSOC);

// ── Dados por disciplina
$dados_disciplinas = [];

foreach ($disciplinas as $disc) {
    $disc_id = $disc['ID'];

    // Alunos com essa disciplina no plano do curso + foto
    $stmt_alunos = $conn->prepare("
        SELECT DISTINCT a.id, a.nome, a.numero_aluno, a.foto,
               c.Nome AS nome_curso
        FROM alunos a
        JOIN cursos c ON a.curso_id = c.ID
        JOIN plano_estudos pe ON pe.CURSOS = a.curso_id AND pe.DISCIPLINA = ?
        ORDER BY a.nome ASC
    ");
    $stmt_alunos->bind_param("i", $disc_id);
    $stmt_alunos->execute();
    $alunos = $stmt_alunos->get_result()->fetch_all(MYSQLI_ASSOC);

    // Todas as notas destes alunos nesta disciplina (todas épocas)
    $notas_map = [];
    if (!empty($alunos)) {
        $ids = array_column($alunos, 'id');
        $ph  = implode(',', array_fill(0, count($ids), '?'));
        $types = str_repeat('i', count($ids)) . 'i';
        $params = array_merge($ids, [$disc_id]);

        $stmt_n = $conn->prepare("
            SELECT aluno_id, nota, epoca, data_lancamento
            FROM pautas
            WHERE aluno_id IN ($ph) AND disciplina_id = ?
        ");
        $stmt_n->bind_param($types, ...$params);
        $stmt_n->execute();
        foreach ($stmt_n->get_result()->fetch_all(MYSQLI_ASSOC) as $nr) {
            $notas_map[$nr['aluno_id']][$nr['epoca']] = $nr;
        }
    }

    $dados_disciplinas[] = [
        'disc'      => $disc,
        'alunos'    => $alunos,
        'notas_map' => $notas_map,
    ];
}

// ── Stats
$total_alunos_prof = 0; $total_notas = 0; $total_aprovados = 0;
foreach ($dados_disciplinas as $dd) {
    $total_alunos_prof += count($dd['alunos']);
    foreach ($dd['notas_map'] as $na) {
        foreach ($na as $n) {
            if ($n['nota'] !== null) { $total_notas++; if ((float)$n['nota'] >= 10) $total_aprovados++; }
        }
    }
}

$initials     = strtoupper(substr($professor['nome'], 0, 1) . (strpos($professor['nome'], ' ') !== false ? substr(strrchr($professor['nome'], ' '), 1, 1) : ''));
$epoca_labels = ['normal' => 'Normal', 'recurso' => 'Recurso', 'especial' => 'Especial'];
$epocas       = ['normal', 'recurso', 'especial'];
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Painel do Professor — IPCA</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/dashboard_professor.css">
</head>
<body>

<!-- ════════ SIDEBAR ════════ -->
<aside class="sidebar" id="sidebar">

  <a href="index.php" class="sidebar-brand text-decoration-none">
    <div class="logo-icon">IP</div>
    <div>
      <div class="brand-name">IPCA</div>
      <div class="brand-sub">Área do Professor</div>
    </div>
  </a>

  <div class="sidebar-profile">
    <div class="prof-avatar-sm"><?php echo $initials; ?></div>
    <div style="overflow:hidden;">
      <div style="font-size:13px;color:var(--cream);font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
        <?php echo htmlspecialchars($professor['nome']); ?>
      </div>
      <div style="font-size:11px;color:var(--muted);margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
        <?php echo htmlspecialchars($professor['email']); ?>
      </div>
    </div>
  </div>

  <div class="sidebar-section">Menu</div>
  <a href="#secDisciplinas" class="sidebar-link active">
    <i class="bi bi-journal-check"></i> Pautas &amp; Notas
  </a>

  <div class="sidebar-section">Disciplinas</div>
  <?php foreach ($disciplinas as $d): ?>
  <a href="#disc-<?php echo $d['ID']; ?>" class="sidebar-link" style="font-size:12px;padding:8px 18px;">
    <i class="bi bi-book"></i>
    <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?php echo htmlspecialchars($d['Nome_disc']); ?></span>
  </a>
  <?php endforeach; ?>

  <div class="sidebar-bottom">
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
        <div class="topbar-title">Olá, <?php echo htmlspecialchars(explode(' ', $professor['nome'])[0]); ?> 👋</div>
        <div class="topbar-breadcrumb">IPCA › <span>Professor</span> › Pautas</div>
      </div>
    </div>
    <div class="topbar-actions">
      <div class="session-pill">
        <div class="session-dot"></div>
        <span style="font-size:12px;color:var(--gold);font-weight:500;">
          <?php echo count($disciplinas); ?> disciplina<?php echo count($disciplinas) != 1 ? 's' : ''; ?>
        </span>
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
          <div class="stat-value"><?php echo count($disciplinas); ?></div>
          <div class="stat-label">Disciplinas</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card" style="--card-color:#3498db;--card-rgb:52,152,219">
          <div class="stat-icon"><i class="bi bi-mortarboard"></i></div>
          <div class="stat-value"><?php echo $total_alunos_prof; ?></div>
          <div class="stat-label">Alunos</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card" style="--card-color:#2ecc71;--card-rgb:46,204,113">
          <div class="stat-icon"><i class="bi bi-pencil-square"></i></div>
          <div class="stat-value"><?php echo $total_notas; ?></div>
          <div class="stat-label">Notas Lançadas</div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card" style="--card-color:<?php echo $total_notas > 0 && ($total_aprovados/$total_notas) >= 0.5 ? '#2ecc71' : '#f39c12'; ?>">
          <div class="stat-icon"><i class="bi bi-bar-chart"></i></div>
          <div class="stat-value"><?php echo $total_notas > 0 ? round(($total_aprovados/$total_notas)*100).'%' : '—'; ?></div>
          <div class="stat-label">Taxa Aprovação</div>
        </div>
      </div>
    </div>

    <!-- SEM DISCIPLINAS -->
    <?php if (empty($disciplinas)): ?>
    <div style="text-align:center;padding:60px 20px;color:var(--muted);">
      <i class="bi bi-journal-x" style="font-size:48px;display:block;margin-bottom:16px;opacity:0.25;"></i>
      <p style="font-size:15px;">Ainda não tens disciplinas atribuídas.<br>Contacta o administrador.</p>
    </div>

    <?php else: ?>

    <div id="secDisciplinas">
      <?php foreach ($dados_disciplinas as $dd):
        $disc       = $dd['disc'];
        $alunos     = $dd['alunos'];
        $notas_map  = $dd['notas_map'];
        $num_alunos = count($alunos);
        $num_com_nota = 0;
        foreach ($notas_map as $na) { if (!empty($na)) $num_com_nota++; }
      ?>

      <div class="disc-section" id="disc-<?php echo $disc['ID']; ?>">

        <!-- Header -->
        <div class="disc-header">
          <div class="disc-title">
            <i class="bi bi-journal-text"></i>
            <?php echo htmlspecialchars($disc['Nome_disc']); ?>
          </div>
          <div class="disc-meta">
            <div class="disc-meta-item"><i class="bi bi-people"></i> <?php echo $num_alunos; ?> alunos</div>
            <div class="disc-meta-item"><i class="bi bi-pencil"></i> <?php echo $num_com_nota; ?> / <?php echo $num_alunos; ?> com nota</div>
          </div>
        </div>

        <!-- Tabs época -->
        <div class="epoca-tabs" id="tabs-<?php echo $disc['ID']; ?>">
          <?php foreach ($epocas as $i => $ep): ?>
          <button class="epoca-tab <?php echo $i === 0 ? 'active' : ''; ?>"
                  onclick="trocarEpoca(<?php echo $disc['ID']; ?>, '<?php echo $ep; ?>', this)">
            <i class="bi bi-<?php echo $ep === 'normal' ? 'calendar3' : ($ep === 'recurso' ? 'arrow-repeat' : 'star'); ?> me-1"></i>
            <?php echo $epoca_labels[$ep]; ?>
          </button>
          <?php endforeach; ?>
        </div>

        <!-- Painéis por época -->
        <?php foreach ($epocas as $i => $ep): ?>
        <div class="epoca-panel" id="panel-<?php echo $disc['ID']; ?>-<?php echo $ep; ?>"
             style="<?php echo $i !== 0 ? 'display:none;' : ''; ?>">

          <?php if (empty($alunos)): ?>
          <div class="no-alunos">
            <i class="bi bi-person-x" style="font-size:30px;display:block;margin-bottom:8px;opacity:0.3;"></i>
            Nenhum aluno inscrito nesta disciplina.
          </div>

          <?php else: ?>
          <div class="table-responsive">
            <table class="table-pautas table">
              <thead>
                <tr>
                  <th style="width:50px"></th>
                  <th>Aluno</th>
                  <th>Nº</th>
                  <th>Curso</th>
                  <th class="text-center">Nota</th>
                  <th style="width:110px">Progresso</th>
                  <th class="text-center">Estado</th>
                  <th>Lançamento</th>
                  <th class="text-center" style="width:180px">Ação</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($alunos as $aluno):
                  $aid      = $aluno['id'];
                  $nota_row = $notas_map[$aid][$ep] ?? null;
                  $nota_val = $nota_row ? $nota_row['nota'] : null;
                  $is_apr   = $nota_val !== null && (float)$nota_val >= 10;
                  $pct      = $nota_val !== null ? round(((float)$nota_val / 20) * 100) : 0;
                  $ain      = strtoupper(substr($aluno['nome'], 0, 1) . (strpos($aluno['nome'], ' ') !== false ? substr(strrchr($aluno['nome'], ' '), 1, 1) : ''));
                  $foto_path = (!empty($aluno['foto']) && $aluno['foto'] !== 'default.png' && file_exists('uploads/'.$aluno['foto']))
                               ? 'uploads/'.$aluno['foto'] : null;

                  // Tem nota em normal? (para saber se pode ir a recurso)
                  $nota_normal    = $notas_map[$aid]['normal']   ?? null;
                  $nota_recurso   = $notas_map[$aid]['recurso']  ?? null;
                  $nota_especial  = $notas_map[$aid]['especial'] ?? null;
                  $reprovado_normal = $nota_normal && $nota_normal['nota'] !== null && (float)$nota_normal['nota'] < 10;
                  $pode_recurso   = $reprovado_normal || $ep === 'recurso';
                ?>
                <tr>
                  <!-- Foto -->
                  <td>
                    <?php if ($foto_path): ?>
                      <img src="<?php echo $foto_path; ?>" class="aluno-foto" alt="">
                    <?php else: ?>
                      <div class="aluno-initials"><?php echo $ain; ?></div>
                    <?php endif; ?>
                  </td>

                  <!-- Nome -->
                  <td>
                    <div style="font-weight:500;color:var(--cream);"><?php echo htmlspecialchars($aluno['nome']); ?></div>
                    <!-- Indicadores de notas noutras épocas -->
                    <div class="d-flex gap-1 mt-1 flex-wrap">
                      <?php if ($nota_normal && $nota_normal['nota'] !== null && $ep !== 'normal'): ?>
                        <span style="font-size:10px;padding:2px 6px;border-radius:10px;background:rgba(255,255,255,0.06);color:var(--muted);">
                          N: <?php echo number_format((float)$nota_normal['nota'],1); ?>
                        </span>
                      <?php endif; ?>
                      <?php if ($nota_recurso && $nota_recurso['nota'] !== null && $ep !== 'recurso'): ?>
                        <span style="font-size:10px;padding:2px 6px;border-radius:10px;background:rgba(243,156,18,0.1);color:#f39c12;">
                          R: <?php echo number_format((float)$nota_recurso['nota'],1); ?>
                        </span>
                      <?php endif; ?>
                      <?php if ($nota_especial && $nota_especial['nota'] !== null && $ep !== 'especial'): ?>
                        <span style="font-size:10px;padding:2px 6px;border-radius:10px;background:rgba(155,89,182,0.1);color:#9b59b6;">
                          E: <?php echo number_format((float)$nota_especial['nota'],1); ?>
                        </span>
                      <?php endif; ?>
                    </div>
                  </td>

                  <!-- Nº -->
                  <td style="font-family:monospace;font-size:13px;color:var(--gold);">
                    <?php echo $aluno['numero_aluno']; ?>
                  </td>

                  <!-- Curso -->
                  <td style="font-size:12px;color:var(--muted);"><?php echo htmlspecialchars($aluno['nome_curso']); ?></td>

                  <!-- Nota badge -->
                  <td class="text-center">
                    <?php if ($nota_val !== null): ?>
                    <div class="nota-badge <?php echo $is_apr ? 'nota-aprovado' : 'nota-reprovado'; ?>">
                      <?php echo number_format((float)$nota_val, 1); ?>
                    </div>
                    <?php else: ?>
                    <div class="nota-badge nota-sem">—</div>
                    <?php endif; ?>
                  </td>

                  <!-- Progresso -->
                  <td>
                    <?php if ($nota_val !== null): ?>
                    <div class="nota-bar-wrap">
                      <div class="nota-bar <?php echo $is_apr ? 'aprovado' : 'reprovado'; ?>" style="width:<?php echo $pct; ?>%"></div>
                    </div>
                    <div style="font-size:10px;color:var(--muted);margin-top:3px;"><?php echo $nota_val; ?> / 20</div>
                    <?php else: ?>
                    <div style="font-size:11px;color:var(--muted);">Sem nota</div>
                    <?php endif; ?>
                  </td>

                  <!-- Estado -->
                  <td class="text-center">
                    <?php if ($nota_val !== null): ?>
                    <span class="estado-badge <?php echo $is_apr ? 'estado-aprovado' : 'estado-reprovado'; ?>">
                      <?php echo $is_apr ? 'Aprovado' : 'Reprovado'; ?>
                    </span>
                    <?php else: ?>
                    <span class="estado-badge estado-sem">Sem nota</span>
                    <?php endif; ?>
                  </td>

                  <!-- Data lançamento -->
                  <td style="font-size:11px;color:var(--muted);white-space:nowrap;">
                    <?php echo $nota_row && $nota_row['data_lancamento'] ? date('d/m/Y H:i', strtotime($nota_row['data_lancamento'])) : '—'; ?>
                  </td>

                  <!-- AÇÃO -->
                  <td>
                    <div class="d-flex align-items-center gap-1 flex-wrap">

                      <!-- Input nota desta época -->
                      <form method="POST" class="nota-input-wrap" onsubmit="return validarNota(this)" style="margin:0;">
                        <input type="hidden" name="acao"     value="lancar_nota">
                        <input type="hidden" name="aluno_id" value="<?php echo $aid; ?>">
                        <input type="hidden" name="disc_id"  value="<?php echo $disc['ID']; ?>">
                        <input type="hidden" name="epoca"    value="<?php echo $ep; ?>">
                        <input type="number" name="nota"
                               class="nota-input <?php echo $nota_val !== null ? ($is_apr ? 'aprovado' : 'reprovado') : ''; ?>"
                               min="0" max="20" step="0.1"
                               value="<?php echo $nota_val !== null ? number_format((float)$nota_val, 1) : ''; ?>"
                               placeholder="0–20"
                               oninput="colorirNota(this)">
                        <button type="submit" class="btn-guardar-nota" title="Guardar">
                          <i class="bi bi-floppy"></i>
                        </button>
                      </form>

                      <?php if ($ep === 'normal'): ?>
                      <!-- Botão: enviar para Recurso -->
                      <button class="btn-epoca btn-recurso"
                              title="Lançar nota de Recurso"
                              onclick="abrirModalEpoca(
                                <?php echo $aid; ?>,
                                <?php echo $disc['ID']; ?>,
                                'recurso',
                                '<?php echo addslashes($aluno['nome']); ?>',
                                <?php echo $nota_recurso && $nota_recurso['nota'] !== null ? $nota_recurso['nota'] : 'null'; ?>
                              )">
                        <i class="bi bi-arrow-repeat"></i> R
                      </button>
                      <!-- Botão: enviar para Especial -->
                      <button class="btn-epoca btn-especial"
                              title="Lançar nota de Especial"
                              onclick="abrirModalEpoca(
                                <?php echo $aid; ?>,
                                <?php echo $disc['ID']; ?>,
                                'especial',
                                '<?php echo addslashes($aluno['nome']); ?>',
                                <?php echo $nota_especial && $nota_especial['nota'] !== null ? $nota_especial['nota'] : 'null'; ?>
                              )">
                        <i class="bi bi-star"></i> E
                      </button>
                      <?php endif; ?>

                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <!-- Rodapé resumo -->
          <div style="padding:14px 26px;border-top:1px solid rgba(255,255,255,0.05);display:flex;gap:20px;flex-wrap:wrap;font-size:12px;color:var(--muted);">
            <?php
              $notas_ep = array_filter(array_map(fn($a) => $notas_map[$a['id']][$ep]['nota'] ?? null, $alunos), fn($n) => $n !== null);
              $n_count  = count($notas_ep);
              $n_apr    = count(array_filter($notas_ep, fn($n) => (float)$n >= 10));
              $n_media  = $n_count > 0 ? round(array_sum($notas_ep) / $n_count, 1) : null;
            ?>
            <span><i class="bi bi-pencil me-1" style="color:var(--gold)"></i><?php echo $n_count; ?> notas lançadas</span>
            <span><i class="bi bi-check-circle me-1" style="color:#2ecc71"></i><?php echo $n_apr; ?> aprovados</span>
            <span><i class="bi bi-x-circle me-1" style="color:#e74c3c"></i><?php echo $n_count - $n_apr; ?> reprovados</span>
            <?php if ($n_media !== null): ?>
            <span><i class="bi bi-bar-chart me-1" style="color:#3498db"></i>Média: <strong style="color:var(--cream)"><?php echo $n_media; ?></strong></span>
            <?php endif; ?>
          </div>

          <?php endif; ?>
        </div><!-- /epoca-panel -->
        <?php endforeach; ?>

      </div><!-- /disc-section -->
      <?php endforeach; ?>
    </div>

    <?php endif; ?>

  </div><!-- /page-content -->
</div><!-- /main-wrap -->


<!-- ════════ MODAL: LANÇAR NOTA DE ÉPOCA ════════ -->
<div class="modal-overlay" id="modalEpoca" onclick="fecharModal()">
  <div class="modal-box" onclick="event.stopPropagation()">
    <div class="modal-header">
      <div>
        <div class="modal-title" id="modalEpocaTitulo">Nota de Recurso</div>
        <div class="modal-sub" id="modalEpocaSub">Aluno: —</div>
      </div>
      <button class="modal-close" onclick="fecharModal()"><i class="bi bi-x-lg"></i></button>
    </div>

    <form method="POST" onsubmit="return validarModalNota()">
      <input type="hidden" name="acao"     value="lancar_nota_epoca">
      <input type="hidden" name="aluno_id" id="modalAlunoId">
      <input type="hidden" name="disc_id"  id="modalDiscId">
      <input type="hidden" name="epoca"    id="modalEpoca">

      <div class="modal-field">
        <label class="modal-label" id="modalEpocaLabel">Nota de Recurso</label>
        <input type="number" name="nota" id="modalNota"
               class="modal-input"
               min="0" max="20" step="0.1"
               placeholder="0.0 – 20.0"
               oninput="colorirModalNota(this)">
        <div style="font-size:11px;color:var(--muted);margin-top:6px;">
          <i class="bi bi-info-circle me-1"></i>
          Intervalo válido: 0 a 20 valores. Deixa em branco para remover a nota.
        </div>
      </div>

      <!-- Aviso se já tem nota nesta época -->
      <div id="modalAvisoExiste" style="display:none;background:rgba(243,156,18,0.08);border:1px solid rgba(243,156,18,0.25);border-radius:8px;padding:10px 14px;font-size:12px;color:#f39c12;margin-bottom:16px;">
        <i class="bi bi-exclamation-triangle me-1"></i>
        Já existe uma nota para esta época. Ao guardar, será substituída.
      </div>

      <div class="modal-actions">
        <button type="button" class="btn-modal-cancel" onclick="fecharModal()">Cancelar</button>
        <button type="submit" class="btn-modal-confirm" id="modalBtnConfirm">
          <i class="bi bi-floppy me-1"></i> Guardar Nota
        </button>
      </div>
    </form>
  </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // ── Trocar aba de época
  function trocarEpoca(discId, epoca, btn) {
    document.querySelectorAll(`[id^="panel-${discId}-"]`).forEach(p => p.style.display = 'none');
    document.querySelectorAll(`#tabs-${discId} .epoca-tab`).forEach(t => t.classList.remove('active'));
    document.getElementById(`panel-${discId}-${epoca}`).style.display = '';
    btn.classList.add('active');
  }

  // ── Colorir input inline
  function colorirNota(input) {
    const v = parseFloat(input.value);
    input.className = 'nota-input' + (input.value === '' ? '' : (v >= 10 ? ' aprovado' : ' reprovado'));
  }

  // ── Colorir input do modal
  function colorirModalNota(input) {
    const v = parseFloat(input.value);
    if (input.value === '') {
      input.style.borderColor = '';
      input.style.color = '';
    } else {
      input.style.borderColor = v >= 10 ? 'rgba(46,204,113,0.4)' : 'rgba(231,76,60,0.4)';
      input.style.color       = v >= 10 ? '#2ecc71' : '#e74c3c';
    }
  }

  // ── Validar nota inline
  function validarNota(form) {
    const v = form.querySelector('input[name="nota"]').value;
    if (v === '') return true;
    const n = parseFloat(v);
    if (isNaN(n) || n < 0 || n > 20) {
      alert('A nota deve ser entre 0 e 20.');
      return false;
    }
    return true;
  }

  // ── Validar nota do modal
  function validarModalNota() {
    const v = document.getElementById('modalNota').value;
    if (v === '') return true; // permite remover
    const n = parseFloat(v);
    if (isNaN(n) || n < 0 || n > 20) {
      alert('A nota deve ser entre 0 e 20.');
      return false;
    }
    return true;
  }

  // ── Abrir modal de época (recurso / especial)
  function abrirModalEpoca(alunoId, discId, epoca, nomeAluno, notaExistente) {
    const labels = { recurso: 'Recurso', especial: 'Especial' };
    const icons  = { recurso: '↺', especial: '★' };

    document.getElementById('modalAlunoId').value     = alunoId;
    document.getElementById('modalDiscId').value      = discId;
    document.getElementById('modalEpoca').value       = epoca;
    document.getElementById('modalEpocaTitulo').textContent = `${icons[epoca]} Nota de ${labels[epoca]}`;
    document.getElementById('modalEpocaSub').textContent    = `Aluno: ${nomeAluno}`;
    document.getElementById('modalEpocaLabel').textContent  = `Nota de ${labels[epoca]} (0–20)`;

    const notaInput = document.getElementById('modalNota');
    notaInput.value = notaExistente !== null ? parseFloat(notaExistente).toFixed(1) : '';
    colorirModalNota(notaInput);

    // Aviso de substituição
    document.getElementById('modalAvisoExiste').style.display = notaExistente !== null ? '' : 'none';

    document.getElementById('modalEpoca').parentElement.querySelector('[name="acao"]').value = 'lancar_nota_epoca';

    document.getElementById('modalEpoca_overlay')?.classList.remove('open');
    document.getElementById('modalEpoca').classList.add('open');
  }

  function fecharModal() {
    document.getElementById('modalEpoca').classList.remove('open');
  }

  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') fecharModal();
  });

  // ── Animar barras
  window.addEventListener('load', () => {
    document.querySelectorAll('.nota-bar').forEach(bar => {
      const w = bar.style.width;
      bar.style.width = '0%';
      setTimeout(() => { bar.style.width = w; bar.style.transition = 'width 0.8s ease'; }, 200);
    });
  });

  // ── Auto-dismiss toast
  setTimeout(() => {
    const tw = document.getElementById('toastWrap');
    if (tw) { tw.style.opacity = '0'; tw.style.transition = 'opacity 0.4s'; setTimeout(() => tw?.remove(), 400); }
  }, 4000);

  // ── Sidebar mobile
  document.addEventListener('click', e => {
    const sidebar = document.getElementById('sidebar');
    if (window.innerWidth < 768 && sidebar.classList.contains('open')) {
      if (!sidebar.contains(e.target) && !e.target.closest('.sidebar-toggle')) sidebar.classList.remove('open');
    }
  });
</script>
</body>
</html>