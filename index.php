<?php 
session_start();
include_once("db.php"); 

if(isset($_GET['logout']) && $_GET['logout'] == 'success') {
  echo '
  <script>
  window.addEventListener("load", function() {
    Swal.fire({
        icon: "info",
        title: "Successfully Logged Out",
        text: "We hope to see you again soon!",
        timer: 3000,
        showConfirmButton: false
    });
  });
  </script>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Elegance Salon | Premium Beauty & Styling</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="/eproject/assets/css/style.css">
    <script>
    // Global Premium SweetAlert2 Theme Configuration (Sync with header.php)
    window.addEventListener('DOMContentLoaded', () => {
        const PremiumSwal = Swal.mixin({
            position: 'center',
            background: '#0f0f0f',
            color: '#ffffff',
            confirmButtonColor: '#D4AF37',
            cancelButtonColor: '#333',
            backdrop: 'rgba(0,0,0,0.85)',
            customClass: {
                popup: 'glass-card border-gold shadow-lg',
                title: 'text-gold fw-bold mb-3',
                content: 'text-light',
                confirmButton: 'px-4 py-2 rounded-pill',
                cancelButton: 'px-4 py-2 rounded-pill'
            }
        });
        window.Swal = PremiumSwal;
    });
    </script>
    <style>
        .hero {
            position: relative;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            background: url('/eproject/assets/images/hero_salon.png') no-repeat center center/cover;
            color: #fff;
        }
        .hero::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(to bottom, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0.4) 100%);
            z-index: 1;
        }
        .hero-content {
            position: relative;
            z-index: 2;
        }
        .hero h1 {
            font-size: 4rem;
            letter-spacing: 2px;
            color: var(--accent-gold);
            margin-bottom: 1rem;
        }
        .services-section {
            padding: 5rem 0;
            background: var(--bg-dark);
        }
        .section-title {
            text-align: center;
            margin-bottom: 4rem;
            color: var(--accent-gold);
        }
        .navbar {
            transition: all 0.4s ease-in-out;
            padding: 1.5rem 0;
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }
        /* Mobile Navbar Styling */
        @media (max-width: 991.98px) {
            .navbar-collapse {
                background: rgba(15, 15, 15, 0.98);
                backdrop-filter: blur(15px);
                margin-top: 1rem;
                padding: 1.5rem;
                border-radius: 15px;
                border: 1px solid rgba(255,255,255,0.1);
            }
            .navbar-brand img {
                height: 100px !important; /* Smaller logo on mobile */
            }
        }
    </style>
</head>
<body>

<nav id="topNav" class="navbar navbar-expand-lg navbar-dark" style="background: transparent !important; border: none; box-shadow: none;">
  <div class="container">
    <a class="navbar-brand" href="index.php" style="border: none !important; outline: none !important; box-shadow: none !important;">
      <img src="assets/images/logo.png" alt="ELEGANCE" height="150" style="border: none !important; outline: none !important;">
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-expanded="false">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto align-items-center">
        <li class="nav-item"><a class="nav-link px-3" href="#services">Services</a></li>
        <li class="nav-item"><a class="nav-link px-3" href="#about">About</a></li>
        <li class="nav-item"><a class="nav-link px-3" href="#contact">Contact</a></li>
        <?php if(isset($_SESSION['id'])): 
            $role_dir = $_SESSION['role'] == 'customer' ? 'customer' : 'staff';
            if($_SESSION['role'] == 'admin') $role_dir = 'admin';
            
            // Get profile pic
            $u_id = $_SESSION['id'];
            $p_pic = "/eproject/assets/img/default-user.png";
            if($_SESSION['role'] == 'customer') {
                $q = mysqli_query($con, "SELECT profile_pic FROM clients WHERE email='{$_SESSION['email']}'");
                if($r = mysqli_fetch_assoc($q)) $p_pic = $r['profile_pic'] ? "/eproject/assets/profile_pics/".$r['profile_pic'] : $p_pic;
            } else if($_SESSION['role'] !== 'admin') {
                $q = mysqli_query($con, "SELECT profile_pic FROM staff WHERE user_id=$u_id");
                if($r = mysqli_fetch_assoc($q)) $p_pic = $r['profile_pic'] ? "/eproject/assets/profile_pics/".$r['profile_pic'] : $p_pic;
            }
        ?>
        <li class="nav-item ms-lg-3">
          <a class="nav-link p-0" href="dashboard/<?= $role_dir ?>/index.php">
              <div style="width: 40px; height: 40px; border-radius: 50%; overflow: hidden; border: 2px solid var(--accent-gold);">
                  <img src="<?= $p_pic ?>?v=<?= time() ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
              </div>
          </a>
        </li>
        <?php else: ?>
        <li class="nav-item ms-lg-3 d-none d-lg-block">
            <a href="login.php" class="btn-gold px-5 py-2 fs-5 text-nowrap" style="display: inline-block;">Login / Book Now</a>
        </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

</script>


<header class="hero">
    <div class="hero-content">
        <h1>ELEVATE YOUR STYLE</h1>
        <p class="lead mb-4">Experience premium hair styling, manicures, and facials in an atmosphere of pure luxury.</p>
        <?php if(isset($_SESSION['role'])): 
            $role_link = "dashboard/customer/index.php"; // Default to customer dashboard
            $btn_text = "View My Dashboard";

            if($_SESSION['role'] !== 'customer') {
                $role_link = ($_SESSION['role'] == 'admin') ? "dashboard/admin/index.php" : "dashboard/staff/index.php";
                $btn_text = "View My Command Center";
            }
        ?>
            <a href="<?= $role_link ?>" class="btn btn-gold btn-lg"><?= $btn_text ?></a>
        <?php else: ?>
            <a href="login.php" class="btn btn-gold btn-lg">Book Your Experience</a>
        <?php endif; ?>
    </div>
</header>

<!-- New Guest Privilege Banner -->
<div class="container-fluid p-0" style="margin-top: -100px; position: relative; z-index: 10;">
    <div class="glass-card mx-auto text-center py-3 border-gold shadow-lg" style="max-width: 900px; border-radius: 15px; background: rgba(15, 15, 15, 0.7); backdrop-filter: blur(15px); -webkit-backdrop-filter: blur(15px);">
        <span class="text-gold fw-bold">NEW GUEST PRIVILEGE:</span> 
        <span class="text-light ms-2">Receive a complimentary scalp ritual or custom treatment with your first booking.</span>
        <a href="login.php" class="ms-3 text-gold text-decoration-underline small">Claim Invitation</a>
    </div>
</div>

<!-- The Elegance Ritual Section -->
<section class="container py-5" style="margin-top: 4rem;">
    <div class="text-center mb-5">
        <h6 class="text-gold text-uppercase tracking-widest" style="letter-spacing: 4px;">The Experience</h6>
        <h2 class="display-5 text-light fw-bold">The Elegance Ritual</h2>
    </div>
    <div class="row g-4">
        <div class="col-md-4">
            <div class="glass-card text-center h-100 py-5">
                <div class="ritual-icon mb-4 mx-auto" style="width: 60px; height: 60px; border: 1px solid var(--accent-gold); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                    <span class="text-gold fs-3">01</span>
                </div>
                <h4 class="text-gold">Curated Consultation</h4>
                <p class="text-muted px-3">We begin by understanding your unique features, style goals, and hair/skin health through a private, 1-on-1 discovery session.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="glass-card text-center h-100 py-5">
                <div class="ritual-icon mb-4 mx-auto" style="width: 60px; height: 60px; border: 1px solid var(--accent-gold); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                    <span class="text-gold fs-3">02</span>
                </div>
                <h4 class="text-gold">Artistic Performance</h4>
                <p class="text-muted px-3">Using world-class techniques and premium organic products, our experts perform your chosen service with meticulous precision.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="glass-card text-center h-100 py-5">
                <div class="ritual-icon mb-4 mx-auto" style="width: 60px; height: 60px; border: 1px solid var(--accent-gold); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                    <span class="text-gold fs-3">03</span>
                </div>
                <h4 class="text-gold">The Reveal</h4>
                <p class="text-muted px-3">Walk out with a look that commands respect and confidence. We provide home-care rituals to maintain your salon results.</p>
            </div>
        </div>
    </div>
</section>

<section id="services" class="services-section container">
    <div class="text-center mb-5">
        <h2 class="display-5 text-light fw-bold"><span class="glow-header">Our Premium Services</span></h2>
    </div>
    <div class="row g-4">
        <div class="col-md-4">
            <div class="glass-card text-center h-100">
                <h4 class="text-gold mt-3">Hair Styling & Care</h4>
                <p class="text-muted">Master stylists dedicated to crafting your perfect look with world-class products.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="glass-card text-center h-100 p-0 overflow-hidden">
                <img src="/eproject/assets/images/stylist_action.png" alt="Stylist Working" style="width: 100%; height: 250px; object-fit: cover;">
            </div>
        </div>
        <div class="col-md-4">
            <div class="glass-card text-center h-100">
                <h4 class="text-gold mt-3">Beauty & Wellness</h4>
                <p class="text-muted">From relaxing facials to precision manicures and pedicures.</p>
            </div>
        </div>
    </div>

    <!-- New Row Added -->
    <div class="row g-4 mt-4">
        <div class="col-md-4">
            <div class="glass-card text-center h-100 p-0 overflow-hidden">
                <img src="/eproject/assets/images/nails_service.png" alt="Nail Services" style="width: 100%; height: 250px; object-fit: cover;">
            </div>
        </div>
        <div class="col-md-4">
            <div class="glass-card text-center h-100">
                <h4 class="text-gold mt-3">Nail Artistry & Spa</h4>
                <p class="text-muted">Elite architectural nail design and holistic spa rituals for ultimate rejuvenation.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="glass-card text-center h-100 p-0 overflow-hidden">
                <img src="/eproject/assets/images/spa_service.png" alt="Spa Services" style="width: 100%; height: 250px; object-fit: cover;">
            </div>
        </div>
    </div>
</section>

<section id="about" class="container py-5 border-top" style="border-color: var(--glass-border) !important;">
    <div class="row align-items-center g-5">
        <div class="col-md-6">
            <h2 class="text-gold mb-4">About Elegance Salon</h2>
            <p class="text-muted lead">Welcome to Elegance Salon, where opulence meets expertise. Our salon is built on the philosophy that true beauty stems from customized care, deep relaxation, and professional execution.</p>
            <p class="text-muted">For over a decade, we have provided an uncompromised luxury experience to thousands of distinguished clients. From the moment you walk through our doors, our dedicated team of master stylists and beauty experts focus completely on rejuvenating your mind, body, and style.</p>
        </div>
        <div class="col-md-6">
            <div class="glass-card p-4 text-center">
                <h4 class="text-gold">Why Choose Us?</h4>
                <ul class="list-unstyled text-muted mt-3">
                    <li class="mb-2">✦ World-Class Certified Stylists</li>
                    <li class="mb-2">✦ Premium Organic Hair Products</li>
                    <li class="mb-2">✦ Extravagant Modern Facilities</li>
                    <li class="mb-2">✦ Relaxing Atmospheric Environment</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- Meet the Master Stylists Section -->
<section id="team" class="container py-5 border-top" style="border-color: var(--glass-border) !important;">
    <div class="text-center mb-5">
        <h6 class="text-gold text-uppercase tracking-widest" style="letter-spacing: 4px;">Expertise</h6>
        <h2 class="display-5 text-light fw-bold"><span class="glow-header">Meet the Master Stylists</span></h2>
        <p class="text-muted mx-auto" style="max-width: 700px;">Our team consists of world-class artisans who have dedicated decades to the pursuit of aesthetic perfection.</p>
    </div>
    <div class="row g-4">
        <div class="col-md-4">
            <div class="glass-card text-center h-100 p-0 overflow-hidden">
                <img src="/eproject/assets/images/stylist_julian.png" alt="Julian Vance" style="width: 100%; height: 350px; object-fit: cover;">
                <div class="p-4">
                    <h4 class="text-gold mb-1">Julian Vance</h4>
                    <small class="text-uppercase text-light d-block mb-3" style="letter-spacing: 2px; font-size: 0.75rem;">Creative Director</small>
                    <p class="text-muted small">Specializing in architectural cutting and bespoke styling for high-profile clients.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="glass-card text-center h-100 p-0 overflow-hidden">
                <img src="/eproject/assets/images/stylist_sasha.png" alt="Sasha Rose" style="width: 100%; height: 350px; object-fit: cover;">
                <div class="p-4">
                    <h4 class="text-gold mb-1">Sasha Rose</h4>
                    <small class="text-uppercase text-light d-block mb-3" style="letter-spacing: 2px; font-size: 0.75rem;">Senior Colorist</small>
                    <p class="text-muted small">A master of artistic dimension and organic color formulas that preserve hair integrity.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="glass-card text-center h-100 p-0 overflow-hidden">
                <img src="/eproject/assets/images/stylist_marco.png" alt="Marco Chen" style="width: 100%; height: 350px; object-fit: cover;">
                <div class="p-4">
                    <h4 class="text-gold mb-1">Marco Chen</h4>
                    <small class="text-uppercase text-light d-block mb-3" style="letter-spacing: 2px; font-size: 0.75rem;">Wellness Master</small>
                    <p class="text-muted small">Integrating ancient ritual techniques with modern spa science for ultimate rejuvenation.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Our Signature Portfolio Section -->
<section id="portfolio" class="container py-5 border-top" style="border-color: var(--glass-border) !important;">
    <div class="text-center mb-5">
        <h6 class="text-gold text-uppercase tracking-widest" style="letter-spacing: 4px;">Excellence</h6>
        <h2 class="display-5 text-light fw-bold">Signature Portfolio</h2>
        <p class="text-muted mx-auto" style="max-width: 700px;">A curation of our recent masterworks, captured in their most radiant state.</p>
    </div>
    <div class="row g-4">
        <div class="col-md-6">
            <div class="glass-card p-0 overflow-hidden position-relative h-100" style="min-height: 500px;">
                <img src="/eproject/assets/images/portfolio_hair.png" alt="Hair Styling Portfolio" style="width: 100%; height: 100%; object-fit: cover;">
                <div class="position-absolute bottom-0 start-0 w-100 p-4" style="background: linear-gradient(transparent, rgba(0,0,0,0.8));">
                    <h5 class="text-gold mb-0">High-Fashion Couture</h5>
                    <small class="text-light">Curated by Julian Vance</small>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="row g-4">
                <div class="col-12">
                    <div class="glass-card p-0 overflow-hidden position-relative" style="height: 240px;">
                        <img src="/eproject/assets/images/portfolio_nails.png" alt="Nail Art Portfolio" style="width: 100%; height: 100%; object-fit: cover;">
                        <div class="position-absolute bottom-0 start-0 w-100 p-3" style="background: linear-gradient(transparent, rgba(0,0,0,0.8));">
                            <h6 class="text-gold mb-0">Architectural Nail Art</h6>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="glass-card p-0 overflow-hidden position-relative" style="height: 240px;">
                        <img src="/eproject/assets/images/portfolio_spa.png" alt="Spa Rituals Portfolio" style="width: 100%; height: 100%; object-fit: cover;">
                        <div class="position-absolute bottom-0 start-0 w-100 p-3" style="background: linear-gradient(transparent, rgba(0,0,0,0.8));">
                            <h6 class="text-gold mb-0">Holistic Spa Rituals</h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Curated Price Menu Section -->
<section id="pricing" class="container-fluid py-5 border-top luminous-section" style="border-color: var(--glass-border) !important;">
    <div class="container">
        <div class="text-center mb-5">
            <h6 class="text-gold text-uppercase tracking-widest" style="letter-spacing: 4px;">Investment</h6>
            <h2 class="display-5 text-light fw-bold"><span class="glow-header">Curated Price Menu</span></h2>
        </div>
    <div class="row g-4">
        <div class="col-md-4">
            <div class="glass-card h-100 p-4">
                <h4 class="text-gold mb-4 border-bottom pb-2" style="border-color: rgba(212,175,55,0.2) !important;">Couture Hair</h4>
                <div class="d-flex justify-content-between mb-3"><span class="text-light">Bespoke Haircut</span> <span class="text-gold">From $120</span></div>
                <div class="d-flex justify-content-between mb-3"><span class="text-light">Signature Color</span> <span class="text-gold">From $180</span></div>
                <div class="d-flex justify-content-between mb-3"><span class="text-light">Dimensional Balayage</span> <span class="text-gold">From $250</span></div>
                <div class="d-flex justify-content-between"><span class="text-light">Ritual Blow-Dry</span> <span class="text-gold">From $85</span></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="glass-card h-100 p-4">
                <h4 class="text-gold mb-4 border-bottom pb-2" style="border-color: rgba(212,175,55,0.2) !important;">Architectural Nails</h4>
                <div class="d-flex justify-content-between mb-3"><span class="text-light">Structured Manicure</span> <span class="text-gold">From $90</span></div>
                <div class="d-flex justify-content-between mb-3"><span class="text-light">Artistic Extensions</span> <span class="text-gold">From $140</span></div>
                <div class="d-flex justify-content-between mb-3"><span class="text-light">Custom Foil Art</span> <span class="text-gold">From $30</span></div>
                <div class="d-flex justify-content-between"><span class="text-light">Restorative Pedicure</span> <span class="text-gold">From $85</span></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="glass-card h-100 p-4">
                <h4 class="text-gold mb-4 border-bottom pb-2" style="border-color: rgba(212,175,55,0.2) !important;">Wellness Rituals</h4>
                <div class="d-flex justify-content-between mb-3"><span class="text-light">Diamond Glow Facial</span> <span class="text-gold">From $175</span></div>
                <div class="d-flex justify-content-between mb-3"><span class="text-light">Aromatherapy Massage</span> <span class="text-gold">From $140</span></div>
                <div class="d-flex justify-content-between mb-3"><span class="text-light">Detoxifying Body Wrap</span> <span class="text-gold">From $160</span></div>
                <div class="d-flex justify-content-between"><span class="text-light">Zen Head Massage</span> <span class="text-gold">From $65</span></div>
            </div>
        </div>
    </div>
        </div>
    </div>
</section>

<?php
$queryFeat = mysqli_query($con, "SELECT * FROM feedbacks WHERE is_featured = 1 ORDER BY date DESC LIMIT 3");
if(mysqli_num_rows($queryFeat) > 0) {
?>
<section id="testimonials" class="container py-5 border-top" style="border-color: var(--glass-border) !important;">
    <h2 class="section-title">What Our Clients Say</h2>
    <div class="row g-4">
        <?php while($f = mysqli_fetch_assoc($queryFeat)) { ?>
        <div class="col-md-4">
            <div class="glass-card text-center h-100">
                <p class="text-light italic">"<?= htmlspecialchars($f['message']) ?>"</p>
                <p class="text-gold mt-3 mb-0">- <?= htmlspecialchars($f['name']) ?></p>
            </div>
        </div>
        <?php } ?>
    </div>
</section>
<?php } ?>

<section id="contact" class="container py-5 border-top" style="border-color: var(--glass-border) !important;">
    <div class="text-center mb-5">
        <h6 class="text-gold text-uppercase tracking-widest" style="letter-spacing: 4px;">Visit Us</h6>
        <h2 class="display-5 text-light fw-bold">Contact & Location</h2>
    </div>
    <div class="row g-5">
        <div class="col-md-7">
            <div class="glass-card p-0 overflow-hidden position-relative" style="height: 400px; border: 1px solid var(--accent-gold);">
                <!-- Stylized Map Placeholder -->
                <div style="width: 100%; height: 100%; background: url('assets/images/hero_salon.png') center center/cover; filter: grayscale(1) brightness(0.3);"></div>
                <div class="position-absolute top-50 start-50 translate-middle text-center">
                    <div class="mb-3 mx-auto" style="width: 50px; height: 50px; border: 2px solid var(--accent-gold); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <span class="text-gold">📍</span>
                    </div>
                    <h5 class="text-gold">The Elegance Flagship</h5>
                    <p class="text-light small">123 Luxury Avenue, Beverly Hills, CA 90210</p>
                    <a href="https://maps.google.com" target="_blank" class="btn btn-outline-gold btn-sm">Get Directions</a>
                </div>
            </div>
        </div>
        <div class="col-md-5">
            <div class="glass-card p-4 h-100">
                <h4 class="text-gold mb-4">Operational Hours</h4>
                <div class="d-flex justify-content-between mb-2 text-muted"><span>Monday - Friday</span> <span>09:00 AM - 08:00 PM</span></div>
                <div class="d-flex justify-content-between mb-2 text-muted"><span>Saturday</span> <span>10:00 AM - 06:00 PM</span></div>
                <div class="d-flex justify-content-between mb-4 text-muted"><span>Sunday</span> <span class="text-rose">By Appointment Only</span></div>
                
                <h4 class="text-gold mb-3">Direct Inquiry</h4>
                <p class="text-muted mb-1"><strong class="text-light">Phone:</strong> +1 (555) 123-4567</p>
                <p class="text-muted"><strong class="text-light">Email:</strong> concierge@elegancesalon.com</p>
                
                <div class="mt-4">
                    <a href="login.php" class="btn btn-gold w-100">Book Your Experience Now</a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Submission Form Row -->
    <div class="row g-5 mt-4">
        <div class="col-12">
            <div class="glass-card">
                <h3 class="text-gold mb-4 text-center">Share Your Experience</h3>
                <?php if(isset($_SESSION['id'])): ?>
                <form action="" method="post">
                <div class="mb-3">
                    <input type="text" class="form-control" name="fb_name" value="<?= $_SESSION['name'] ?>" readonly style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #888;">
                </div>
                <div class="mb-3">
                    <textarea class="form-control" rows="4" name="fb_message" placeholder="We value your feedback..." style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.1); color: #fff;" required></textarea>
                </div>
                <button type="submit" name="submit_feedback" class="btn btn-gold w-100">Send Feedback</button>
            </form>
            <?php else: ?>
            <div class="glass-card text-center py-5">
                <p class="text-muted">Please log in to share your experience with us.</p>
                <a href="login.php" class="btn btn-outline-gold btn-sm px-4">Login Now</a>
            </div>
            <?php endif; ?>
            <?php
            if(isset($_POST['submit_feedback'])) {
                $f_name = mysqli_real_escape_string($con, trim($_POST['fb_name']));
                $f_msg = mysqli_real_escape_string($con, trim($_POST['fb_message']));
                if ($f_name === '' || $f_msg === '') {
                    echo '<script>Swal.fire("Error", "Fields cannot be empty.", "error")</script>';
                } else {
                    $insert_fb = mysqli_query($con, "INSERT INTO feedbacks (name, message) VALUES ('$f_name', '$f_msg')");
                    if($insert_fb) {
                        echo '<script>Swal.fire("Thank You!", "Your feedback has been received.", "success")</script>';
                    } else {
                        echo '<script>Swal.fire("Error", "Could not save feedback. Try again.", "error")</script>';
                    }
                }
            }
            ?>
        </div>
    </div>
</section>

<?php include_once("footer.php"); ?>
