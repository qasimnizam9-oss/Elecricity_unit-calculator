<?php
// dashboard.php (fixed - moved `use` to file scope and added checks)
// Place this file in your consumer folder. Adjust include path to db.php if needed.

session_start();
include '../db.php'; // adjust if your db.php is in another location

// try to autoload composer libs (PHPMailer) if available
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

// PHPMailer aliases must be declared at file scope (not inside a conditional)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ---------- helper ----------
function json_out($arr){ header('Content-Type: application/json'); echo json_encode($arr); exit(); }

// ---------- login state ----------
$logged = isset($_SESSION['consumer_id']);
$logged_id = $logged ? (int)$_SESSION['consumer_id'] : 0;

// Handle logout
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: dashboard.php?loggedout=1");
    exit();
}

// ---------- ACTION: update_profile (AJAX) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    if (!$logged) json_out(['ok'=>false,'error'=>'Not logged in']);
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $consumer_number = trim($_POST['consumer_number'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$name || !$email || !$consumer_number) json_out(['ok'=>false,'error'=>'All fields required']);

    // uniqueness check
    $check = $conn->prepare("SELECT id FROM consumers WHERE (email=? OR consumer_number=?) AND id<>? LIMIT 1");
    $check->bind_param("ssi", $email, $consumer_number, $logged_id);
    $check->execute();
    $res = $check->get_result();
    if ($res->num_rows) json_out(['ok'=>false,'error'=>'Email or consumer number already in use']);

    if ($password) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("UPDATE consumers SET name=?, email=?, consumer_number=?, password=? WHERE id=?");
        $stmt->bind_param("ssssi", $name, $email, $consumer_number, $hash, $logged_id);
    } else {
        $stmt = $conn->prepare("UPDATE consumers SET name=?, email=?, consumer_number=? WHERE id=?");
        $stmt->bind_param("sssi", $name, $email, $consumer_number, $logged_id);
    }

    if ($stmt->execute()) {
        // update session
        $_SESSION['consumer_name'] = $name;
        $_SESSION['consumer_number'] = $consumer_number;
        $_SESSION['consumer_email'] = $email;
        json_out(['ok'=>true]);
    } else json_out(['ok'=>false,'error'=>'DB update failed']);
}

// ---------- ACTION: send_bill (AJAX) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_bill') {
    if (!$logged) json_out(['ok'=>false,'error'=>'Not logged in']);
    $bill_id = isset($_POST['bill_id']) ? (int)$_POST['bill_id'] : 0;
    $to = filter_var(trim($_POST['to'] ?? ''), FILTER_VALIDATE_EMAIL);
    $message = trim($_POST['message'] ?? '');
    if (!$bill_id || !$to) json_out(['ok'=>false,'error'=>'Invalid parameters']);

    $stmt = $conn->prepare("SELECT b.id,b.units,b.amount,b.bill_date,b.due_date,c.name,c.email,c.consumer_number FROM bills b JOIN consumers c ON b.consumer_id=c.id WHERE b.id=? AND b.consumer_id=? LIMIT 1");
    $stmt->bind_param("ii",$bill_id,$logged_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 0) json_out(['ok'=>false,'error'=>'Bill not found']);
    $bill = $res->fetch_assoc();

    // Ensure PHPMailer is installed
    if (!class_exists('\PHPMailer\PHPMailer\PHPMailer')) {
        json_out(['ok'=>false,'error'=>'PHPMailer not installed. Run: composer require phpmailer/phpmailer']);
    }

    try {
        $mail = new PHPMailer(true);
        // SMTP configuration - REPLACE these values with your SMTP provider details
        $mail->isSMTP();
        $mail->Host = 'smtp.example.com';      // e.g. smtp.gmail.com
        $mail->SMTPAuth = true;
        $mail->Username = 'your_email@example.com';
        $mail->Password = 'your_password';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('billing@example.com', 'FESCO Billing');
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = "FESCO Bill #{$bill['id']}";
        $html = "<h3>FESCO Electricity Bill</h3>";
        $html .= "<p><strong>Bill ID:</strong> {$bill['id']}</p>";
        $html .= "<p><strong>Consumer:</strong> ".htmlspecialchars($bill['name'])." ({$bill['consumer_number']})</p>";
        $html .= "<p><strong>Date:</strong> {$bill['bill_date']}</p>";
        $html .= "<p><strong>Units:</strong> {$bill['units']}</p>";
        $html .= "<p><strong>Amount:</strong> Rs. {$bill['amount']}</p>";
        if (!empty($bill['due_date'])) $html .= "<p><strong>Due Date:</strong> {$bill['due_date']}</p>";
        if ($message) $html .= "<hr><p>".nl2br(htmlspecialchars($message))."</p>";

        $mail->Body = $html;

        // Attach PDF if FPDF is available
        $attached = false;
        if (file_exists(__DIR__ . '/fpdf.php')) {
            require_once __DIR__ . '/fpdf.php';
            $pdf = new FPDF();
            $pdf->AddPage();
            $pdf->SetFont('Arial','B',14);
            $pdf->Cell(0,10,'FESCO Electricity Bill',0,1,'C');
            $pdf->SetFont('Arial','',12);
            $pdf->Ln(4);
            $pdf->Cell(0,8,"Bill ID: {$bill['id']}",0,1);
            $pdf->Cell(0,8,"Consumer: {$bill['name']} ({$bill['consumer_number']})",0,1);
            $pdf->Cell(0,8,"Date: {$bill['bill_date']}",0,1);
            $pdf->Cell(0,8,"Units: {$bill['units']}",0,1);
            $pdf->Cell(0,8,"Amount: Rs. {$bill['amount']}",0,1);
            if(!empty($bill['due_date'])) $pdf->Cell(0,8,"Due Date: {$bill['due_date']}",0,1);
            $tmpfile = sys_get_temp_dir() . "/fesco_bill_{$bill['id']}.pdf";
            $pdf->Output('F', $tmpfile);
            $mail->addAttachment($tmpfile, "FESCO_bill_{$bill['id']}.pdf");
            $attached = true;
        }

        $mail->send();
        if ($attached && isset($tmpfile) && file_exists($tmpfile)) @unlink($tmpfile);
        json_out(['ok'=>true]);
    } catch (Exception $e) {
        error_log("Mailer error: " . $e->getMessage());
        json_out(['ok'=>false,'error'=>'Mailer failed: ' . $e->getMessage()]);
    }
}

// ---------- ACTION: PDF generation (GET) ----------
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'pdf' && isset($_GET['bill_id'])) {
    $bill_id = (int)$_GET['bill_id'];
    if (!$logged) { header('HTTP/1.1 403 Forbidden'); echo "Not logged in"; exit; }

    $stmt = $conn->prepare("SELECT b.id,b.units,b.amount,b.bill_date,b.due_date,c.name,c.consumer_number FROM bills b JOIN consumers c ON b.consumer_id=c.id WHERE b.id=? AND b.consumer_id=? LIMIT 1");
    $stmt->bind_param("ii",$bill_id,$logged_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 0) { header('HTTP/1.1 404 Not Found'); echo "Bill not found"; exit; }
    $bill = $res->fetch_assoc();

    // Server-side PDF with FPDF (if available)
    if (file_exists(__DIR__ . '/fpdf.php')) {
        require_once __DIR__ . '/fpdf.php';
        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial','B',16);
        $pdf->Cell(0,10,'FESCO Electricity Bill',0,1,'C');
        $pdf->Ln(6);
        $pdf->SetFont('Arial','',12);
        $pdf->Cell(50,8,'Bill ID:',0,0);
        $pdf->Cell(0,8,$bill['id'],0,1);
        $pdf->Cell(50,8,'Consumer:',0,0);
        $pdf->Cell(0,8,$bill['name'] . ' (' . $bill['consumer_number'] . ')',0,1);
        $pdf->Cell(50,8,'Date:',0,0);
        $pdf->Cell(0,8,$bill['bill_date'],0,1);
        $pdf->Cell(50,8,'Units:',0,0);
        $pdf->Cell(0,8,$bill['units'],0,1);
        $pdf->Cell(50,8,'Amount (Rs):',0,0);
        $pdf->Cell(0,8,$bill['amount'],0,1);
        if (!empty($bill['due_date'])) {
            $pdf->Cell(50,8,'Due Date:',0,0);
            $pdf->Cell(0,8,$bill['due_date'],0,1);
        }
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="FESCO_bill_'.$bill['id'].'.pdf"');
        $pdf->Output();
        exit;
    } else {
        // HTML printable fallback
        $html = "<html><head><title>FESCO Bill #{$bill['id']}</title>";
        $html .= '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">';
        $html .= "</head><body><div class='container mt-4'>";
        $html .= "<h2 class='text-center'>FESCO Electricity Bill</h2><hr>";
        $html .= "<p><strong>Bill ID:</strong> {$bill['id']}</p>";
        $html .= "<p><strong>Consumer:</strong> {$bill['name']} ({$bill['consumer_number']})</p>";
        $html .= "<p><strong>Date:</strong> {$bill['bill_date']}</p>";
        $html .= "<p><strong>Units:</strong> {$bill['units']}</p>";
        $html .= "<p><strong>Amount (Rs):</strong> {$bill['amount']}</p>";
        if (!empty($bill['due_date'])) $html .= "<p><strong>Due Date:</strong> {$bill['due_date']}</p>";
        $html .= "</div><script>window.print();</script></body></html>";
        echo $html;
        exit;
    }
}

// ----------------------- REGISTRATION & BILL CALC & PAGE LOGIC -----------------------
$regMessage = $regMessage ?? "";
$billMessage = "";

// Registration (non-AJAX) - same form handler as before
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $consumer_number_input = trim($_POST['consumer_number'] ?? '');

    if (empty($name) || empty($email) || empty($password) || empty($consumer_number_input)) {
        $regMessage = "Please fill all fields.";
    } else {
        $check = $conn->prepare("SELECT id FROM consumers WHERE email=? OR consumer_number=? LIMIT 1");
        $check->bind_param("ss", $email, $consumer_number_input);
        $check->execute();
        $res = $check->get_result();
        if ($res->num_rows > 0) {
            $regMessage = "Email or Consumer Number already exists!";
        } else {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $insert = $conn->prepare("INSERT INTO consumers (name, email, password, consumer_number, created_at) VALUES (?, ?, ?, ?, NOW())");
            $insert->bind_param("ssss", $name, $email, $hashed_password, $consumer_number_input);
            if ($insert->execute()) {
                // Auto-login and redirect
                $_SESSION['consumer_id'] = $conn->insert_id;
                $_SESSION['consumer_name'] = $name;
                $_SESSION['consumer_number'] = $consumer_number_input;
                $_SESSION['consumer_email'] = $email;
                header("Location: dashboard.php?show=view_bills&reg=1");
                exit();
            } else {
                $regMessage = "Registration failed. Try again.";
            }
        }
    }
}

// Handle calculate (non-AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['calculate']) && $logged) {
    $units = (int)($_POST['units'] ?? 0);
    if ($units < 0) $units = 0;
    $amount = $units * 15;
    $bill_date = date("Y-m-d");
    $due_date = date('Y-m-d', strtotime('+15 days'));
    $late_fee_percent = 2.0;

    $insert = $conn->prepare("INSERT INTO bills (consumer_id, units, amount, bill_date, due_date, late_fee_percent) VALUES (?, ?, ?, ?, ?, ?)");
    if ($insert) {
        $insert->bind_param("iiissd", $logged_id, $units, $amount, $bill_date, $due_date, $late_fee_percent);
        if ($insert->execute()) {
            header("Location: dashboard.php?show=view_bills&created=1");
            exit;
        }
    }
    // fallback
    $insert2 = $conn->prepare("INSERT INTO bills (consumer_id, units, amount, bill_date) VALUES (?, ?, ?, ?)");
    if ($insert2) {
        $insert2->bind_param("iiis", $logged_id, $units, $amount, $bill_date);
        if ($insert2->execute()) {
            header("Location: dashboard.php?show=view_bills&created=1");
            exit;
        } else {
            $billMessage = "Failed to insert bill.";
        }
    } else {
        $billMessage = "Failed to prepare insert; check DB schema.";
    }
}

// Fetch bills for page display
$bills = [];
if ($logged) {
    $stmt = $conn->prepare("SELECT id, units, amount, bill_date, IFNULL(due_date, NULL) as due_date, IFNULL(late_fee_percent, NULL) as late_fee_percent FROM bills WHERE consumer_id=? ORDER BY bill_date DESC LIMIT 200");
    if ($stmt) {
        $stmt->bind_param("i", $logged_id);
        $stmt->execute();
        $bills = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } else {
        $stmt2 = $conn->prepare("SELECT id, units, amount, bill_date FROM bills WHERE consumer_id=? ORDER BY bill_date DESC LIMIT 200");
        $stmt2->bind_param("i", $logged_id);
        $stmt2->execute();
        $rows = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
        foreach ($rows as $r) { $r['due_date'] = null; $r['late_fee_percent'] = null; $bills[] = $r; }
    }
}

// Monthly chart
$months = []; $values = [];
if ($logged) {
    for ($i = 5; $i >= 0; $i--) {
        $m = date("Y-m", strtotime("-$i month"));
        $months[] = date("M Y", strtotime($m . "-01"));
        $start = date("Y-m-01", strtotime($m . "-01"));
        $end = date("Y-m-t", strtotime($m . "-01"));
        $stmt = $conn->prepare("SELECT COALESCE(SUM(amount),0) as total FROM bills WHERE consumer_id=? AND bill_date BETWEEN ? AND ?");
        $stmt->bind_param("iss", $logged_id, $start, $end);
        $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc();
        $values[] = (float)$r['total'];
    }
}

// Consumer details
$consumer = null;
if ($logged) {
    $s = $conn->prepare("SELECT id, name, email, consumer_number FROM consumers WHERE id=? LIMIT 1");
    $s->bind_param("i", $logged_id);
    $s->execute();
    $consumer = $s->get_result()->fetch_assoc();
}

$show = $_GET['show'] ?? null;
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>FESCO Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
:root{--green:#198754;--muted:#6c757d}
body{font-family:Segoe UI,Roboto,Arial;background:#f8f9fa;margin:0}
.sidebar{height:100vh;background:var(--green);color:#fff;padding:20px;position:fixed;width:220px}
.sidebar a{display:flex;align-items:center;color:#fff;padding:10px 8px;text-decoration:none}
.sidebar a:hover{background:rgba(0,0,0,0.08)}
.content{margin-left:240px;padding:20px}
.section{display:none}
.table-fixed{max-height:420px;overflow:auto}
.small-text{font-size:13px;color:var(--muted)}
</style>
</head>
<body>
<div class="sidebar" id="sidebar">
  <h4>FESCO</h4>
  <small class="small-text">Consumer Portal</small>
  <hr style="border-color:rgba(255,255,255,0.08)">
  <?php if(!$logged): ?>
    <a href="#" onclick="showSection('register');return false;"><i class="bi bi-person-plus"></i><span style="margin-left:8px">Register</span></a>
  <?php else: ?>
    <a href="#" onclick="showSection('calculate_bill');return false;"><i class="bi bi-calculator"></i><span style="margin-left:8px">Calculate Bill</span></a>
    <a href="#" onclick="showSection('view_bills');return false;"><i class="bi bi-wallet2"></i><span style="margin-left:8px">View Bills</span></a>
    <a href="#" onclick="showSection('profile');return false;"><i class="bi bi-person-circle"></i><span style="margin-left:8px">Profile</span></a>
    <a href="?logout=true"><i class="bi bi-box-arrow-right"></i><span style="margin-left:8px">Logout</span></a>
  <?php endif; ?>
  <hr>
  <button id="toggleSidebarBtn" class="btn btn-light btn-sm w-100 mb-2"><i class="bi bi-layout-sidebar-collapse"></i> Collapse</button>
  <button id="themeToggleBtn" class="btn btn-light btn-sm w-100">Toggle Theme</button>
</div>

<div class="content">
  <?php if(isset($_GET['loggedout']) && $_GET['loggedout']==1): ?>
    <div class="alert alert-success">You have been successfully logged out.</div>
  <?php endif; ?>
  <?php if(isset($_GET['created'])): ?>
    <div class="alert alert-success">Bill created successfully.</div>
  <?php endif; ?>
  <?php if(isset($_GET['reg'])): ?>
    <div class="alert alert-success">Registration successful. Welcome!</div>
  <?php endif; ?>

  <!-- REGISTER -->
  <?php if(!$logged): ?>
  <div id="register" class="section">
    <h4>Consumer Registration</h4>
    <?php if(!empty($regMessage)) echo "<div class='alert alert-info'>$regMessage</div>"; ?>
    <form method="POST" class="mb-3">
      <div class="row g-2">
        <div class="col-md-4"><input name="name" class="form-control" placeholder="Full Name" required></div>
        <div class="col-md-4"><input name="email" type="email" class="form-control" placeholder="Email" required></div>
        <div class="col-md-4"><input name="consumer_number" class="form-control" placeholder="Consumer Number" required></div>
        <div class="col-md-4"><input name="password" type="password" class="form-control" placeholder="Password" required></div>
        <div class="col-md-2"><button class="btn btn-success" name="register">Register</button></div>
      </div>
    </form>
  </div>
  <?php endif; ?>

  <!-- CALCULATE BILL -->
  <?php if($logged): ?>
  <div id="calculate_bill" class="section">
    <h4>Calculate Bill</h4>
    <?php if(!empty($billMessage)) echo "<div class='alert alert-info'>$billMessage</div>"; ?>
    <form method="POST" class="row g-2 mb-3">
      <div class="col-md-3"><input name="units" type="number" min="0" class="form-control" placeholder="Units Consumed" required></div>
      <div class="col-md-2"><button class="btn btn-primary" name="calculate">Calculate</button></div>
    </form>

    <h5>Monthly Usage</h5>
    <div class="card p-3 mb-3">
      <canvas id="usageChart" height="120"></canvas>
    </div>
  </div>

  <!-- VIEW BILLS -->
  <div id="view_bills" class="section">
    <div class="d-flex justify-content-between align-items-center">
      <h4>Your Bill History</h4>
      <div>
        <button class="btn btn-outline-secondary btn-sm" onclick="exportCSV()"><i class="bi bi-file-earmark-spreadsheet"></i> Export CSV</button>
      </div>
    </div>

    <?php if(count($bills) > 0): ?>
    <div class="table-fixed mt-3">
      <table class="table table-bordered">
        <thead><tr><th>ID</th><th>Date</th><th>Units</th><th>Amount</th><th>Due Date</th><th>Late Fee</th><th>Actions</th></tr></thead>
        <tbody id="billsTbody">
        <?php foreach($bills as $bill):
            $due = $bill['due_date'] ?? null;
            $late_fee = 0;
            if ($due && !empty($bill['late_fee_percent'])) {
                $today = date('Y-m-d');
                if ($today > $due) {
                    $d1 = new DateTime($due); $d2 = new DateTime($today);
                    $diffDays = (int)$d2->diff($d1)->format("%a");
                    $monthsLate = floor($diffDays / 30);
                    $percent = (float)$bill['late_fee_percent'];
                    $late_fee = round(($bill['amount'] * ($percent/100)) * max(1,$monthsLate),2);
                }
            }
        ?>
          <tr data-bill='<?php echo json_encode($bill, JSON_HEX_APOS|JSON_HEX_QUOT); ?>'>
            <td><?php echo $bill['id']; ?></td>
            <td><?php echo $bill['bill_date']; ?></td>
            <td><?php echo $bill['units']; ?></td>
            <td><?php echo $bill['amount']; ?></td>
            <td><?php echo $due ?? '—'; ?></td>
            <td><?php echo $late_fee ? "Rs. $late_fee" : '—'; ?></td>
            <td>
              <a class="btn btn-sm btn-outline-primary" href="dashboard.php?action=pdf&bill_id=<?php echo $bill['id']; ?>" target="_blank" title="Download PDF"><i class="bi bi-file-earmark-pdf"></i></a>
              <button class="btn btn-sm btn-outline-secondary" onclick="printBillRow(<?php echo $bill['id']; ?>)"><i class="bi bi-printer"></i></button>
              <button class="btn btn-sm btn-outline-success" onclick="openEmailPrompt(<?php echo $bill['id']; ?>)"><i class="bi bi-envelope"></i></button>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php else: ?>
      <p class="mt-3">No bills found.</p>
    <?php endif; ?>
  </div>

  <!-- PROFILE -->
  <div id="profile" class="section">
    <h4>Profile</h4>
    <div id="profileMsg"></div>
    <form id="profileForm" class="row g-2">
      <div class="col-md-4"><label>Name</label><input name="name" class="form-control" value="<?php echo htmlspecialchars($consumer['name'] ?? ''); ?>" required></div>
      <div class="col-md-4"><label>Email</label><input name="email" type="email" class="form-control" value="<?php echo htmlspecialchars($consumer['email'] ?? ''); ?>" required></div>
      <div class="col-md-4"><label>Consumer Number</label><input name="consumer_number" class="form-control" value="<?php echo htmlspecialchars($consumer['consumer_number'] ?? ''); ?>" required></div>
      <div class="col-md-4"><label>New Password (leave blank to keep)</label><input name="password" type="password" class="form-control"></div>
      <div class="col-md-2"><button id="saveProfileBtn" class="btn btn-primary">Save</button></div>
    </form>
  </div>
  <?php endif; ?>

</div>

<!-- Email modal -->
<div class="modal fade" id="emailModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form id="sendEmailForm" class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Send Bill via Email</h5></div>
      <div class="modal-body">
        <input type="hidden" name="bill_id" id="email_bill_id">
        <div class="mb-2"><label>Recipient Email</label><input type="email" name="to" id="email_to" class="form-control" required></div>
        <div class="mb-2"><label>Message (optional)</label><textarea name="message" id="email_message" class="form-control"></textarea></div>
        <div id="emailResult"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button class="btn btn-success">Send</button>
      </div>
    </form>
  </div>
</div>

<script>
// show/hide sections
function showSection(id){
  document.querySelectorAll('.section').forEach(s => s.style.display = 'none');
  const el = document.getElementById(id);
  if(el) el.style.display = 'block';
}

// default section logic
let defaultShow = '<?php echo htmlspecialchars($show ?? ''); ?>';
if(!defaultShow) {
  <?php if(!$logged): ?>
    defaultShow = 'register';
  <?php else: ?>
    defaultShow = 'calculate_bill';
  <?php endif; ?>
}
if(document.getElementById(defaultShow)) showSection(defaultShow);

// sidebar controls
document.getElementById('toggleSidebarBtn').addEventListener('click', ()=> {
  document.getElementById('sidebar').classList.toggle('collapsed');
});
document.getElementById('themeToggleBtn').addEventListener('click', ()=> {
  document.body.classList.toggle('dark');
});

// export CSV
function exportCSV(){
  const rows = [['Bill ID','Date','Units','Amount','Due Date','Late Fee']];
  document.querySelectorAll('#billsTbody tr').forEach(tr => {
    const tds = tr.querySelectorAll('td');
    if(tds.length){
      rows.push([tds[0].innerText, tds[1].innerText, tds[2].innerText, tds[3].innerText, tds[4].innerText, tds[5].innerText]);
    }
  });
  const csv = rows.map(r => r.map(c => `"${String(c).replace(/"/g,'""')}"`).join(',')).join('\n');
  const blob = new Blob([csv], {type:'text/csv'});
  const a = document.createElement('a'); a.href = URL.createObjectURL(blob); a.download = 'fesco_bills.csv'; a.click();
}

// print a single bill
function printBillRow(billId){
  const tr = Array.from(document.querySelectorAll('#billsTbody tr')).find(r => r.innerText.includes(String(billId)));
  if(!tr) return alert('Bill not found');
  const bill = JSON.parse(tr.getAttribute('data-bill'));
  const consumerName = <?php echo json_encode($consumer['name'] ?? ''); ?>;
  const consumerNumber = <?php echo json_encode($consumer['consumer_number'] ?? ''); ?>;
  const w = window.open('','_blank','width=800,height=600');
  w.document.write('<html><head><title>FESCO Bill</title><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"></head><body>');
  w.document.write(`<div class="container mt-4"><h2 class="text-center">FESCO Electricity Bill</h2><hr>`);
  w.document.write(`<p><strong>Bill ID:</strong> ${bill.id}</p>`);
  w.document.write(`<p><strong>Consumer:</strong> ${consumerName} (${consumerNumber})</p>`);
  w.document.write(`<p><strong>Date:</strong> ${bill.bill_date}</p>`);
  w.document.write(`<p><strong>Units:</strong> ${bill.units}</p>`);
  w.document.write(`<p><strong>Amount (Rs):</strong> ${bill.amount}</p>`);
  if(bill.due_date) w.document.write(`<p><strong>Due Date:</strong> ${bill.due_date}</p>`);
  w.document.write(`<hr><p class="text-center">This is a system generated bill.</p></div></body></html>`);
  w.document.close(); w.print();
}

// email modal
function openEmailPrompt(billId){
  document.getElementById('email_bill_id').value = billId;
  document.getElementById('email_to').value = '<?php echo htmlspecialchars($consumer['email'] ?? ''); ?>';
  document.getElementById('email_message').value = '';
  var emailModal = new bootstrap.Modal(document.getElementById('emailModal'));
  emailModal.show();
}

// send email
document.getElementById('sendEmailForm').addEventListener('submit', function(e){
  e.preventDefault();
  const form = this;
  const fd = new FormData(form);
  fd.append('action','send_bill');
  document.getElementById('emailResult').innerHTML = 'Sending...';
  fetch('dashboard.php', {method:'POST', body: fd})
    .then(r => r.json())
    .then(j => {
      document.getElementById('emailResult').innerHTML = j.ok ? '<div class="alert alert-success">Sent</div>' : '<div class="alert alert-danger">'+(j.error||'Failed')+'</div>';
    }).catch(err => {
      document.getElementById('emailResult').innerHTML = '<div class="alert alert-danger">Error</div>';
    });
});

// profile update
const profileForm = document.getElementById('profileForm');
if(profileForm){
  profileForm.addEventListener('submit', function(e){
    e.preventDefault();
    const fd = new FormData(this);
    fd.append('action','update_profile');
    fetch('dashboard.php',{method:'POST', body:fd}).then(r => r.json()).then(j => {
      const msg = document.getElementById('profileMsg');
      if(j.ok){ msg.innerHTML = '<div class="alert alert-success">Saved. Reloading...</div>'; setTimeout(()=>location.reload(),800); }
      else msg.innerHTML = '<div class="alert alert-danger">'+(j.error||'Failed')+'</div>';
    }).catch(err => { document.getElementById('profileMsg').innerHTML = '<div class="alert alert-danger">Error</div>'; });
  });
}

// chart
<?php if($logged): ?>
const months = <?php echo json_encode($months); ?>;
const values = <?php echo json_encode($values); ?>;
if(document.getElementById('usageChart')) {
  new Chart(document.getElementById('usageChart'), {
    type: 'bar',
    data: { labels: months, datasets: [{ label: 'Revenue (Rs)', data: values }] },
    options: { responsive:true, maintainAspectRatio:false }
  });
}
<?php endif; ?>
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
