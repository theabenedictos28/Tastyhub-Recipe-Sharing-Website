    <?php
    session_start();
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    // Include database connection
    require 'db.php'; // Make sure this file contains the correct database connection setup

    // Function to fetch the most popular dishes
    function getMostPopularDishes($conn, $limit = 10) {
        $sql = "SELECT recipe.id, recipe.recipe_name, recipe.recipe_description, recipe.image, 
                       COUNT(likes.recipe_id) AS like_count
                FROM recipe
                LEFT JOIN likes ON recipe.id = likes.recipe_id
                WHERE recipe.status = 'approved'
                GROUP BY recipe.id
                ORDER BY like_count DESC, recipe.created_at DESC
                LIMIT ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // Fetch the most popular dishes
    $mostPopularDishes = getMostPopularDishes($conn);
    ?>

    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="utf-8">
        <title>Tasty Hub</title>
        <meta content="width=device-width, initial-scale=1.0" name="viewport">
        <meta content="" name="keywords">
        <meta content="" name="description">

        <!-- Favicon -->
        <link href="img/favicon.png" rel="icon">

        <!-- Google Web Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600&family=Nunito:wght@600;700;800&family=Pacifico&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&amp;display=swap" rel="stylesheet"/>

        <!-- Icon Font Stylesheet -->
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

        <!-- Libraries Stylesheet -->
        <link href="lib/animate/animate.min.css" rel="stylesheet">
        <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
        <link href="lib/tempusdominus/css/tempusdominus-bootstrap-4.min.css" rel="stylesheet" />

        <!-- Customized Bootstrap Stylesheet -->
        <link href="css/bootstrap.min.css" rel="stylesheet">

        <!-- Template Stylesheet -->
        <link href="css/style.css" rel="stylesheet">
    </head>

    <style>


.card {
    position: relative;
    height: 250px;
    width: 250px;
    background-color: #fff;
    margin: 30px 10px;
    padding: 20px 15px;
    display: flex;
    box-shadow: 0 0 45px rgba(0, 0, 0, .08);
    flex-direction: column;
    transition: 0.3s ease-in-out;
    border-color: transparent;
}

.additional-content {
    position: absolute;
    bottom: -100px; /* Adjust based on your design */
    left: 0;
    right: 0;
    background-color: #f8f9fa; /* Light background for the additional card */
    border-radius: 0 0 15px 15px; /* Rounded corners */
    padding: 10px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
    transition: bottom 0.3s ease-in-out; /* Smooth transition */
    opacity: 0; /* Initially hidden */
}

.card:hover .additional-content {
    bottom: 0; /* Slide up */
    opacity: 1; /* Show the additional card */
}
.flip-card {
    background-color: transparent;
    perspective: 1000px; /* Add perspective */
}

.card-inner {
    position: relative;
    width: 100%;
    height: 100%;
    transition: transform 0.6s;
    transform-style: preserve-3d; /* Preserve 3D space */
}

.card-front, .card-back {
    position: absolute;
    width: 100%;
    height: 100%;
    backface-visibility: hidden; /* Hide back face when facing away */
}

.card-front {
    background-color: #fff; /* Front side color */
    z-index: 2; /* Place front side above */
}

.card-back {
    transform: rotateY(180deg); /* Rotate back side */
}

.flip-card:hover .card-inner {
    transform: rotateY(180deg); /* Rotate on hover */
}

.recipe-rank {
    position: relative;
    display: flex;
    align-items: center;
    margin-bottom: 20px;
    padding: 15px;
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    border-left: 4px solid transparent;
    min-height: 150px;
    height: 150px;
}

.recipe-rank:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
}

.recipe-rank.top-3 {
    background: linear-gradient(135deg, #fff7e6 0%, #ffffff 100%);
    border-left-color: #ffa500;
}

.recipe-rank.rank-1 {
    background: linear-gradient(135deg, #fff9c4 0%, #ffffff 100%);
    border-left-color: #ffd700;
}

.recipe-rank.rank-2 {
    background: linear-gradient(135deg, #f5f5f5 0%, #ffffff 100%);
    border-left-color: #c0c0c0;
}

.recipe-rank.rank-3 {
    background: linear-gradient(135deg, #fff4e6 0%, #ffffff 100%);
    border-left-color: #cd7f32;
}

.rank-badge {
    position: relative;
    min-width: 45px;
    height: 45px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 18px;
    border-radius: 50%;
    margin-right: 15px;
    color: white;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
    flex-shrink: 0;
}

.rank-badge.gold {
    background: linear-gradient(135deg, #ffd700, #ffed4e);
    box-shadow: 0 4px 15px rgba(255, 215, 0, 0.4);
    animation: pulse-gold 2s infinite;
}

.rank-badge.silver {
    background: linear-gradient(135deg, #c0c0c0, #e5e5e5);
    box-shadow: 0 4px 15px rgba(192, 192, 192, 0.4);
}

.rank-badge.bronze {
    background: linear-gradient(135deg, #cd7f32, #daa520);
    box-shadow: 0 4px 15px rgba(205, 127, 50, 0.4);
}

.rank-badge.regular {
    background: linear-gradient(135deg, #ffa500, #ff8c00);
    box-shadow: 0 4px 15px rgba(255, 165, 0, 0.3);
}

@keyframes pulse-gold {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

.rank-badge::before {
    content: '';
    position: absolute;
    top: -2px;
    left: -2px;
    right: -2px;
    bottom: -2px;
    background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    border-radius: 50%;
    z-index: -1;
}

.recipe-content {
    flex: 1;
    display: flex;
    align-items: center;
}

.recipe-image-container {
    position: relative;
    margin-right: 15px;
    flex-shrink: 0;
}

.recipe-image {
    width: 100px;
    height: 100px;
    object-fit: cover;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease;
}

.recipe-rank:hover .recipe-image {
    transform: scale(1.05);
}

.recipe-details-new {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.recipe-title-new {
    font-size: 16px;
    font-weight: 600;
    color: #2c3e50;
    text-decoration: none;
    margin-bottom: 8px;
    border-bottom: 2px solid #e9ecef;
    padding-bottom: 8px;
    transition: color 0.3s ease;
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    max-height: 40px;
}

.recipe-title-new:hover {
    color: #ffa500;
    text-decoration: none;
}

.recipe-description-new {
    color: #6c757d;
    font-style: italic;
    font-size: 13px;
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    max-height: 34px;
}

.crown-icon {
    position: absolute;
    top: -8px;
    right: -8px;
    color: #ffd700;
    font-size: 20px;
    animation: bounce 2s infinite;
}

@keyframes bounce {
    0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
    40% { transform: translateY(-10px); }
    60% { transform: translateY(-5px); }
}

.popular-section-header {
    text-align: center;
    margin-bottom: 40px;
}

.trend-badge {
    display: inline-block;
    background: linear-gradient(135deg, #ffa500, #ff8c00);
    color: white;
    padding: 8px 20px;
    border-radius: 25px;
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 10px;
    text-transform: uppercase;
    letter-spacing: 1px;
    box-shadow: 0 4px 15px rgba(255, 165, 0, 0.3);
}

.logo {
    display: flex;
    align-items: center;
}
body {
    font-family: 'Poppins', sans-serif;
}

        /* Flip card styles - keeping original appearance */
        .flip-card {
            background-color: transparent;
            width: 100%;
            height: 250px;
            perspective: 1000px;
        }

        .card-inner {
            position: relative;
            width: 100%;
            height: 100%;
            text-align: center;
            transition: transform 0.6s;
            transform-style: preserve-3d;
        }

        .flip-card:hover .card-inner {
            transform: rotateY(180deg);
        }

        .card-front, {
            position: absolute;
            width: 100%;
            height: 100%;
            -webkit-backface-visibility: hidden;
            backface-visibility: hidden;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .card-front {
            background-color: #fff;
        }

        .card-back {
            background-color: #fff;
            color: black;
            transform: rotateY(180deg);
        }

        /* Mobile responsive adjustments - only layout, not appearance */
        @media (max-width: 767.98px) {
            /* On mobile, disable hover and enable tap */
            .flip-card:hover .card-inner {
                transform: none;
            }
            
            .flip-card.mobile-flipped .card-inner {
                transform: rotateY(180deg);
            }
            
            /* Slightly smaller cards on very small screens */
            .flip-card {
                height: 220px;
            }
            
            /* Adjust padding for mobile */
            .container-fluid {
                padding: 3rem 1rem;
            }
            .container.text-center .row {
                margin-left: -30px;
            }
             .col-lg-6 h1 {
                font-size: 1.75rem !important; /* Slightly bigger heading text */
            }
            
            .col-lg-6 h1 img {
                width: 45px !important; /* Slightly bigger logo */
                height: 45px !important;
            }
        }

        @media (max-width: 575.98px) {
            .flip-card {
                height: 200px;
            }
            
            .container-fluid {
                padding: 2rem 1rem;
            }
            .col-lg-6 h1 {
                font-size: 1.5rem !important; /* Slightly bigger for very small screens */
            }
            
            .col-lg-6 h1 img {
                width: 40px !important; /* Slightly bigger logo */
                height: 40px !important;
            }
        }

        /* Bootstrap column adjustments for better mobile stacking */
        @media (max-width: 575.98px) {
            .col-sm-6 {
                flex: 0 0 100%;
                max-width: 100%;
            }
        }
        /* Prevent horizontal scroll on mobile */
        html, body {
            max-width: 100% !important;
            overflow-x: hidden !important;
        }

    </style>

    <body class="bg-white">
        <div class="container-fluid p-0">
            <!-- Spinner Start -->
            <div id="spinner" class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
                <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
            </div>
            <!-- Spinner End -->


            <!-- Navbar & Hero Start -->
            <div class="container-fluid position-relative p-0">
                <nav class="navbar navbar-expand-lg navbar-dark bg-dark px-4 px-lg-5 py-3 py-lg-0">
                    <a href="" class="navbar-brand p-0">
                        <h2 class="logo text-primary"><img src="img/logo_new.png" alt="Logo" style="width: 50px; height: 50px;"></i>Tasty Hub</h2>
                    </a>
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
                        <span class="fa fa-bars"></span>
                    </button>
                    <div class="collapse navbar-collapse" id="navbarCollapse">
                        <div class="navbar-nav ms-auto py-0 pe-4">
                            <a href="index.php" class="nav-item nav-link active">Home</a>
                            <a href="guestdashboard.php" class="nav-item nav-link">Browse</a>
                            <a href="guestabout.html" class="nav-item nav-link">About</a>
                            </div>
                            <a href="signin.php" class="btn btn-primary py-2 px-4">SIGN IN</a>

                        </div>
                    </div>
                </nav>

                <div class="container-fluid py-4 bg-dark hero-header mb-5">
                    <div class="container my-5 py-5">
                        <div class="row align-items-center g-5">
                            <div class="col-lg-6 text-center text-lg-start">
                                <p class="display-3 text-white animated slideInLeft">Discover. Create. Share.</p>
                                <p class="text-white animated slideInLeft mb-4 pb-2">A vibrant community for home cooks, professional chefs, food enthusiasts and food lovers to explore and share delicious recipes.</p>
                                <a href="signin.php?signup=true" class="btn btn-primary py-sm-3 px-sm-5 me-3 animated slideInLeft" id="joinNowBtn">Join Now</a>
                            </div>
                            <div class="col-lg-6 text-center text-lg-end overflow-hidden">
                                <img class="img-fluid" src="img/hero.png" alt="">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Navbar & Hero End -->

     <!-- Feature Start -->
    <div class="container-fluid py-5">
        <div class="container text-center">
            <h2 class="section-title ff-secondary text-center text-primary fw-normal">Discover, Share & Connect Through Food</h2>
            <p class="mb-5">Find amazing recipes, share your favorites, and connect with fellow food lovers!</p>
            <div class="row">
                <div class="col-lg-3 col-sm-6 wow fadeInUp" data-wow-delay="0.1s">
                    <div class="card flip-card" onclick="handleMobileFlip(this)">
                        <div class="card-inner">
                            <div class="card-front">
                                <div class="p-2">
                                    <i class="fa fa-3x fa-search text-primary mb-4"></i>
                                    <h5>Find Recipe</h5>
                                    <p>Discover delicious recipes in a snap!</p>
                                </div>
                            </div>
                            <div class="card-back">
                                <div class="p-2">
                                    <p>Search through thousands of recipes based on ingredients and using any keyword—filter by dietary needs, or even exclude specific ingredients. Find the perfect dish in just a click!</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-sm-6 wow fadeInUp" data-wow-delay="0.3s">
                    <div class="card flip-card" onclick="handleMobileFlip(this)">
                        <div class="card-inner">
                            <div class="card-front">
                                <div class="p-2">
                                    <i class="fa fa-3x fa-share-alt text-primary mb-4"></i>
                                    <h5>Share & Inspire</h5>
                                    <p>Share your favorite recipes and inspire others to cook!</p>
                                </div>
                            </div>
                            <div class="card-back">
                                <div class="p-2">
                                    <p>Upload your own recipes and share them with the community. Whether it's a classic favorite or an experimental dish, inspire others with your unique culinary creations!</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-sm-6 wow fadeInUp" data-wow-delay="0.5s">
                    <div class="card flip-card" onclick="handleMobileFlip(this)">
                        <div class="card-inner">
                            <div class="card-front">
                                <div class="p-2">
                                    <i class="fa fa-3x fa-users text-primary mb-4"></i>
                                    <h5>Cook & Connect</h5>
                                    <p>Engage with fellow food lovers!</p>
                                </div>
                            </div>
                            <div class="card-back">
                                <div class="p-2">
                                    <p>Join discussions, share cooking tips, and connect with fellow food lovers. Grow your culinary network and be part of a passionate community!</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-sm-6 wow fadeInUp" data-wow-delay="0.7s">
                    <div class="card flip-card" onclick="handleMobileFlip(this)">
                        <div class="card-inner">
                            <div class="card-front">
                                <div class="p-2">
                                    <i class="fas fa-award fa-3x text-primary mb-4"></i> 
                                    <h5>Contribution Badges</h5>
                                    <p>Earn recognition for your activity and creativity on the platform!</p>
                                </div>
                            </div>
                            <div class="card-back">
                                <div class="p-3">
                                    <p>Unlock contribution badges as you share recipes, receive likes, and engage with the community. Climb the ranks and aim for the ultimate title — <strong style="color: gold
                                    ">Culinary Legend</strong>!</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Feature End -->

    <script>
        function handleMobileFlip(card) {
            // Only flip on mobile devices
            if (window.innerWidth <= 767) {
                card.classList.toggle('mobile-flipped');
            }
        }

        // Reset flip state when window is resized
        window.addEventListener('resize', function() {
            if (window.innerWidth > 767) {
                document.querySelectorAll('.flip-card').forEach(card => {
                    card.classList.remove('mobile-flipped');
                });
            }
        });
    </script>

    <?php
    // Fetch the most popular dishes
    $mostPopularDishes = getMostPopularDishes($conn);
    ?>
<div class="container-fluid py-5">
    <div class="container">
        <div class="popular-section-header wow fadeInUp" data-wow-delay="0.1s">
            <div class="trend-badge">Trending Now</div> <br>
            <h1 class="section-title ff-secondary text-center text-primary fw-normal mb-4">Most Popular Recipes</h1>
            <div style="width: 100%; height: 2px; background-color: orange; border-radius: 2px; margin-bottom: 20px;"></div>
        </div>
        
        <div class="tab-class text-center wow fadeInUp" data-wow-delay="0.1s">
            <div class="tab-content">
                <div id="tab-1" class="tab-pane fade show p-0 active">
                    <div class="row g-4">
                        <?php 
                        $rank = 1;
                        foreach ($mostPopularDishes as $dish): 
                            // Determine rank class and badge style
                            $rankClass = '';
                            $badgeClass = 'regular';
                            
                            if ($rank == 1) {
                                $rankClass = 'rank-1 top-3';
                                $badgeClass = 'gold';
                            } elseif ($rank == 2) {
                                $rankClass = 'rank-2 top-3';
                                $badgeClass = 'silver';
                            } elseif ($rank == 3) {
                                $rankClass = 'rank-3 top-3';
                                $badgeClass = 'bronze';
                            } elseif ($rank <= 5) {
                                $rankClass = 'top-3';
                            }
                        ?>
                            <div class="col-lg-6">
                                <div class="recipe-rank <?php echo $rankClass; ?>" data-wow-delay="<?php echo 0.1 + ($rank * 0.1); ?>s">
                                    <!-- Rank Badge -->
                                    <div class="rank-badge <?php echo $badgeClass; ?>">
                                        <?php echo $rank; ?>
                                        <?php if ($rank <= 3): ?>
                                            <i class="fas fa-crown crown-icon"></i>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Recipe Content -->
                                    <div class="recipe-content">
                                        <div class="recipe-image-container">
                                            <a href="guestrecipe_details.php?id=<?php echo $dish['id']; ?>">
                                                <img class="recipe-image" 
                                                     src="<?php echo !empty($dish['image']) ? htmlspecialchars($dish['image']) : 'uploads/default-placeholder.png'; ?>" 
                                                     alt="<?php echo htmlspecialchars($dish['recipe_name']); ?>">
                                            </a>
                                        </div>
                                        
                                        <div class="recipe-details-new">
                                            <a href="guestrecipe_details.php?id=<?php echo $dish['id']; ?>" 
                                               class="recipe-title-new">
                                                <?php echo htmlspecialchars($dish['recipe_name']); ?>
                                            </a>
                                            <div class="recipe-description-new">
                                                <?php echo htmlspecialchars($dish['recipe_description']); ?>
                                            </div>
                                            
                                            <!-- Like count display -->
                                            <div class="mt-2">
                                                <small class="text-muted">
                                                    <i class="fas fa-heart text-danger"></i> 
                                                    <?php echo $dish['like_count']; ?> likes
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php 
                        $rank++;
                        endforeach; 
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Most Popular End -->

            <!-- Back to Top -->
            <a href="#" class="btn btn-lg btn-primary btn-lg-square back-to-top p-2" id="backToTopBtn">
    <i class="bi bi-arrow-up"></i>
</a>

<!-- Tutorial Carousel Section Start -->
<section class="tutorial-carousel-section bg-white" aria-label="Tutorial carousel">
  <div class="container">
<div class="row align-items-start g-5">

   <!-- Left Text Column -->
<div class="col-lg-6 col-md-12 d-flex flex-column justify-content-start px-5 px-sm-5">
    <h2 class="ff-secondary text-primary fw-normal mb-4 d-flex align-items-center gap-1">
        <span style="font-size: clamp(1.6rem, 3.5vw, 2.2rem);">Let's get you started!</span>
        <img src="img/logo_new.png" alt="Logo" style="width: clamp(35px, 4vw, 55px); height: clamp(35px, 4vw, 55px); object-fit: contain;">
    </h2>

    <p class="text-muted mb-3">
        Follow this quick tutorial to learn how to make the most out of our website. You'll discover how to explore features, find recipes, and set your preferences easily. From browsing trending dishes to searching with filters, you'll quickly get familiar with all the tools designed to make cooking fun and effortless.
    </p>

    <p class="text-muted mb-3">
        Whether you're new or a returning user, our step-by-step carousel will guide you through everything you need to know to get started. Learn how to submit your own recipes, view other users’ profiles, and interact with the community. Each step is designed to be simple and intuitive so you can focus on creating and enjoying meals.
    </p>
</div>


      <!-- Right Carousel Column -->
      <div class="col-lg-6 col-md-12">
        <div class="tutorial-carousel" id="tutorialCarousel">
          <div class="carousel-track">

            <div class="carousel-slide">
              <img src="img/submits.png" alt="Step 1">
              <div class="caption">
                <h3>Submit Your First Recipe</h3>
                <p>Share your own delicious creations with the community.</p>
              </div>
            </div>

            <div class="carousel-slide">
              <img src="img/stalks.png" alt="Step 2">
              <div class="caption">
                <h3>View Other Profiles</h3>
                <p>Discover what other food lovers are cooking and get inspired by their creations!</p>
              </div>
            </div>

            <div class="carousel-slide">
              <img src="img/prefe.png" alt="Step 3">
              <div class="caption">
                <h3>Personalize Your Experience</h3>
                <p>Select your food preferences so we can serve up dishes that match your unique taste.</p>
              </div>
            </div>

            <div class="carousel-slide">
              <img src="img/search.png" alt="Step 4">
              <div class="caption">
                <h3>Cook with What You Have</h3>
                <p>Type in your ingredients or refine your search with filters — perfect for spontaneous cooking!</p>
              </div>
            </div>

          </div>

          <!-- Dots -->
          <div class="carousel-dots">
            <button class="dot active" aria-label="Go to slide 1"></button>
            <button class="dot" aria-label="Go to slide 2"></button>
            <button class="dot" aria-label="Go to slide 3"></button>
            <button class="dot" aria-label="Go to slide 4"></button>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
<!-- Tutorial Carousel Section End -->

<!-- Styles -->
<style>
.tutorial-carousel-section {
  background: #fff;
  color: #333;
}

.tutorial-carousel {
  position: relative;
  overflow: hidden;
  border-radius: 16px;
}

.carousel-track {
  display: flex;
  overflow-x: auto;
  scroll-snap-type: x mandatory;
  scroll-behavior: smooth;
  gap: 14px; /* small gap between images */
  padding: 10px 0;
  scrollbar-width: none;
  -ms-overflow-style: none;
}

.carousel-track::-webkit-scrollbar {
  display: none;
}

.carousel-slide {
  flex: 0 0 100%;
  scroll-snap-align: start;
  scroll-snap-stop: always;
  display: flex;
  flex-direction: column;
  align-items: center;
}

/* ✅ Image with fixed width, border & same size */
.carousel-slide img {
  width: 90%; /* ✅ keeps a small margin on sides */
  height: 265px;
  border-radius: 16px;
  object-fit: contain;
  display: block;
  background-color: #f8f8f8;
  border: 3px solid #ff7b00;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  margin: 0 auto; /* centers image */
}

.caption {
  margin-top: 20px;
  text-align: center;
  padding: 0 10px;
}

.caption h3 {
  font-size: 1.3rem;
  color: #ff7b00;
  margin-bottom: 5px;
}

.caption p {
  color: #666;
  font-size: 0.85rem;
}

.carousel-dots {
  display: flex;
  justify-content: center;
  margin-top: 15px;
  gap: 8px;
}

.carousel-dots .dot {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  background: #ccc;
  border: none;
  cursor: pointer;
  transition: background 0.3s, transform 0.2s;
}

.carousel-dots .dot:hover {
  transform: scale(1.2);
}

.carousel-dots .dot.active {
  background: #ff7b00;
}

/* ✅ Responsive adjustments */
@media (max-width: 768px) {
  .tutorial-carousel-section h2 {
    font-size: 1.8rem !important;
  }

  .tutorial-carousel-section .col-lg-6.col-md-12:last-child {
    margin-top: -20px; /* adjust this value as needed */
  }
  .carousel-slide img {
    width: 95%; /* slightly wider on mobile */
    height: 230px;
    border-width: 2px;
  }

  .caption h3 {
    font-size: 1.05rem;
  }

  .caption p {
    font-size: 0.85rem;
  }

  .carousel-dots .dot {
    width: 10px;
    height: 10px;
  }
}

/* ✅ Extra small phones */
@media (max-width: 480px) {
  .carousel-slide img {
    width: 80%;
    height: 165px;
    border-width: 2px;
  }
  .tutorial-carousel-section .col-lg-6.col-md-12:last-child {
    margin-top: -15px; /* slightly less on very small phones */
  }
}



</style>

<!-- Script -->
<script>
document.addEventListener('DOMContentLoaded', function () {
  const carousel = document.getElementById('tutorialCarousel');
  if (!carousel) return;

  const track = carousel.querySelector('.carousel-track');
  const slides = Array.from(carousel.querySelectorAll('.carousel-slide'));
  const dots = Array.from(carousel.querySelectorAll('.dot'));
  let currentIndex = 0;
  let autoplayTimer = null;
  const AUTOPLAY = true;
  const AUTOPLAY_DELAY = 4000;

  function updateDots(index) {
    dots.forEach((d, i) => d.classList.toggle('active', i === index));
    currentIndex = index;
  }

  function scrollToSlide(index) {
    const slideWidth = slides[0].offsetWidth;
    track.scrollLeft = slideWidth * index;
    updateDots(index);
  }

  let scrollTimeout;
  track.addEventListener('scroll', () => {
    clearTimeout(scrollTimeout);
    scrollTimeout = setTimeout(() => {
      const slideWidth = slides[0].offsetWidth;
      const newIndex = Math.round(track.scrollLeft / slideWidth);
      if (newIndex !== currentIndex) {
        updateDots(newIndex);
        resetAutoplay();
      }
    }, 100);
  });

  dots.forEach((dot, i) => {
    dot.addEventListener('click', () => {
      scrollToSlide(i);
      resetAutoplay();
    });
  });

  function startAutoplay() {
    if (AUTOPLAY && !autoplayTimer) {
      autoplayTimer = setInterval(() => {
        const nextIndex = (currentIndex + 1) % slides.length;
        scrollToSlide(nextIndex);
      }, AUTOPLAY_DELAY);
    }
  }

  function stopAutoplay() {
    if (autoplayTimer) {
      clearInterval(autoplayTimer);
      autoplayTimer = null;
    }
  }

  function resetAutoplay() {
    stopAutoplay();
    startAutoplay();
  }

  updateDots(0);
  startAutoplay();

  carousel.addEventListener('mouseenter', stopAutoplay);
  carousel.addEventListener('mouseleave', startAutoplay);
  carousel.addEventListener('touchstart', stopAutoplay);
  carousel.addEventListener('touchend', startAutoplay);
});
</script>


<!-- FAQs Start -->
<div class="container-fluid py-5">
    <div class="container">
       <div class="wow fadeInUp text-center" data-wow-delay="0.1s">
    <div class="trend-badge mx-auto">Need Help?</div> <br>
    <h1 class="section-title ff-secondary text-center text-primary fw-normal mb-4">Frequently Asked Questions</h1>
            <div style="width: 100%; height: 2px; background-color: orange; border-radius: 2px; margin-bottom: 20px;"></div>

</div>

        <div class="accordion" id="faqAccordion">
            <?php
            // Example static FAQs
            $faqs = [
                [
                    'question' => 'How do I submit a recipe?',
                    'answer' => 'To submit a recipe, click the "Submit Recipe" button on profile and fill out the form with your recipe details.'
                ],
                [
                    'question' => 'How are recipes ranked in the Most Popular section?',
                    'answer' => 'Recipes are ranked based on the number of likes'
                ],
                [
                    'question' => 'How do I edit or delete my recipe?',
                    'answer' => 'You can edit or delete your recipes from your recipe details.'
                ],
                [
                    'question' => 'What are badges and how do I earn them?',
                    'answer' => 'Badges are achievements based on your activity on Tasty Hub. You earn points from recipe uploads, likes, and favorites. Points unlock badges and new features' 
                ],
                [
                    'question' => 'Why isn’t my recipe showing up?',
                    'answer' => 'Recipes go through admin review before becoming visible to others.'
                ],
                 [
                    'question' => 'How do I contact support?',
                    'answer' => 'You can contact our support team directly via email at tastyhubrecipe@gmail.com.'
                ]

            ];

            $faqIndex = 0;
            foreach ($faqs as $faq):
                $faqIndex++;
            ?>
            <div class="accordion-item mb-2 shadow-sm rounded">
                <h2 class="accordion-header" id="heading<?php echo $faqIndex; ?>">
                    <button class="accordion-button collapsed bg-white text-dark fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $faqIndex; ?>" aria-expanded="false" aria-controls="collapse<?php echo $faqIndex; ?>">
                        <?php echo $faq['question']; ?>
                    </button>
                </h2>
                <div id="collapse<?php echo $faqIndex; ?>" class="accordion-collapse collapse" aria-labelledby="heading<?php echo $faqIndex; ?>" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        <?php echo $faq['answer']; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<!-- FAQs End -->

         <!-- Footer Start -->
    <div class="container-fluid bg-dark text-light footer pt-3 wow fadeIn" data-wow-delay="0.1s">
        <div class="container py-2">
            <div class="row g-2">
                <div class="col-lg-3 col-md-6">
                    <h4 class="section-title ff-secondary text-start text-primary fw-normal mb-4">Company</h4>
                    <a class="btn btn-link" href="guestabout.html">About Us</a>
                <a href="#" class="btn btn-link" data-bs-toggle="modal" data-bs-target="#legalModal" data-tab="privacy">Privacy Policy</a>
                <a href="#" class="btn btn-link" data-bs-toggle="modal" data-bs-target="#legalModal" data-tab="terms">Terms & Conditions</a>
                </div>

                 <div class="col-lg-6 col-md-12 text-center">
                    <h1 class="text-primary" style="font-size: 5rem; font-weight: extrabold; letter-spacing: 3px;">Tasty Hub</h1>
                    <p class="section-title text-center text-white fw-normal"> Discover. Create. Share.</p>
                </div>
                <div class="col-lg-3 col-md-6">
                    <h4 class="section-title ff-secondary text-start text-primary fw-normal mb-4">Contact</h4>
                    <p class="mb-2"><i class="fa fa-map-marker-alt me-3"></i>Bulacan, Philippines</p>
                    <p class="mb-2"><i class="fa fa-envelope me-3"></i>tastyhubrecipe@gmail.com</p>
                   

                        <!--
                    <div class="d-flex pt-2">
                        <a class="btn btn-outline-light btn-social" href=""><i class="fab fa-twitter"></i></a>
                        <a class="btn btn-outline-light btn-social" href=""><i class="fab fa-facebook-f"></i></a>
                        <a class="btn btn-outline-light btn-social" href=""><i class="fab fa-youtube"></i></a>
                    </div>
                    -->
                </div>
            </div>
        </div>
        <div class="container">
            <div class="copyright">
                <div class="row">
                    <div class="col-md-8 text-center text-md-start mb-md-0">
                        &copy; <a class="border-bottom" href="#">Tasty Hub</a>, All Right Reserved. 
                        Designed By <a class="border-bottom" href="guestabout.html">Our Team</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Footer End -->

<!-- Legal Modal -->
<div class="modal fade" id="legalModal" tabindex="-1" aria-labelledby="legalModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content">

      <div class="modal-header border-bottom-0 flex-column flex-sm-row align-items-stretch p-0">
        <ul class="nav nav-tabs w-100 flex-column flex-sm-row text-center" id="legalTabs" role="tablist">
          <li class="nav-item flex-fill">
            <button class="nav-link active w-100" id="privacy-tab" data-bs-toggle="tab" data-bs-target="#privacy" type="button" role="tab" aria-controls="privacy" aria-selected="true">
              Privacy Policy
            </button>
          </li>
          <li class="nav-item flex-fill">
            <button class="nav-link w-100" id="terms-tab" data-bs-toggle="tab" data-bs-target="#terms" type="button" role="tab" aria-controls="terms" aria-selected="false">
              Terms & Conditions
            </button>
          </li>
        </ul>
        <button type="button" class="btn-close position-absolute end-0 top-0 m-3" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <!-- Modal Body -->
      <div class="modal-body tab-content overflow-auto" style="max-height:70vh;">
        
        <!-- Privacy Tab -->
        <div class="tab-pane fade show active" id="privacy" role="tabpanel" aria-labelledby="privacy-tab">
          <h6 class="text-muted">Effective Date: April 10, 2025</h6>
                <p>Welcome to Tasty Hub, a community-driven recipe-sharing website. Your privacy is important to us, and this Privacy Policy outlines how we collect, use, disclose, and protect your information when you use our services.</p>
                
                <h6>1. Information We Collect</h6>
                <p>At Tasty Hub, we practice data minimization and only collect essential information needed to provide our services:</p>
                <p><strong>Personal Information:</strong><br>
                - <strong>Username:</strong> Your unique identifier on our platform, enabling you to create and manage recipes, interact with other users, and build your culinary profile within our community.<br>
                - <strong>Email Address:</strong> Used exclusively for essential communications including account verification, password recovery, important service updates, and optional community newsletters (which you can unsubscribe from at any time).</p>
                <p><strong>Automatically Collected Information:</strong><br>
                - <strong>Usage Data:</strong> We collect information about how you interact with our platform, including pages visited, time spent, and features used to improve user experience.<br>
                - <strong>Technical Data:</strong> IP address, browser type, device information, and operating system for security purposes and platform optimization.<br>
                - <strong>Cookies:</strong> We use essential cookies for site functionality and optional analytics cookies (with your consent) to understand user preferences and improve our services.</p>

                <h6>2. User-Generated Content</h6>
                <p>Your creative contributions are the heart of Tasty Hub's community. When you submit recipes, you maintain ownership of your content while granting us specific rights for platform operation:</p>
                <p>- <strong>Content Ownership:</strong> You retain full ownership and copyright of all recipes, images, and content you create.<br>
                - <strong>Platform License:</strong> By submitting content, you grant Tasty Hub a non-exclusive, worldwide, royalty-free license to use, reproduce, modify, and display your content on our platform and in promotional materials.<br>
                - <strong>Future Publications:</strong> We may feature selected community recipes in cookbooks or other publications. If your recipe is chosen, we will notify you in advance, provide proper attribution, and may offer compensation for featured content.<br>
                - <strong>Content Responsibility:</strong> You are responsible for ensuring your submitted content is original, accurate, and does not infringe on others' intellectual property rights.</p>

                <h6>3. How We Use Your Information</h6>
                <p>We use collected information solely for legitimate business purposes to enhance your Tasty Hub experience:</p>
                <p><strong>Core Services:</strong><br>
                - Account creation, management, and authentication<br>
                - Recipe submission, editing, and deletion capabilities<br>
                - Community features including comments, ratings, and user interactions<br>
                - Content moderation through our admin approval system</p>
                <p><strong>Communications:</strong><br>
                - Essential account notifications and security alerts<br>
                - Service updates and policy changes<br>
                - Optional community newsletters and featured content highlights<br>
                - Response to user inquiries and customer support</p>
                <p><strong>Platform Improvement:</strong><br>
                - Analytics to understand user preferences and platform usage<br>
                - Feature development based on community needs<br>
                - Security monitoring and fraud prevention</p>

                <h6>4. Information Sharing and Disclosure</h6>
                <p>We respect your privacy and do not sell, rent, or trade your personal information. We may share information only in these limited circumstances:</p>
                <p><strong>With Your Explicit Consent:</strong> We will ask for your permission before sharing personal information for purposes not covered in this policy.</p>
                <p><strong>Trusted Service Providers:</strong> We work with carefully vetted third-party companies for:<br>
                - Website hosting and cloud storage services<br>
                - Email delivery and communication tools<br>
                - Analytics and performance monitoring<br>
                - Payment processing (if applicable)<br>
                All service providers are bound by strict confidentiality agreements and may only use your information to provide services on our behalf.</p>
                <p><strong>Legal Requirements:</strong> We may disclose information when required by law, court order, or to protect the rights, property, or safety of Tasty Hub, our users, or others.</p>
                <p><strong>Business Transfers:</strong> In the event of a merger, acquisition, or sale of assets, user information may be transferred as part of the business transaction, with continued protection under this privacy policy.</p>

                <h6>5. Data Security and Protection</h6>
                <p>We implement comprehensive security measures to protect your personal information:</p>
                <p><strong>Technical Safeguards:</strong><br>
                - SSL encryption for all data transmission<br>
                - Secure database storage with access controls<br>
                - Regular security audits and vulnerability assessments<br>
                - Automated backup systems with encryption</p>
                <p><strong>Administrative Safeguards:</strong><br>
                - Limited employee access to personal data on a need-to-know basis<br>
                - Regular security training for staff<br>
                - Incident response procedures for potential breaches</p>
                <p>While we employ industry-standard security measures, no internet transmission or electronic storage is 100% secure. We encourage users to use strong passwords and keep login credentials confidential.</p>

                <h6>6. Your Privacy Rights</h6>
                <p>You have comprehensive control over your personal information and can exercise the following rights at any time:</p>
                <p><strong>Access Rights:</strong> Request a copy of all personal information we hold about you, including how it's used and shared.</p>
                <p><strong>Correction Rights:</strong> Update or correct any inaccurate personal information in your profile or account settings.</p>
                <p><strong>Deletion Rights:</strong> Request complete account deletion, which will remove all personal information and user-generated content from our systems within 30 days.</p>
                <p><strong>Data Portability:</strong> Request your data in a machine-readable format for transfer to another service.</p>
                <p><strong>Communication Preferences:</strong> Opt out of non-essential communications while maintaining account functionality.</p>
                <p>To exercise these rights, contact us at <a href="mailto:tastyhub@gmail.com" class="text-primary hover:underline">tastyhub@gmail.com</a> with your request and account information.</p>

                <h6>7. Data Retention</h6>
                <p>We retain your information only as long as necessary to provide services and fulfill legal obligations:</p>
                <p>- <strong>Active Accounts:</strong> Information is retained while your account remains active<br>
                - <strong>Deleted Accounts:</strong> Personal information is deleted within 30 days of account closure<br>
                - <strong>Legal Requirements:</strong> Some information may be retained longer to comply with legal obligations or resolve disputes</p>

                <h6>8. International Data Transfers</h6>
                <p>Tasty Hub operates primarily in the Philippines. If you access our services from other countries, your information may be transferred to and processed in the Philippines, where our servers and primary operations are located. We ensure appropriate safeguards are in place for any international transfers.</p>

                <h6>9. Children's Privacy</h6>
                <p>Tasty Hub is designed for users of all ages. However, we do not knowingly collect personal information from children under 13 without parental consent. If you believe a child has provided personal information without consent, please contact us immediately.</p>

                <h6>10. Changes to This Privacy Policy</h6>
                <p>We may update this Privacy Policy periodically to reflect changes in our practices or legal requirements. Significant changes will be communicated through:</p>
                <p>- Email notification to registered users<br>
                - Prominent notice on our website<br>
                - Updated effective date at the top of this policy</p>
                <p>Your continued use of Tasty Hub after changes take effect constitutes acceptance of the updated policy.</p>

                <h6>11. Contact Information</h6>
                <p>For questions, concerns, or requests regarding this Privacy Policy or our data practices, please contact us:</p>
                <p><strong>Email:</strong> <a href="mailto:tastyhub@gmail.com" class="text-primary hover:underline">tastyhub@gmail.com</a><br>
                <strong>Subject Line:</strong> Privacy Policy Inquiry<br>
                <strong>Response Time:</strong> We aim to respond to all privacy-related inquiries within 48 hours.</p>
        </div>

        <!-- Terms Tab -->
        <div class="tab-pane fade" id="terms" role="tabpanel" aria-labelledby="terms-tab">
          <h6 class="text-muted">Effective Date: April 10, 2025</h6>
                <p>Welcome to Tasty Hub, a community-driven recipe-sharing website where users can discover, create, and share their own recipes. By accessing or using Tasty Hub, you agree to comply with and be bound by these Terms and Conditions. If you do not agree with any part of these Terms, you must not use our services.</p>
                
                <h6>1. User Eligibility and Account Creation</h6>
                <p><strong>General Eligibility:</strong> Tasty Hub welcomes all users who share a passion for food and cooking. Our platform is especially designed for food enthusiasts who want to contribute to and learn from our vibrant culinary community.</p>
                <p><strong>Account Requirements:</strong><br>
                - Users must provide accurate and truthful information during registration<br>
                - Each user is limited to one account to maintain community integrity<br>
                - Users under 13 require parental consent to create an account<br>
                - Account holders are responsible for maintaining the security of their login credentials</p>
                <p><strong>Account Termination:</strong> We reserve the right to suspend or terminate accounts that violate these terms, engage in fraudulent activity, or compromise the safety and integrity of our community.</p>
                
                <h6>2. User Responsibilities and Community Standards</h6>
                <p>As a valued member of the Tasty Hub community, you agree to uphold our standards and contribute positively to the platform:</p>
                <p><strong>Content Integrity:</strong><br>
                - Provide accurate, original, and truthful information in all recipe submissions<br>
                - Ensure recipes are tested and safe for consumption<br>
                - Include proper attribution when adapting recipes from other sources<br>
                - Avoid submitting duplicate or spam content</p>
                <p><strong>Prohibited Activities:</strong><br>
                - Engaging in misinformation, plagiarism, or deceptive practices<br>
                - Harassing, threatening, discriminating against, or abusing other users<br>
                - Posting content that is illegal, harmful, or violates intellectual property rights<br>
                - Attempting to circumvent our content moderation systems<br>
                - Using automated tools or bots to create accounts or submit content</p>
                <p><strong>Community Interaction:</strong><br>
                - Treat all community members with respect and courtesy<br>
                - Provide constructive feedback and helpful suggestions<br>
                - Report inappropriate content or behavior to our moderation team<br>
                - Respect cultural diversity in cooking styles and dietary preferences</p>
                <p><strong>Legal Compliance:</strong> Users must comply with all applicable local, national, and international laws while using our services.</p>
                
                <h6>3. User-Generated Content and Licensing</h6>
                <p><strong>Content Ownership:</strong> You retain full ownership and copyright of all original content you submit to Tasty Hub, including recipes, images, videos, and written descriptions.</p>
                <p><strong>Platform License:</strong> By submitting content, you grant Tasty Hub a non-exclusive, worldwide, royalty-free, transferable license to:</p>
                <p>- Use, reproduce, modify, adapt, and display your content on our platform<br>
                - Distribute your content through our website, mobile applications, and related services<br>
                - Create derivative works for promotional and marketing purposes<br>
                - Include your content in compilations, cookbooks, or other publications</p>
                <p><strong>Future Commercial Use:</strong> We may feature selected community recipes in printed cookbooks, digital publications, or promotional materials. Contributors will be notified in advance and receive appropriate credit. For commercial publications, we may offer compensation or revenue sharing.</p>
                <p><strong>Content Removal:</strong> You may delete your content at any time through your account settings. However, content that has been shared, republished, or incorporated into derivative works may continue to exist.</p>
                
                <h6>4. Content Monitoring, Moderation, and Approval Process</h6>
                <p><strong>Quality Assurance:</strong> Tasty Hub employs a comprehensive admin approval system to ensure all user-generated content meets our quality standards before publication. This process helps maintain the integrity and safety of our culinary community.</p>
                <p><strong>Review Process:</strong><br>
                - All submitted recipes undergo initial automated screening for obvious violations<br>
                - Content is then reviewed by our moderation team within 24-48 hours<br>
                - Recipes are evaluated for accuracy, safety, clarity, and community guidelines compliance<br>
                - Approved content is published and becomes visible to the community</p>
                <p><strong>Content Standards:</strong> Submissions must meet the following criteria:<br>
                - Clear, complete ingredient lists with accurate measurements<br>
                - Step-by-step instructions that are easy to follow<br>
                - Safe cooking methods and food handling practices<br>
                - Appropriate images that represent the actual recipe<br>
                - Original content or properly attributed adaptations</p>
                <p><strong>Rejection and Appeals:</strong> If content is rejected, you will receive notification with specific reasons. Rejected content will appear in your profile's "Declined" section. You may revise and resubmit content or appeal the decision by contacting our support team.</p>
                
                <h6>5. Intellectual Property Rights and Protection</h6>
                <p><strong>Tasty Hub Property:</strong> All platform elements including but not limited to website design, software, logos, trademarks, graphics, and proprietary features are the exclusive property of Tasty Hub and protected by copyright, trademark, and other intellectual property laws.</p>
                <p><strong>User Content Rights:</strong> While users retain ownership of their submitted recipes, they grant Tasty Hub the licensing rights outlined in Section 3. Users are responsible for ensuring they have the right to submit and license any content they share.</p>
                <p><strong>Third-Party Content:</strong> Users must respect the intellectual property rights of others. Submission of copyrighted material without permission is strictly prohibited and may result in account suspension or termination.</p>
                <p><strong>DMCA Compliance:</strong> We respond promptly to valid copyright infringement notices. Rights holders may report violations through our designated copyright agent contact information.</p>
                
                <h6>6. Limitation of Liability and Disclaimers</h6>
                <p><strong>Platform Disclaimer:</strong> Tasty Hub provides a platform for sharing recipes and culinary information. We do not guarantee the accuracy, completeness, safety, or reliability of any user-generated content. Users follow recipes and cooking advice at their own risk.</p>
                <p><strong>Health and Safety:</strong> We strongly encourage users to:<br>
                - Consider food allergies and dietary restrictions when trying new recipes<br>
                - Follow proper food safety and handling procedures<br>
                - Consult healthcare providers for specific dietary needs or health conditions<br>
                - Use common sense and cooking experience when interpreting recipes</p>
                <p><strong>Limitation of Liability:</strong> To the fullest extent permitted by law, Tasty Hub shall not be liable for any indirect, incidental, special, consequential, or punitive damages arising from your use of the platform or any user-generated content. Our total liability shall not exceed the amount paid by you (if any) for using our services.</p>
                <p><strong>Service Availability:</strong> While we strive for continuous service, we do not guarantee uninterrupted access to Tasty Hub. The platform may be temporarily unavailable due to maintenance, updates, or technical issues.</p>
                
                <h6>7. Privacy and Data Protection</h6>
                <p>Your privacy is important to us. Our collection, use, and protection of your personal information is governed by our Privacy Policy, which is incorporated into these Terms by reference. By using Tasty Hub, you consent to our data practices as described in the Privacy Policy.</p>
                
                <h6>8. Dispute Resolution and Governing Law</h6>
                <p><strong>Governing Law:</strong> These Terms and Conditions are governed by the laws of the Republic of the Philippines, without regard to conflict of law principles.</p>
                <p><strong>Dispute Resolution:</strong> Any disputes arising from these terms or your use of Tasty Hub will be resolved through:<br>
                1. Direct communication with our support team<br>
                2. Mediation if direct resolution is unsuccessful<br>
                3. Binding arbitration in the Philippines as a final resort</p>
                <p><strong>Class Action Waiver:</strong> You agree to resolve disputes individually and waive the right to participate in class action lawsuits.</p>
                
                <h6>9. Modifications to Terms and Service</h6>
                <p><strong>Terms Updates:</strong> Tasty Hub reserves the right to modify these Terms and Conditions at any time to reflect changes in our services, legal requirements, or business practices.</p>
                <p><strong>Notification Process:</strong> Significant changes will be communicated through:<br>
                - Email notification to registered users at least 30 days in advance<br>
                - Prominent notice on our website homepage<br>
                - Updated effective date at the top of these Terms</p>
                <p><strong>Acceptance:</strong> Your continued use of Tasty Hub after changes take effect constitutes acceptance of the modified Terms. If you disagree with changes, you may terminate your account before the effective date.</p>
                <p><strong>Service Modifications:</strong> We may modify, suspend, or discontinue any aspect of our services at any time. We will provide reasonable notice for significant service changes that materially affect user experience.</p>
                
                <h6>10. Account Termination and Data Retention</h6>
                <p><strong>Voluntary Termination:</strong> You may delete your account at any time through your account settings. Upon deletion, your personal information will be removed within 30 days, though some content may remain for legal or operational purposes.</p>
                <p><strong>Involuntary Termination:</strong> We may suspend or terminate accounts that violate these Terms, engage in harmful behavior, or compromise platform security. Terminated users will receive notification with specific reasons when possible.</p>
                <p><strong>Data Retention:</strong> After account termination, we may retain certain information as required by law, for fraud prevention, or to resolve disputes. Retained data is subject to our Privacy Policy.</p>
                
                <h6>11. Severability and Entire Agreement</h6>
                <p>If any provision of these Terms is found to be unenforceable or invalid, that provision will be limited or eliminated to the minimum extent necessary so that these Terms shall otherwise remain in full force and effect. These Terms, along with our Privacy Policy, constitute the entire agreement between you and Tasty Hub regarding your use of our services.</p>
                
                <h6>12. Contact Information and Support</h6>
                <p>For questions, concerns, or support regarding these Terms and Conditions or any aspect of Tasty Hub services, please contact us:</p>
                <p><strong>Email:</strong> <a href="mailto:tastyhub@gmail.com" class="text-primary hover:underline">tastyhub@gmail.com</a><br>
                <strong>Subject Line:</strong> Terms and Conditions Inquiry<br>
                <strong>Response Time:</strong> We aim to respond to all inquiries within 24-48 hours<br>
                <strong>Business Hours:</strong> Monday-Friday, 9:00 AM - 6:00 PM (Philippine Standard Time)</p>
                <p>Thank you for being part of the Tasty Hub community. We're excited to share this culinary journey with you!</p>
        </div>

      </div>

      <!-- Footer -->
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script>
  // Automatically switch to correct tab when clicked from footer
  const legalModal = document.getElementById('legalModal');
  legalModal.addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    const tab = button.getAttribute('data-tab');

    if (tab === "terms") {
      const termsTab = new bootstrap.Tab(document.querySelector('#terms-tab'));
      termsTab.show();
    } else {
      const privacyTab = new bootstrap.Tab(document.querySelector('#privacy-tab'));
      privacyTab.show();
    }
  });
</script>

<script>
    // Show/hide button on scroll
    window.onscroll = function () {
        let btn = document.getElementById("backToTopBtn");
        if (document.body.scrollTop > 100 || document.documentElement.scrollTop > 100) {
            btn.style.display = "block";
        } else {
            btn.style.display = "none";
        }
    };

    // Smooth scroll to top when clicked
    document.getElementById("backToTopBtn").addEventListener("click", function (e) {
        e.preventDefault();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
</script>

        <!-- JavaScript Libraries -->
        <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
        <script src="lib/wow/wow.min.js"></script>
        <script src="lib/easing/easing.min.js"></script>
        <script src="lib/waypoints/waypoints.min.js"></script>
        <script src="lib/counterup/counterup.min.js"></script>
        <script src="lib/owlcarousel/owl.carousel.min.js"></script>
        <script src="lib/tempusdominus/js/moment.min.js"></script>
        <script src="lib/tempusdominus/js/moment-timezone.min.js"></script>
        <script src="lib/tempusdominus/js/tempusdominus-bootstrap-4.min.js"></script>

        <!-- Template Javascript -->
        <script src="js/main.js"></script>

    <script>
        document.querySelector("form").addEventListener("submit", function (event) {
        var password = document.getElementById("password").value;
        var confirmPassword = document.getElementById("confirm_password").value;
        var passwordError = document.getElementById("passwordError");
        var confirmPasswordError = document.getElementById("confirmPasswordError");

        // Clear previous error messages
        passwordError.textContent = "";
        confirmPasswordError.textContent = "";

        let isValid = true;

        if (password.length < 8) {
            passwordError.textContent = "Password must be at least 8 characters long!";
            isValid = false;
        }

        if (password !== confirmPassword) {
            confirmPasswordError.textContent = "Passwords do not match!";
            isValid = false;
        }

        if (!isValid) {
            event.preventDefault();
        }
    });

    </script>

        <script>

            //Switch Modal
            document.addEventListener("DOMContentLoaded", function () {
                document.getElementById("switchToSignIn").addEventListener("click", function (event) {
                    event.preventDefault(); // Prevent the default link action
                    
                    var registerModal = document.getElementById("registerModal");
                    var signInModal = new bootstrap.Modal(document.getElementById("signInModal"));

                    // Hide register modal first
                    var registerModalInstance = bootstrap.Modal.getInstance(registerModal);
                    registerModalInstance.hide();

                    // Wait for the register modal to fully close, then show sign-in modal
                    registerModal.addEventListener("hidden.bs.modal", function () {
                        signInModal.show();
                    }, { once: true }); // Ensures this runs only once per click
                });
            });

            document.addEventListener("DOMContentLoaded", function () {
            document.getElementById("switchToSignUp").addEventListener("click", function (event) {
                event.preventDefault(); // Prevent default link behavior

                var signInModal = document.getElementById("signInModal");
                var signUpModal = new bootstrap.Modal(document.getElementById("registerModal"));

                // Hide Sign In modal first
                var signInModalInstance = bootstrap.Modal.getInstance(signInModal);
                signInModalInstance.hide();

                // Wait for the Sign In modal to fully close, then open Sign Up modal
                signInModal.addEventListener("hidden.bs.modal", function () {
                    signUpModal.show();
                }, { once: true }); // Runs only once per click
            });
        });
        
        </script>
    </body>

    </html>