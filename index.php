<?php
// index.php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PWC Catering & Event Planner</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <style>
    body {
      font-family: Arial, sans-serif;
    }
    /* Transparent, blurred navbar */
    .navbar {
      background: rgba(0, 0, 0, 0.4) !important;
      backdrop-filter: blur(10px);
    }
    .navbar .nav-link, .navbar-brand {
      color: #fff !important;
      font-weight: 500;
    }
    .navbar .nav-link:hover {
      color: #f8c146 !important;
    }
    .hero {
      background: url("https://images.unsplash.com/photo-1504674900247-0877df9cc836") no-repeat center center/cover;
      height: 100vh;
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      text-align: center;
      position: relative;
    }
    .hero::before {
      content: "";
      position: absolute;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: rgba(0,0,0,0.4);
    }
    .hero > div {
      position: relative;
      z-index: 2;
    }
    .hero h1 {
      font-size: 3rem;
      font-weight: bold;
      text-shadow: 2px 2px 8px #000;
    }
    .hero p {
      font-size: 1.2rem;
      text-shadow: 1px 1px 6px #000;
    }
    .service-card {
      transition: transform 0.3s;
    }
    .service-card:hover {
      transform: scale(1.05);
    }
    footer {
      background: #222;
      color: white;
      padding: 20px 0;
    }
    footer a {
      color: #fff;
      text-decoration: none;
      margin: 0 10px;
    }
    footer a:hover {
      color: #f8c146;
    }
    /* Add space because navbar is fixed */
    section {
      scroll-margin-top: 80px;
    }
  </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark fixed-top">
  <div class="container">
    <a class="navbar-brand fw-bold" href="#">PWC Catering</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" 
            data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" 
            aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-link" href="#about">About</a></li>
        <li class="nav-item"><a class="nav-link" href="#services">Services</a></li>
        <li class="nav-item"><a class="nav-link" href="#contact">Contact</a></li>
        <!-- <li class="nav-item"><a class="btn btn-warning ms-2" href="admin/login.php">Admin Login</a></li> -->
        <li class="nav-item"><a class="btn btn-primary ms-2" href="customer/login.php">Customer Login</a></li>
      </ul>
    </div>
  </div>
</nav>

<!-- Hero -->
<section class="hero">
  <div>
    <h1>Welcome to PWC Catering & Events</h1>
    <p>Delicious Food • Elegant Events • Unforgettable Memories</p>
    <a href="#services" class="btn btn-warning btn-lg mt-3">Explore Services</a>
  </div>
</section>

<!-- About -->
<section id="about" class="py-5">
  <div class="container text-center">
    <h2 class="fw-bold mb-4">About Us</h2>
    <p class="lead">At <b>PWC Catering</b>, we believe every event deserves a perfect touch of taste and elegance. 
    Whether it’s a wedding, corporate meeting, birthday, or private gathering, our team ensures your guests enjoy 
    an exceptional culinary experience paired with flawless event management.</p>
  </div>
</section>

<!-- Services -->
<section id="services" class="py-5 bg-light">
  <div class="container">
    <h2 class="text-center fw-bold mb-4">Our Services</h2>
    <div class="row g-4">
      <div class="col-md-3">
        <div class="card service-card text-center p-3 shadow-sm">
          <i class="fas fa-utensils fa-3x mb-3 text-warning"></i>
          <h5 class="fw-bold">Catering</h5>
          <p>Wide variety of delicious meals prepared with love<br>& hygiene.</p>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card service-card text-center p-3 shadow-sm">
          <i class="fas fa-glass-cheers fa-3x mb-3 text-warning"></i>
          <h5 class="fw-bold">Weddings</h5>
          <p>Make your special day memorable with elegant catering & décor.</p>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card service-card text-center p-3 shadow-sm">
          <i class="fas fa-briefcase fa-3x mb-3 text-warning"></i>
          <h5 class="fw-bold">Corporate Events</h5>
          <p>Professional services for conferences, meetings, and seminars.</p>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card service-card text-center p-3 shadow-sm">
          <i class="fas fa-birthday-cake fa-3x mb-3 text-warning"></i>
          <h5 class="fw-bold">Parties</h5>
          <p>Birthday, anniversaries, and social gatherings with perfect setup.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Contact -->
<section id="contact" class="py-5">
  <div class="container text-center">
    <h2 class="fw-bold mb-4">Contact Us</h2>
    <p class="lead">We’d love to make your event unforgettable. Get in touch today!</p>
    <p><i class="fas fa-map-marker-alt"></i> Karachi, Pakistan</p>
    <p><i class="fas fa-phone"></i> +92 331 2721292</p>
    <p><i class="fas fa-envelope"></i> Pitalwalacaterers@gmail.com</p>
    <div class="mt-3">
      <a href="https://www.facebook.com/profile.php?id=100063613973907" target="_blank" class="btn btn-primary">
        <i class="fab fa-facebook"></i> Facebook
      </a>
    </div>
  </div>
</section>

<!-- Footer -->
<footer class="text-center">
  <p>&copy; <?php echo date("Y"); ?> PWC Catering & Event Planner. All Rights Reserved.</p>
  <div>
    <a href="https://www.facebook.com/profile.php?id=100063613973907" target="_blank" ><i class="fab fa-facebook fa-lg"></i></a>
    <a href="#"><i class="fab fa-instagram fa-lg"></i></a>
    <a href="#"><i class="fab fa-twitter fa-lg"></i></a>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
