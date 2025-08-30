<?php
require __DIR__ . '/vendor/autoload.php';
use Google\Cloud\Firestore\FirestoreClient;

$db = new FirestoreClient([
    'projectId'   => 'safeotw1',
    'keyFilePath' => __DIR__ . '/service_account.json',
]);

// --- Fetch Users with Search, Filter & Sort ---
$users   = [];
$search  = isset($_GET['search']) ? trim($_GET['search']) : '';
$role    = isset($_GET['role']) ? $_GET['role'] : '';
$company = isset($_GET['company']) ? $_GET['company'] : '';
$status  = isset($_GET['status']) ? $_GET['status'] : '';

// Default query (no filters yet until Apply button clicked)
$query = $db->collection('users')->orderBy('userLastName');

// Fetch documents
$documents = $query->documents();

foreach ($documents as $doc) {
    if ($doc->exists()) {
        $data = $doc->data();
        $userFullName = trim(
            ($data['userFirstName'] ?? '') . ' ' .
            (($data['userMiddleName'] ?? '') ? strtoupper(substr($data['userMiddleName'], 0, 1)) . ". " : '') .
            ($data['userLastName'] ?? '')
        );
        $data['userFullName'] = $userFullName;
        $data['user_id'] = $doc->id();

        $includeUser = true;

        // Only apply filters if Apply button was clicked
        if (isset($_GET['apply'])) {
            // Search
            $matchesSearch = (
                $search === '' ||
                stripos($userFullName, $search) !== false ||
                stripos($data['company'] ?? '', $search) !== false ||
                stripos($data['userRole'] ?? '', $search) !== false ||
                stripos($data['userStatus'] ?? '', $search) !== false
            );

            // Role
            $matchesRole = ($role === '' || strcasecmp($data['userRole'] ?? '', $role) === 0);

            // Company (always visible, only filter if not empty)
            $matchesCompany = ($company === '' || (isset($data['company']) && strcasecmp($data['company'], $company) === 0));

            // Status
            $matchesStatus = ($status === '' || strcasecmp($data['userStatus'] ?? '', $status) === 0);

            $includeUser = $matchesSearch && $matchesRole && $matchesCompany && $matchesStatus;
        }

        if ($includeUser) {
            $users[] = $data;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Users</title>
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
    .page-header { display: flex; justify-content: center; align-items: center; margin-bottom: 30px; font-size: 1.5rem; }
    .page-title {font-size: 1.8rem; font-weight: bold; margin: 0; text-align: center;}
    .page-title::after { content: ""; display: block; width: 100px; height: 5px; background: #a8180aff; margin: 10px auto 0 auto; border-radius: 12px; }
    .search-container { position: relative; width: 350px; }
    .search-container i { position: absolute; top: 50%; right: 15px; transform: translateY(-50%); color: #888; font-size: 1.2rem; }
    .search-input { border-radius: 50px; padding-right: 40px; width: 100%; height: calc(2.25rem + 2px) !important; font-size: 0.85rem; }
    .add-company-sort { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; font-size: 18px; }
    .filters-left { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; font-size: 0.9rem; }
    .filters-left select { font-size: 0.85rem; padding: 4px 8px; border-radius: 8px; width: 120px; }
    .filter-label { font-weight: 500; margin-right: 4px; color: #a8180aff; font-size: 0.9rem; }
    .right-controls { display: flex; align-items: center; gap: 10px; }
    .card { 
        border-radius: 20px; 
        box-shadow: 0 6px 15px rgba(0,0,0,0.1); 
        padding: 20px; 
        background: #fff; 
        position: relative; 
        overflow: hidden;
    }
    .table { border-collapse: separate; border-spacing: 0 10px; font-size: 1.05rem; }
    .table thead { background: #f8df55; color: #000; font-size: 1.1rem; }
    .table thead th { padding: 14px 16px; text-align: center; }
    .table tbody tr { 
        background: #fff; 
        border-radius: 12px; 
        box-shadow: 0 2px 6px rgba(0,0,0,0.05); 
        transition: all 0.3s ease;
    }
    .table tbody td { font-size: 1.0rem; padding: 14px 16px; vertical-align: middle; text-align: center; }
    /* 🔹 Hover only per row */
    .table tbody tr:hover { 
        background: #fff9e6; 
        transform: translateY(-4px); 
        box-shadow: 0 6px 15px #dda80bac;
    }
    .btn-theme { background: #a8180aff; border: none; border-radius: 20px; padding: 6px 16px; font-weight: 600; color: #FFF; font-size: 0.85rem; }
    .btn-theme:hover { background: #FFC107; color: #000; }
    .logout-btn {margin-top: auto;margin-bottom: 20px;font-size: 1.8rem;color: #be281aff;transition: 0.3s;}
    .logout-btn:hover {color: #FFC107;transform: scale(1.2);}
</style>
</head>
<body>

<div class="sidebar">
<img src="logo1.png" alt="Logo" class="logo-circle">
<a href="dashboard.php" class="nav-link" title="Dashboard"><i class="bi bi-house"></i></a>
<a href="companies.php" class="nav-link" title="Companies"><i class="bi bi-buildings"></i></a>
<a href="traffic_signs.php" class="nav-link" title="Traffic Signs"><i class="bi bi-signpost-split"></i></a>
<a href="users.php" class="nav-link active" title="Users"><i class="bi bi-people"></i></a>
<a href="students.php" class="nav-link" title="Students"><i class="bi bi-mortarboard"></i></a>
<a href="login.php" class="nav-link logout-btn" title="Logout"><i class="bi bi-box-arrow-right"></i></a>
</div>

<div class="main-content">
<div class="page-header">
    <h2 class="page-title">Users</h2>
</div>

<div class="add-company-sort">
    <!-- Filters & Sorting Left -->
    <div class="filters-left">
        <form method="GET" class="d-flex flex-wrap align-items-center gap-2">
            <!-- Company -->
            <div class="d-flex align-items-center">
                <span class="filter-label">Company:</span>
                <select name="company" class="form-select">
                    <option value="" <?= $company === '' ? 'selected' : '' ?>>All</option>
                    <option value="JYBG Technical Vocational Training and Assessment Center, Inc." <?= $company === 'JYBG Technical Vocational Training and Assessment Center, Inc.' ? 'selected' : '' ?>>JYBG</option>
                    <option value="St. Peter Velle Technical Training Center, Inc." <?= $company === 'St. Peter Velle Technical Training Center, Inc.' ? 'selected' : '' ?>>St.Peter Velle</option>
                </select>
            </div>
            <!-- Role -->
            <div class="d-flex align-items-center">
                <span class="filter-label">Role:</span>
                <select name="role" class="form-select">
                    <option value="" <?= $role === '' ? 'selected' : '' ?>>All</option>
                    <option value="Super Admin" <?= $role === 'Super Admin' ? 'selected' : '' ?>>Super Admin</option>
                    <option value="Admin" <?= $role === 'Admin' ? 'selected' : '' ?>>Admin</option>
                    <option value="Instructor" <?= $role === 'Instructor' ? 'selected' : '' ?>>Instructor</option>
                </select>
            </div>
            <!-- Status -->
            <div class="d-flex align-items-center">
                <span class="filter-label">Status:</span>
                <select name="status" class="form-select">
                    <option value="" <?= $status === '' ? 'selected' : '' ?>>All</option>
                    <option value="Active" <?= $status === 'Active' ? 'selected' : '' ?>>Active</option>
                    <option value="Inactive" <?= $status === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
            <!-- Apply -->
            <button type="submit" name="apply" value="1" class="btn btn-theme">Apply</button>
        </form>
    </div>

    <!-- Search Right -->
    <div class="right-controls">
        <form method="GET" class="d-flex align-items-center">
            <div class="search-container">
                <input type="text" name="search" class="form-control search-input" 
                       placeholder="Search..." value="<?= htmlspecialchars($search) ?>">
                <i class="bi bi-search"></i>
            </div>
        </form>
    </div>
</div>

<div class="card">
<div class="table-responsive">
<table class="table align-middle">
<thead>
<tr>
    <th>Name</th>
    <th>Company</th>
    <th>Role</th>
    <th>Status</th>
</tr>
</thead>
<tbody>
<?php if ($users): ?>
    <?php foreach ($users as $u): ?>
        <tr>
            <td><?= htmlspecialchars($u['userFullName']) ?></td>
            <td><?= htmlspecialchars($u['company'] ?? 'N/A') ?></td>
            <td><?= htmlspecialchars($u['userRole'] ?? 'N/A') ?></td>
            <td><?= htmlspecialchars($u['userStatus'] ?? 'N/A') ?></td>
        </tr>
    <?php endforeach; ?>
<?php else: ?>
    <tr><td colspan="4" class="text-center">No users found</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
</div>

</div>
</body>
</html>
