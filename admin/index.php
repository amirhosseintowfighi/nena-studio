<?php
require_once __DIR__ . '/../includes/db.php';
if (session_status()===PHP_SESSION_NONE) session_start();
if (empty($_SESSION['admin_user'])) { header('Location: login.php'); exit; }

// Handle actions
$msg=''; $err='';
$action = $_POST['action'] ?? $_GET['action'] ?? '';

function redirect_msg(string $m, bool $isErr=false): never {
  $k=$isErr?'err':'msg';
  header('Location: index.php?'.$k.'='.urlencode($m).'#'.($_POST['tab'] ?? 'enquiries'));
  exit;
}

// Logout
if (($_GET['action'] ?? '')==='logout') { session_destroy(); header('Location: login.php'); exit; }

// Verify CSRF for POST
if ($_SERVER['REQUEST_METHOD']==='POST') {
  if (!verify_csrf($_POST['csrf_token'] ?? null)) { $err='Invalid session — please refresh.'; }
  else {
    $act = $_POST['action'] ?? '';
    // Enquiry: update status/notes/delete
    if ($act==='enquiry_update' && !$err) {
      $id=(int)($_POST['id'] ?? 0);
      $status=trim($_POST['status'] ?? 'New');
      $notes=trim($_POST['admin_notes'] ?? '');
      $allowed=['New','In Discussion','In Progress','Completed','Archived'];
      if(!in_array($status,$allowed,true)) $status='New';
      $pdo->prepare("UPDATE enquiries SET status=?, admin_notes=? WHERE id=?")->execute([$status,$notes,$id]);
      redirect_msg('Enquiry updated.');
    }
    if ($act==='enquiry_delete' && !$err) {
      $id=(int)($_POST['id'] ?? 0);
      // optionally unlink files — keep for now
      $pdo->prepare("DELETE FROM enquiries WHERE id=?")->execute([$id]);
      redirect_msg('Enquiry deleted.');
    }
    // Portfolio create/update
    if (($act==='portfolio_save') && !$err) {
      $id = (int)($_POST['portfolio_id'] ?? 0);
      $title=trim($_POST['title'] ?? '');
      $slug=trim($_POST['slug'] ?? '');
      $category=trim($_POST['category'] ?? '');
      $summary=trim($_POST['summary'] ?? '');
      $story=$_POST['story'] ?? '';
      $alt_text=trim($_POST['alt_text'] ?? '');
      $seo_title=trim($_POST['seo_title'] ?? '');
      $seo_description=trim($_POST['seo_description'] ?? '');
      $status=trim($_POST['status'] ?? 'published');
      $sort_order=(int)($_POST['sort_order'] ?? 0);
      if ($title==='') $err='Title is required.';
      elseif ($slug==='') $slug=slugify($title);
      else $slug=slugify($slug);
      $allowedCat=['Weddings & Special Occasions','Made for You','Alterations & Reimagining'];
      if(!in_array($category,$allowedCat,true)) $category=$allowedCat[0];
      if(!in_array($status,['published','draft'],true)) $status='published';
      if(!$err) {
        // Handle cover upload
        $cover_image=trim($_POST['existing_cover'] ?? '');
        if (!empty($_FILES['cover_image']['name']) && ($_FILES['cover_image']['error'] ?? 1)===UPLOAD_ERR_OK) {
          $tmp=$_FILES['cover_image']['tmp_name'];
          $ext=strtolower(pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION));
          if(!in_array($ext,['jpg','jpeg','png','webp'],true)) $ext='jpg';
          $name='portfolio/'.bin2hex(random_bytes(6)).'.'.$ext;
          $dest=__DIR__.'/../uploads/'.$name;
          @mkdir(dirname($dest),0755,true);
          if(move_uploaded_file($tmp,$dest)) $cover_image='uploads/'.$name;
        }
        // Additional images (multiple)
        $existingImages = $_POST['existing_images'] ?? '[]';
        $imgs = json_decode($existingImages, true);
        if(!is_array($imgs)) $imgs=[];
        if (!empty($_FILES['images']['name'][0])) {
          for($i=0;$i<count($_FILES['images']['name']);$i++){
            if(($_FILES['images']['error'][$i] ?? 1)!==UPLOAD_ERR_OK) continue;
            $tmp=$_FILES['images']['tmp_name'][$i];
            $ext=strtolower(pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION));
            if(!in_array($ext,['jpg','jpeg','png','webp'],true)) continue;
            $name='portfolio/'.bin2hex(random_bytes(6)).'.'.$ext;
            $dest=__DIR__.'/../uploads/'.$name;
            @mkdir(dirname($dest),0755,true);
            if(move_uploaded_file($tmp,$dest)) $imgs[]='uploads/'.$name;
          }
        }
        // Video upload
        $video=trim($_POST['existing_video'] ?? '');
        if (!empty($_FILES['video']['name']) && ($_FILES['video']['error'] ?? 1)===UPLOAD_ERR_OK) {
          $tmp=$_FILES['video']['tmp_name'];
          $ext=strtolower(pathinfo($_FILES['video']['name'], PATHINFO_EXTENSION));
          if(in_array($ext,['mp4','webm'],true)){
            $name='portfolio/'.bin2hex(random_bytes(6)).'.'.$ext;
            $dest=__DIR__.'/../uploads/'.$name;
            @mkdir(dirname($dest),0755,true);
            if(move_uploaded_file($tmp,$dest)) $video='uploads/'.$name;
          }
        }
        $now=date('Y-m-d H:i:s');
        if($id>0){
          $pdo->prepare("UPDATE portfolio SET title=?,slug=?,category=?,summary=?,story=?,cover_image=?,images=?,video=?,alt_text=?,seo_title=?,seo_description=?,status=?,sort_order=?,updated_at=? WHERE id=?")
              ->execute([$title,$slug,$category,$summary,$story,$cover_image,json_encode($imgs), $video,$alt_text,$seo_title,$seo_description,$status,$sort_order,$now,$id]);
          redirect_msg('Story updated.');
        } else {
          $pdo->prepare("INSERT INTO portfolio(title,slug,category,summary,story,cover_image,images,video,alt_text,seo_title,seo_description,status,sort_order,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
              ->execute([$title,$slug,$category,$summary,$story,$cover_image,json_encode($imgs),$video,$alt_text,$seo_title,$seo_description,$status,$sort_order,$now,$now]);
          redirect_msg('Story created.');
        }
      }
    }
    if ($act==='portfolio_delete' && !$err) {
      $id=(int)($_POST['id'] ?? 0);
      $pdo->prepare("DELETE FROM portfolio WHERE id=?")->execute([$id]);
      redirect_msg('Story deleted.');
    }
    if ($act==='settings_save' && !$err) {
      $fields=['site_title','meta_title','meta_description','meta_keywords','og_image','site_url','contact_email','instagram_url','robots'];
      foreach($fields as $f){ if(isset($_POST[$f])) set_setting($f, trim((string)$_POST[$f])); }
      // password change
      $newUser=trim($_POST['admin_username'] ?? '');
      $newPass=trim($_POST['admin_password'] ?? '');
      if($newUser!=='') set_setting('admin_username',$newUser);
      if($newPass!=='') set_setting('admin_password_hash', password_hash($newPass, PASSWORD_DEFAULT));
      redirect_msg('Settings saved.');
    }
  }
}
if (isset($_GET['msg'])) $msg=(string)$_GET['msg'];
if (isset($_GET['err'])) $err=(string)$_GET['err'];

// Data
$enquiries=[]; $portfolio=[];
try { $enquiries=$pdo->query("SELECT * FROM enquiries ORDER BY created_at DESC")->fetchAll(); } catch(Throwable $e){}
try { $portfolio=$pdo->query("SELECT * FROM portfolio ORDER BY sort_order ASC, created_at DESC")->fetchAll(); } catch(Throwable $e){}
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editItem = null;
if($editId){ foreach($portfolio as $p){ if((int)$p['id']===$editId){ $editItem=$p; break; } } }

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES,'UTF-8'); }
?>
<!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin — Nena Studio</title>
<meta name="robots" content="noindex, nofollow">
<link rel="stylesheet" href="../assets/css/style.css">
<style>
  .admin-wrap{max-width:1180px; margin:0 auto; padding:18px 20px}
  .admin-nav{display:flex; gap:10px; align-items:center; justify-content:space-between; flex-wrap:wrap; margin-bottom:14px}
  .tabs{display:flex; gap:8px; flex-wrap:wrap}
  .tab-btn{padding:9px 14px; border-radius:999px; border:1px solid rgba(0,109,119,0.12); background:rgba(255,255,255,0.78); font-weight:700; font-size:13px; cursor:pointer}
  .tab-btn.active{background:var(--teal-dark); color:#fff; border-color:var(--teal-dark)}
  .panel{display:none}
  .panel.active{display:block}
  table{width:100%; border-collapse:collapse; font-size:13px}
  th,td{padding:10px 8px; border-bottom:1px solid rgba(0,109,119,0.08); text-align:left; vertical-align:top}
  th{font-size:11px; letter-spacing:0.12em; text-transform:uppercase; color:var(--teal-dark); background:rgba(255,255,255,0.66)}
  .badge-status{display:inline-block; padding:4px 8px; border-radius:999px; font-size:11px; font-weight:800; border:1px solid rgba(0,109,119,0.12); background:rgba(255,255,255,0.9)}
  .badge-status.New{background:rgba(42,157,143,0.10); color:var(--teal-dark)}
  .badge-status.Completed{background:rgba(34,139,34,0.10); color:#1e7a1e}
  .card{border-radius:18px; padding:16px}
  .grid2{display:grid; grid-template-columns:1fr 1fr; gap:12px}
  @media(max-width:900px){ .grid2{grid-template-columns:1fr} table{font-size:12px} }
  .field input,.field textarea,.field select{width:100%; padding:10px 12px; border-radius:12px; border:1px solid rgba(0,109,119,0.14); background:rgba(255,255,255,0.92); font:500 13px 'Plus Jakarta Sans',sans-serif}
  .field textarea{min-height:96px}
  .msg{padding:10px 12px; border-radius:12px; font-size:13px; margin-bottom:12px}
  .msg.ok{background:rgba(42,157,143,0.10); border:1px solid rgba(42,157,143,0.18); color:var(--teal-dark)}
  .msg.err{background:rgba(180,40,40,0.08); border:1px solid rgba(180,40,40,0.16); color:#7a1f1f}
  .thumb{width:64px; height:64px; object-fit:cover; border-radius:10px; border:1px solid rgba(0,109,119,0.10)}
</style>
</head><body>
<div class="bg-auras" aria-hidden="true"><div class="aura aura--1"></div><div class="aura aura--2"></div></div>
<nav class="nav"><div class="nav-inner" style="max-width:1180px">
  <a class="brand" href="../"><span class="brand-mark"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#006D77" stroke-width="1.6"><path d="M6 20 L18 8"/><path d="M7 8 Q12 6 17 8"/></svg></span> Nena Studio <span style="font-size:11px; letter-spacing:0.14em; text-transform:uppercase; color:var(--turq-soft); margin-left:6px">Admin</span></a>
  <div style="display:flex; gap:8px; align-items:center; font-size:13px">
    <span class="muted">Hi, <?= h($_SESSION['admin_user']) ?></span>
    <a class="btn btn--glass btn--sm" href="../" target="_blank" rel="noopener">View site</a>
    <a class="btn btn--primary btn--sm" href="?action=logout">Log out</a>
  </div>
</div></nav>

<div class="admin-wrap">
  <div class="admin-nav">
    <div class="tabs" role="tablist">
      <button class="tab-btn active" data-tab="enquiries" role="tab" aria-selected="true">Enquiries (<?= count($enquiries) ?>)</button>
      <button class="tab-btn" data-tab="portfolio" role="tab" aria-selected="false">Behind the Stitches</button>
      <button class="tab-btn" data-tab="settings" role="tab" aria-selected="false">SEO & Settings</button>
    </div>
    <div style="font-size:12px; color:var(--muted)">SQLite · cPanel-ready · <a href="../sitemap.xml" target="_blank">sitemap.xml</a></div>
  </div>

  <?php if($msg): ?><div class="msg ok"><?= h($msg) ?></div><?php endif; ?>
  <?php if($err): ?><div class="msg err"><?= h($err) ?></div><?php endif; ?>

  <!-- ENQUIRIES -->
  <section class="panel active" id="panel-enquiries" role="tabpanel">
    <div class="card glass stitch">
      <h2 style="margin:0 0 8px; font-size:22px">Enquiries &amp; Orders CRM</h2>
      <p class="muted" style="margin:0 0 12px; font-size:13px">Update status, add internal notes, preview inspiration files. New enquiries trigger an email to <strong><?= h(get_setting('contact_email','')) ?></strong>.</p>
      <div style="overflow:auto">
      <table>
        <thead><tr><th>Date</th><th>Client</th><th>Project</th><th>When</th><th>Contact</th><th>Status</th><th style="min-width:220px">Admin</th></tr></thead>
        <tbody>
        <?php if(empty($enquiries)): ?><tr><td colspan="7" style="text-align:center; color:var(--muted); padding:22px">No enquiries yet — submissions will appear here ✨</td></tr>
        <?php else: foreach($enquiries as $e):
          $files = json_decode($e['files'] ?? '[]', true); if(!is_array($files)) $files=[];
        ?>
          <tr>
            <td style="white-space:nowrap"><?= h(date('Y-m-d H:i', strtotime($e['created_at']))) ?></td>
            <td><strong><?= h($e['name']) ?></strong><br><a href="mailto:<?= h($e['email']) ?>" style="font-size:12px"><?= h($e['email']) ?></a><br><span style="font-size:12px; color:var(--muted)"><?= nl2br(h(mb_strimwidth($e['message'],0,160,'…'))) ?></span>
              <?php if($files): ?><div style="margin-top:6px; display:flex; gap:6px; flex-wrap:wrap">
                <?php foreach($files as $f): $isImg=preg_match('/\.(jpe?g|png|webp)$/i',$f); ?>
                  <a href="../<?= h($f) ?>" target="_blank" rel="noopener" title="<?= h($f) ?>"><?php if($isImg): ?><img src="../<?= h($f) ?>" alt="" class="thumb" loading="lazy"><?php else: ?><span style="display:inline-block; padding:6px 8px; border-radius:8px; background:#fff; border:1px solid rgba(0,109,119,0.12); font-size:11px">PDF</span><?php endif; ?></a>
                <?php endforeach; ?>
              </div><?php endif; ?>
            </td>
            <td><?= h($e['project_type']) ?></td>
            <td><?= $e['flexible'] ? '<span class="badge-status">Flexible</span>' : h($e['target_date'] ?: '—') ?></td>
            <td><?= h($e['contact_method'] ?: '—') ?><br><span style="font-size:12px"><?= h($e['contact_detail'] ?: '') ?></span></td>
            <td><span class="badge-status <?= h($e['status']) ?>"><?= h($e['status']) ?></span></td>
            <td>
              <form method="post" style="display:grid; gap:6px">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="action" value="enquiry_update">
                <input type="hidden" name="id" value="<?= (int)$e['id'] ?>">
                <input type="hidden" name="tab" value="enquiries">
                <select name="status">
                  <?php foreach(['New','In Discussion','In Progress','Completed','Archived'] as $s): ?><option value="<?= h($s) ?>" <?= $e['status']===$s?'selected':'' ?>><?= h($s) ?></option><?php endforeach; ?>
                </select>
                <textarea name="admin_notes" rows="2" placeholder="Internal notes…"><?= h($e['admin_notes'] ?? '') ?></textarea>
                <div style="display:flex; gap:6px">
                  <button class="btn btn--primary btn--sm" type="submit" style="flex:1">Save</button>
                </div>
              </form>
              <form method="post" onsubmit="return confirm('Delete this enquiry?')" style="margin-top:6px">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="action" value="enquiry_delete">
                <input type="hidden" name="id" value="<?= (int)$e['id'] ?>">
                <input type="hidden" name="tab" value="enquiries">
                <button class="btn btn--glass btn--sm" type="submit" style="width:100%">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
      </div>
    </div>
  </section>

  <!-- PORTFOLIO -->
  <section class="panel" id="panel-portfolio" role="tabpanel">
    <div class="card glass stitch" style="margin-bottom:14px">
      <h2 style="margin:0 0 8px; font-size:22px"><?= $editItem ? 'Edit Story — '.h($editItem['title']) : 'Create a new story' ?></h2>
      <p class="muted" style="margin:0 0 12px; font-size:13px">Upload multiple photos + optional process video (mp4/webm). Add alt-text &amp; SEO fields for image search.</p>
      <form method="post" enctype="multipart/form-data" class="grid2">
        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="action" value="portfolio_save">
        <input type="hidden" name="tab" value="portfolio">
        <input type="hidden" name="portfolio_id" value="<?= $editItem ? (int)$editItem['id'] : 0 ?>">
        <input type="hidden" name="existing_cover" value="<?= h($editItem['cover_image'] ?? '') ?>">
        <input type="hidden" name="existing_video" value="<?= h($editItem['video'] ?? '') ?>">
        <input type="hidden" name="existing_images" value="<?= h($editItem['images'] ?? '[]') ?>">
        <div class="field"><label>Title *</label><input name="title" required value="<?= h($editItem['title'] ?? '') ?>"></div>
        <div class="field"><label>Slug (auto if empty)</label><input name="slug" value="<?= h($editItem['slug'] ?? '') ?>" placeholder="my-beautiful-story"></div>
        <div class="field"><label>Category *</label><select name="category"><?php foreach(['Weddings & Special Occasions','Made for You','Alterations & Reimagining'] as $c): ?><option value="<?= h($c) ?>" <?= ($editItem['category'] ?? '')===$c?'selected':'' ?>><?= h($c) ?></option><?php endforeach; ?></select></div>
        <div class="field"><label>Order</label><input name="sort_order" type="number" value="<?= h($editItem['sort_order'] ?? 0) ?>"></div>
        <div class="field"><label>Status</label><select name="status"><option value="published" <?= ($editItem['status'] ?? 'published')==='published'?'selected':'' ?>>Published</option><option value="draft" <?= ($editItem['status'] ?? '')==='draft'?'selected':'' ?>>Draft</option></select></div>
        <div class="field"><label>Cover image <?= $editItem && !empty($editItem['cover_image']) ? '(current: '.h(basename($editItem['cover_image'])).')' : '' ?></label><input type="file" name="cover_image" accept=".jpg,.jpeg,.png,.webp"></div>
        <div class="field" style="grid-column:1/-1"><label>Summary (card preview)</label><textarea name="summary" rows="2"><?= h($editItem['summary'] ?? '') ?></textarea></div>
        <div class="field" style="grid-column:1/-1"><label>Full story (HTML allowed — paragraphs, bold, emphasis)</label><textarea name="story" rows="6"><?= h($editItem['story'] ?? '') ?></textarea></div>
        <div class="field"><label>More photos (multi)</label><input type="file" name="images[]" multiple accept=".jpg,.jpeg,.png,.webp"></div>
        <div class="field"><label>Process video (mp4/webm)</label><input type="file" name="video" accept=".mp4,.webm"></div>
        <div class="field"><label>Image alt-text (for SEO &amp; accessibility)</label><input name="alt_text" value="<?= h($editItem['alt_text'] ?? '') ?>" placeholder="Describe the cover image…"></div>
        <div class="field"><label>SEO title</label><input name="seo_title" value="<?= h($editItem['seo_title'] ?? '') ?>"></div>
        <div class="field" style="grid-column:1/-1"><label>SEO description</label><textarea name="seo_description" rows="2"><?= h($editItem['seo_description'] ?? '') ?></textarea></div>
        <div style="grid-column:1/-1; display:flex; gap:8px">
          <button class="btn btn--primary" type="submit"><?= $editItem ? 'Update Story' : 'Create Story' ?></button>
          <?php if($editItem): ?><a class="btn btn--glass" href="index.php#portfolio">Cancel</a><?php endif; ?>
        </div>
      </form>
    </div>

    <div class="card glass stitch">
      <h3 style="margin:0 0 10px">All Stories (<?= count($portfolio) ?>)</h3>
      <div style="overflow:auto">
      <table>
        <thead><tr><th>Cover</th><th>Title</th><th>Category</th><th>Status</th><th>Order</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach($portfolio as $p): ?>
          <tr>
            <td><?php if(!empty($p['cover_image'])): ?><img src="../<?= h($p['cover_image']) ?>" alt="" class="thumb" loading="lazy"><?php else: ?><span class="muted">—</span><?php endif; ?></td>
            <td><strong><?= h($p['title']) ?></strong><br><span style="font-size:11px; color:var(--muted)"><?= h($p['slug']) ?></span></td>
            <td><?= h($p['category']) ?></td>
            <td><span class="badge-status"><?= h($p['status']) ?></span></td>
            <td><?= (int)$p['sort_order'] ?></td>
            <td style="display:flex; gap:6px; flex-wrap:wrap">
              <a class="btn btn--glass btn--sm" href="?edit=<?= (int)$p['id'] ?>#portfolio">Edit</a>
              <form method="post" onsubmit="return confirm('Delete this story?')"><input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><input type="hidden" name="action" value="portfolio_delete"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><input type="hidden" name="tab" value="portfolio"><button class="btn btn--glass btn--sm" type="submit">Delete</button></form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if(empty($portfolio)): ?><tr><td colspan="6" style="text-align:center; color:var(--muted); padding:18px">No stories yet — create your first above.</td></tr><?php endif; ?>
        </tbody>
      </table>
      </div>
    </div>
  </section>

  <!-- SETTINGS -->
  <section class="panel" id="panel-settings" role="tabpanel">
    <form method="post" class="card glass stitch">
      <h2 style="margin:0 0 8px; font-size:22px">SEO &amp; Site Settings</h2>
      <p class="muted" style="margin:0 0 12px; font-size:13px">Edits apply instantly — no code changes needed. Open Graph image is used for WhatsApp / Instagram / Pinterest previews.</p>
      <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
      <input type="hidden" name="action" value="settings_save">
      <input type="hidden" name="tab" value="settings">
      <div class="grid2">
        <div class="field" style="grid-column:1/-1"><label>Site title</label><input name="site_title" value="<?= h(get_setting('site_title','')) ?>"></div>
        <div class="field" style="grid-column:1/-1"><label>Meta title (browser tab &amp; Google)</label><input name="meta_title" value="<?= h(get_setting('meta_title','')) ?>"></div>
        <div class="field" style="grid-column:1/-1"><label>Meta description</label><textarea name="meta_description" rows="2"><?= h(get_setting('meta_description','')) ?></textarea></div>
        <div class="field" style="grid-column:1/-1"><label>Meta keywords</label><input name="meta_keywords" value="<?= h(get_setting('meta_keywords','')) ?>"></div>
        <div class="field" style="grid-column:1/-1"><label>Open Graph cover image (URL)</label><input name="og_image" value="<?= h(get_setting('og_image','')) ?>"></div>
        <div class="field"><label>Site URL (for sitemap &amp; canonical)</label><input name="site_url" value="<?= h(get_setting('site_url','')) ?>" placeholder="https://nenastudio.nl"></div>
        <div class="field"><label>Robots</label><select name="robots"><option value="index, follow" <?= get_setting('robots')==='index, follow'?'selected':'' ?>>index, follow</option><option value="noindex, nofollow" <?= get_setting('robots')==='noindex, nofollow'?'selected':'' ?>>noindex, nofollow</option></select></div>
        <div class="field"><label>Contact email</label><input name="contact_email" type="email" value="<?= h(get_setting('contact_email','')) ?>"></div>
        <div class="field"><label>Instagram URL</label><input name="instagram_url" value="<?= h(get_setting('instagram_url','')) ?>"></div>
        <div class="field"><label>Admin username</label><input name="admin_username" value="<?= h(get_setting('admin_username','')) ?>"></div>
        <div class="field"><label>New admin password (leave blank to keep)</label><input name="admin_password" type="password" placeholder="••••••••"></div>
      </div>
      <button class="btn btn--primary" type="submit" style="margin-top:12px">Save Settings</button>
    </form>
  </section>
</div>

<script>
  const btns=[...document.querySelectorAll('.tab-btn')];
  const panels={ enquiries: document.getElementById('panel-enquiries'), portfolio: document.getElementById('panel-portfolio'), settings: document.getElementById('panel-settings') };
  function show(tab){
    btns.forEach(b=>{ const on=b.dataset.tab===tab; b.classList.toggle('active', on); b.setAttribute('aria-selected', String(on)); });
    Object.entries(panels).forEach(([k,el])=> el.classList.toggle('active', k===tab));
    location.hash=tab;
  }
  btns.forEach(b=> b.addEventListener('click', ()=> show(b.dataset.tab)));
  const initial = location.hash.replace('#','');
  if(initial && panels[initial]) show(initial);
</script>
</body></html>
