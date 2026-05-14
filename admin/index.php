<?php
session_start();
require_once '../config.php';

// Create uploads directory if not exists
$upload_dir = '../uploads/products/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Check login
if(!isset($_SESSION['admin_logged'])) {
    if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['username'])) {
        $user = $conn->real_escape_string($_POST['username']);
        $pass = $_POST['password'];
        $result = $conn->query("SELECT * FROM users WHERE username='$user' LIMIT 1");
        $admin = $result->fetch_assoc();
        
        if($admin && password_verify($pass, $admin['password'])) {
            $_SESSION['admin_logged'] = true;
        } else {
            $error = "Invalid credentials";
        }
    }
    
    if(!isset($_SESSION['admin_logged'])) {
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Admin Login - Revobake B2B</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        </head>
        <body class="bg-light">
            <div class="container mt-5">
                <div class="row justify-content-center">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h4 class="mb-0">Revobake B2B Admin</h4>
                            </div>
                            <div class="card-body">
                                <?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
                                <form method="post">
                                    <div class="mb-3">
                                        <input type="text" name="username" class="form-control" placeholder="Username" required>
                                    </div>
                                    <div class="mb-3">
                                        <input type="password" name="password" class="form-control" placeholder="Password" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100">Login</button>
                                </form>
                                <div class="mt-3 text-muted small">Default: admin / admin123</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

// Handle image upload
function uploadImages($files, $upload_dir, $existing_images = '') {
    $uploaded_files = [];
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    // Keep existing images if any
    if(!empty($existing_images)) {
        $uploaded_files = explode(',', $existing_images);
        $uploaded_files = array_map('trim', $uploaded_files);
    }
    
    if(isset($files['images']) && !empty($files['images']['name'][0])) {
        foreach($files['images']['tmp_name'] as $key => $tmp_name) {
            if($files['images']['error'][$key] == 0) {
                $file_name = $files['images']['name'][$key];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                
                if(in_array($file_ext, $allowed)) {
                    $new_name = time() . '_' . uniqid() . '.' . $file_ext;
                    $destination = $upload_dir . $new_name;
                    
                    if(move_uploaded_file($tmp_name, $destination)) {
                        $uploaded_files[] = 'uploads/products/' . $new_name;
                    }
                }
            }
        }
    }
    
    // Handle image removal
    if(isset($_POST['remove_images']) && !empty($_POST['remove_images'])) {
        $to_remove = explode(',', $_POST['remove_images']);
        foreach($uploaded_files as $key => $img) {
            if(in_array($img, $to_remove)) {
                $file_path = '../' . $img;
                if(file_exists($file_path)) {
                    unlink($file_path);
                }
                unset($uploaded_files[$key]);
            }
        }
        $uploaded_files = array_values($uploaded_files);
    }
    
    return implode(',', $uploaded_files);
}

// Handle logout
if(isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

// Handle page save
if(isset($_POST['save_page'])) {
    $id = (int)$_POST['id'];
    $title = $conn->real_escape_string($_POST['title']);
    $slug = $conn->real_escape_string(strtolower(trim(preg_replace('/[^a-z0-9-]+/', '-', $_POST['slug']))));
    $content = $conn->real_escape_string($_POST['content']);
    $menu_order = (int)$_POST['menu_order'];
    $is_published = isset($_POST['is_published']) ? 1 : 0;
    
    if($id) {
        $conn->query("UPDATE pages SET title='$title', slug='$slug', content='$content', menu_order=$menu_order, is_published=$is_published WHERE id=$id");
    } else {
        $conn->query("INSERT INTO pages (title, slug, content, menu_order, is_published) VALUES ('$title','$slug','$content',$menu_order,$is_published)");
    }
    $success = "Page saved successfully!";
}

// Handle page delete
if(isset($_GET['delete_page'])) {
    $conn->query("DELETE FROM pages WHERE id=".(int)$_GET['delete_page']);
    $success = "Page deleted!";
}

// Handle product save with image upload
if(isset($_POST['save_product'])) {
    $id = (int)$_POST['id'];
    $name = $conn->real_escape_string($_POST['name']);
    $slug = $conn->real_escape_string(strtolower(trim(preg_replace('/[^a-z0-9-]+/', '-', $_POST['slug']))));
    $short_desc = $conn->real_escape_string($_POST['short_description']);
    $desc = $conn->real_escape_string($_POST['description']);
    $specs = $conn->real_escape_string($_POST['technical_specs']);
    $price = $conn->real_escape_string($_POST['price_info']);
    $featured = isset($_POST['featured']) ? 1 : 0;
    
    // Get existing images if any
    $existing = '';
    if($id) {
        $result = $conn->query("SELECT images FROM products WHERE id=$id");
        $row = $result->fetch_assoc();
        $existing = $row['images'] ?? '';
    }
    
    // Upload new images and manage removal
    $images = uploadImages($_FILES, $upload_dir, $existing);
    
    if($id) {
        $conn->query("UPDATE products SET name='$name', slug='$slug', short_description='$short_desc', description='$desc', technical_specs='$specs', images='$images', price_info='$price', featured=$featured WHERE id=$id");
    } else {
        $conn->query("INSERT INTO products (name, slug, short_description, description, technical_specs, images, price_info, featured) VALUES ('$name','$slug','$short_desc','$desc','$specs','$images','$price',$featured)");
    }
    $success = "Product saved successfully!";
}

// Handle product delete (also delete images)
if(isset($_GET['delete_product'])) {
    $id = (int)$_GET['delete_product'];
    $result = $conn->query("SELECT images FROM products WHERE id=$id");
    $row = $result->fetch_assoc();
    if($row && $row['images']) {
        $images = explode(',', $row['images']);
        foreach($images as $img) {
            $file_path = '../' . trim($img);
            if(file_exists($file_path)) {
                unlink($file_path);
            }
        }
    }
    $conn->query("DELETE FROM products WHERE id=$id");
    $success = "Product deleted!";
}

// Get data for editing
$edit_page = null;
if(isset($_GET['edit_page'])) {
    $edit_page = $conn->query("SELECT * FROM pages WHERE id=".(int)$_GET['edit_page'])->fetch_assoc();
}

$edit_product = null;
if(isset($_GET['edit_product'])) {
    $edit_product = $conn->query("SELECT * FROM products WHERE id=".(int)$_GET['edit_product'])->fetch_assoc();
}

$pages = $conn->query("SELECT * FROM pages ORDER BY menu_order");
$products = $conn->query("SELECT * FROM products ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backoffice - Revobake B2B</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.js"></script>
    <style>
        .sidebar { background-color: #1a1a2e; min-height: 100vh; }
        .sidebar .nav-link { color: #fff; }
        .sidebar .nav-link:hover { background-color: #e67e22; color: #fff; }
        .image-preview { 
            display: inline-block; 
            margin: 10px; 
            position: relative;
            border: 2px solid #ddd;
            border-radius: 5px;
            padding: 5px;
            background: #f8f9fa;
        }
        .image-preview img { 
            width: 150px; 
            height: 150px; 
            object-fit: cover;
            border-radius: 5px;
        }
        .remove-image-btn {
            position: absolute;
            top: -10px;
            right: -10px;
            background: red;
            color: white;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            text-align: center;
            cursor: pointer;
            font-weight: bold;
            line-height: 25px;
        }
        .upload-area {
            border: 2px dashed #ccc;
            padding: 20px;
            text-align: center;
            background: #f8f9fa;
            cursor: pointer;
            border-radius: 5px;
            transition: all 0.3s;
        }
        .upload-area:hover {
            border-color: #e67e22;
            background: #fdf5e6;
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-2 p-0 sidebar">
            <div class="p-3 text-white">
                <h5><i class="fas fa-bread-slice"></i> Revobake Admin</h5>
                <small>B2B Management System</small>
            </div>
            <nav class="nav flex-column">
                <a class="nav-link" href="?section=pages"><i class="fas fa-file-alt"></i> Pages</a>
                <a class="nav-link" href="?section=products"><i class="fas fa-box"></i> Products</a>
                <a class="nav-link" href="?section=inquiries"><i class="fas fa-envelope"></i> Inquiries</a>
                <a class="nav-link" href="?logout=1"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </div>
        
        <!-- Main Content -->
        <div class="col-md-10 p-4">
            <?php if(isset($success)): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php
            $section = $_GET['section'] ?? 'dashboard';
            
            if($section == 'dashboard'):
            ?>
                <div class="row">
                    <div class="col-md-12">
                        <h2>Dashboard</h2>
                        <p>Welcome to Revobake B2B Management System</p>
                        <hr>
                    </div>
                </div>
                <div class="row mt-4">
                    <div class="col-md-4">
                        <div class="card text-white bg-primary mb-3">
                            <div class="card-body">
                                <h5 class="card-title">Pages</h5>
                                <p class="card-text display-4"><?php echo $conn->query("SELECT COUNT(*) as count FROM pages")->fetch_assoc()['count']; ?></p>
                                <a href="?section=pages" class="text-white">Manage Pages →</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-white bg-success mb-3">
                            <div class="card-body">
                                <h5 class="card-title">Products</h5>
                                <p class="card-text display-4"><?php echo $conn->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count']; ?></p>
                                <a href="?section=products" class="text-white">Manage Products →</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-white bg-info mb-3">
                            <div class="card-body">
                                <h5 class="card-title">Inquiries</h5>
                                <p class="card-text display-4"><?php echo $conn->query("SELECT COUNT(*) as count FROM product_inquiries")->fetch_assoc()['count']; ?></p>
                                <a href="?section=inquiries" class="text-white">View Inquiries →</a>
                            </div>
                        </div>
                    </div>
                </div>
            
            <?php elseif($section == 'pages'): ?>
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><?php echo $edit_page ? 'Edit Page' : 'Create New Page'; ?></h4>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <input type="hidden" name="id" value="<?php echo $edit_page['id'] ?? ''; ?>">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Page Title</label>
                                <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($edit_page['title'] ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">URL Slug</label>
                                <input type="text" name="slug" class="form-control" value="<?php echo htmlspecialchars($edit_page['slug'] ?? ''); ?>" required>
                                <small class="text-muted">Example: about-us, contact, products (use lowercase and hyphens)</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Content (WYSIWYG Editor)</label>
                                <textarea name="content" id="content" class="form-control summernote"><?php echo htmlspecialchars($edit_page['content'] ?? ''); ?></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Menu Order</label>
                                        <input type="number" name="menu_order" class="form-control" value="<?php echo $edit_page['menu_order'] ?? 0; ?>">
                                        <small>Lower numbers appear first</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3 form-check mt-4">
                                        <input type="checkbox" name="is_published" class="form-check-input" <?php echo ($edit_page['is_published'] ?? 1) ? 'checked' : ''; ?>>
                                        <label class="form-check-label fw-bold">Published (visible on website)</label>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" name="save_page" class="btn btn-primary"><i class="fas fa-save"></i> Save Page</button>
                            <a href="?section=pages" class="btn btn-secondary"><i class="fas fa-times"></i> Cancel</a>
                        </form>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h4 class="mb-0"><i class="fas fa-list"></i> Existing Pages</h4>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered table-hover">
                            <thead class="table-dark">
                                <tr><th>Title</th><th>Slug</th><th>Order</th><th>Published</th><th>Actions</th></tr>
                            </thead>
                            <tbody>
                                <?php while($row = $pages->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($row['title']); ?></strong></td>
                                    <td><code><?php echo $row['slug']; ?></code></td>
                                    <td><?php echo $row['menu_order']; ?></td>
                                    <td><?php echo $row['is_published'] ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-danger">No</span>'; ?></td>
                                    <td>
                                        <a href="?section=pages&edit_page=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i> Edit</a>
                                        <a href="?section=pages&delete_page=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this page permanently?')"><i class="fas fa-trash"></i> Delete</a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            
            <?php elseif($section == 'products'): 
                $existing_images = [];
                if($edit_product && $edit_product['images']) {
                    $existing_images = explode(',', $edit_product['images']);
                }
            ?>
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h4 class="mb-0"><?php echo $edit_product ? '<i class="fas fa-edit"></i> Edit Product' : '<i class="fas fa-plus"></i> Add New Product'; ?></h4>
                    </div>
                    <div class="card-body">
                        <form method="post" enctype="multipart/form-data" id="productForm">
                            <input type="hidden" name="id" value="<?php echo $edit_product['id'] ?? ''; ?>">
                            <input type="hidden" name="remove_images" id="remove_images" value="">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Product Name *</label>
                                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($edit_product['name'] ?? ''); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">URL Slug *</label>
                                        <input type="text" name="slug" class="form-control" value="<?php echo htmlspecialchars($edit_product['slug'] ?? ''); ?>" required>
                                        <small>Auto-generated from name if left empty</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Short Description</label>
                                <textarea name="short_description" class="form-control" rows="2" placeholder="Brief product description for catalog view"><?php echo htmlspecialchars($edit_product['short_description'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Full Description (WYSIWYG)</label>
                                <textarea name="description" id="prod_desc" class="form-control summernote"><?php echo htmlspecialchars($edit_product['description'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Technical Specifications (WYSIWYG)</label>
                                <textarea name="technical_specs" id="tech_specs" class="form-control summernote"><?php echo htmlspecialchars($edit_product['technical_specs'] ?? ''); ?></textarea>
                            </div>
                            
                            <!-- Image Upload Section -->
                            <div class="mb-3">
                                <label class="form-label fw-bold">Product Images</label>
                                
                                <!-- Existing Images Preview -->
                                <?php if(!empty($existing_images)): ?>
                                <div class="mb-3">
                                    <label class="form-label">Current Images:</label>
                                    <div id="existing-images">
                                        <?php foreach($existing_images as $img): 
                                            $img = trim($img);
                                            if($img):
                                        ?>
                                        <div class="image-preview" data-img="<?php echo htmlspecialchars($img); ?>">
                                            <img src="../<?php echo $img; ?>" alt="Product image">
                                            <div class="remove-image-btn" onclick="removeImage('<?php echo htmlspecialchars($img); ?>')">×</div>
                                        </div>
                                        <?php endif; endforeach; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Upload New Images -->
                                <div class="upload-area" onclick="$('#imageInput').click()">
                                    <i class="fas fa-cloud-upload-alt fa-3x text-muted"></i>
                                    <p class="mt-2">Click or drag images here to upload</p>
                                    <small class="text-muted">Supports JPG, PNG, GIF, WebP (Max 5MB each)</small>
                                </div>
                                <input type="file" name="images[]" id="imageInput" class="form-control d-none" accept="image/*" multiple>
                                <div id="new-images-preview" class="mt-3"></div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Price Info</label>
                                        <input type="text" name="price_info" class="form-control" value="<?php echo htmlspecialchars($edit_product['price_info'] ?? ''); ?>" placeholder="Quote required / Contact sales / $1,500">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3 form-check mt-4">
                                        <input type="checkbox" name="featured" class="form-check-input" <?php echo ($edit_product['featured'] ?? 0) ? 'checked' : ''; ?>>
                                        <label class="form-check-label fw-bold">Featured Product (shown prominently)</label>
                                    </div>
                                </div>
                            </div>
                            
                            <button type="submit" name="save_product" class="btn btn-success btn-lg"><i class="fas fa-save"></i> Save Product</button>
                            <a href="?section=products" class="btn btn-secondary btn-lg"><i class="fas fa-times"></i> Cancel</a>
                        </form>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h4 class="mb-0"><i class="fas fa-list"></i> Product List</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Image</th>
                                        <th>Name</th>
                                        <th>Slug</th>
                                        <th>Featured</th>
                                        <th>Price Info</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($prod = $products->fetch_assoc()): 
                                        $prod_images = explode(',', $prod['images']);
                                        $first_image = trim($prod_images[0]);
                                    ?>
                                    <tr>
                                        <td>
                                            <?php if($first_image && file_exists('../' . $first_image)): ?>
                                                <img src="../<?php echo $first_image; ?>" style="width: 50px; height: 50px; object-fit: cover;" alt="<?php echo htmlspecialchars($prod['name']); ?>">
                                            <?php else: ?>
                                                <i class="fas fa-image fa-2x text-muted"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td><strong><?php echo htmlspecialchars($prod['name']); ?></strong></td>
                                        <td><code><?php echo $prod['slug']; ?></code></td>
                                        <td><?php echo $prod['featured'] ? '<span class="badge bg-warning">⭐ Featured</span>' : '<span class="badge bg-secondary">No</span>'; ?></td>
                                        <td><?php echo htmlspecialchars($prod['price_info']); ?></td>
                                        <td>
                                            <a href="?section=products&edit_product=<?php echo $prod['id']; ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i> Edit</a>
                                            <a href="?section=products&delete_product=<?php echo $prod['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this product and all its images?')"><i class="fas fa-trash"></i> Delete</a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            
            <?php elseif($section == 'inquiries'): ?>
                <div class="card mb-4">
                    <div class="card-header bg-warning">
                        <h4 class="mb-0"><i class="fas fa-shopping-cart"></i> Product Inquiries</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Date</th>
                                        <th>Product</th>
                                        <th>Company</th>
                                        <th>Contact Person</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Quantity</th>
                                        <th>Message</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $inquiries = $conn->query("SELECT pi.*, p.name as product_name FROM product_inquiries pi LEFT JOIN products p ON pi.product_id=p.id ORDER BY pi.created_at DESC");
                                    while($inq = $inquiries->fetch_assoc()):
                                    ?>
                                    <tr>
                                        <td><?php echo date('Y-m-d H:i', strtotime($inq['created_at'])); ?></td>
                                        <td><strong><?php echo htmlspecialchars($inq['product_name'] ?? 'N/A'); ?></strong></td>
                                        <td><?php echo htmlspecialchars($inq['company_name']); ?></td>
                                        <td><?php echo htmlspecialchars($inq['contact_person']); ?></td>
                                        <td><a href="mailto:<?php echo $inq['email']; ?>"><?php echo htmlspecialchars($inq['email']); ?></a></td>
                                        <td><?php echo htmlspecialchars($inq['phone']); ?></td>
                                        <td><?php echo htmlspecialchars($inq['quantity']); ?></td>
                                        <td><?php echo nl2br(htmlspecialchars(substr($inq['message'], 0, 100))); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header bg-info">
                        <h4 class="mb-0"><i class="fas fa-envelope"></i> Contact Form Submissions</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Date</th>
                                        <th>Name</th>
                                        <th>Company</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Message</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $contacts = $conn->query("SELECT * FROM contact_submissions ORDER BY created_at DESC");
                                    while($cont = $contacts->fetch_assoc()):
                                    ?>
                                    <tr>
                                        <td><?php echo date('Y-m-d H:i', strtotime($cont['created_at'])); ?></td>
                                        <td><?php echo htmlspecialchars($cont['name']); ?></td>
                                        <td><?php echo htmlspecialchars($cont['company']); ?></td>
                                        <td><a href="mailto:<?php echo $cont['email']; ?>"><?php echo htmlspecialchars($cont['email']); ?></a></td>
                                        <td><?php echo htmlspecialchars($cont['phone']); ?></td>
                                        <td><?php echo nl2br(htmlspecialchars(substr($cont['message'], 0, 100))); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('.summernote').summernote({
            height: 300,
            toolbar: [
                ['style', ['bold', 'italic', 'underline', 'clear']],
                ['font', ['strikethrough', 'superscript', 'subscript']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['insert', ['link', 'picture', 'video']],
                ['view', ['fullscreen', 'codeview']]
            ]
        });
    });
    
    // Image upload preview
    $('#imageInput').on('change', function(e) {
        var files = e.target.files;
        var preview = $('#new-images-preview');
        preview.empty();
        
        for(var i = 0; i < files.length; i++) {
            var file = files[i];
            var reader = new FileReader();
            
            reader.onload = function(e) {
                preview.append(`
                    <div class="image-preview">
                        <img src="${e.target.result}" style="width:150px;height:150px;object-fit:cover;">
                        <div class="remove-image-btn" onclick="$(this).parent().remove()">×</div>
                    </div>
                `);
            }
            
            reader.readAsDataURL(file);
        }
    });
    
    function removeImage(imgPath) {
        if(confirm('Remove this image?')) {
            var currentRemoved = $('#remove_images').val();
            if(currentRemoved) {
                $('#remove_images').val(currentRemoved + ',' + imgPath);
            } else {
                $('#remove_images').val(imgPath);
            }
            $('.image-preview[data-img="' + imgPath + '"]').remove();
        }
    }
    
    // Auto-generate slug from name
    $('input[name="name"]').on('blur', function() {
        var slugInput = $('input[name="slug"]');
        if(slugInput.val() == '') {
            var name = $(this).val();
            var slug = name.toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');
            slugInput.val(slug);
        }
    });
</script>
</body>
</html>