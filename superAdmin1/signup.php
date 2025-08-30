<?php
require __DIR__ . '/vendor/autoload.php';

use Google\Cloud\Firestore\FirestoreClient;

// 🔑 Firestore connection
$db = new FirestoreClient([
    'projectId'   => 'safeotw1',
    'keyFilePath' => __DIR__ . '/service_account.json'
]);

$message = "";

// Handle form submit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $lastName   = trim($_POST['last_name']);
    $firstName  = trim($_POST['first_name']);
    $middleName = trim($_POST['middle_name']);
    $userNo     = trim($_POST['user_no']);
    $password   = trim($_POST['password']);
    $role       = $_POST['role'];
    $company    = isset($_POST['company']) ? $_POST['company'] : "";

    // Validate required fields
    if (!empty($lastName) && !empty($firstName) && !empty($userNo) && !empty($password) && !empty($role)) {

        // ✅ If Admin or Instructor → Company required
        if (($role === "Admin" || $role === "Instructor") && empty($company)) {
            $message = "⚠️ Company is required for Admin and Instructor roles.";
        } else {
            try {
                // Save to Firestore "users" collection
                $docRef = $db->collection('users')->add([
                    'userLastName'   => $lastName,
                    'userFirstName'  => $firstName,
                    'userMiddleName' => $middleName,
                    'userNo'         => $userNo,
                    'userPassword'   => password_hash($password, PASSWORD_DEFAULT), // 🔐 hashed
                    'userRole'       => $role,
                    'company'        => $company,
                    'userStatus'     => 'Active',
                    'created_at'     => date("Y-m-d H:i:s"),
                    'updated_at'     => date("Y-m-d H:i:s")
                ]);

                // ✅ Redirect to login after success
                header("Location: login.php?registered=1");
                exit;
            } catch (Exception $e) {
                $message = "❌ Error: " . $e->getMessage();
            }
        }
    } else {
        $message = "⚠️ Please fill out all required fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Sign Up - SafeOTW</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, #ffffffff 0%, #ffffffff 100%);
      height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .card {
      border-radius: 20px;
      backdrop-filter: blur(12px);
      background: linear-gradient(#FFF8E1, #ffe082);
      box-shadow: 0 8px 32px rgba(107, 17, 7, 0.41);
      color: #760f0fff;
      padding: 40px;
      width: 100%;
      max-width: 700px;
    }
    .card h2 {
      font-weight: 700;
      text-align: center;
      margin-bottom: 30px;
    }
    label {
      font-weight: 500;
      color: #646161ff;
    }
    .form-control, .form-select {
      border-radius: 12px;
      padding: 12px;
    }
    .btn-custom {
      border-radius: 12px;
      padding: 12px;
      font-weight: 600;
      background: linear-gradient(135deg, #FFC107 0%, #a8180aff 100%);
      border: none;
      color: #fff;
      transition: 0.3s ease-in-out;
    }
    .btn-custom:hover {
      transform: scale(1.05);
      background: linear-gradient(135deg, #a8180aff 0%, #FFC107 100%);
    }
    .link-text {
      margin-top: 15px;
      text-align: center;
    }
    .link-text a {
      color: #760f0fff;
      font-weight: 600;
      text-decoration: none;
    }
    .link-text a:hover {
      text-decoration: underline;
    }
     select.form-select option {
    background-color: #f6f0deff;  /* dropdown background */
    color: #514c4aff;             /* text color */
    font-weight: 500;
    }

    select.form-select option:checked {
    background-color: #ffc1075f; !important; /* selected highlight */
    color: #000 !important;
    }

    select.form-select option:hover,
    select.form-select option:focus {
    background-color: #760f0fff !important;
    color: #ffffffff !important;
    }
  </style>
  <script>
    // Show/hide company field based on role
    function toggleCompany() {
      var role = document.getElementById("role").value;
      var companyDiv = document.getElementById("companyDiv");
      var companySelect = document.getElementById("company");

      if (role === "Admin" || role === "Instructor") {
        companyDiv.style.display = "block";
        companySelect.setAttribute("required", "required"); // ✅ required
      } else {
        companyDiv.style.display = "none";
        companySelect.removeAttribute("required"); // ❌ not required
      }
    }
  </script>
</head>
<body>
<div class="card">
  <h2>Create Your Account</h2>

  <?php if (!empty($message)): ?>
    <div class="alert alert-warning text-dark"><?php echo $message; ?></div>
  <?php endif; ?>

  <form method="POST">
    <div class="row mb-3">
      <div class="col">
        <label>Last Name <span style="color:red">*</span></label>
        <input type="text" name="last_name" class="form-control" required>
      </div>
      <div class="col">
        <label>First Name <span style="color:red">*</span></label>
        <input type="text" name="first_name" class="form-control" required>
      </div>
      <div class="col">
        <label>Middle Name</label>
        <input type="text" name="middle_name" class="form-control">
      </div>
    </div>

    <div class="mb-3">
      <label>User No. <span style="color:red">*</span></label>
      <input type="text" name="user_no" class="form-control" required>
    </div>

    <div class="mb-3">
      <label>Password <span style="color:red">*</span></label>
      <input type="password" name="password" class="form-control" required>
    </div>

    <div class="row mb-3">
      <div class="col">
        <label>Role <span style="color:red">*</span></label>
        <select name="role" id="role" class="form-select" onchange="toggleCompany()" required>
          <option value="">-- Select Role --</option>
          <option value="Super Admin">Super Admin</option>
          <option value="Admin">Admin</option>
          <option value="Instructor">Instructor</option>
        </select>
      </div>
      <div class="col" id="companyDiv" style="display:none;">
        <label>Company <span style="color:red">*</span></label>
        <select name="company" id="company" class="form-select">
          <option value="">-- Select Company --</option>
          <option value="JYBG">JYBG</option>
          <option value="St. Peter Velle">St. Peter Velle</option>
        </select>
      </div>
    </div>

    <button type="submit" class="btn btn-custom w-100">🚀 Register Now</button>
  </form>

  <div class="link-text">
    <p>Already have an account? <a href="login.php">Login here</a></p>
  </div>
</div>
</body>
</html>
