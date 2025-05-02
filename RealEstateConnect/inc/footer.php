    </main>

    <!-- Footer -->
    <footer class="footer bg-dark text-white py-5 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4 mb-md-0">
                    <h5 class="text-primary mb-4">Real Estate Listing System</h5>
                    <p>Find your dream home with our comprehensive real estate listing platform. Browse properties, connect with sellers, and make informed decisions.</p>
                    <div class="social-icons mt-3">
                        <a href="#" class="text-white me-3"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                
                <div class="col-md-2 mb-4 mb-md-0">
                    <h5 class="text-white mb-4">Quick Links</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="/" class="text-white text-decoration-none">Home</a></li>
                        <li class="mb-2"><a href="/search.php" class="text-white text-decoration-none">Properties</a></li>
                        <li class="mb-2"><a href="/contact.php" class="text-white text-decoration-none">Contact</a></li>
                        <?php if (!isLoggedIn()): ?>
                            <li class="mb-2"><a href="/login.php" class="text-white text-decoration-none">Login</a></li>
                            <li class="mb-2"><a href="/register.php" class="text-white text-decoration-none">Register</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <div class="col-md-3 mb-4 mb-md-0">
                    <h5 class="text-white mb-4">Property Types</h5>
                    <ul class="list-unstyled">
                        <?php
                        $propertyTypes = getPropertyTypes();
                        foreach ($propertyTypes as $type) {
                            echo '<li class="mb-2"><a href="/search.php?type=' . $type['id'] . '" class="text-white text-decoration-none">' . $type['name'] . '</a></li>';
                        }
                        ?>
                    </ul>
                </div>
                
                <div class="col-md-3">
                    <h5 class="text-white mb-4">Contact Us</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><i class="fas fa-map-marker-alt me-2"></i> 123 Real Estate St, City</li>
                        <li class="mb-2"><i class="fas fa-phone-alt me-2"></i> (123) 456-7890</li>
                        <li class="mb-2"><i class="fas fa-envelope me-2"></i> info@realestate.com</li>
                    </ul>
                </div>
            </div>
            
            <hr class="bg-light my-4">
            
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start">
                    <p class="mb-0">&copy; <?= date('Y') ?> Real Estate Listing System. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-center text-md-end mt-3 mt-md-0">
                    <p class="mb-0">
                        <a href="#" class="text-white text-decoration-none me-3">Privacy Policy</a>
                        <a href="#" class="text-white text-decoration-none me-3">Terms of Service</a>
                        <a href="#" class="text-white text-decoration-none">FAQ</a>
                    </p>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
