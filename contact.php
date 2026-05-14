<?php
require_once 'config.php';

$message = '';
$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verify reCAPTCHA
    $recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';
    if (!verifyRecaptcha($recaptchaResponse)) {
        $error = "<div class='alert alert-danger'>Please complete the reCAPTCHA verification.</div>";
    } else {
        // Sanitize inputs
        $name = sanitizeInput($_POST['name'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $company = sanitizeInput($_POST['company'] ?? '');
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $msg = sanitizeInput($_POST['message'] ?? '');
        
        $errors = [];
        
        if(empty($name)) $errors[] = "Name is required";
        if(empty($email)) $errors[] = "Email is required";
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
        if(empty($msg)) $errors[] = "Message is required";
        
        if(empty($errors)) {
            // Save to database
            $stmt = $conn->prepare("INSERT INTO contact_submissions (name, email, company, phone, message) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $name, $email, $company, $phone, $msg);
            $stmt->execute();
            
            // Send email to admin
            $admin_subject = "B2B Contact Form - Revobake China";
            $admin_body = "
            <html>
            <head><style>body{font-family:Arial,sans-serif;}</style></head>
            <body>
                <h2>New Contact Form Submission</h2>
                <p><strong>Name:</strong> " . htmlspecialchars($name) . "</p>
                <p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>
                <p><strong>Company:</strong> " . htmlspecialchars($company) . "</p>
                <p><strong>Phone:</strong> " . htmlspecialchars($phone) . "</p>
                <p><strong>Message:</strong><br>" . nl2br(htmlspecialchars($msg)) . "</p>
            </body>
            </html>
            ";
            
            sendEmail("bossplass@gmail.com", $admin_subject, $admin_body);
            
           
            
            $message = "<div class='alert alert-success'><i class='fas fa-check-circle'></i> Message sent successfully! We'll reply within 24 hours.</div>";
        } else {
            $error = "<div class='alert alert-danger'><ul>";
            foreach($errors as $err) {
                $error .= "<li>" . htmlspecialchars($err) . "</li>";
            }
            $error .= "</ul></div>";
        }
    }
}

$menuItems = getMenuItems($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us | Revobake B2B China</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google reCAPTCHA v2 -->
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <style>
        .navbar-dark.bg-dark { background-color: #1a1a2e !important; }
        .btn-primary { background-color: #e67e22; border-color: #e67e22; }
        .btn-primary:hover { background-color: #d35400; }
        .contact-form { background: #f8f9fa; border-radius: 10px; padding: 30px; }
        .contact-info { background: #1a1a2e; color: white; border-radius: 10px; padding: 30px; height: 100%; }
        .contact-info i { color: #e67e22; margin-right: 10px; }
        footer { background: #1a1a2e; color: white; padding: 50px 0 20px; margin-top: 60px; }
        footer a { color: #e67e22; }
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

<div class="container my-5">
    <?php echo $message; ?>
    <?php echo $error; ?>
    
    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="contact-form">
                <h2><i class="fas fa-envelope"></i> Contact Our B2B Team</h2>
                <p class="text-muted">Fill out the form below and we'll get back to you within 24 hours.</p>
                
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Address <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Company Name</label>
                        <input type="text" name="company" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone Number</label>
                        <input type="tel" name="phone" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Message <span class="text-danger">*</span></label>
                        <textarea name="message" class="form-control" rows="5" required></textarea>
                    </div>
                    
                    <!-- Google reCAPTCHA v2 -->
                    <div class="g-recaptcha mb-3" data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>"></div>
                    
                    <button type="submit" class="btn btn-primary btn-lg w-100">
                        <i class="fas fa-paper-plane"></i> Send Message
                    </button>
                </form>
            </div>
        </div>
        
        <div class="col-lg-6 mb-4">
            <div class="contact-info">
                <h3><i class="fas fa-building"></i> Our Office</h3>
                <p><i class="fas fa-map-marker-alt"></i> Revobake (Shanghai) Co., Ltd.<br>No. 123, Pudong New Area<br>Shanghai, China 200120</p>
                <hr class="bg-secondary">
                <p><i class="fas fa-phone"></i> +86 21 1234 5678</p>
                <p><i class="fas fa-envelope"></i> sales@revobake.cn</p>
                <p><i class="fab fa-weixin"></i> WeChat: revobake_official</p>
                <p><i class="fab fa-whatsapp"></i> WhatsApp: +86 123 4567 890</p>
                <hr class="bg-secondary">
                <h4><i class="fas fa-clock"></i> Business Hours</h4>
                <p>Monday - Friday: 9:00 - 18:00 (CST)<br>Saturday: 9:00 - 13:00</p>
            </div>
        </div>
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
                <p><i class="fas fa-envelope"></i> sales@revobake.cn</p>
            </div>
        </div>
        <hr>
        <p class="text-center">&copy; <?php echo date('Y'); ?> Revobake B2B China</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>