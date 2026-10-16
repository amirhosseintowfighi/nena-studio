<?php
// api/submit_enquiry.php — secure AJAX handler with CSRF, honeypot, file uploads
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json; charset=UTF-8');

function fail(string $msg, int $code=400): never {
  http_response_code($code);
  echo json_encode(['ok'=>false, 'error'=>$msg], JSON_UNESCAPED_UNICODE);
  exit;
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') fail('Method not allowed', 405);

// Honeypot
if (!empty($_POST['website'])) fail('Spam detected.');

// CSRF
$csrf = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
if (!verify_csrf($csrf)) fail('Invalid session — please refresh the page and try again.', 419);

// Validate
$name = trim((string)($_POST['name'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$project_type = trim((string)($_POST['project_type'] ?? 'Other'));
$message = trim((string)($_POST['message'] ?? ''));
$target_date = trim((string)($_POST['target_date'] ?? ''));
$flexible = !empty($_POST['flexible']) ? 1 : 0;
$contact_method = trim((string)($_POST['contact_method'] ?? ''));
$contact_detail = trim((string)($_POST['contact_detail'] ?? ''));

if ($name === '' || mb_strlen($name) < 2) fail('Please enter your name.');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) fail('Please enter a valid email address.');
if ($message === '' || mb_strlen($message) < 10) fail('Please share a little more about your idea (at least 10 characters).');
$allowedTypes = ['Weddings & Special Occasions','Made for You','Alterations & Reimagining','Other'];
if (!in_array($project_type, $allowedTypes, true)) $project_type = 'Other';
$allowedContact = ['Email','WhatsApp / Phone','Instagram',''];
if (!in_array($contact_method, $allowedContact, true)) $contact_method = '';
if ($flexible) $target_date = '';
elseif ($target_date !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $target_date)) fail('Invalid date.');
// Optional contact detail: require if method is Phone/Instagram and detail empty? keep optional per spec — no fail

// File uploads — drag & drop multi (JPG/PNG/WEBP/PDF up to 10MB each, max 8)
$uploadDir = __DIR__ . '/../uploads/enquiries';
if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
$saved = [];
$allowedMime = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','application/pdf'=>'pdf'];
$maxBytes = 10 * 1024 * 1024;

if (!empty($_FILES['inspiration'])) {
  $files = $_FILES['inspiration'];
  $count = is_array($files['name']) ? count($files['name']) : 0;
  if ($count > 8) fail('Maximum 8 files allowed.');
  for ($i=0; $i<$count; $i++) {
    if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;
    if (($files['error'][$i] ?? 0) !== UPLOAD_ERR_OK) fail('Upload failed for ' . ($files['name'][$i] ?? 'a file') . '.');
    $tmp = $files['tmp_name'][$i];
    $orig = $files['name'][$i] ?? 'file';
    $size = (int)($files['size'][$i] ?? 0);
    if ($size > $maxBytes) fail($orig . ' is over 10MB.');
    // MIME check via finfo
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo ? finfo_file($finfo, $tmp) : (mime_content_type($tmp) ?: '');
    if ($finfo) finfo_close($finfo);
    // allow webp which finfo sometimes reports as image/webp already covered
    if (!isset($allowedMime[$mime])) {
      // fallback by extension for edge cases
      $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
      $extMap = ['jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp','pdf'=>'application/pdf'];
      $mimeByExt = $extMap[$ext] ?? null;
      if (!$mimeByExt || !isset($allowedMime[$mimeByExt])) fail($orig . ' — unsupported file type. Use JPG, PNG, WEBP or PDF.');
      $mime = $mimeByExt;
    }
    $ext = $allowedMime[$mime];
    $safe = bin2hex(random_bytes(8)) . '-' . preg_replace('/[^a-z0-9._-]+/i','-', pathinfo($orig, PATHINFO_FILENAME));
    $safe = substr($safe, 0, 48) . '.' . $ext;
    $dest = $uploadDir . '/' . $safe;
    if (!is_uploaded_file($tmp) || !move_uploaded_file($tmp, $dest)) fail('Could not save ' . $orig . '.');
    $saved[] = 'uploads/enquiries/' . $safe;
  }
}

// Store
try {
  $stmt = $pdo->prepare("INSERT INTO enquiries(name,email,project_type,message,target_date,flexible,contact_method,contact_detail,files,status,created_at) VALUES(:n,:e,:pt,:m,:td,:fl,:cm,:cd,:fi,'New',:ca)");
  $stmt->execute([
    ':n'=>$name, ':e'=>$email, ':pt'=>$project_type, ':m'=>$message, ':td'=>$target_date, ':fl'=>$flexible,
    ':cm'=>$contact_method, ':cd'=>$contact_detail, ':fi'=> json_encode($saved, JSON_UNESCAPED_SLASHES), ':ca'=> date('Y-m-d H:i:s')
  ]);
} catch(Throwable $e) { fail('Could not save your enquiry — please try again.', 500); }

// Optional email notification (best-effort, never fails the request)
try {
  $to = get_setting('contact_email', 'hello@nenastudio.nl');
  if ($to && filter_var($to, FILTER_VALIDATE_EMAIL)) {
    $subject = 'New enquiry — ' . $project_type . ' — ' . $name;
    $body = "New enquiry from Nena Studio website\n\nName: {$name}\nEmail: {$email}\nProject: {$project_type}\nDate: " . ($flexible ? 'Flexible' : ($target_date ?: '—')) . "\nContact via: {$contact_method} {$contact_detail}\n\nMessage:\n{$message}\n\nFiles: " . (empty($saved) ? '—' : implode(', ', $saved)) . "\n";
    @mail($to, $subject, $body, "From: Nena Studio <noreply@" . ($_SERVER['HTTP_HOST'] ?? 'nenastudio.nl') . ">\r\nContent-Type: text/plain; charset=UTF-8");
  }
} catch(Throwable $e) {}

echo json_encode(['ok'=>true], JSON_UNESCAPED_UNICODE);
