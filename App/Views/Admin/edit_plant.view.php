<?php /** @var array $errors */ /** @var bool $success */ /** @var array $plant */ /** @var \Framework\Support\LinkGenerator $link */ ?>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <h2 class="text-center mb-4" style="color:#21b573;">Edit Plant</h2>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $error): ?>
                        <div><?= htmlspecialchars($error) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= $link->url('admin.editPlant') ?>">
                <input type="hidden" name="plant_id" value="<?= (int)($plant['plant_id'] ?? 0) ?>">

                <div class="mb-3">
                    <label for="common_name" class="form-label">Common Name *</label>
                    <input type="text" class="form-control" id="common_name" name="common_name" required value="<?= htmlspecialchars($_POST['common_name'] ?? $plant['common_name'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label for="scientific_name" class="form-label">Scientific Name</label>
                    <input type="text" class="form-control" id="scientific_name" name="scientific_name" value="<?= htmlspecialchars($_POST['scientific_name'] ?? $plant['scientific_name'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label for="location" class="form-label">Location</label>
                    <input type="text" class="form-control" id="location" name="location" value="<?= htmlspecialchars($_POST['location'] ?? $plant['location'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label for="purchase_date" class="form-label">Purchase Date <span style="color:red">*</span></label>
                    <input type="date" class="form-control" id="purchase_date" name="purchase_date" required value="<?= htmlspecialchars($_POST['purchase_date'] ?? $plant['purchase_date'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label for="notes" class="form-label">Notes</label>
                    <textarea class="form-control" id="notes" name="notes" rows="3"><?= htmlspecialchars($_POST['notes'] ?? $plant['notes'] ?? '') ?></textarea>
                </div>
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary" style="background:#21b573;border:none;">Save Changes</button>
                    <a href="<?= $link->url('admin.index') ?>" class="btn btn-danger" style="background:#dc3545;border:none;color:#fff;">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
