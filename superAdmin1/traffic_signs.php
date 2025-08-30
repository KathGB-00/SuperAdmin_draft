<?php
require __DIR__ . '/vendor/autoload.php';
use Google\Cloud\Firestore\FirestoreClient;

$db = new FirestoreClient([
    'projectId'   => 'safeotw1',
    'keyFilePath' => __DIR__ . '/service_account.json',
]);

$trafficSignsRef = $db->collection('trafficSigns');

// Handle add sign form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['addSign'])) {
    $category = $_POST['category'] ?? 'Uncategorized';
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $imageUrl = '';

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . "/uploads/";
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $filename = uniqid() . "_" . basename($_FILES['image']['name']);
        $targetPath = $uploadDir . $filename;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
            $imageUrl = "uploads/" . $filename;
        }
    }

    $trafficSignsRef->add([
        'category' => $category,
        'name' => $name,
        'description' => $description,
        'imageUrl' => $imageUrl,
    ]);

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle edit sign form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editSign'])) {
    $id = $_POST['id'] ?? '';
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $category = $_POST['category'] ?? '';
    $imageUrl = $_POST['currentImage'] ?? '';

    if ($id) {
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . "/uploads/";
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $filename = uniqid() . "_" . basename($_FILES['image']['name']);
            $targetPath = $uploadDir . $filename;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                $imageUrl = "uploads/" . $filename;
            }
        }

        $trafficSignsRef->document($id)->set([
            'category' => $category,
            'name' => $name,
            'description' => $description,
            'imageUrl' => $imageUrl,
        ], ['merge' => true]);
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
// Handle delete sign
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deleteSign'])) {
    $id = $_POST['id'] ?? '';
    if ($id) {
        $trafficSignsRef->document($id)->delete();
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
$signsByCategory = [];
try {
    $documents = $trafficSignsRef->documents();
    foreach ($documents as $doc) {
        if ($doc->exists()) {
            $data = $doc->data();
            $category = $data['category'] ?? 'Uncategorized';
            $signsByCategory[$category][] = [
                'id'          => $doc->id(),
                'name'        => $data['name'] ?? '',
                'description' => $data['description'] ?? '',
                'imageUrl'    => $data['imageUrl'] ?? '',
                'category'    => $category,
            ];
        }
    }
} catch (Exception $e) {
    $signsByCategory = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Traffic Signs</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
  <style>
    html, body {height: 100%;margin: 0;padding: 0;}

    body {
    background: linear-gradient(135deg, #FFF8E1, #ffe082);
    background-repeat: no-repeat;
    background-attachment: fixed; /* ✅ stays fixed when scrolling */
    background-size: cover;       /* ✅ covers the entire screen */
    font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
    color: #1A1A1A;
    }

    .sidebar { position: fixed; top: 0; left: 0; height: 100%; width: 90px; background: #1A1A1A; padding-top: 20px; display: flex; flex-direction: column; align-items: center; box-shadow: 2px 0 8px rgba(0,0,0,0.2); z-index: 1000; }
    .logo-circle { width: 60px; height: 60px; border-radius: 50%; object-fit: cover; border: 3px solid #a8180aff; margin-bottom: 40px; box-shadow: 0 4px 6px rgba(0,0,0,0.3); }
    .sidebar .nav-link { color: #be281aff; font-size: 1.8rem; margin: 18px 0; transition: 0.3s; display: flex; justify-content: center; width: 100%; }
    .sidebar .nav-link:hover, .sidebar .nav-link.active { color: #FFC107; transform: scale(1.2); }
    .main-content { margin-left: 90px; padding: 20px; }
    .page-title { text-align: center; font-weight: bold; margin: 40px 0 30px; letter-spacing: 1px; font-size: 2.0rem; }
    .page-title::after { content: ""; display: block; width: 120px; height: 5px; background: #a8180aff; margin: 10px auto 0 auto; border-radius: 12px; }
    .cat-card { position: relative; border-radius: 20px; overflow: hidden; cursor: pointer; width: 260px; height: 280px; display: flex; align-items: flex-end; justify-content: flex-start; box-shadow: 0 6px 18px rgba(255,0,0,0.35); transition: transform 0.3s ease, box-shadow 0.3s ease; margin: 22px; }
    .cat-card:hover { transform: translateY(-8px) scale(1.03); box-shadow: 0 14px 32px rgba(255,0,0,0.55); }
    .cat-bg { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: contain; filter: brightness(0.85); }
    .cat-overlay { position: absolute; bottom: 0; left: 0; width: 100%; padding: 14px 18px; background: rgba(0,0,0,0.55); backdrop-filter: blur(8px); color: #FFC107; text-align: left; z-index: 2; }
    .cat-overlay h5 { font-weight: 700; margin: 0; font-size: 1.2rem; text-shadow: 1px 1px 4px rgba(0,0,0,0.7); }
    .cat-overlay p { margin: 2px 0 0; font-size: 0.85rem; opacity: 0.95; }
    .cat-card::after { content: ""; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: radial-gradient(circle at top right, rgba(255, 23, 68, 0.15), transparent 70%); z-index: 1; }
    .ts-card { position: relative; border-radius: 18px; overflow: hidden; background: #fff; width: 100%; min-width: 260px; max-width: 320px; height: 240px; margin: 12px auto; display: flex; flex-direction: column; justify-content: space-between; box-shadow: 0 6px 16px rgba(255,0,0,0.35); cursor: pointer; transition: transform 0.3s ease, box-shadow 0.3s ease; animation: flipBounce 0.7s ease forwards; }
    .ts-card:hover { transform: translateY(-8px) scale(1.03); box-shadow: 0 14px 32px rgba(255,0,0,0.55); }
    .ts-img { height: 160px; object-fit: contain; padding: 12px; background: #fff; margin-top: 18px;}
    .ts-body { padding: 12px; text-align: center; flex-grow: 1; }
    .ts-name { margin: 0; font-weight: 600; font-size: 1rem; color: #333; }
    .btn-back { background: none !important; border: none; color: #333; font-weight: 600; }
    .btn-back:hover { color: #b71c1c; }
    .btn-add { background: #a8180aff; color: #fff; font-weight: 600; border-radius: 25px; padding: 6px 18px; box-shadow: 0 4px 12px rgba(255,0,0,0.4);}
    .btn-add:hover { background: #FFC107; }
    .ts-popup { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); justify-content: center; align-items: center; z-index: 2000; }
    .ts-popup-content {width: 420px; min-height: 380px; max-height: 420px; background: #fff;border-radius: 18px;padding: 20px;padding-top: 55px;display: flex;flex-direction: column;align-items: center;position: relative;overflow: hidden;box-shadow: 0 6px 20px rgba(0,0,0,0.25);}
    .ts-popup-content img { max-width: 100%;max-height: 200px;object-fit: contain;margin-bottom: 15px;}
    #popupName {font-size: 1.3rem;font-weight: 600;color: #a8180aff;  /* theme accent */text-align: center;margin-bottom: 8px;}
    #popupDesc {max-height: 120px;overflow-y: auto;text-align: center;font-size: 0.95rem;color: #333;padding: 0 8px;}
    .popup-actions-top {position: absolute;top: 12px;right: 15px;display: flex;gap: 12px;}
    .popup-actions-top i {cursor: pointer;transition: transform 0.25s ease, color 0.25s ease, opacity 0.25s ease;}
    .popup-actions-top i:hover {transform: scale(1.25);opacity: 0.8;}
    .close-btn { position: absolute; top: 10px; right: 15px; font-size: 1.5rem; cursor: pointer; }
    .popup-actions { margin-top: 15px; display: flex; justify-content: center; gap: 15px; }
    .popup-actions button { border: none; padding: 6px 16px; border-radius: 8px; font-weight: 600; cursor: pointer; }
    .popup-actions-top {position: absolute;top: 10px;right: 15px;display: flex;gap: 12px;}
    .popup-actions-top i {cursor: pointer;transition: transform 0.2s ease, opacity 0.2s ease;}
    .popup-actions-top i:hover {transform: scale(1.25);opacity: 0.7;}
    .icon-edit { color: #a8180aff; }
    .icon-edit:hover { color: #ff5252; transform: scale(1.2); }
    .icon-delete { color: #d32f2f; }
    .icon-delete:hover { color: #ff1744; transform: scale(1.2); }
    .icon-close { color: #1A1A1A; }
    .icon-close:hover { color: #555; transform: scale(1.2) rotate(90deg); }
    .edit-modal {border-radius: 18px;overflow: hidden;}
    .edit-header {background: linear-gradient(135deg, #a8180aff, #ff5252);color: #fff;padding: 16px 20px;}
    .text-theme {color: #a8180aff;}
    .upload-box {transition: 0.3s;}
    .upload-box:hover {background: #fff8f8;border-color: #ff5252 !important;transform: scale(1.02);}
    .btn-cancel {background: #6c757d;color: #fff;}
    .btn-cancel:hover {background: #5a6268;}
    .btn-update {background: #a8180aff;color: #fff;font-weight: 600;box-shadow: 0 4px 12px rgba(255,0,0,0.3);}
    .btn-update:hover {background: #FFC107;color: #1A1A1A;}
    .logout-btn {margin-top: auto;margin-bottom: 20px;font-size: 1.8rem;color: #be281aff;transition: 0.3s;}
    .logout-btn:hover {color: #FFC107;transform: scale(1.2);}
    #editSignModal .modal-content { animation: fadeUp 0.4s ease;}
    @keyframes fadeUp {
    from { transform: translateY(40px); opacity: 0; }
    to   { transform: translateY(0); opacity: 1; }
  }

  </style>
</head>
<body>
  <div class="sidebar">
    <img src="logo1.png" alt="Logo" class="logo-circle">
    <a href="dashboard.php" class="nav-link" title="Dashboard"><i class="bi bi-house"></i></a>
    <a href="companies.php" class="nav-link" title="Companies"><i class="bi bi-buildings"></i></a>
    <a href="traffic_signs.php" class="nav-link active" title="Traffic Signs"><i class="bi bi-signpost-split"></i></a>
    <a href="users.php" class="nav-link" title="Users"><i class="bi bi-people"></i></a>
    <a href="students.php" class="nav-link" title="Students"><i class="bi bi-mortarboard"></i></a>
    <a href="login.php" class="nav-link logout-btn" title="Logout"><i class="bi bi-box-arrow-right"></i></a>
  </div>

  <div class="main-content">
    <h2 class="page-title">Traffic Signs</h2>

    <div id="category-view" class="row justify-content-center">
      <?php foreach ($signsByCategory as $category => $signs): 
        $preview = $signs[0]['imageUrl'] ?? ''; ?>
        <div class="col-auto d-flex justify-content-center">
          <div class="cat-card" onclick="showCategory('<?= htmlspecialchars($category) ?>')">
            <?php if ($preview): ?>
              <img src="<?= $preview ?>" alt="<?= htmlspecialchars($category) ?>" class="cat-bg">
            <?php endif; ?>
            <div class="cat-overlay">
              <h5><?= htmlspecialchars($category) ?></h5>
              <p><?= count($signs) ?> signs</p>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <?php foreach ($signsByCategory as $category => $signs): ?>
      <div id="category-<?= htmlspecialchars($category) ?>" class="category-section" style="display: none;">
        <div class="mb-3">
          <button class="btn btn-sm btn-back mb-2" onclick="backToCategories()">
            <i class="bi bi-arrow-left"></i> Back
          </button>
          <h2 class="m-0"><?= htmlspecialchars($category) ?></h2>
          <button class="btn btn-add mt-2" data-bs-toggle="modal" data-bs-target="#addSignModal" data-category="<?= htmlspecialchars($category) ?>">
            <i class="bi bi-plus-circle"></i> Add Traffic Sign
          </button>
        </div>
        <div class="row g-2">
          <?php foreach ($signs as $row): ?>
            <div class="col-12 col-sm-6 col-md-4 col-lg-3 d-flex">
              <div class="ts-card" id="sign-<?= $row['id'] ?>"
                   data-id="<?= $row['id'] ?>"
                   data-img="<?= htmlspecialchars($row['imageUrl']) ?>"
                   data-name="<?= htmlspecialchars($row['name']) ?>"
                   data-desc="<?= htmlspecialchars($row['description']) ?>"
                   onclick="wowEffect(this); handleCardClick(this)">
                <img src="<?= $row['imageUrl'] ?>" alt="<?= htmlspecialchars($row['name']) ?>" class="ts-img">
                <div class="ts-body">
                  <h6 class="ts-name"><?= htmlspecialchars($row['name']) ?></h6>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Add Traffic Sign Modal -->
  <div class="modal fade" id="addSignModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg" style="border-radius: 18px; overflow: hidden;">
        <form method="POST" enctype="multipart/form-data">
          <div class="modal-header border-0" style="background: linear-gradient(135deg, #a8180aff, #ff5252); color: #fff;">
            <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle me-2"></i> Add Traffic Sign</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body p-4 bg-light">
            <input type="hidden" name="category" id="modalCategory">

            <div class="mb-3">
              <label class="form-label fw-semibold">Traffic Sign Name</label>
              <input type="text" class="form-control form-control-lg" name="name" placeholder="Enter sign name..." required>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold">Description</label>
              <textarea class="form-control" name="description" rows="3" placeholder="Enter description..."></textarea>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold">Upload Image (PNG/JPG)</label>
              <div class="upload-box border border-2 border-dashed rounded-3 p-4 text-center" 
                   onclick="this.querySelector('input').click()" 
                   style="cursor: pointer; transition: 0.3s;">
                <i class="bi bi-cloud-arrow-up fs-1 text-danger"></i>
                <p class="mt-2 mb-1 fw-semibold">Click to upload</p>
                <p class="text-muted small">Only PNG and JPG are allowed</p>
                <input type="file" name="image" accept=".png,.jpg,.jpeg" required hidden onchange="previewImage(event)">
                <img id="uploadPreview" src="" alt="" class="mt-3 rounded d-none" style="max-height: 150px;">
              </div>
            </div>
          </div>
          <div class="modal-footer border-0">
            <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" name="addSign" class="btn btn-add rounded-pill px-4">
              <i class="bi bi-save me-1"></i> Save
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <style>
    .upload-box:hover {background: #fff3f3; border-color: #ff1744 !important; transform: scale(1.02);}
  </style>
  
  <script>
    function previewImage(event) {
      const input = event.target;
      const preview = document.getElementById('uploadPreview');
      if (input.files && input.files[0]) {
        const file = input.files[0];
        const validTypes = ["image/png", "image/jpg", "image/jpeg"];
        if (!validTypes.includes(file.type)) {
          alert("Only PNG and JPG files are allowed.");
          input.value = "";
          preview.classList.add("d-none");
          return;
        }
        preview.src = URL.createObjectURL(file);
        preview.classList.remove("d-none");
      }
    }
  </script>
    <!-- Edit Modal -->
  <div class="modal fade" id="editSignModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg edit-modal">
        <form method="POST" enctype="multipart/form-data">
          <div class="modal-header border-0 edit-header">
            <h5 class="modal-title fw-bold">
              <i class="bi bi-pencil me-2"></i> Edit Traffic Sign
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body p-4 bg-light">
            <input type="hidden" name="id" id="editId">
            <input type="hidden" name="category" id="editCategory">
            <input type="hidden" name="currentImage" id="currentImage">
            <div class="mb-3">
              <label class="form-label fw-semibold text-theme">Traffic Sign Name</label>
              <input type="text" class="form-control form-control-lg" name="name" id="editName" required placeholder="Enter sign name...">
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold text-theme">Description</label>
              <textarea class="form-control form-control-lg" name="description" id="editDescription" rows="3" placeholder="Enter description..."></textarea>
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold text-theme">Change Image</label>
              <div class="upload-box border border-2 border-dashed rounded-3 p-4 text-center" 
                  onclick="this.querySelector('input').click()" 
                  style="cursor: pointer;">
                <i class="bi bi-cloud-arrow-up fs-1 text-danger"></i>
                <p class="mt-2 mb-1 fw-semibold">Click to upload new image</p>
                <p class="text-muted small">Only PNG and JPG are allowed</p>
                <input type="file" name="image" accept=".png,.jpg,.jpeg" hidden onchange="previewEditImage(event)">
                <img id="editPreview" src="" class="mt-3 rounded d-none" style="max-height: 150px;">
              </div>
            </div>
          </div>
          <div class="modal-footer border-0">
            <button type="button" class="btn btn-cancel rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" name="editSign" class="btn btn-update rounded-pill px-4">
              <i class="bi bi-check-circle me-1"></i> Update
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Delete Modal -->
  <div class="modal fade" id="deleteSignModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg rounded-3">
        <form method="POST">
          <div class="modal-header bg-danger text-white border-0">
            <h5 class="modal-title"><i class="bi bi-exclamation-triangle me-2"></i>Confirm Delete</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="id" id="deleteId">
            <p>Are you sure you want to delete this traffic sign?</p>
          </div>
          <div class="modal-footer border-0">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" name="deleteSign" class="btn btn-danger">Delete</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="ts-popup" id="tsPopup" onclick="closePopup(event)">
    <div class="close-btn" onclick="closePopup(event)">&times;</div>
    <div class="ts-popup-content" onclick="event.stopPropagation()">
      <img id="popupImg" src="" alt="">
      <h5 id="popupName"></h5>
      <p id="popupDesc"></p>
      <div class="popup-actions-top">
        <i class="bi bi-pencil fs-4 icon-edit" onclick="editSign()" title="Edit"></i>
        <i class="bi bi-trash3 fs-4 icon-delete" onclick="deleteSign()" title="Delete"></i>
        <i class="bi bi-x-lg fs-4 icon-close" onclick="closePopup()" title="Close"></i>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    let currentId = null, currentName="", currentDesc="", currentImg="", currentCategory="";

    function showCategory(category) {document.getElementById("category-view").style.display = "none";document.querySelectorAll(".category-section").forEach(el => el.style.display = "none");document.getElementById("category-" + category).style.display = "block";document.querySelector(".page-title").style.display = "none";}
    function backToCategories() {document.querySelectorAll(".category-section").forEach(el => el.style.display = "none"); document.getElementById("category-view").style.display = "flex";document.querySelector(".page-title").style.display = "block";}
    function openPopup(img, name, desc, id) {currentId = id;document.getElementById("popupImg").src = img;document.getElementById("popupName").innerText = name;document.getElementById("popupDesc").innerText = desc;document.getElementById("tsPopup").style.display = "flex";}
    function closePopup(event) {
      if (!event || event.target.id === "tsPopup" || event.target.classList.contains("close-btn")) {
        document.getElementById("tsPopup").style.display = "none";
      }
    }
    function wowEffect(el) {el.style.animation = "wowClick 0.5s ease";setTimeout(() => { el.style.animation = ""; }, 500);}
    function handleCardClick(el) {const img = el.getAttribute("data-img");const name = el.getAttribute("data-name");const desc = el.getAttribute("data-desc");const id = el.getAttribute("data-id");openPopup(img, name, desc, id);
    }
    function handleCardClick(el) {
      const img = el.getAttribute("data-img");
      const name = el.getAttribute("data-name");
      const desc = el.getAttribute("data-desc");
      const id = el.getAttribute("data-id");
      const cat = el.closest(".category-section").querySelector("h2").innerText;

      currentId = id; currentName = name; currentDesc = desc; currentImg = img; currentCategory = cat;
      openPopup(img, name, desc, id); }

   function editSign() {
    closePopup(); // ✅ close ts-popup first
      document.getElementById("editId").value = currentId;
      document.getElementById("editName").value = currentName;
      document.getElementById("editDescription").value = currentDesc;
      document.getElementById("editCategory").value = currentCategory;
      document.getElementById("currentImage").value = currentImg;
      document.getElementById("editPreview").src = currentImg;
      new bootstrap.Modal(document.getElementById("editSignModal")).show();
    }

    function previewEditImage(event) {
      const input = event.target;
      const preview = document.getElementById('editPreview');
      if (input.files && input.files[0]) {
        const file = input.files[0];
        const validTypes = ["image/png", "image/jpg", "image/jpeg"];
        if (!validTypes.includes(file.type)) {
          alert("Only PNG and JPG files are allowed.");
          input.value = "";
          preview.classList.add("d-none");
          return;
        }
        preview.src = URL.createObjectURL(file);
        preview.classList.remove("d-none");
      }
    }
    function deleteSign() {
      closePopup(); // ✅ close ts-popup first
      document.getElementById("deleteId").value = currentId;
      new bootstrap.Modal(document.getElementById("deleteSignModal")).show();
    }
    // pass category to modal hidden input
    const addSignModal = document.getElementById('addSignModal');
    addSignModal.addEventListener('show.bs.modal', event => {
      const button = event.relatedTarget;
      const category = button.getAttribute('data-category');
      document.getElementById('modalCategory').value = category;
    });
  </script>
</body>
</html>
