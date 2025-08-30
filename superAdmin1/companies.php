<?php

require __DIR__ . '/vendor/autoload.php';
use Google\Cloud\Firestore\FirestoreClient;

$db = new FirestoreClient([
    'projectId'   => 'safeotw1',
    'keyFilePath' => __DIR__ . '/service_account.json',
]);

// --- Add Company ---
if (isset($_POST['add_company'])) {
    $first = trim($_POST['company_head_first']);
    $middle = trim($_POST['company_head_middle']);
    $last = trim($_POST['company_head_last']);
    $companyHead = $first . (!empty($middle) ? " " . strtoupper(substr($middle, 0, 1)) . "." : "") . " " . $last;

    $street   = trim($_POST['companyStreet']);
    $barangay = trim($_POST['companyBarangay']);
    $city     = trim($_POST['companyCity']);
    $province = trim($_POST['companyProvince']);
    $addressParts = array_filter([$street, $barangay, $city, $province]);
    $companyAddress = implode(", ", $addressParts);

    $db->collection('companies')->add([
        'company'         => $_POST['company'],
        'companyHead'     => $companyHead,
        'companyContact'  => $_POST['companyContact'],
        'companyAddress'  => $companyAddress,
        'companyStartDate'=> $_POST['companyStartDate']
    ]);
    header("Location: companies.php"); exit;
}

// --- Edit Company ---
if (isset($_POST['edit_company'])) {
    $docId = $_POST['company_id'];
    $first = trim($_POST['company_head_first']);
    $middle = trim($_POST['company_head_middle']);
    $last = trim($_POST['company_head_last']);
    $companyHead = $first . (!empty($middle) ? " " . strtoupper(substr($middle, 0, 1)) . "." : "") . " " . $last;

    $street   = trim($_POST['companyStreet']);
    $barangay = trim($_POST['companyBarangay']);
    $city     = trim($_POST['companyCity']);
    $province = trim($_POST['companyProvince']);
    $addressParts = array_filter([$street, $barangay, $city, $province]);
    $companyAddress = implode(", ", $addressParts);

    $db->collection('companies')->document($docId)->set([
        'company'         => $_POST['company'],
        'companyHead'     => $companyHead,
        'companyContact'  => $_POST['companyContact'],
        'companyAddress'  => $companyAddress,
        'companyStartDate'=> $_POST['companyStartDate']
    ]);
    header("Location: companies.php"); exit;
}

// --- Delete Company ---
if (isset($_POST['delete_company'])) {
    $docId = $_POST['company_id'];
    $db->collection('companies')->document($docId)->delete();
    header("Location: companies.php"); exit;
}

// --- Fetch Companies with Search & Filter ---
$companies = [];
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'latest';

$query = $db->collection('companies');
if ($filter === 'latest') {
    $query = $query->orderBy('companyStartDate', 'DESCENDING');
} elseif ($filter === 'earliest') {
    $query = $query->orderBy('companyStartDate', 'ASCENDING');
} else {
    $query = $query->orderBy('company');
}
$documents = $query->documents();

foreach ($documents as $doc) {
    if ($doc->exists()) {
        $data = array_merge($doc->data(), ['company_id' => $doc->id()]);
        if ($search === '' || stripos($data['company'], $search) !== false || stripos($data['companyHead'], $search) !== false) {
            $companies[] = $data;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Companies</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<style>
    body { background: #FFF8E1; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #1A1A1A; font-size: 1.1rem; }
    .sidebar { position: fixed; top: 0; left: 0; height: 100%; width: 90px; background: #1A1A1A; padding-top: 20px; display: flex; flex-direction: column; align-items: center; box-shadow: 2px 0 8px rgba(0,0,0,0.2); z-index: 1000;}
    .logo-circle { width: 60px; height: 60px; border-radius: 50%; object-fit: cover; border: 3px solid #a8180aff; margin-bottom: 40px; box-shadow: 0 4px 6px rgba(0,0,0,0.3); }
    .sidebar .nav-link { color: #be281aff; font-size: 1.8rem; margin: 18px 0; transition: 0.3s; display: flex; justify-content: center; width: 100%; }
    .sidebar .nav-link:hover, .sidebar .nav-link.active { color: #FFC107; transform: scale(1.2); }
    .main-content { margin-left: 90px; padding: 30px; }
    .page-header { display: flex; justify-content: center; align-items: center; margin-bottom: 20px; font-size: 1.5rem; }
    .page-title {font-size: 1.8rem; font-weight: bold; margin: 0; text-align: center;}
    .page-title::after { content: ""; display: block; width: 120px; height: 5px; background: #a8180aff; margin: 10px auto 0 auto; border-radius: 12px; }
    .search-container { position: relative; width: 400px; }
    .search-container i { position: absolute; top: 50%; right: 15px; transform: translateY(-50%); color: #888; font-size: 1.2rem; }
    .search-input { border-radius: 50px; padding-right: 40px; width: 100%; }
    .add-company-sort { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; font-size: 24px;}
    .right-controls { display: flex; align-items: center; gap: 10px; }
    .dropdown-toggle-icon { color: #726868ff; border: none; background: none; display: flex; align-items: center; justify-content: center; cursor: pointer; }
    .dropdown-menu { border-radius: 12px; overflow: hidden; }
    .dropdown-menu .dropdown-item:hover { background: #f8df55; color: #000; }
    .card { border-radius: 20px; box-shadow: 0 6px 15px rgba(0,0,0,0.1); padding: 20px; background: #fff; }
    .table { border-collapse: separate; border-spacing: 0 10px; font-size: 1.05rem; }
    .table thead { background: #f8df55; color: #000; font-size: 1.1rem; }
    .table thead th { padding: 14px 16px; text-align: center; }
    .table tbody tr { background: #fff; border-radius: 12px; box-shadow: 0 2px 6px rgba(0,0,0,0.05); transition: transform 0.2s ease, box-shadow 0.2s ease; }
    .table tbody td { font-size: 1.0rem; padding: 14px 16px; vertical-align: middle; }
    .table tbody tr:hover { 
        background: #fff9e6; 
        transform: translateY(-4px); 
        box-shadow: 0 6px 15px #dda80bac;
    }
    .btn-theme { background: #a8180aff; border: none; border-radius: 30px; padding: 10px 24px; font-weight: 600; color: #FFF; font-size: 0.95rem; }
    .btn-theme:hover { background: #FFC107; color: #000; }
    .btn-warning, .btn-danger, .btn-light { border-radius: 30px; font-size: 0.9rem; padding: 6px 12px; margin: 0 3px; }
    .btn i { font-size: 1rem; }
    .modal-content { border-radius: 15px; font-size: 1.05rem; }
    .form-label { font-weight: 700; color: #000; font-size: 1rem; }
    .sub-label { font-size: 0.85rem; color: #666; margin-bottom: 3px; display: block; }
    .modal-lg { max-width: 750px; }
    .form-control { padding: 8px; font-size: 0.95rem; }
    .modal-header { background: linear-gradient(135deg, #a8180aff); color: #fff; }
    .text-danger { font-weight: bold; }
    .modal-title { font-weight: 700; }
    .logout-btn {margin-top: auto;margin-bottom: 20px;font-size: 1.8rem;color: #be281aff;transition: 0.3s;}
    .logout-btn:hover {color: #FFC107;transform: scale(1.2);}
    .table th:last-child,
    .table td:last-child {width: 140px;text-align: center;white-space: nowrap;}

    /* ✅ FIX: Allow wrapping in address column */
    .table td:nth-child(4) {
        white-space: normal;
        word-wrap: break-word;
        overflow-wrap: anywhere;
    }
</style>
</head>
<body>

<div class="sidebar">
<img src="logo1.png" alt="Logo" class="logo-circle">
<a href="dashboard.php" class="nav-link" title="Dashboard"><i class="bi bi-house"></i></a>
<a href="companies.php" class="nav-link active" title="Companies"><i class="bi bi-buildings"></i></a>
<a href="traffic_signs.php" class="nav-link" title="Traffic Signs"><i class="bi bi-signpost-split"></i></a>
<a href="users.php" class="nav-link" title="Users"><i class="bi bi-people"></i></a>
<a href="students.php" class="nav-link" title="Students"><i class="bi bi-mortarboard"></i></a>
<a href="login.php" class="nav-link logout-btn" title="Logout"><i class="bi bi-box-arrow-right"></i></a>
</div>

<div class="main-content">
<div class="page-header">
    <h2 class="page-title">Companies</h2>
</div>

<div class="add-company-sort">
    <div class="add-company-btn">
        <button class="btn btn-theme" data-bs-toggle="modal" data-bs-target="#addCompanyModal">+ Add Company</button>
    </div>
    <div class="right-controls">
        <!-- Search Bar moved here -->
        <form class="search-container" method="GET">
            <input type="text" name="search" class="form-control search-input" placeholder="Search..." value="<?= htmlspecialchars($search) ?>">
            <i class="bi bi-search"></i>
        </form>
        <!-- Sort Dropdown -->
        <div class="dropdown">
            <button class="dropdown-toggle-icon" id="sortDropdown" data-bs-toggle="dropdown" aria-expanded="false" title="Sort">
                <i class="bi bi-arrow-down-up"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="sortDropdown">
                <li><a class="dropdown-item" href="?filter=latest&search=<?= urlencode($search) ?>">Latest</a></li>
                <li><a class="dropdown-item" href="?filter=earliest&search=<?= urlencode($search) ?>">Earliest</a></li>
                <li><a class="dropdown-item" href="?filter=name&search=<?= urlencode($search) ?>">Company (A-Z)</a></li>
            </ul>
        </div>
    </div>
</div>

<div class="card">
<div class="table-responsive">
<table class="table align-middle">
<thead>
<tr><th>Company</th><th>Company Head</th><th>Contact</th><th>Address</th><th>Start Date</th><th class="text-center">Action</th></tr>
</thead>
<tbody>
<?php 
$modals = "";
if ($companies):
foreach ($companies as $c):
$parts = explode(" ", $c['companyHead']);
$first = $parts[0] ?? '';
$middle = (isset($parts[1]) && str_ends_with($parts[1], ".")) ? rtrim($parts[1], ".") : '';
$last = $middle ? ($parts[2] ?? '') : ($parts[1] ?? '');

$addrParts = array_map('trim', explode(",", $c['companyAddress']));
$street = $barangay = $city = $province = '';
if (count($addrParts) === 4) {
    [$street, $barangay, $city, $province] = $addrParts;
} elseif (count($addrParts) === 3) {
    [$barangay, $city, $province] = $addrParts;
} elseif (count($addrParts) === 2) {
    [$city, $province] = $addrParts;
} elseif (count($addrParts) === 1) {
    $province = $addrParts[0];
}

$modalIdSafe = preg_replace('/[^A-Za-z0-9_-]/', '', $c['company_id']);
?>
<tr>
<td><?= htmlspecialchars($c['company']) ?></td>
<td><?= htmlspecialchars($c['companyHead']) ?></td>
<td><?= htmlspecialchars($c['companyContact']) ?></td>
<td><?= htmlspecialchars($c['companyAddress']) ?></td>
<td><?= htmlspecialchars($c['companyStartDate']) ?></td>
<td class="text-center">
<button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editCompanyModal<?= $modalIdSafe ?>" title="Edit"><i class="bi bi-pencil-square"></i></button>
<button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteCompanyModal<?= $modalIdSafe ?>" title="Delete"><i class="bi bi-trash"></i></button>
</td>
</tr>
<?php ob_start(); ?>
<!-- Edit Modal -->
<div class="modal fade" id="editCompanyModal<?= $modalIdSafe ?>" tabindex="-1">
<div class="modal-dialog modal-lg">
<div class="modal-content">
<form method="POST">
<div class="modal-header"><h5 class="modal-title">Edit Company</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body row g-2">
<input type="hidden" name="company_id" value="<?= $c['company_id'] ?>">
<div class="col-12"><label class="form-label">Company Name <span class="text-danger">*</span></label><input type="text" name="company" class="form-control" value="<?= htmlspecialchars($c['company']) ?>" required></div>
<div class="col-12"><label class="form-label">Company Head <span class="text-danger">*</span></label>
<div class="row g-2">
<div class="col-md-4"><span class="sub-label">First Name *</span><input type="text" name="company_head_first" class="form-control" value="<?= htmlspecialchars($first) ?>" required></div>
<div class="col-md-2"><span class="sub-label">M.I.</span><input type="text" name="company_head_middle" maxlength="1" class="form-control" value="<?= htmlspecialchars($middle) ?>"></div>
<div class="col-md-6"><span class="sub-label">Last Name *</span><input type="text" name="company_head_last" class="form-control" value="<?= htmlspecialchars($last) ?>" required></div>
</div>
</div>
<div class="col-md-6"><label class="form-label">Contact <span class="text-danger">*</span></label><input type="text" name="companyContact" class="form-control" value="<?= htmlspecialchars($c['companyContact']) ?>" required></div>
<div class="col-12"><label class="form-label">Address <span class="text-danger">*</span></label></div>
<div class="col-md-6"><span class="sub-label">Street</span><input type="text" name="companyStreet" class="form-control" value="<?= htmlspecialchars($street) ?>"></div>
<div class="col-md-6"><span class="sub-label">Barangay *</span><input type="text" name="companyBarangay" class="form-control" value="<?= htmlspecialchars($barangay) ?>" required></div>
<div class="col-md-6"><span class="sub-label">City *</span><input type="text" name="companyCity" class="form-control" value="<?= htmlspecialchars($city) ?>" required></div>
<div class="col-md-6"><span class="sub-label">Province *</span><input type="text" name="companyProvince" class="form-control" value="<?= htmlspecialchars($province) ?>" required></div>
<div class="col-md-6"><label class="form-label">Start Date <span class="text-danger">*</span></label><input type="date" name="companyStartDate" class="form-control" value="<?= htmlspecialchars($c['companyStartDate']) ?>" required></div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button type="submit" name="edit_company" class="btn btn-warning">Update</button></div>
</form>
</div>
</div>
</div>
<!-- Delete Modal -->
<div class="modal fade" id="deleteCompanyModal<?= $modalIdSafe ?>" tabindex="-1">
<div class="modal-dialog">
<div class="modal-content">
<form method="POST">
<div class="modal-header bg-danger text-white"><h5 class="modal-title">Delete Company</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
<div class="modal-body"><p>Are you sure you want to delete <strong><?= htmlspecialchars($c['company']) ?></strong>?</p><input type="hidden" name="company_id" value="<?= $c['company_id'] ?>"></div>
<div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button type="submit" name="delete_company" class="btn btn-danger">Delete</button></div>
</form>
</div>
</div>
</div>
<?php $modals .= ob_get_clean(); endforeach; else: ?>
<tr><td colspan="6" class="text-center text-muted">No companies found.</td></tr>
<?php endif; ?>
</tbody>
</table>
<?= $modals ?>
</div>
</div>
</div>
<!-- Add Company Modal -->
<div class="modal fade" id="addCompanyModal" tabindex="-1">
<div class="modal-dialog modal-lg">
<div class="modal-content">
<form method="POST">
<div class="modal-header"><h5 class="modal-title">Add New Company</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body row g-2">
<div class="col-12"><label class="form-label">Company Name <span class="text-danger">*</span></label><input type="text" name="company" class="form-control" required></div>
<div class="col-12"><label class="form-label">Company Head <span class="text-danger">*</span></label>
<div class="row g-2">
<div class="col-md-4"><span class="sub-label">First Name *</span><input type="text" name="company_head_first" class="form-control" required></div>
<div class="col-md-2"><span class="sub-label">M.I.</span><input type="text" name="company_head_middle" maxlength="1" class="form-control"></div>
<div class="col-md-6"><span class="sub-label">Last Name *</span><input type="text" name="company_head_last" class="form-control" required></div>
</div>
</div>
<div class="col-md-6"><label class="form-label">Contact <span class="text-danger">*</span></label><input type="text" name="companyContact" class="form-control" required></div>
<div class="col-12"><label class="form-label">Address <span class="text-danger">*</span></label></div>
<div class="col-md-6"><span class="sub-label">Street</span><input type="text" name="companyStreet" class="form-control"></div>
<div class="col-md-6"><span class="sub-label">Barangay *</span><input type="text" name="companyBarangay" class="form-control" required></div>
<div class="col-md-6"><span class="sub-label">City *</span><input type="text" name="companyCity" class="form-control" required></div>
<div class="col-md-6"><span class="sub-label">Province *</span><input type="text" name="companyProvince" class="form-control" required></div>
<div class="col-md-6"><label class="form-label">Start Date <span class="text-danger">*</span></label><input type="date" name="companyStartDate" class="form-control" required></div>
</div>
<div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button type="submit" name="add_company" class="btn btn-warning">Save</button></div>
</form>
</div>
</div>
</div>
<script>
document.addEventListener("DOMContentLoaded", function(){
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
    tooltipTriggerList.map(el => new bootstrap.Tooltip(el));
});
</script>
</body>
</html>
