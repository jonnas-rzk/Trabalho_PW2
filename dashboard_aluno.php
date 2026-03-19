<?php
include "db.php";

if (!isset($_SESSION['aluno_id'])) {
    header("Location: login_aluno.php");
    exit();
}

$aluno_id = (int)$_SESSION['aluno_id'];
$msg = $msg_tipo = '';

// ══ UPLOAD DE FOTO ══
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['foto'])) {
    $file   = $_FILES['foto'];
    $allowed = ['image/jpeg', 'image/png', 'image/webp'];
    $max_size = 2 * 1024 * 1024; // 2MB

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $msg = "Erro ao fazer upload."; $msg_tipo = "erro";
    } elseif (!in_array($file['type'], $allowed)) {
        $msg = "Formato inválido. Usa JPG, PNG ou WebP."; $msg_tipo = "erro";
    } elseif ($file['size'] > $max_size) {
        $msg = "Ficheiro demasiado grande. Máximo 2MB."; $msg_tipo = "erro";
    } else {
        $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . strtolower($ext);
        $dest     = 'uploads/' . $filename;

        if (move_uploaded_file($file['tmp_name'], $dest)) {
            // Apagar foto antiga se não for default
            $old = $conn->prepare("SELECT foto FROM alunos WHERE id=?");
            $old->bind_param("i", $aluno_id);
            $old->execute();
            $old_foto = $old->get_result()->fetch_assoc()['foto'] ?? '';
            if ($old_foto && $old_foto !== 'default.png' && file_exists('uploads/'.$old_foto)) {
                unlink('uploads/'.$old_foto);
            }

            $upd = $conn->prepare("UPDATE alunos SET foto=? WHERE id=?");
            $upd->bind_param("si", $filename, $aluno_id);
            $upd->execute();
            $msg = "Foto atualizada com sucesso!"; $msg_tipo = "sucesso";
        } else {
            $msg = "Falha ao guardar o ficheiro."; $msg_tipo = "erro";
        }
    }
}

// ══ BUSCAR DADOS DO ALUNO ══
$stmt = $conn->prepare("
    SELECT a.*, c.Nome AS nome_curso
    FROM alunos a
    LEFT JOIN cursos c ON a.curso_id = c.ID
    WHERE a.id = ?
");
$stmt->bind_param("i", $aluno_id);
$stmt->execute();
$aluno = $stmt->get_result()->fetch_assoc();

if (!$aluno) {
    session_destroy();
    header("Location: login_aluno.php");
    exit();
}

// ══ BUSCAR DISCIPLINAS DO CURSO ══
$stmt_disc = $conn->prepare("
    SELECT d.ID, d.Nome_disc
    FROM plano_estudos pe
    JOIN disciplinas d ON pe.DISCIPLINA = d.ID
    WHERE pe.CURSOS = ?
    ORDER BY d.Nome_disc ASC
");
$stmt_disc->bind_param("i", $aluno['curso_id']);
$stmt_disc->execute();
$disciplinas = $stmt_disc->get_result()->fetch_all(MYSQLI_ASSOC);

// ══ BUSCAR PAUTAS DO ALUNO ══
$stmt_pautas = $conn->prepare("
    SELECT p.disciplina_id, p.nota, p.epoca, p.data_lancamento,
           d.Nome_disc
    FROM pautas p
    JOIN disciplinas d ON p.disciplina_id = d.ID
    WHERE p.aluno_id = ?
    ORDER BY d.Nome_disc ASC, p.epoca ASC
");
$stmt_pautas->bind_param("i", $aluno_id);
$stmt_pautas->execute();
$pautas_raw = $stmt_pautas->get_result()->fetch_all(MYSQLI_ASSOC);

// Organizar pautas por disciplina_id
$pautas = [];
foreach ($pautas_raw as $p) {
    $pautas[$p['disciplina_id']][$p['epoca']] = $p;
}

// ══ CALCULAR ESTATÍSTICAS ══
$total_disc     = count($disciplinas);
$com_nota       = 0;
$aprovadas      = 0;
$soma_notas     = 0;
$count_notas    = 0;

foreach ($disciplinas as $d) {
    $id = $d['ID'];
    // Melhor nota (normal > recurso > especial)
    $melhor = null;
    foreach (['normal','recurso','especial'] as $ep) {
        if (isset($pautas[$id][$ep]) && $pautas[$id][$ep]['nota'] !== null) {
            $n = (float)$pautas[$id][$ep]['nota'];
            if ($melhor === null || $n > $melhor) $melhor = $n;
        }
    }
    if ($melhor !== null) {
        $com_nota++;
        $soma_notas += $melhor;
        $count_notas++;
        if ($melhor >= 10) $aprovadas++;
    }
}

$media = $count_notas > 0 ? round($soma_notas / $count_notas, 1) : null;

// Foto
$foto_path = (!empty($aluno['foto']) && $aluno['foto'] !== 'default.png' && file_exists('uploads/'.$aluno['foto']))
    ? 'uploads/' . $aluno['foto']
    : null;

$initials = strtoupper(substr($aluno['nome'] ?? 'A', 0, 1) .
    (strpos($aluno['nome'], ' ') !== false ? substr(strrchr($aluno['nome'], ' '), 1, 1) : ''));

// Épocas labels
$epoca_labels = ['normal' => 'Normal', 'recurso' => 'Recurso', 'especial' => 'Especial'];
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Área do Aluno — IPCA</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
/* ── TOKENS ── */
:root {
  --navy:       #0a1628;
  --navy-mid:   #112240;
  --navy-light: #162d4a;
  --gold:       #c9a84c;
  --gold-light: #e8c97a;
  --cream:      #f5f0e8;
  --text:       #e8e4dc;
  --muted:      #8a9ab5;
  --success:    #2ecc71;
  --danger:     #e74c3c;
  --warning:    #f39c12;
  --sidebar-w:  260px;
}

*, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
html { scroll-behavior: smooth; }

body {
  background: var(--navy);
  font-family: 'DM Sans', sans-serif;
  font-weight: 300;
  color: var(--text);
  min-height: 100vh;
}

body::before {
  content:''; position:fixed; inset:0;
  background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E");
  pointer-events:none; z-index:0; opacity:0.35;
}

/* ── SIDEBAR ── */
.sidebar {
  position: fixed; top:0; left:0;
  width: var(--sidebar-w); height:100vh;
  background: var(--navy-mid);
  border-right: 1px solid rgba(201,168,76,0.12);
  display: flex; flex-direction:column;
  z-index: 200; transition: transform 0.3s;
  overflow-y: auto;
}

.sidebar-brand {
  padding: 26px 22px 22px;
  border-bottom: 1px solid rgba(255,255,255,0.06);
  display: flex; align-items:center; gap:12px;
  text-decoration: none;
}

.logo-icon {
  width:38px; height:38px; flex-shrink:0;
  background: linear-gradient(135deg, var(--gold), var(--gold-light));
  border-radius:8px;
  display:flex; align-items:center; justify-content:center;
  font-family: 'Playfair Display', serif;
  font-weight:900; font-size:15px; color:var(--navy);
}

.brand-name {
  font-family: 'Playfair Display', serif;
  font-weight:700; font-size:15px; color:var(--cream); line-height:1.1;
}

.brand-sub {
  font-size:9px; color:var(--muted);
  letter-spacing:2px; text-transform:uppercase;
}

/* Perfil no topo da sidebar */
.sidebar-profile {
  padding: 20px 22px;
  border-bottom: 1px solid rgba(255,255,255,0.06);
  display: flex; align-items:center; gap:14px;
}

.profile-avatar-wrap {
  position: relative; flex-shrink:0;
  cursor: pointer;
}

.profile-avatar {
  width:52px; height:52px; border-radius:50%;
  border: 2px solid rgba(201,168,76,0.4);
  object-fit:cover;
  display:block;
}

.profile-avatar-initials {
  width:52px; height:52px; border-radius:50%;
  border: 2px solid rgba(201,168,76,0.4);
  background: linear-gradient(135deg, var(--navy-light), var(--navy-mid));
  display:flex; align-items:center; justify-content:center;
  font-family: 'Playfair Display', serif;
  font-weight:700; font-size:18px; color:var(--gold);
}

.profile-avatar-overlay {
  position:absolute; inset:0; border-radius:50%;
  background: rgba(0,0,0,0.55);
  display:flex; align-items:center; justify-content:center;
  opacity:0; transition: opacity 0.2s;
  color: var(--gold); font-size:16px;
}

.profile-avatar-wrap:hover .profile-avatar-overlay { opacity:1; }

.profile-info { overflow:hidden; }

.profile-nome {
  font-size:14px; font-weight:500; color:var(--cream);
  white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
}

.profile-num {
  font-size:11px; color:var(--gold);
  font-family:monospace; margin-top:2px;
}

.profile-curso {
  font-size:11px; color:var(--muted); margin-top:2px;
  white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
}

/* Nav links */
.sidebar-section {
  padding: 18px 18px 6px;
  font-size:10px; font-weight:500; color:var(--gold);
  letter-spacing:2px; text-transform:uppercase;
}

.sidebar-link {
  display:flex; align-items:center; gap:10px;
  padding:10px 20px; margin:2px 8px;
  border-radius:8px; font-size:13px; font-weight:400;
  color:var(--muted); text-decoration:none;
  transition:all 0.2s;
}

.sidebar-link i { font-size:15px; width:18px; text-align:center; flex-shrink:0; }
.sidebar-link:hover { background:rgba(255,255,255,0.05); color:var(--cream); }
.sidebar-link.active {
  background:rgba(201,168,76,0.1); color:var(--gold);
  border-left:2px solid var(--gold); margin-left:6px; padding-left:18px;
}

.sidebar-bottom {
  margin-top:auto; padding:16px 8px;
  border-top: 1px solid rgba(255,255,255,0.06);
}

/* ── MAIN ── */
.main-wrap {
  margin-left: var(--sidebar-w);
  min-height:100vh;
  display:flex; flex-direction:column;
  position:relative; z-index:1;
}

/* ── TOPBAR ── */
.topbar {
  height:64px;
  background:rgba(10,22,40,0.85);
  backdrop-filter:blur(12px);
  border-bottom:1px solid rgba(255,255,255,0.06);
  padding:0 32px;
  display:flex; align-items:center; justify-content:space-between;
  position:sticky; top:0; z-index:100;
}

.topbar-title {
  font-family:'Playfair Display',serif;
  font-size:20px; font-weight:700; color:var(--cream);
}

.topbar-breadcrumb { font-size:12px; color:var(--muted); margin-top:2px; }
.topbar-breadcrumb span { color:var(--gold); }

.topbar-actions { display:flex; align-items:center; gap:10px; }

.session-pill {
  display:flex; align-items:center; gap:8px;
  background:rgba(201,168,76,0.08);
  border:1px solid rgba(201,168,76,0.2);
  border-radius:20px; padding:5px 14px 5px 8px;
}

.session-dot {
  width:8px; height:8px; background:var(--success);
  border-radius:50%; animation:pulse-dot 2s ease infinite;
}

@keyframes pulse-dot { 0%,100%{opacity:1} 50%{opacity:0.4} }

/* ── PAGE CONTENT ── */
.page-content { padding:32px; flex:1; }

/* ── STAT CARDS ── */
.stat-card {
  background:rgba(255,255,255,0.03);
  border:1px solid rgba(255,255,255,0.07);
  border-radius:14px; padding:22px 24px;
  position:relative; overflow:hidden; transition:all 0.3s;
}

.stat-card:hover {
  border-color:rgba(201,168,76,0.2);
  transform:translateY(-2px);
}

.stat-card::before {
  content:''; position:absolute; top:0; left:0;
  width:100%; height:3px;
  background:linear-gradient(90deg, var(--card-color, var(--gold)), transparent);
}

.stat-icon {
  width:42px; height:42px; border-radius:10px;
  background:rgba(var(--card-rgb,201,168,76), 0.12);
  display:flex; align-items:center; justify-content:center;
  font-size:18px; color:var(--card-color, var(--gold));
  margin-bottom:14px;
}

.stat-value {
  font-family:'Playfair Display',serif;
  font-size:30px; font-weight:700; color:var(--cream); line-height:1;
}

.stat-label {
  font-size:12px; color:var(--muted);
  margin-top:4px; text-transform:uppercase; letter-spacing:0.5px;
}

/* ── SECTION CARD ── */
.section-card {
  background:rgba(255,255,255,0.03);
  border:1px solid rgba(255,255,255,0.07);
  border-radius:16px; overflow:hidden;
  margin-bottom:24px;
}

.section-card-header {
  padding:18px 24px;
  border-bottom:1px solid rgba(255,255,255,0.06);
  display:flex; align-items:center; gap:10px;
}

.section-card-header i { color:var(--gold); font-size:16px; }

.section-card-title {
  font-family:'Playfair Display',serif;
  font-size:17px; font-weight:700; color:var(--cream);
}

.section-card-sub { font-size:12px; color:var(--muted); margin-top:1px; }
.section-card-body { padding:24px; }

/* ── PROFILE SECTION ── */
.profile-big-avatar {
  width:100px; height:100px; border-radius:50%;
  border:3px solid rgba(201,168,76,0.4);
  object-fit:cover;
}

.profile-big-initials {
  width:100px; height:100px; border-radius:50%;
  border:3px solid rgba(201,168,76,0.4);
  background:linear-gradient(135deg, var(--navy-light), var(--navy-mid));
  display:flex; align-items:center; justify-content:center;
  font-family:'Playfair Display',serif;
  font-size:34px; font-weight:700; color:var(--gold);
}

.field-row {
  display:flex; align-items:flex-start;
  padding:12px 0; border-bottom:1px solid rgba(255,255,255,0.05);
}
.field-row:last-child { border-bottom:none; }
.field-key {
  width:150px; flex-shrink:0;
  font-size:11px; font-weight:500; color:var(--muted);
  letter-spacing:1px; text-transform:uppercase; padding-top:2px;
}
.field-val { flex:1; font-size:14px; color:var(--cream); word-break:break-all; }
.field-val.mono { font-family:monospace; font-size:13px; }
.field-val.gold { color:var(--gold); font-weight:600; }

/* ── FOTO UPLOAD ── */
.upload-zone {
  border:2px dashed rgba(201,168,76,0.25);
  border-radius:12px; padding:28px 20px;
  text-align:center; transition:all 0.3s; cursor:pointer;
  position:relative;
}

.upload-zone:hover, .upload-zone.drag { border-color:var(--gold); background:rgba(201,168,76,0.05); }

.upload-zone input[type="file"] {
  position:absolute; inset:0; opacity:0; cursor:pointer; width:100%; height:100%;
}

.upload-icon {
  width:48px; height:48px; border-radius:12px;
  background:rgba(201,168,76,0.1);
  display:flex; align-items:center; justify-content:center;
  font-size:22px; color:var(--gold); margin:0 auto 12px;
}

/* ── PAUTAS TABLE ── */
.table-pautas { color:var(--text); margin:0; }

.table-pautas > :not(caption) > * > * {
  background:transparent !important;
  border-color:rgba(255,255,255,0.05);
  padding:14px 16px;
}

.table-pautas thead th {
  background:rgba(201,168,76,0.06) !important;
  color:var(--gold); text-transform:uppercase;
  font-size:10px; letter-spacing:1.5px; font-weight:600;
  border-bottom:1px solid rgba(201,168,76,0.2) !important;
  white-space:nowrap;
}

.table-pautas tbody tr { transition:background 0.2s; }
.table-pautas tbody tr:hover td { background:rgba(255,255,255,0.03) !important; }

/* Nota visual */
.nota-badge {
  display:inline-flex; align-items:center; justify-content:center;
  width:52px; height:52px; border-radius:50%;
  font-family:'Playfair Display',serif;
  font-size:17px; font-weight:700;
  border:2px solid;
}

.nota-aprovado  { background:rgba(46,204,113,0.1);  color:#2ecc71; border-color:rgba(46,204,113,0.3); }
.nota-reprovado { background:rgba(231,76,60,0.1);   color:#e74c3c; border-color:rgba(231,76,60,0.3); }
.nota-sem       { background:rgba(255,255,255,0.04); color:var(--muted); border-color:rgba(255,255,255,0.1); }

/* Barra de progresso da nota */
.nota-bar-wrap { width:100%; background:rgba(255,255,255,0.07); border-radius:4px; height:6px; margin-top:6px; overflow:hidden; }
.nota-bar      { height:6px; border-radius:4px; transition:width 0.8s ease; }
.nota-bar.aprovado  { background:linear-gradient(90deg, #2ecc71, #27ae60); }
.nota-bar.reprovado { background:linear-gradient(90deg, #e74c3c, #c0392b); }

/* Época tags */
.epoca-tag {
  display:inline-block; padding:3px 9px; border-radius:20px;
  font-size:10px; font-weight:500; letter-spacing:0.5px;
  text-transform:uppercase;
}
.epoca-normal   { background:rgba(52,152,219,0.1);  color:#3498db; }
.epoca-recurso  { background:rgba(243,156,18,0.1);  color:#f39c12; }
.epoca-especial { background:rgba(155,89,182,0.1);  color:#9b59b6; }

/* Disciplina sem notas */
.no-nota-row { opacity:0.5; }

/* ── TOAST ── */
.toast-wrap {
  position:fixed; top:76px; right:24px; z-index:999;
  display:flex; flex-direction:column; gap:10px;
}

.toast-custom {
  display:flex; align-items:flex-start; gap:12px;
  padding:16px 18px; border-radius:12px;
  min-width:300px; max-width:420px;
  box-shadow:0 8px 32px rgba(0,0,0,0.4);
  animation:slideIn 0.4s ease;
  border:1px solid rgba(255,255,255,0.08);
}

@keyframes slideIn {
  from{opacity:0;transform:translateX(40px);}
  to{opacity:1;transform:translateX(0);}
}

.toast-success { background:rgba(17,34,64,0.97); border-left:3px solid #2ecc71; }
.toast-error   { background:rgba(17,34,64,0.97); border-left:3px solid #e74c3c; }
.toast-custom > i { font-size:18px; margin-top:2px; }
.toast-success > i { color:#2ecc71; }
.toast-error   > i { color:#e74c3c; }
.toast-custom > div { flex:1; font-size:13px; color:var(--cream); line-height:1.5; }
.toast-custom > button {
  background:none; border:none; color:var(--muted);
  cursor:pointer; font-size:16px; line-height:1; transition:color 0.2s;
}
.toast-custom > button:hover { color:var(--cream); }

/* ── MOBILE TOGGLE ── */
.sidebar-toggle {
  display:none; background:none; border:none;
  color:var(--muted); font-size:22px; cursor:pointer;
}

/* ── RESPONSIVE ── */
@media (max-width:768px) {
  .sidebar { transform:translateX(-100%); }
  .sidebar.open { transform:translateX(0); }
  .main-wrap { margin-left:0; }
  .sidebar-toggle { display:block; }
  .page-content { padding:20px 16px; }
  .topbar { padding:0 16px; }
}

/* ── MEDIA query para animação de entrada ── */
.fade-in {
  animation: fadeIn 0.5s ease both;
}
@keyframes fadeIn {
  from { opacity:0; transform:translateY(12px); }
  to   { opacity:1; transform:translateY(0); }
}
</style>
</head>
<body>

<!-- ════════ SIDEBAR ════════ -->
<aside class="sidebar" id="sidebar">

  <a href="index.php" class="sidebar-brand text-decoration-none">
    <div class="logo-icon">IP</div>
    <div>
      <div class="brand-name">IPCA</div>
      <div class="brand-sub">Área do Aluno</div>
    </div>
  </a>

  <!-- Mini perfil na sidebar -->
  <div class="sidebar-profile">
    <div class="profile-avatar-wrap" onclick="document.getElementById('sectionPerfil').scrollIntoView({behavior:'smooth'})">
      <?php if ($foto_path): ?>
        <img src="<?php echo $foto_path; ?>" class="profile-avatar" alt="">
      <?php else: ?>
        <div class="profile-avatar-initials"><?php echo $initials; ?></div>
      <?php endif; ?>
      <div class="profile-avatar-overlay"><i class="bi bi-camera"></i></div>
    </div>
    <div class="profile-info">
      <div class="profile-nome"><?php echo htmlspecialchars($aluno['nome']); ?></div>
      <div class="profile-num">Nº <?php echo $aluno['numero_aluno']; ?></div>
      <div class="profile-curso"><?php echo htmlspecialchars($aluno['nome_curso'] ?? '—'); ?></div>
    </div>
  </div>

  <div class="sidebar-section">Menu</div>

  <a href="#sectionPerfil" class="sidebar-link active" onclick="setActive(this)">
    <i class="bi bi-person-circle"></i> O Meu Perfil
  </a>
  <a href="#sectionPautas" class="sidebar-link" onclick="setActive(this)">
    <i class="bi bi-journal-check"></i> Pautas de Avaliação
    <?php if ($com_nota > 0): ?>
    <span style="margin-left:auto;background:var(--gold);color:var(--navy);font-size:10px;font-weight:700;padding:2px 7px;border-radius:10px;">
      <?php echo $com_nota; ?>
    </span>
    <?php endif; ?>
  </a>

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
        <div class="topbar-title">Olá, <?php echo htmlspecialchars(explode(' ', $aluno['nome'])[0]); ?> 👋</div>
        <div class="topbar-breadcrumb">IPCA › <span>Área do Aluno</span></div>
      </div>
    </div>
    <div class="topbar-actions">
      <div class="session-pill">
        <div class="session-dot"></div>
        <span style="font-size:12px;color:var(--gold);font-weight:500;">
          <?php echo htmlspecialchars($aluno['nome_curso'] ?? 'Sem curso'); ?>
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

    <!-- ── STAT CARDS ── -->
    <div class="row g-3 mb-4 fade-in">

      <div class="col-6 col-md-3">
        <div class="stat-card" style="--card-color:#c9a84c;--card-rgb:201,168,76">
          <div class="stat-icon"><i class="bi bi-journal-text"></i></div>
          <div class="stat-value"><?php echo $total_disc; ?></div>
          <div class="stat-label">Disciplinas</div>
        </div>
      </div>

      <div class="col-6 col-md-3">
        <div class="stat-card" style="--card-color:#3498db;--card-rgb:52,152,219">
          <div class="stat-icon"><i class="bi bi-pencil-square"></i></div>
          <div class="stat-value"><?php echo $com_nota; ?>/<?php echo $total_disc; ?></div>
          <div class="stat-label">Com Nota</div>
        </div>
      </div>

      <div class="col-6 col-md-3">
        <div class="stat-card" style="--card-color:#2ecc71;--card-rgb:46,204,113">
          <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
          <div class="stat-value"><?php echo $aprovadas; ?></div>
          <div class="stat-label">Aprovadas</div>
        </div>
      </div>

      <div class="col-6 col-md-3">
        <div class="stat-card" style="--card-color:<?php echo $media !== null ? ($media >= 10 ? '#2ecc71' : '#e74c3c') : '#8a9ab5'; ?>">
          <div class="stat-icon"><i class="bi bi-bar-chart"></i></div>
          <div class="stat-value"><?php echo $media !== null ? $media : '—'; ?></div>
          <div class="stat-label">Média Geral</div>
        </div>
      </div>

    </div>


    <!-- ══════ SECÇÃO: PERFIL ══════ -->
    <div id="sectionPerfil" class="row g-4 mb-4 fade-in">

      <!-- Dados pessoais -->
      <div class="col-lg-7">
        <div class="section-card">
          <div class="section-card-header">
            <i class="bi bi-person-vcard"></i>
            <div>
              <div class="section-card-title">Dados Pessoais</div>
              <div class="section-card-sub">As tuas informações académicas</div>
            </div>
          </div>
          <div class="section-card-body">

            <div class="d-flex align-items-center gap-4 mb-4 pb-4" style="border-bottom:1px solid rgba(255,255,255,0.06)">
              <?php if ($foto_path): ?>
                <img src="<?php echo $foto_path; ?>" class="profile-big-avatar" alt="">
              <?php else: ?>
                <div class="profile-big-initials"><?php echo $initials; ?></div>
              <?php endif; ?>
              <div>
                <div style="font-family:'Playfair Display',serif;font-size:24px;font-weight:700;color:var(--cream);">
                  <?php echo htmlspecialchars($aluno['nome']); ?>
                </div>
                <div style="font-size:13px;color:var(--gold);margin-top:4px;font-family:monospace;">
                  <?php echo htmlspecialchars($aluno['email']); ?>
                </div>
              </div>
            </div>

            <div class="field-row">
              <div class="field-key">Nº de Aluno</div>
              <div class="field-val mono gold"><?php echo $aluno['numero_aluno']; ?></div>
            </div>
            <div class="field-row">
              <div class="field-key">Email</div>
              <div class="field-val mono"><?php echo htmlspecialchars($aluno['email']); ?></div>
            </div>
            <div class="field-row">
              <div class="field-key">Curso</div>
              <div class="field-val gold"><?php echo htmlspecialchars($aluno['nome_curso'] ?? '—'); ?></div>
            </div>
            <div class="field-row">
              <div class="field-key">Disciplinas</div>
              <div class="field-val"><?php echo $total_disc; ?> no plano de estudos</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Upload de foto -->
      <div class="col-lg-5">
        <div class="section-card h-100">
          <div class="section-card-header">
            <i class="bi bi-camera"></i>
            <div>
              <div class="section-card-title">Foto de Perfil</div>
              <div class="section-card-sub">JPG, PNG ou WebP · máx. 2MB</div>
            </div>
          </div>
          <div class="section-card-body">

            <!-- Preview atual -->
            <div class="text-center mb-4">
              <?php if ($foto_path): ?>
                <img src="<?php echo $foto_path; ?>?t=<?php echo time(); ?>"
                     id="fotoPreview"
                     style="width:100px;height:100px;border-radius:50%;object-fit:cover;border:3px solid rgba(201,168,76,0.4);">
              <?php else: ?>
                <div id="fotoPreview" class="profile-big-initials mx-auto"
                     style="width:100px;height:100px;font-size:34px;">
                  <?php echo $initials; ?>
                </div>
              <?php endif; ?>
            </div>

            <!-- Zona de upload -->
            <form method="POST" enctype="multipart/form-data" id="fotoForm">
              <div class="upload-zone" id="uploadZone">
                <input type="file" name="foto" id="fotoInput" accept="image/jpeg,image/png,image/webp"
                       onchange="previewFoto(this)">
                <div class="upload-icon"><i class="bi bi-cloud-arrow-up"></i></div>
                <div style="font-size:13px;color:var(--muted);" id="uploadLabel">
                  Clica ou arrasta a imagem aqui
                </div>
                <div style="font-size:11px;color:var(--muted);margin-top:4px;">JPG · PNG · WebP</div>
              </div>

              <button type="submit" id="btnUpload"
                      style="display:none;width:100%;margin-top:12px;padding:11px;
                             background:linear-gradient(135deg,var(--gold),var(--gold-light));
                             color:var(--navy);font-weight:600;font-size:13px;
                             border:none;border-radius:10px;cursor:pointer;
                             font-family:'DM Sans',sans-serif;transition:all 0.2s;">
                <i class="bi bi-floppy me-1"></i> Guardar Foto
              </button>
            </form>

          </div>
        </div>
      </div>

    </div><!-- /perfil row -->


    <!-- ══════ SECÇÃO: PAUTAS ══════ -->
    <div id="sectionPautas" class="fade-in">
      <div class="section-card">
        <div class="section-card-header">
          <i class="bi bi-journal-check"></i>
          <div>
            <div class="section-card-title">Pautas de Avaliação</div>
            <div class="section-card-sub">
              Curso: <?php echo htmlspecialchars($aluno['nome_curso'] ?? '—'); ?>
            </div>
          </div>

          <!-- Filtro de época -->
          <div class="ms-auto">
            <select id="filtroEpoca" onchange="filtrarEpoca(this.value)"
                    style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);
                           border-radius:8px;color:var(--text);padding:7px 12px;font-size:12px;
                           font-family:'DM Sans',sans-serif;">
              <option value="todas">Todas as épocas</option>
              <option value="normal">Normal</option>
              <option value="recurso">Recurso</option>
              <option value="especial">Especial</option>
            </select>
          </div>
        </div>

        <?php if (empty($disciplinas)): ?>
        <div class="section-card-body text-center py-5" style="color:var(--muted);">
          <i class="bi bi-journal" style="font-size:40px;display:block;margin-bottom:12px;opacity:0.3;"></i>
          Não estás inscrito em nenhum curso.
        </div>
        <?php else: ?>

        <div class="table-responsive">
          <table class="table-pautas table" id="tablePautas">
            <thead>
              <tr>
                <th style="width:36%">Disciplina</th>
                <th class="text-center">Época</th>
                <th class="text-center">Nota</th>
                <th style="width:30%">Progresso</th>
                <th class="text-center">Estado</th>
                <th>Lançamento</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($disciplinas as $disc):
                $disc_id = $disc['ID'];
                $epocas_com_nota = $pautas[$disc_id] ?? [];

                // Descobrir melhor nota
                $melhor_nota = null;
                $melhor_epoca = null;
                foreach (['normal','recurso','especial'] as $ep) {
                  if (isset($epocas_com_nota[$ep]) && $epocas_com_nota[$ep]['nota'] !== null) {
                    $n = (float)$epocas_com_nota[$ep]['nota'];
                    if ($melhor_nota === null || $n > $melhor_nota) {
                      $melhor_nota  = $n;
                      $melhor_epoca = $ep;
                    }
                  }
                }

                if (empty($epocas_com_nota)):
                  // Sem notas para esta disciplina
              ?>
              <tr class="no-nota-row" data-epoca="sem">
                <td>
                  <div style="font-weight:500;color:var(--text);"><?php echo htmlspecialchars($disc['Nome_disc']); ?></div>
                </td>
                <td class="text-center">—</td>
                <td class="text-center">
                  <div class="nota-badge nota-sem">—</div>
                </td>
                <td>
                  <div class="nota-bar-wrap"><div class="nota-bar" style="width:0%"></div></div>
                  <div style="font-size:11px;color:var(--muted);margin-top:4px;">Aguarda lançamento</div>
                </td>
                <td class="text-center">
                  <span style="font-size:11px;color:var(--muted);">Sem nota</span>
                </td>
                <td style="font-size:12px;color:var(--muted);">—</td>
              </tr>
              <?php else:
                // Mostrar uma linha por época lançada
                foreach ($epocas_com_nota as $ep => $pauta):
                  $nota = $pauta['nota'] !== null ? (float)$pauta['nota'] : null;
                  $is_aprovado = $nota !== null && $nota >= 10;
                  $pct = $nota !== null ? round(($nota / 20) * 100) : 0;
                  $is_melhor = ($ep === $melhor_epoca);
              ?>
              <tr data-epoca="<?php echo $ep; ?>" <?php echo !$is_melhor ? 'style="opacity:0.65"' : ''; ?>>
                <td>
                  <div style="font-weight:500;color:<?php echo $is_melhor ? 'var(--cream)' : 'var(--text)'; ?>;">
                    <?php echo htmlspecialchars($disc['Nome_disc']); ?>
                    <?php if ($is_melhor && count($epocas_com_nota) > 1): ?>
                      <span style="font-size:10px;color:var(--gold);margin-left:6px;">melhor</span>
                    <?php endif; ?>
                  </div>
                </td>
                <td class="text-center">
                  <span class="epoca-tag epoca-<?php echo $ep; ?>"><?php echo $epoca_labels[$ep]; ?></span>
                </td>
                <td class="text-center">
                  <?php if ($nota !== null): ?>
                  <div class="nota-badge <?php echo $is_aprovado ? 'nota-aprovado' : 'nota-reprovado'; ?>">
                    <?php echo number_format($nota, 1); ?>
                  </div>
                  <?php else: ?>
                  <div class="nota-badge nota-sem">—</div>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($nota !== null): ?>
                  <div class="nota-bar-wrap">
                    <div class="nota-bar <?php echo $is_aprovado ? 'aprovado' : 'reprovado'; ?>"
                         style="width:<?php echo $pct; ?>%"></div>
                  </div>
                  <div style="font-size:11px;color:var(--muted);margin-top:4px;">
                    <?php echo $nota; ?> / 20 valores
                  </div>
                  <?php else: ?>
                  <div style="font-size:11px;color:var(--muted);">Aguarda lançamento</div>
                  <?php endif; ?>
                </td>
                <td class="text-center">
                  <?php if ($nota !== null): ?>
                  <span style="font-size:12px;font-weight:600;color:<?php echo $is_aprovado ? '#2ecc71' : '#e74c3c'; ?>">
                    <i class="bi bi-<?php echo $is_aprovado ? 'check-circle-fill' : 'x-circle-fill'; ?> me-1"></i>
                    <?php echo $is_aprovado ? 'Aprovado' : 'Reprovado'; ?>
                  </span>
                  <?php else: ?>
                  <span style="font-size:11px;color:var(--muted);">—</span>
                  <?php endif; ?>
                </td>
                <td style="font-size:12px;color:var(--muted);white-space:nowrap;">
                  <?php echo !empty($pauta['data_lancamento'])
                    ? date('d/m/Y', strtotime($pauta['data_lancamento']))
                    : '—'; ?>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php endif; ?>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <!-- Legenda -->
        <div class="section-card-body" style="padding-top:16px;padding-bottom:16px;border-top:1px solid rgba(255,255,255,0.05);">
          <div class="d-flex flex-wrap gap-3 align-items-center" style="font-size:12px;color:var(--muted);">
            <span><span class="epoca-tag epoca-normal">Normal</span></span>
            <span><span class="epoca-tag epoca-recurso">Recurso</span></span>
            <span><span class="epoca-tag epoca-especial">Especial</span></span>
            <span style="margin-left:auto;">
              <i class="bi bi-info-circle me-1"></i>
              Nota mínima de aprovação: <strong style="color:var(--cream);">10 valores</strong>
            </span>
          </div>
        </div>

        <?php endif; ?>
      </div><!-- /section-card pautas -->
    </div><!-- /sectionPautas -->

  </div><!-- /page-content -->
</div><!-- /main-wrap -->


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // ── Preview foto antes de submeter
  function previewFoto(input) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];
    const label = document.getElementById('uploadLabel');
    label.textContent = file.name;
    document.getElementById('btnUpload').style.display = 'block';

    const reader = new FileReader();
    reader.onload = e => {
      const prev = document.getElementById('fotoPreview');
      if (prev.tagName === 'IMG') {
        prev.src = e.target.result;
      } else {
        // Era div com iniciais, substituir por img
        const img = document.createElement('img');
        img.id = 'fotoPreview';
        img.src = e.target.result;
        img.style.cssText = 'width:100px;height:100px;border-radius:50%;object-fit:cover;border:3px solid rgba(201,168,76,0.4);';
        prev.replaceWith(img);
      }
    };
    reader.readAsDataURL(file);
  }

  // ── Drag & drop na zona de upload
  const zone = document.getElementById('uploadZone');
  if (zone) {
    zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('drag'); });
    zone.addEventListener('dragleave', () => zone.classList.remove('drag'));
    zone.addEventListener('drop', e => {
      e.preventDefault(); zone.classList.remove('drag');
      const files = e.dataTransfer.files;
      if (files.length) {
        const input = document.getElementById('fotoInput');
        const dt = new DataTransfer();
        dt.items.add(files[0]);
        input.files = dt.files;
        previewFoto(input);
      }
    });
  }

  // ── Filtro de época
  function filtrarEpoca(valor) {
    document.querySelectorAll('#tablePautas tbody tr').forEach(row => {
      if (valor === 'todas') {
        row.style.display = '';
      } else {
        row.style.display = row.dataset.epoca === valor ? '' : 'none';
      }
    });
  }

  // ── Activar link sidebar ao fazer scroll
  function setActive(el) {
    document.querySelectorAll('.sidebar-link').forEach(l => l.classList.remove('active'));
    el.classList.add('active');
  }

  // ── Auto-dismiss toast ao fim de 4s
  setTimeout(() => {
    const tw = document.getElementById('toastWrap');
    if (tw) { tw.style.opacity='0'; tw.style.transition='opacity 0.4s'; setTimeout(()=>tw.remove(),400); }
  }, 4000);

  // ── Fechar sidebar mobile ao clicar fora
  document.addEventListener('click', e => {
    const sidebar = document.getElementById('sidebar');
    if (window.innerWidth < 768 && sidebar.classList.contains('open')) {
      if (!sidebar.contains(e.target) && !e.target.closest('.sidebar-toggle')) {
        sidebar.classList.remove('open');
      }
    }
  });

  // ── Animar barras de progresso após load
  window.addEventListener('load', () => {
    document.querySelectorAll('.nota-bar').forEach(bar => {
      const w = bar.style.width;
      bar.style.width = '0%';
      setTimeout(() => bar.style.width = w, 200);
    });
  });
</script>
</body>
</html>