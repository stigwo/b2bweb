<?php
require_once 'config.php';

// Debug: Check if database connection works
if (!$conn) {
    die("Database connection failed");
}

$products = $conn->query("SELECT * FROM products ORDER BY featured DESC, name ASC");

// Check if query failed
if (!$products) {
    die("Query failed: " . $conn->error);
}

$menuItems = getMenuItems($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our B2B Product Line | Revobake China</title>
    <meta name="description" content="Professional bakery equipment for B2B buyers - industrial ovens, mixers, proofers, and complete bakery solutions.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        .navbar-dark.bg-dark { background-color: #1a1a2e !important; }
        .navbar-brand { font-weight: bold; font-size: 1.5rem; }
        .navbar-brand i { color: #e67e22; }
        
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
        
        .product-card {
            transition: transform 0.3s, box-shadow 0.3s;
            margin-bottom: 30px;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-radius: 10px;
            overflow: hidden;
            height: 100%;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .product-card-img {
            width: 100%;
            height: auto;
            min-height: 200px;
            object-fit: contain;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 20px;
            transition: transform 0.3s ease;
        }
        .product-card:hover .product-card-img {
            transform: scale(1.02);
        }
        
        .product-card .card-body {
            padding: 1.25rem;
            background: white;
        }
        
        .product-card .card-title {
            font-size: 1.25rem;
            font-weight: bold;
            color: #1a1a2e;
            margin-bottom: 0.75rem;
        }
        
        .product-card .card-text {
            color: #666;
            font-size: 0.9rem;
            line-height: 1.5;
            margin-bottom: 1rem;
        }
        
        .price-info {
            color: #e67e22;
            font-weight: bold;
            font-size: 1rem;
            margin: 10px 0;
            padding: 5px 0;
        }
        
        .products-hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 60px 0;
            margin-bottom: 40px;
            border-radius: 0 0 20px 20px;
        }
        .products-hero h1 {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 15px;
        }
        .products-hero p {
            font-size: 1.1rem;
            opacity: 0.95;
        }
        
        .filter-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        .search-input {
            border-radius: 25px 0 0 25px;
            border-right: none;
        }
        .search-btn {
            border-radius: 0 25px 25px 0;
            background-color: #e67e22;
            border-color: #e67e22;
            color: white;
        }
        .search-btn:hover {
            background-color: #d35400;
        }
        
        .breadcrumb {
            background: transparent;
            padding: 15px 0;
            margin-bottom: 0;
        }
        .breadcrumb-item a {
            color: #e67e22;
            text-decoration: none;
        }
        
        footer {
            background: #1a1a2e;
            color: #fff;
            padding: 50px 0 20px;
            margin-top: 60px;
        }
        footer a {
            color: #e67e22;
            text-decoration: none;
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
        
        @media (max-width: 768px) {
            .products-hero h1 { font-size: 1.8rem; }
            .product-card-img { padding: 15px; min-height: 180px; }
        }
        
        .no-results {
            text-align: center;
            padding: 50px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        
        .product-link {
            text-decoration: none;
            color: inherit;
            display: block;
            height: 100%;
        }
        .product-link:hover {
            text-decoration: none;
            color: inherit;
        }
    </style>
</head>
<body>

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
                    <a class="nav-link" href="index.php?page=<?php echo urlencode($item['slug']); ?>">
                        <?php echo htmlspecialchars($item['title']); ?>
                    </a>
                </li>
                <?php endforeach; ?>
                <li class="nav-item">
                    <a class="nav-link active" href="index.php?page=products">
                        <i class="fas fa-box"></i> Products
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php?page=home">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">Products</li>
        </ol>
    </nav>
</div>

<div class="products-hero">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1><i class="fas fa-boxes"></i> Our B2B Product Line</h1>
                <p>Discover our complete range of professional bakery equipment. All products are CE certified and come with international warranty.</p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <i class="fas fa-industry fa-4x opacity-50"></i>
            </div>
        </div>
    </div>
</div>

<div class="container my-4">
    <div class="filter-section">
        <div class="row align-items-center">
            <div class="col-md-8">
                <div class="input-group">
                    <input type="text" id="searchInput" class="form-control search-input" placeholder="Search products by name or description..." onkeyup="filterProducts()">
                    <button class="btn search-btn" type="button" onclick="filterProducts()">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
            </div>
            <div class="col-md-4 mt-3 mt-md-0 text-md-end">
                <div class="btn-group w-100">
                    <button class="btn btn-outline-secondary" onclick="sortProducts('name')">
                        <i class="fas fa-sort-alpha-down"></i> Sort by Name
                    </button>
                    <button class="btn btn-outline-secondary" onclick="sortProducts('featured')">
                        <i class="fas fa-star"></i> Featured First
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="row" id="productsGrid">
        <?php 
        $hasProducts = false;
        while($prod = $products->fetch_assoc()): 
            $hasProducts = true;
            $images = explode(',', $prod['images']);
            $firstImg = trim($images[0]);
            
            $imgPath = 'img/placeholder.jpg';
            if(!empty($firstImg) && (file_exists($firstImg) || filter_var($firstImg, FILTER_VALIDATE_URL))) {
                $imgPath = $firstImg;
            }
            
            // CORRECT URL for product detail page
            $productUrl = "index.php?product_id=" . $prod['id'];
        ?>
        <div class="col-md-6 col-lg-4 product-item" 
             data-name="<?php echo strtolower(htmlspecialchars($prod['name'])); ?>" 
             data-description="<?php echo strtolower(htmlspecialchars(strip_tags($prod['short_description']))); ?>"
             data-featured="<?php echo $prod['featured']; ?>"
             data-name-sort="<?php echo htmlspecialchars($prod['name']); ?>">
            
            <div class="card product-card h-100">
                <a href="<?php echo $productUrl; ?>" class="product-link">
                    <img src="<?php echo $imgPath; ?>" 
                         class="product-card-img" 
                         alt="<?php echo htmlspecialchars($prod['name']); ?>"
                         loading="lazy"
                         onerror="this.src='img/placeholder.jpg'">
                    <div class="card-body d-flex flex-column">
                        <?php if($prod['featured']): ?>
                            <div class="mb-2">
                                <span class="badge bg-warning text-dark">
                                    <i class="fas fa-star"></i> Featured
                                </span>
                            </div>
                        <?php endif; ?>
                        <h5 class="card-title"><?php echo htmlspecialchars($prod['name']); ?></h5>
                        <p class="card-text flex-grow-1">
                            <?php echo htmlspecialchars(substr(strip_tags($prod['short_description']), 0, 120)); ?>...
                        </p>
                        <div class="price-info">
                            <i class="fas fa-tag"></i> <?php echo htmlspecialchars($prod['price_info']); ?>
                        </div>
                        <div class="mt-3">
                            <span class="btn btn-primary w-100">
                                <i class="fas fa-eye"></i> View Details & Inquire
                            </span>
                        </div>
                    </div>
                </a>
            </div>
        </div>
        <?php endwhile; ?>
        
        <?php if(!$hasProducts): ?>
        <div class="col-12">
            <div class="no-results">
                <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
                <h3>No Products Found</h3>
                <p>Check back soon for our product catalog or contact us directly for inquiries.</p>
                <a href="index.php?page=contact" class="btn btn-primary">Contact Sales Team</a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<footer>
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4">
                <h5><i class="fas fa-bread-slice"></i> Revobake China</h5>
                <p>Professional bakery solutions for B2B partners worldwide. Since 2005, we've been providing high-quality commercial baking equipment to over 2000+ clients globally.</p>
                <div class="social-icons mt-3">
                    <a href="#"><i class="fab fa-weixin"></i></a>
                    <a href="#"><i class="fab fa-whatsapp"></i></a>
                    <a href="#"><i class="fab fa-linkedin"></i></a>
                    <a href="#"><i class="fab fa-facebook"></i></a>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function filterProducts() {
        const searchTerm = document.getElementById('searchInput').value.toLowerCase().trim();
        const productItems = document.querySelectorAll('.product-item');
        let visibleCount = 0;
        
        productItems.forEach(item => {
            const name = item.getAttribute('data-name');
            const description = item.getAttribute('data-description');
            
            if(searchTerm === '' || name.includes(searchTerm) || description.includes(searchTerm)) {
                item.style.display = '';
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        });
        
        let noResultsMsg = document.getElementById('noResultsMsg');
        if(visibleCount === 0) {
            if(!noResultsMsg) {
                noResultsMsg = document.createElement('div');
                noResultsMsg.id = 'noResultsMsg';
                noResultsMsg.className = 'col-12';
                noResultsMsg.innerHTML = `
                    <div class="no-results">
                        <i class="fas fa-search fa-4x text-muted mb-3"></i>
                        <h3>No products found</h3>
                        <p>Try a different search term or browse our full catalog.</p>
                        <button class="btn btn-primary" onclick="resetSearch()">Clear Search</button>
                    </div>
                `;
                document.getElementById('productsGrid').appendChild(noResultsMsg);
            }
        } else if(noResultsMsg) {
            noResultsMsg.remove();
        }
    }
    
    function resetSearch() {
        document.getElementById('searchInput').value = '';
        filterProducts();
    }
    
    function sortProducts(type) {
        const grid = document.getElementById('productsGrid');
        const items = Array.from(document.querySelectorAll('.product-item'));
        
        if(type === 'name') {
            items.sort((a, b) => {
                const nameA = a.getAttribute('data-name-sort');
                const nameB = b.getAttribute('data-name-sort');
                return nameA.localeCompare(nameB);
            });
        } else if(type === 'featured') {
            items.sort((a, b) => {
                const featuredA = parseInt(a.getAttribute('data-featured'));
                const featuredB = parseInt(b.getAttribute('data-featured'));
                return featuredB - featuredA;
            });
        }
        
        items.forEach(item => grid.appendChild(item));
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        const productCards = document.querySelectorAll('.product-card');
        productCards.forEach((card, index) => {
            setTimeout(() => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'all 0.5s ease';
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, 50);
            }, index * 100);
        });
        
        setTimeout(() => {
            productCards.forEach(card => {
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            });
        }, 500);
    });
</script>
</body>
</html>