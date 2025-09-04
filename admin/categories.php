<?php
require_once '../src/config.php';
require_once '../src/db.php';
require_once '../src/helpers.php';
require_once '../src/models/Category.php';

requireLogin();

$categoryModel = new Category();

$success = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $name = sanitizeInput($_POST['name'] ?? '');
        $slug = slugify($_POST['slug'] ?? $name);
        
        if ($name && $slug) {
            // Check if slug exists
            $existing = $categoryModel->getBySlug($slug);
            if ($existing) {
                $error = 'Category slug already exists';
            } else {
                $categoryModel->create(['name' => $name, 'slug' => $slug]);
                $success = 'Category created successfully';
            }
        } else {
            $error = 'Please fill all required fields';
        }
    }
    
    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $name = sanitizeInput($_POST['name'] ?? '');
        $slug = sanitizeInput($_POST['slug'] ?? '');
        
        if ($id && $name && $slug) {
            // Check if slug exists for other categories
            $existing = $categoryModel->getBySlug($slug);
            if ($existing && $existing['id'] != $id) {
                $error = 'Category slug already exists';
            } else {
                $categoryModel->update($id, ['name' => $name, 'slug' => $slug]);
                $success = 'Category updated successfully';
            }
        } else {
            $error = 'Please fill all required fields';
        }
    }
    
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $categoryModel->delete($id);
            $success = 'Category deleted successfully';
        }
    }
}

$categories = $categoryModel->getAll();

$pageTitle = 'Manage Categories';
include 'includes/header.php';
?>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Add Category Form -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-lg font-bold text-gray-800 mb-4">Add New Category</h2>
                    
                    <?php if ($success): ?>
                    <div class="alert alert-success mb-4">
                        <i class="fas fa-check-circle mr-2"></i><?= $success ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                    <div class="alert alert-error mb-4">
                        <i class="fas fa-exclamation-circle mr-2"></i><?= $error ?>
                    </div>
                    <?php endif; ?>

                    <form method="POST" data-validate>
                        <input type="hidden" name="action" value="create">
                        
                        <div class="form-group">
                            <label class="form-label">Category Name *</label>
                            <input type="text" name="name" class="form-input" required 
                                   placeholder="e.g., Central Government">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Slug</label>
                            <input type="text" name="slug" class="form-input" 
                                   placeholder="Auto-generated from name">
                            <small class="text-gray-500">Leave empty to auto-generate</small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-full">
                            <i class="fas fa-plus mr-2"></i>Add Category
                        </button>
                    </form>
                </div>
            </div>

            <!-- Categories List -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-lg font-bold text-gray-800 mb-4">Existing Categories</h2>
                    
                    <div class="overflow-x-auto">
                        <table class="table w-full">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Slug</th>
                                    <th>Jobs Count</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $category): ?>
                                <tr>
                                    <td class="font-medium"><?= htmlspecialchars($category['name']) ?></td>
                                    <td class="text-sm text-gray-600"><?= htmlspecialchars($category['slug']) ?></td>
                                    <td>
                                        <span class="badge badge-primary">
                                            <?= $categoryModel->getJobCount($category['id']) ?> jobs
                                        </span>
                                    </td>
                                    <td>
                                        <div class="flex gap-2">
                                            <a href="<?= SITE_URL ?>/jobs/<?= urlencode($category['slug']) ?>" target="_blank"
                                               class="text-blue-600 hover:text-blue-800" title="View Jobs">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <button onclick="editCategory(<?= $category['id'] ?>, '<?= addslashes($category['name']) ?>', '<?= addslashes($category['slug']) ?>')"
                                                    class="text-green-600 hover:text-green-800" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form method="POST" class="inline" onsubmit="return confirm('Are you sure?')">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= $category['id'] ?>">
                                                <button type="submit" class="text-red-600 hover:text-red-800" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Category Modal -->
    <div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Edit Category</h3>
                
                <form id="editForm" method="POST">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="editId">
                    
                    <div class="form-group">
                        <label class="form-label">Category Name *</label>
                        <input type="text" name="name" id="editName" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Slug *</label>
                        <input type="text" name="slug" id="editSlug" class="form-input" required>
                    </div>
                    
                    <div class="flex gap-3">
                        <button type="submit" class="btn btn-primary flex-1">
                            <i class="fas fa-save mr-2"></i>Update
                        </button>
                        <button type="button" onclick="closeEditModal()" class="btn btn-secondary">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function editCategory(id, name, slug) {
            document.getElementById('editId').value = id;
            document.getElementById('editName').value = name;
            document.getElementById('editSlug').value = slug;
            document.getElementById('editModal').classList.remove('hidden');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }

        // Auto-generate slug from name
        document.querySelector('input[name="name"]').addEventListener('input', function() {
            const slug = this.value.toLowerCase()
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-')
                .trim('-');
            document.querySelector('input[name="slug"]').value = slug;
        });
    </script>

<?php include 'includes/footer.php'; ?>
