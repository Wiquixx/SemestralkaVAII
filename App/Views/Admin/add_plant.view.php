<?php /** @var array $errors */ /** @var bool $success */ /** @var \Framework\Support\LinkGenerator $link */ ?>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <h2 class="text-center mb-4" style="color:#21b573;">Add Plant</h2>
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $error): ?>
                        <div><?= htmlspecialchars($error) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success">Plant was successfully added!</div>
            <?php endif; ?>
            <form id="addPlantForm" method="post" enctype="multipart/form-data" action="<?= $link->url('admin.addPlant') ?>">
                <div class="mb-3">
                    <label for="common_name" class="form-label">Common Name *</label>
                    <input type="text" class="form-control" id="common_name" name="common_name" required value="<?= htmlspecialchars($_POST['common_name'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label for="scientific_name" class="form-label">Scientific Name</label>
                    <input type="text" class="form-control" id="scientific_name" name="scientific_name" value="<?= htmlspecialchars($_POST['scientific_name'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label for="location" class="form-label">Location</label>
                    <input type="text" class="form-control" id="location" name="location" value="<?= htmlspecialchars($_POST['location'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label for="purchase_date" class="form-label">Purchase Date <span style="color:red">*</span></label>
                    <input type="date" class="form-control" id="purchase_date" name="purchase_date" required max="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($_POST['purchase_date'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label for="notes" class="form-label">Notes</label>
                    <textarea class="form-control" id="notes" name="notes" rows="3"><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
                </div>

                <!-- Image upload -->
                <div class="mb-3">
                    <label for="image" class="form-label">Plant Image</label>
                    <input type="file" class="form-control" id="image" name="image" accept="image/*">
                    <div class="form-text">Optional. Max size 5MB. Allowed types: JPEG, PNG, GIF.</div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-success" style="background:#21b573;border:none;">Add Plant</button>
                    <a href="<?= $link->url('admin.index') ?>" class="btn btn-danger" style="background:#dc3545;border:none;color:#fff;">Cancel</a>
                </div>
            </form>

            <!-- Load external JS to enforce client-side validation (keeps view logic-free) -->
            <script src="<?= htmlspecialchars($link->asset('js/admin_add_plant.js'), ENT_QUOTES) ?>"></script>
        </div>
    </div>
</div>
