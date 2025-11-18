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
    .dropdown-menu {
        right: 0; /* Aligns the dropdown menu to the right */
        left: auto; /* Prevents the left alignment */
    }
    .big-icon {
        font-size: 25px;
    }
    .video-container {
        position: relative;
        width: 100%;
        height: 100%;
        overflow: hidden;
    }
    .video-zoom {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transform: scale(1.2);
    }

.modal-content {
    border-radius: 10px;
}

.modal-header {
    border-bottom: none;
}

.modal-footer {
    border-top: none;
}

.form-label {
    font-weight: bold;
}

.float-end {
    margin-top: 5px;
}
.navbar .nav-item .big-icon {
    font-size: 25px; /* Adjust the size of the icon */
    margin-top: 5px; /* Center the icon vertically */
}
        .navbar {
            background-color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .nav-item .nav-link {
            color: #6c757d !important;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .nav-item .nav-link:hover {
            color: #0d6efd !important;
            border-radius: 8px;
        }
        
        .big-icon {
            font-size: 1.5rem;
        }
        
        .dropdown-menu {
            border: none;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            border-radius: 12px;
            padding: 0.5rem 0;
            min-width: 200px;
            margin-top: 0.5rem;
        }
        
        .dropdown-item {
            padding: 0.7rem 1.2rem;
            font-weight: 500;
            color: #495057;
            transition: all 0.2s ease;
            border-radius: 0;
        }
        
        .dropdown-item i {
            color: #e9802a;
        }

        .dropdown-item:hover {
            background-color: #f8f9fa;
            color: #e9802a;
        }

        .dropdown-item.text-danger:hover {
            color: #dc3545 !important;
            background-color: rgba(220, 53, 69, 0.1);
        }
        
        .dropdown-item.text-danger i {
            color: #dc3545 !important;
        }
        
        .dropdown-divider {
            margin: 0.5rem 0;
            border-color: #dee2e6;
        }
        
        /* Orange accent colors to match the screenshot */
        .text-orange {
            color: #fd7e14 !important;
        }
        
        .dropdown-item.text-orange:hover {
            color: #fd7e14 !important;
            background-color: rgba(253, 126, 20, 0.1);
        }
        
        .dropdown-item.text-orange i {
            color: #fd7e14 !important;
        }

.nav-link.dropdown-toggle::after {
  display: none !important;
}

/* Dropdown items black text */
.dropdown-menu .dropdown-item {
  color: #000 !important;
}

.dropdown-menu .dropdown-item:hover {
  background-color: #f8f9fa !important; /* light gray hover */
  color: #e9802a !important; /* orange hover text */
}

/* Compact navbar collapse */
.navbar-nav .nav-link {
  padding-top: 0.35rem !important;
  padding-bottom: 0.35rem !important;
  font-size: 0.9rem;
}

.navbar-toggler {
  padding: 0.25rem 0.5rem !important;
  font-size: 0.9rem !important;
}
</style>
<body class="bg-white">
           <!-- Navbar & Hero Start -->
        <div class="container-xxl position-relative p-0">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark px-4 py-2">
  <a href="dashboard.php" class="navbar-brand p-0">
    <h1 class="text-primary m-0 d-flex align-items-center">
      <img src="img/logo.png" alt="Logo" style="width: 40px; height: 40px;"> 
      <span class="ms-2">Tasty Hub</span>
    </h1>
  </a>

  <!-- Toggler for mobile -->
  <button class="navbar-toggler py-1 px-2" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
    <span class="navbar-toggler-icon"></span>
  </button>

  <div class="collapse navbar-collapse" id="navbarCollapse">
    <ul class="navbar-nav ms-auto">
      <li class="nav-item dropdown">
        <a href="#" class="nav-link dropdown-toggle" id="flyoutMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
          <i class="fa fa-user-circle me-1 big-icon text-orange"></i>
        </a>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="flyoutMenu">
          <li><a class="dropdown-item" href="profile.php"><i class="text-primary fa fa-user me-2"></i> Profile</a></li>
          <li><a class="dropdown-item" href="change_password.php"><i class="text-primary fa fa-key me-2"></i> Change Password</a></li>
          <li><a class="dropdown-item" href="dashboard.php"><i class="text-primary fa fa-th-large me-2"></i> Dashboard</a></li>
          <li><a class="dropdown-item" href="submit_recipe.php"><i class="text-primary fa fa-clipboard-list me-2"></i> Submit a Recipe</a></li>
          <li><a class="dropdown-item" href="about.php"><i class="text-primary fa fa-info-circle me-2"></i> About</a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item text-danger" href="logout.php"><i class="fa fa-sign-out-alt me-2"></i> Logout</a></li>
        </ul>
      </li>
    </ul>
  </div>
</nav>



            <div class="container-xxl py-5 bg-dark hero-header mb-5">
                <div class="container text-center my-5 pt-5 pb-4">
                    <h1 class="display-3 text-white mb-3 animated slideInDown">About Us</h1>
                </div>
            </div>
        </div>
        <!-- Navbar & Hero End -->
        
     

        <!-- About Start -->
        <div class="container-xxl py-5">
            <div class="container">
                <div class="row g-5 align-items-center">
                    <div class="col-lg-6">
                        <div class="row g-3">
                            <div class="col-6 text-start">
                                <img class="img-fluid rounded w-100 wow zoomIn" data-wow-delay="0.1s" src="img/about-1.jpg">
                            </div>
                            <div class="col-6 text-start">
                                <img class="img-fluid rounded w-75 wow zoomIn" data-wow-delay="0.3s" src="img/about-2.jpg" style="margin-top: 25%;">
                            </div>
                            <div class="col-6 text-end">
                                <img class="img-fluid rounded w-75 wow zoomIn" data-wow-delay="0.5s" src="img/about-3.jpg">
                            </div>
                            <div class="col-6 text-end">
                                <img class="img-fluid rounded w-100 wow zoomIn" data-wow-delay="0.7s" src="img/about-4.jpg">
                            </div>
                        </div>
                    </div>
<div class="col-lg-6">
  <h5 class="section-title ff-secondary text-start text-primary fw-normal">About Us</h5>
  <h1 class="mb-4">
    Welcome to <img src="img/logo.png" alt="Logo" style="width: 60px; height: 60px;"> Tasty Hub
  </h1>
  <p class="mb-4">
    At <strong>Tasty Hub</strong>, we believe food is more than just a meal—it’s an experience meant to be shared. Our community is a gathering place for home cooks, food lovers, and curious taste adventurers from around the world. Whether you’re whipping up a quick snack, perfecting a family classic, or experimenting with new flavors, you’ll find inspiration, support, and a whole lot of delicious ideas right here.
  </p>
  <p class="mb-4">
    Tasty Hub makes cooking fun, easy, and stress-free. Explore thousands of recipes tailored to your cravings, dietary needs, or special occasions. From <em>weeknight dinners</em> to <em>holiday feasts</em> to <em>sweet treats</em>, our smart search tools and personalized recommendations ensure you’ll always find the perfect dish. More than just recipes, we’re about connection—sharing stories, tips, and creativity to bring people closer together, one bite at a time.
  </p>
  <p class="mb-4">
    So grab your apron, join our community, and let’s cook, share, and celebrate food together. Welcome home to Tasty Hub—where every recipe starts a new story. 🍽️✨
  </p>
</div>

                </div>
            </div>
        </div>
        <!-- About End -->

<!-- Feedback Form Start -->
<div class="container-xxl py-5 px-0 wow fadeInUp" data-wow-delay="0.1s">
    <div class="row g-0">
        <!-- Feedback Form -->
        <div class="col-md-6 bg-dark d-flex align-items-center">
            <div class="p-5 wow fadeInUp" data-wow-delay="0.2s">
                <h5 class="section-title ff-secondary text-start text-primary fw-normal">Feedback</h5>
                <h1 class="text-white mb-4">We Value Your Opinion</h1>
                <form id="feedbackForm" action="submit_feedback.php" method="POST">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" class="form-control" name="name" id="name" placeholder="Enter your name" required>
                                <label for="name">Your Name</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="email" class="form-control" name="email" id="email" placeholder="Enter your email" required>
                                <label for="email">Your Email</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-floating">
                                <textarea class="form-control" name="message" id="message" rows="4" placeholder="Write your message..." required></textarea>
                                <label for="message">Your Feedback</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <button class="btn btn-primary w-100 py-3" type="submit">Submit Feedback</button>
                        </div>
                    </div>
                </form>
                <div id="feedbackMessage" class="text-white mt-3" style="display: none;"></div> <!-- Success message -->
            </div>
        </div>
        <!-- Video Section -->
        <div class="col-md-6">
            <div class="video-container">
                <video class="video-zoom" autoplay loop muted playsinline>
                    <source src="img/video.mp4" type="video/mp4">
                    Your browser does not support the video tag.
                </video>
            </div>
        </div>
    </div>
</div>


<!-- Team Start -->
<div class="container-xl pt-5 pb-3 bg-white">
  <div class="container">
    <div class="text-center wow fadeInUp" data-wow-delay="0.1s">
      <h3 class="section-title ff-secondary text-center text-primary fw-normal">Team Members</h3>
      <h1 class="mb-5">Our Developers</h1>
    </div>
    
    <div class="row g-4 justify-content-center">
      <!-- Member 1 -->
      <div class="col-lg-3 col-md-6 col-12 wow fadeInUp" data-wow-delay="0.1s">
        <div class="team-item text-center rounded overflow-hidden">
          <div class="rounded-circle overflow-hidden m-5">
            <img class="img-fluid" src="img/Althea.jpg" alt="Althea">
          </div>
          <h5 class="mb-0">Althea Benedictos</h5>
          <small>Back-End Programmer</small>
          <div class="d-flex justify-content-center mt-3">
            <a class="btn btn-square btn-primary mx-1" href="https://www.facebook.com/teyiaa"><i class="fab fa-facebook-f"></i></a>
            <a class="btn btn-square btn-primary mx-1" href="https://twitter.com/teyiaxx"><i class="fab fa-twitter"></i></a>
            <a class="btn btn-square btn-primary mx-1" href="https://www.instagram.com/theabenedictos"><i class="fab fa-instagram"></i></a>
          </div>
        </div>
      </div>

      <!-- Member 2 -->
      <div class="col-lg-3 col-md-6 col-12 wow fadeInUp" data-wow-delay="0.7s">
        <div class="team-item text-center rounded overflow-hidden">
          <div class="rounded-circle overflow-hidden m-5">
            <img class="img-fluid" src="img/Chailes.jpg" alt="Chailes">
          </div>
          <h5 class="mb-0">Christine Chailes Reyes</h5>
          <small>Front-End Programmer</small>
          <div class="d-flex justify-content-center mt-3">
            <a class="btn btn-square btn-primary mx-1" href="https://www.facebook.com/christinechailesrys"><i class="fab fa-facebook-f"></i></a>
            <a class="btn btn-square btn-primary mx-1" href="https://twitter.com/zmfltmxls0v0"><i class="fab fa-twitter"></i></a>
            <a class="btn btn-square btn-primary mx-1" href="https://www.instagram.com/christineee_rys"><i class="fab fa-instagram"></i></a>
          </div>
        </div>
      </div>
    </div>
    
  </div>
</div>
<!-- Team End -->

        
      <!-- Footer Start -->
    <div class="container-fluid bg-dark text-light footer pt-5 mt-5 wow fadeIn" data-wow-delay="0.1s">
        <div class="container py-5">
            <div class="row g-5">
                <div class="col-lg-3 col-md-6">
                    <h4 class="section-title ff-secondary text-start text-primary fw-normal mb-4">Company</h4>
                    <a class="btn btn-link" href="about.php">About Us</a>
                <a class="btn btn-link" href="#" data-bs-toggle="modal" data-bs-target="#privacyPolicyModal">Privacy Policy</a>
                <a class="btn btn-link" href="#" data-bs-toggle="modal" data-bs-target="#termsConditionsModal">Terms & Conditions</a>
                </div>

                 <div class="col-lg-6 col-md-12 text-center">
                    <h1 class="text-primary" style="font-size: 6rem; font-weight: bold; letter-spacing: 3px;">Tasty Hub</h1>
                </div>
                <div class="col-lg-3 col-md-6">
                    <h4 class="section-title ff-secondary text-start text-primary fw-normal mb-4">Contact</h4>
                    <p class="mb-2"><i class="fa fa-map-marker-alt me-3"></i>Bulacan, Philippines</p>
                    <p class="mb-2"><i class="fa fa-envelope me-3"></i>tastyhub@gmail.com</p>
                    <p class="mb-2"><i class="fa fa-phone-alt me-3"></i>+639352558377</p>

                        <!--
                    <div class="d-flex pt-2">
                        <a class="btn btn-outline-light btn-social" href=""><i class="fab fa-twitter"></i></a>
                        <a class="btn btn-outline-light btn-social" href=""><i class="fab fa-facebook-f"></i></a>
                        <a class="btn btn-outline-light btn-social" href=""><i class="fab fa-youtube"></i></a>
                    </div>
                    -->
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="position-relative mx-auto" style="max-width: 400px;">
                        <!-- Additional content if needed -->
                    </div>
                </div>
            </div>
        </div>
        <div class="container">
            <div class="copyright">
                <div class="row">
                    <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                        &copy; <a class="border-bottom" href="#">Tasty Hub</a>, All Right Reserved. 
                        Developed By <a class="border-bottom" href="about.php">Our Team</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Footer End -->

    <!-- Privacy Policy Modal -->
<div class="modal fade" id="privacyPolicyModal" tabindex="-1" aria-labelledby="privacyPolicyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="privacyPolicyModalLabel">Privacy Policy</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6>Effective Date: April 11, 2025</h6>
                <p>Welcome to Tasty Hub, a community-driven recipe-sharing website. Your privacy is important to us, and this Privacy Policy outlines how we collect, use, disclose, and protect your information when you use our services.</p>
                <h6>1. Information We Collect</h6>
                <p>At Tasty Hub, we only collect the following information: <br>
                - <strong>Username:</strong> This is your unique identifier on our platform, allowing you to create and manage your recipes and interact with other users.<br>
                - <strong>Email Address:</strong> We use your email to communicate with you, including account verification, password recovery, and important updates about our services.</p>
                <h6>2. User-Generated Content</h6>
                <p>As a user of Tasty Hub, you have full control over the recipes you create and manage. When you submit a recipe, you are responsible for the content you provide, including ingredients, instructions, and any images. By submitting your recipes, you grant Tasty Hub a non-exclusive, worldwide, royalty-free license to use, reproduce, and display your content on our platform. In the future, we may publish a cookbook featuring selected recipes from our community. If your recipe is chosen for inclusion, we will notify you and provide appropriate credit.</p>
                <h6>3. How We Use Your Information</h6>
                <p>We use the information we collect for the following purposes: <br>
                - <strong>Account Management:</strong> To create and manage your user account, allowing you to submit, edit, and delete your recipes.<br>
                <strong>Communication:</strong> To send you important updates, newsletters, and notifications related to your account and our services.<br>
                - <strong>Community Engagement:</strong> To facilitate interactions between users, such as comments and feedback on recipes.</p>
                
                <h6>4. Sharing Your Information</h6>
                <p>We do not sell or rent your personal information to third parties. However, we may share your information in the following situations: <br>
                - <strong>With Your Consent:</strong> We may share your information if you provide explicit consent.<br>
                - <strong>Service Providers:</strong> We may employ third-party companies and individuals to facilitate our services, such as hosting and data analysis. These third parties have access to your personal information only to perform tasks on our behalf and are obligated not to disclose or use it for any other purpose.</p>
                
                <h6>5. Data Security</h6>
                <p>We take the security of your personal information seriously and implement reasonable measures to protect it from unauthorized access, use, or disclosure. However, please be aware that no method of transmission over the Internet or method of electronic storage is 100% secure.</p>
                
                <h6>6. Your Rights</h6>
                <p>You have the following rights regarding your personal information: <br>
                - <strong>Access:</strong> You can request access to the personal information we hold about you.<br>
                - <strong>Correction:</strong> You can request that we correct any inaccuracies in your personal information.<br>
                - <strong>Deletion:</strong> You can request the deletion of your account and any associated data at any time.</p>
                
                <h6>7. Changes to This Privacy Policy</h6>
                <p>We may update our Privacy Policy from time to time. We will notify you of any changes by posting the new Privacy Policy on this page, and we encourage you to review it periodically.</p>
                
                <h6>8. Contact Us</h6>
                <p>If you have any questions or concerns about this Privacy Policy or our data practices, please contact us at <a href="mailto:tastyhub@gmail.com">tastyhub@gmail.com</a>.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<!-- Privacy Policy Modal End -->

<!-- Terms and Conditions Modal -->
<div class="modal fade" id="termsConditionsModal" tabindex="-1" aria-labelledby="termsConditionsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="termsConditionsModalLabel">Terms and Conditions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6>Effective Date: April 10, 2025</h6>
                <p>Welcome to Tasty Hub, a community-driven recipe-sharing website where users can discover, create, and share their own recipes. By accessing or using Tasty Hub, you agree to comply with and be bound by these Terms and Conditions. If you do not agree with any part of these Terms, you must not use our services.</p>
                
                <h6>1. User Eligibility</h6>
                <p>Tasty Hub is open to all users. Especially those who loved food and want to contribute to the food community</p>
                
                <h6>2. User Responsibilities</h6>
                <p>As a user of Tasty Hub, you agree to:<br>
                - Provide accurate and truthful information when creating your account and submitting recipes.<br>
                - Not engage in any form of misinformation, plagiarism, or any other deceptive practices.<br>
                - Respect the rights of other users and refrain from harassing, threatening, or abusing others.<br>
                - Comply with all applicable laws and regulations while using our services.</p>
                
                <h6>3. User-Generated Content</h6>
                <p>When you submit a recipe or any other content to Tasty Hub, you grant us a non-exclusive, worldwide, royalty-free license to use, reproduce, modify, publish, and display your content for future purposes, including but not limited to potential inclusion in cookbooks.</p>
                
                <h6>4. Content Monitoring and Approval</h6>
                <p>Tasty Hub employs an admin approval system to monitor user-generated content before it is published on the platform. This process is in place to ensure the quality and integrity of the content shared on our website. We reserve the right to reject any content that does not meet our standards or violates these Terms. If your content is rejected, you will see it in declined section in your profile.</p>
                
                <h6>5. Intellectual Property</h6>
                <p>All content on Tasty Hub, including but not limited to text, graphics, logos, and software, is the property of Tasty Hub or its licensors and is protected by copyright, trademark, and other intellectual property laws. Users retain ownership of their submitted recipes but grant Tasty Hub the rights outlined in Section 3.</p>
                
                <h6>6. Limitation of Liability</h6>
                <p>Tasty Hub is not liable for any damages arising from your use of the website or any content submitted by users. We do not guarantee the accuracy, reliability, or completeness of any user-generated content. You acknowledge that any reliance on such content is at your own risk.</p>
                
                <h6>7. Changes to These Terms</h6>
                <p>Tasty Hub reserves the right to modify these Terms and Conditions at any time. We will let users know of any significant changes by posting the updated Terms on our website. Your continued use of Tasty Hub after any changes constitutes your acceptance of the new Terms.</p>
                
                <h6>8. Contact Us</h6>
                <p>If you have any questions or concerns about these Terms and Conditions, please contact us at <a href="mailto:tastyhub@gmail.com">tastyhub@gmail.com</a>.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<!-- Terms and Conditions Modal End -->
    <!-- Footer End -->




        <!-- Back to Top -->
        <a href="#" class="btn btn-lg btn-primary btn-lg-square back-to-top"><i class="bi bi-arrow-up"></i></a>
    </div>

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

<script>
    $(document).ready(function() {
        $('#feedbackForm').on('submit', function(event) {
            event.preventDefault(); // Prevent the default form submission

            $.ajax({
                url: $(this).attr('action'), // Use the form's action attribute
                type: $(this).attr('method'), // Use the form's method attribute
                data: $(this).serialize(), // Serialize the form data
                success: function(response) {
                    // Assuming the server returns a success message
                    $('#feedbackMessage').text('Thank you for your feedback!').show();
                    $('#feedbackForm')[0].reset(); // Reset the form fields
                },
                error: function() {
                    $('#feedbackMessage').text('There was an error submitting your feedback. Please try again.').show();
                }
            });
        });
    });
</script>
 
</body>

</html>