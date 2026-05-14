<?php
require_once 'config.php';

// ==============================================
// ROUTING - IMPORTANT: Order matters!
// ==============================================

// 1. FIRST: Check for product detail page (product_id parameter)
if (isset($_GET['product_id']) && is_numeric($_GET['product_id'])) {
    include 'product-detail.php';
    exit;
}

// 2. SECOND: Check for special standalone pages
$page = isset($_GET['page']) ? $_GET['page'] : 'home';
$slug = $conn->real_escape_string($page);

// Special page routing
if ($slug == 'products') {
    include 'products.php';
    exit;
} elseif ($slug == 'contact') {
    include 'contact.php';
    exit;
}

// 3. THIRD: Regular CMS pages from database
$result = $conn->query("SELECT * FROM pages WHERE slug='$slug' AND is_published=1 LIMIT 1");
$pageData = $result->fetch_assoc();

// If page not found, show 404
if (!$pageData) {
    $pageData = [
        'title' => '404 - Page Not Found',
        'content' => '
        <div class="text-center py-5">
            <i class="fas fa-search fa-4x text-muted mb-4"></i>
            <h1 class="display-4">404 - Page Not Found</h1>
            <p class="lead">The page you are looking for does not exist or has been moved.</p>
            <a href="index.php?page=home" class="btn btn-primary mt-3">
                <i class="fas fa-home"></i> Back to Home
            </a>
        </div>'
    ];
}

// Get menu items for navigation
$menuItems = getMenuItems($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageData['title']); ?> | Revobake B2B China</title>
    <meta name="description" content="Professional baking equipment for B2B buyers - industrial ovens, mixers, proofers, and complete bakery solutions from Revobake China.">
    <meta name="keywords" content="bakery equipment, commercial ovens, baking machines, B2B, China manufacturer">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        /* Navigation */
        .navbar-dark.bg-dark { background-color: #1a1a2e !important; }
        .navbar-brand { font-weight: bold; font-size: 1.5rem; }
        .navbar-brand i { color: #e67e22; }
        .nav-link.active { color: #e67e22 !important; font-weight: bold; }
        
        /* Buttons */
        .btn-primary { 
            background-color: #e67e22; 
            border-color: #e67e22; 
            transition: all 0.3s ease;
        }
        .btn-primary:hover { 
            background-color: #d35400; 
            border-color: #d35400;
            transform: translateY(-2px);
        }
        .btn-outline-primary {
            border-color: #e67e22;
            color: #e67e22;
        }
        .btn-outline-primary:hover {
            background-color: #e67e22;
            border-color: #e67e22;
        }
        
        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 80px 0;
            margin-bottom: 40px;
            border-radius: 0 0 20px 20px;
        }
        .hero-section h1 {
            font-size: 3rem;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .hero-section p {
            font-size: 1.2rem;
            opacity: 0.95;
        }
        
        /* Feature Cards */
        .feature-card {
            text-align: center;
            padding: 30px;
            border-radius: 10px;
            transition: all 0.3s ease;
            background: white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 30px;
            height: 100%;
        }
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .feature-card i {
            font-size: 3rem;
            color: #e67e22;
            margin-bottom: 20px;
        }
        .feature-card h3 {
            font-size: 1.5rem;
            margin-bottom: 15px;
            color: #1a1a2e;
        }
        .feature-card p {
            color: #666;
            line-height: 1.6;
        }
        
        /* CTA Section */
        .cta-section {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: white;
            padding: 60px 0;
            margin: 40px 0;
            border-radius: 10px;
        }
        .cta-section h2 {
            font-size: 2rem;
            margin-bottom: 15px;
        }
        
        /* Footer */
        footer {
            background: #1a1a2e;
            color: #fff;
            padding: 50px 0 20px;
            margin-top: 60px;
        }
        footer a {
            color: #e67e22;
            text-decoration: none;
            transition: color 0.3s;
        }
        footer a:hover { 
            color: #d35400;
            text-decoration: underline;
        }
        footer h5 {
            margin-bottom: 20px;
            font-weight: bold;
        }
        footer .social-icons a {
            display: inline-block;
            margin-right: 15px;
            font-size: 1.5rem;
        }
        
        /* Breadcrumb */
        .breadcrumb {
            background: transparent;
            padding: 15px 0;
            margin-bottom: 0;
        }
        .breadcrumb-item a {
            color: #e67e22;
            text-decoration: none;
        }
        .breadcrumb-item.active {
            color: #6c757d;
        }
        
        /* Page Content */
        .page-content {
            min-height: 400px;
        }
        .page-content h1, .page-content h2 {
            color: #1a1a2e;
            margin-top: 20px;
            margin-bottom: 15px;
        }
        .page-content h3 {
            color: #e67e22;
            margin-top: 20px;
            margin-bottom: 15px;
        }
        .page-content img {
            max-width: 100%;
            height: auto;
            border-radius: 10px;
            margin: 20px 0;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .hero-section h1 { font-size: 2rem; }
            .hero-section p { font-size: 1rem; }
            .feature-card { padding: 20px; }
            .cta-section h2 { font-size: 1.5rem; }
        }
        
        /* Back to top button */
        .back-to-top {
            position: fixed;
            bottom: 20px;
            right: 20px;
            display: none;
            background: #e67e22;
            color: white;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            text-align: center;
            line-height: 45px;
            cursor: pointer;
            transition: all 0.3s;
            z-index: 1000;
        }
        .back-to-top:hover {
            background: #d35400;
            transform: translateY(-3px);
        }
        
        /* Loading animation */
        .loading {
            display: none;
            text-align: center;
            padding: 50px;
        }
    </style>
</head>
<body>

<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
    <div class="container">
        <a class="navbar-brand" href="index.php?page=home">
            <i class="fas fa-bread-slice"></i> Revobake B2B China
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <?php foreach($menuItems as $item): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($slug == $item['slug']) ? 'active' : ''; ?>" 
                       href="index.php?page=<?php echo urlencode($item['slug']); ?>">
                        <?php echo htmlspecialchars($item['title']); ?>
                    </a>
                </li>
                <?php endforeach; ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($slug == 'products') ? 'active' : ''; ?>" 
                       href="index.php?page=products">
                        <i class="fas fa-box"></i> Products
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Breadcrumb (only for non-home pages) -->
<?php if($slug != 'home'): ?>
<div class="container">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php?page=home">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($pageData['title']); ?></li>
        </ol>
    </nav>
</div>
<?php endif; ?>

<!-- Main Content -->
<div class="container page-content my-4">
    <?php 
    // For home page, add hero section and features
    if($slug == 'home'): 
    ?>
    <div class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-7">
                    <h1><?php echo htmlspecialchars($pageData['title']); ?></h1>
                    <p>Professional baking equipment for wholesale buyers. Trusted by over 2000+ bakeries worldwide.</p>
                    <div class="mt-4">
                        <a href="index.php?page=products" class="btn btn-primary btn-lg me-3">
                            <i class="fas fa-box"></i> View Products
                        </a>
                        <a href="index.php?page=contact" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-envelope"></i> Contact Sales
                        </a>
                    </div>
                </div>
                <div class="col-lg-5 text-center d-none d-lg-block">
                    <i class="fas fa-industry fa-6x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Feature Cards for Home Page -->
    <div class="row mt-4">
        <div class="col-md-4">
            <div class="feature-card">
                <i class="fas fa-trophy"></i>
                <h3>Premium Quality</h3>
                <p>All equipment meets international standards with CE and ISO certifications.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="feature-card">
                <i class="fas fa-headset"></i>
                <h3>24/7 Support</h3>
                <p>Dedicated B2B support team available round the clock for your business needs.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="feature-card">
                <i class="fas fa-globe"></i>
                <h3>Worldwide Shipping</h3>
                <p>Fast and reliable shipping to over 50 countries with installation support.</p>
            </div>
        </div>
    </div>
    
    <!-- Dynamic content from database -->
    <?php echo $pageData['content']; ?>
    
    <!-- CTA Section -->
    <div class="cta-section text-center">
        <div class="container">
            <h2>Ready to Grow Your Bakery Business?</h2>
            <p class="mb-4">Get a custom quote for your commercial bakery equipment needs.</p>
            <a href="index.php?page=contact" class="btn btn-primary btn-lg">
                <i class="fas fa-paper-plane"></i> Request a Quote
            </a>
        </div>
    </div>
    
    <?php else: ?>
        <!-- Regular page content (non-home) -->
        <?php echo $pageData['content']; ?>
    <?php endif; ?>
</div>

<!-- Footer with dynamic year -->
<footer>
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4">
                <h5><i class="fas fa-bread-slice"></i> Revobake China</h5>
                <p>Professional bakery solutions for B2B partners worldwide. Since 2005, we've been providing high-quality commercial baking equipment to over 2000+ clients globally.</p>
                <div class="social-icons mt-3">
                    <a href="#" aria-label="WeChat"><i class="fab fa-weixin"></i></a>
                    <a href="#" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                    <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin"></i></a>
                    <a href="#" aria-label="Facebook"><i class="fab fa-facebook"></i></a>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <h5>Quick Links</h5>
                <ul class="list-unstyled">
                    <?php foreach($menuItems as $item): ?>
                    <li class="mb-2"><a href="index.php?page=<?php echo urlencode($item['slug']); ?>"><?php echo htmlspecialchars($item['title']); ?></a></li>
                    <?php endforeach; ?>
                    <li class="mb-2"><a href="index.php?page=products">All Products</a></li>
                    <li class="mb-2"><a href="index.php?page=contact">Contact Us</a></li>
                </ul>
            </div>
            <div class="col-md-4 mb-4">
                <h5>Contact & Support</h5>
                <p><i class="fas fa-envelope"></i> <a href="mailto:sales@revobake.cn">sales@revobake.cn</a></p>
                <p><i class="fas fa-phone"></i> <a href="tel:+862112345678">+86 21 1234 5678</a></p>
                <p><i class="fab fa-weixin"></i> WeChat: revobake_b2b</p>
                <p><i class="fab fa-whatsapp"></i> WhatsApp: +86 123 4567 890</p>
                <p><i class="fas fa-clock"></i> Mon-Fri: 9:00 - 18:00 (CST)</p>
            </div>
        </div>
        <hr class="bg-secondary">
        <div class="row">
            <div class="col-md-6">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> Revobake China. All B2B rights reserved.</p>
            </div>
            <div class="col-md-6 text-md-end">
                <a href="#" class="text-white me-3">Privacy Policy</a>
                <a href="#" class="text-white">Terms of Service</a>
            </div>
        </div>
    </div>
</footer>

<!-- Back to Top Button -->
<div class="back-to-top" id="backToTop">
    <i class="fas fa-arrow-up"></i>
</div>

<!-- JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Back to top button functionality
    const backToTopButton = document.getElementById('backToTop');
    
    window.addEventListener('scroll', function() {
        if (window.scrollY > 300) {
            backToTopButton.style.display = 'block';
        } else {
            backToTopButton.style.display = 'none';
        }
    });
    
    backToTopButton.addEventListener('click', function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
    
    // Add animation to feature cards on scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);
    
    document.querySelectorAll('.feature-card').forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px)';
        card.style.transition = 'all 0.6s ease';
        observer.observe(card);
    });
    
    // Smooth page load animation
    document.addEventListener('DOMContentLoaded', function() {
        document.body.style.opacity = '0';
        document.body.style.transition = 'opacity 0.3s ease';
        setTimeout(() => {
            document.body.style.opacity = '1';
        }, 100);
    });
    
    // Add active class to current nav item
    const currentUrl = window.location.href;
    document.querySelectorAll('.navbar-nav .nav-link').forEach(link => {
        if (link.href === currentUrl) {
            link.classList.add('active');
        }
    });
</script>
</body>
</html>