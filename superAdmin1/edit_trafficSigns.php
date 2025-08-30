<?php
// ✅ Handle edit sign form submit
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
?>

<!-- ✅ Edit Traffic Sign Modal -->
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
            <input type="text" class="form-control form-control-lg" name="name" id="editName" required>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold text-theme">Description</label>
            <textarea class="form-control form-control-lg" name="description" id="editDescription" rows="3"></textarea>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold text-theme">Change Image</label>
            <div class="upload-box border border-2 border-dashed rounded-3 p-4 text-center" 
                onclick="this.querySelector('input').click()" style="cursor: pointer;">
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
