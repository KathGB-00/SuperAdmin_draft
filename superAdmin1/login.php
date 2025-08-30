<?php
session_start(); // ✅ session must start at the very top
require __DIR__ . '/vendor/autoload.php';

use Google\Cloud\Firestore\FirestoreClient;

// 🔑 Firestore connection
$db = new FirestoreClient([
    'projectId'   => 'safeotw1',
    'keyFilePath' => __DIR__ . '/service_account.json'
]);

$message = "";

// Handle login submit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userNo   = trim($_POST['user_no']);
    $password = trim($_POST['password']);

    if (!empty($userNo) && !empty($password)) {
        try {
            // Query Firestore users collection
            $query = $db->collection('users')->where('userNo', '=', $userNo)->documents();

            if ($query->isEmpty()) {
                $message = "❌ User not found.";
            } else {
                foreach ($query as $doc) {
                    $user = $doc->data();

                    // safety check
                    if (!isset($user['userPassword'])) {
                        $message = "⚠️ Password not set for this account.";
                        break;
                    }

                    if (password_verify($password, $user['userPassword'])) {
                        // ✅ Login success
                        $_SESSION['userNo']   = $user['userNo'] ?? "";
                        $_SESSION['userRole'] = $user['userRole'] ?? "";
                        $_SESSION['company']  = $user['company'] ?? "";

                        // 🔀 Redirect based on role + company
                        if ($user['userRole'] === "Super Admin") {
                            header("Location: dashboard.php");
                        } elseif ($user['userRole'] === "Admin" || $user['userRole'] === "Instructor") {
                            if ($user['company'] === "JYBG") {
                                header("Location: jybg_company.php");
                            } elseif ($user['company'] === "St. Peter Ville") {
                                header("Location: stpeter_company.php");
                            } else {
                                header("Location: dashboard.php"); // fallback
                            }
                        } else {
                            header("Location: dashboard.php"); // fallback
                        }
                        exit;
                    } else {
                        $message = "❌ Incorrect password.";
                    }
                }
            }
        } catch (Exception $e) {
            $message = "❌ Error: " . $e->getMessage();
        }
    } else {
        $message = "⚠️ Please fill in all fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login Form</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(135deg, #ffffff, #ffffff);
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
    }
    .login-card {
      background: linear-gradient(#FFF8E1, #ffe082);
      backdrop-filter: blur(15px);
      border-radius: 20px;
      padding: 40px;
      width: 100%;
      max-width: 400px;
      color: #760f0f;
      box-shadow: 0 8px 32px rgba(0,0,0,0.3);
      animation: fadeIn 1s ease;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-20px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .login-card h2 {
      text-align: center;
      margin-bottom: 20px;
      font-weight: bold;
    }
    .form-control {
      background: #fff8e1;
      border: 1px solid #ccc;
      color: #333;
      border-radius: 10px;
      padding: 10px;
    }
    .form-control:focus {
      background: #fff3cd;
      border-color: #FFC107;  /* ✅ fixed */
      box-shadow: 0 0 5px rgba(168, 24, 10, 0.5);
      outline: none;
    }
    .form-control::placeholder {
      color: #999;
    }
    .btn-custom {
      background: linear-gradient(135deg, #a8180a, #FFC107);
      border: none;
      padding: 10px;
      font-weight: bold;
      border-radius: 10px;
      transition: 0.3s;
      color: #fff;
    }
    .btn-custom:hover {
      transform: scale(1.05);
      background: linear-gradient(135deg, #FFC107, #a8180a);
    }
    .register-link {
      margin-top: 15px;
      text-align: center;
      color: #a8180a;
    }
    .register-link a {
      color: #a8180a;
      font-weight: bold;
      text-decoration: none;
    }
    .register-link a:hover {
      text-decoration: underline;
    }
    .alert {
      border-radius: 10px;
      text-align: center;
    }
    label {
      font-weight: 500;
      color: #646161;
      margin-bottom: 5px;
    }
  </style>
</head>
<body>
  <div class="login-card">
    <h2>Login</h2>
    <?php if (!empty($message)): ?>
      <div class="alert alert-warning"><?php echo $message; ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="mb-3">
        <label>User No. <span style="color:red">*</span></label>
        <input type="text" name="user_no" class="form-control" required>
      </div>
      <div class="mb-3">
        <label>Password <span style="color:red">*</span></label>
        <input type="password" name="password" class="form-control" required>
      </div>
      <button type="submit" class="btn btn-custom w-100">Login</button>
    </form>

    <div class="register-link">
      <p>Don't have an account yet? <a href="signup.php">Register here</a></p>
    </div>
  </div>
</body>
</html>
