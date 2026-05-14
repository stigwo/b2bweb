<?php
require_once 'config.php';

// Get product ID from URL
$id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;

if($id == 0) {
    header('Location: index.php?page=products');
    exit;
}

// Fetch product details
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$prod = $result->fetch_assoc();

if(!$prod) {
    header('Location: index.php?page=products');
    exit;
}

// Process inquiry form submission
$message = '';
$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_inquiry'])) {
    // Verify reCAPTCHA
    $recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';
    if (!verifyRecaptcha($recaptchaResponse)) {
        $error = "<div class='alert alert-danger'>Please complete the reCAPTCHA verification.</div>";
    } else {
        // Sanitize inputs
        $company = sanitizeInput($_POST['company'] ?? '');
        $contact_person = sanitizeInput($_POST['contact_person'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $quantity = sanitizeInput($_POST['quantity'] ?? '');
        $inquiry_message = sanitizeInput($_POST['message'] ?? '');
        
        $errors = [];
        
        if(empty($company)) $errors[] = "Company name is required";
        if(empty($contact_person)) $errors[] = "Contact person name is required";
        if(empty($email)) $errors[] = "Email is required";
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
        if(empty($inquiry_message)) $errors[] = "Message is required";
        
        if(empty($errors)) {
            // Save to database
            $stmt = $conn->prepare("INSERT INTO product_inquiries (product_id, company_name, contact_person, email, phone, quantity, message) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssss", $id, $company, $contact_person, $email, $phone, $quantity, $inquiry_message);
            
            if($stmt->execute()) {
                // Send email to admin
                $admin_subject = "B2B Inquiry: {$prod['name']} from {$company}";
                $admin_body = "
                <html>
                <head><style>body{font-family:Arial,sans-serif;}</style></head>
                <body>
                    <h2>New B2B Product Inquiry</h2>
                    <p><strong>Product:</strong> {$prod['name']}</p>
                    <p><strong>Company:</strong> " . htmlspecialchars($company) . "</p>
                    <p><strong>Contact Person:</strong> " . htmlspecialchars($contact_person) . "</p>
                    <p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>
                    <p><strong>Phone:</strong> " . htmlspecialchars($phone) . "</p>
                    <p><strong>Quantity:</strong> " . htmlspecialchars($quantity) . "</p>
                    <p><strong>Message:</strong><br>" . nl2br(htmlspecialchars($inquiry_message)) . "</p>
                </body>
                </html>
                ";
                
                sendEmail("bossplass@gmail.com", $admin_subject, $admin_body);
                
              
                
                $message = "<div class='alert alert-success'><i class='fas fa-check-circle'></i> Thank you! Your inquiry has been sent. Our sales team will contact you within 24 hours.</div>";
                
                // Redirect after 3 seconds
                echo "<script>setTimeout(function() { window.location.href = 'index.php?page=products&product_id={$id}&success=1'; }, 3000);</script>";
            } else {
                $error = "<div class='alert alert-danger'>Database error. Please try again later.</div>";
            }
        } else {
            $error = "<div class='alert alert-danger'><ul>";
            foreach($errors as $err) {
                $error .= "<li>" . htmlspecialchars($err) . "</li>";
            }
            $error .= "</ul></div>";
        }
    }
}

// Check for success parameter
if(isset($_GET['success']) && $_GET['success'] == 1) {
    $message = "<div class='alert alert-success'><i class='fas fa-check-circle'></i> Thank you! Your inquiry has been sent successfully.</div>";
}

// Get menu items
$menuItems = getMenuItems($conn);

// Parse images
$images = [];
if($prod['images']) {
    $images = explode(',', $prod['images']);
    $images = array_map('trim', $images);
    $images = array_filter($images, function($img) {
        return !empty($img) && (file_exists($img) || filter_var($img, FILTER_VALIDATE_URL));
    });
}
if(empty($images)) {
    $images = ['img/placeholder.jpg'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($prod['name']); ?> | Revobake B2B China</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google reCAPTCHA v2 -->
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <style>
        .navbar-dark.bg-dark { background-color: #1a1a2e !important; }
        .btn-primary { background-color: #e67e22; border-color: #e67e22; }
        .btn-primary:hover { background-color: #d35400; }
        .inquiry-form { background: #f8f9fa; border-radius: 10px; padding: 30px; margin-top: 30px; }
        .product-gallery { background: #f8f9fa; border-radius: 10px; padding: 20px; }
        .main-image img { width: 100%; height: auto; cursor: pointer; }
        .thumbnail { width: 80px; cursor: pointer; margin: 5px; border: 2px solid transparent; }
        .thumbnail:hover, .thumbnail.active { border-color: #e67e22; }
        footer { background: #1a1a2e; color: white; padding: 50px 0 20px; margin-top: 60px; }
        footer a { color: #e67e22; }
        .price-info { font-size: 1.5rem; color: #e67e22; font-weight: bold; margin: 20px 0; }
        .specs-box { background: #f8f9fa; padding: 20px; border-radius: 10px; margin: 20px 0; }
        .g-recaptcha { margin: 20px 0; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
    <div class="container">
        <a class="navbar-brand" href="index.php?page=home"><i class="fas fa-bread-slice"></i> Revobake B2B China</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <?php foreach($menuItems as $item): ?>
                <li class="nav-item">
                    <a class="nav-link" href="index.php?page=<?php echo urlencode($item['slug']); ?>"><?php echo htmlspecialchars($item['title']); ?></a>
                </li>
                <?php endforeach; ?>
                <li class="nav-item">
                    <a class="nav-link active" href="index.php?page=products">Products</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container my-4">
    <?php echo $message; ?>
    <?php echo $error; ?>
    
    <div class="row">
        <div class="col-lg-6">
            <div class="product-gallery">
                <div class="main-image">
                    <img id="mainImage" src="<?php echo $images[0]; ?>" alt="<?php echo htmlspecialchars($prod['name']); ?>">
                </div>
                <?php if(count($images) > 1): ?>
                <div class="thumbnail-list mt-3 d-flex gap-2">
                    <?php foreach($images as $index => $img): ?>
                    <img src="<?php echo $img; ?>" class="thumbnail <?php echo $index == 0 ? 'active' : ''; ?>" onclick="changeImage('<?php echo $img; ?>', this)">
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="col-lg-6">
            <h1><?php echo htmlspecialchars($prod['name']); ?></h1>
            <?php if($prod['featured']): ?>
                <span class="badge bg-warning mb-2"><i class="fas fa-star"></i> Featured Product</span>
            <?php endif; ?>
            <div class="price-info"><?php echo htmlspecialchars($prod['price_info']); ?></div>
            <div class="short-description"><?php echo nl2br(htmlspecialchars($prod['short_description'])); ?></div>
            <div class="specs-box">
                <h4><i class="fas fa-microchip"></i> Technical Specifications</h4>
                <?php echo $prod['technical_specs']; ?>
            </div>
            <div class="full-description"><?php echo $prod['description']; ?></div>
        </div>
    </div>
    
    <!-- Inquiry Form with reCAPTCHA -->
    <div class="inquiry-form">
        <h3><i class="fas fa-paper-plane"></i> Request a Quotation</h3>
        <p class="text-muted">Fill out the form below and our B2B sales team will respond within 24 hours.</p>
        
        <form method="post">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Company Name <span class="text-danger">*</span></label>
                    <input type="text" name="company" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Contact Person <span class="text-danger">*</span></label>
                    <input type="text" name="contact_person" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Phone</label>
                    <input type="tel" name="phone" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Quantity</label>
                    <input type="text" name="quantity" class="form-control" placeholder="e.g., 5 units, container">
                </div>
                <div class="col-12 mb-3">
                    <label class="form-label">Message <span class="text-danger">*</span></label>
                    <textarea name="message" class="form-control" rows="4" required></textarea>
                </div>
                
                <!-- Google reCAPTCHA v2 -->
                <div class="col-12 mb-3">
                    <div class="g-recaptcha" data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>"></div>
                </div>
                
                <div class="col-12">
                    <button type="submit" name="submit_inquiry" class="btn btn-primary btn-lg">
                        <i class="fas fa-paper-plane"></i> Send Inquiry
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<footer>
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4">
                <h5><i class="fas fa-bread-slice"></i> Revobake China</h5>
                <p>Professional bakery solutions for B2B partners worldwide.</p>
            </div>
            <div class="col-md-4 mb-4">
                <h5>Quick Links</h5>
                <ul class="list-unstyled">
                    <?php foreach($menuItems as $item): ?>
                    <li><a href="index.php?page=<?php echo urlencode($item['slug']); ?>"><?php echo htmlspecialchars($item['title']); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="col-md-4 mb-4">
                <h5>Contact</h5>
                <p><i class="fas fa-envelope"></i> sales@revobake.cn<br>
                <i class="fas fa-phone"></i> +86 21 1234 5678</p>
            </div>
        </div>
        <hr>
        <p class="text-center">&copy; <?php echo date('Y'); ?> Revobake B2B China</p>
    </div>
</footer>

<script>
function changeImage(src, element) {
    document.getElementById('mainImage').src = src;
    document.querySelectorAll('.thumbnail').forEach(thumb => {
        thumb.classList.remove('active');
    });
    element.classList.add('active');
}
</script>
</body>
</html>