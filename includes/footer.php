    </main>
    
    <!-- Footer -->
    <footer class="text-white py-5 mt-5" style="background-color: <?php echo PRIMARY_COLOR; ?>;">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h5 class="fw-bold mb-3">
                        <i class="fas fa-graduation-cap me-2"></i>UIU Alumni Connect
                    </h5>
                    <p class="text-white-50">
                        Connecting United International University alumni worldwide for networking, 
                        experience sharing, and collaboration.
                    </p>
                    <div class="social-links mt-3">
                        <a href="#" class="text-white me-3"><i class="fab fa-facebook fa-lg"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-twitter fa-lg"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-linkedin fa-lg"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-instagram fa-lg"></i></a>
                    </div>
                </div>
                
                <div class="col-md-2 mb-4">
                    <h6 class="fw-bold mb-3">Quick Links</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="<?php echo SITE_URL; ?>" class="text-white-50 text-decoration-none">Home</a></li>
                        <li class="mb-2"><a href="<?php echo SITE_URL; ?>/user/feed.php" class="text-white-50 text-decoration-none">Feed</a></li>
                        <li class="mb-2"><a href="<?php echo SITE_URL; ?>/user/explore.php" class="text-white-50 text-decoration-none">Alumni</a></li>
                        <li class="mb-2"><a href="<?php echo SITE_URL; ?>/user/jobs.php" class="text-white-50 text-decoration-none">Jobs</a></li>
                    </ul>
                </div>
                
                <div class="col-md-3 mb-4">
                    <h6 class="fw-bold mb-3">Resources</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#" class="text-white-50 text-decoration-none">About UIU</a></li>
                        <li class="mb-2"><a href="#" class="text-white-50 text-decoration-none">Events</a></li>
                        <li class="mb-2"><a href="#" class="text-white-50 text-decoration-none">Privacy Policy</a></li>
                        <li class="mb-2"><a href="#" class="text-white-50 text-decoration-none">Terms of Service</a></li>
                    </ul>
                </div>
                
                <div class="col-md-3 mb-4">
                    <h6 class="fw-bold mb-3">Contact</h6>
                    <ul class="list-unstyled text-white-50">
                        <li class="mb-2">
                            <i class="fas fa-map-marker-alt me-2"></i>
                            United City, Madani Avenue, Dhaka
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-phone me-2"></i>
                            +880 2 890 1912
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-envelope me-2"></i>
                            alumni@uiu.ac.bd
                        </li>
                    </ul>
                </div>
            </div>
            
            <hr class="bg-white my-4">
            
            <div class="row">
                <div class="col-md-6 text-center text-md-start">
                    <p class="mb-0 text-white-50">
                        &copy; <?php echo date('Y'); ?> United International University. All rights reserved.
                    </p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <p class="mb-0 text-white-50">
                        Made with <i class="fas fa-heart text-danger"></i> for UIU Alumni
                    </p>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- MDB Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.0/mdb.min.js"></script>
    
    <!-- Custom JS -->
    <script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
</body>
</html>
<?php ob_end_flush(); // Flush output buffer ?>
