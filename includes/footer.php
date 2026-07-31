<?php
// Prevent direct access to footer template
if (count(get_included_files()) === 1) {
    exit("Direct access not permitted.");
}
?>
</div> <!-- End of main-content-body -->

<!-- Footer Section -->
<footer>
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4 col-md-6">
                <h5 class="fw-bold"><i class="fa-solid fa-utensils me-2 text-warning"></i>FoodExpress</h5>
                <p>Order delicious, fresh food from your favorite restaurants and get it delivered straight to your door step in minutes.</p>
                <div class="d-flex gap-3 mt-3">
                    <a href="#" class="fs-4 text-white"><i class="fa-brands fa-facebook"></i></a>
                    <a href="#" class="fs-4 text-white"><i class="fa-brands fa-instagram"></i></a>
                    <a href="#" class="fs-4 text-white"><i class="fa-brands fa-twitter"></i></a>
                </div>
            </div>
            
            <div class="col-lg-2 col-md-6">
                <h5 class="fw-bold">Quick Links</h5>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="/FoodExpress/index.php">Home</a></li>
                    <li class="mb-2"><a href="/FoodExpress/categories.php">Categories</a></li>
                    <li class="mb-2"><a href="/FoodExpress/search.php">Search Foods</a></li>
                </ul>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <h5 class="fw-bold">Customer Services</h5>
                <ul class="list-unstyled">
                    <?php if (isLoggedIn()): ?>
                        <li class="mb-2"><a href="/FoodExpress/profile.php">My Account</a></li>
                        <li class="mb-2"><a href="/FoodExpress/orders.php">Order History</a></li>
                        <li class="mb-2"><a href="/FoodExpress/cart.php">Shopping Cart</a></li>
                    <?php else: ?>
                        <li class="mb-2"><a href="/FoodExpress/login.php">Login</a></li>
                        <li class="mb-2"><a href="/FoodExpress/register.php">Register</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <h5 class="fw-bold">Contact Info</h5>
                <p><i class="fa-solid fa-location-dot me-2 text-warning"></i>123 Food Street, Gourmet City</p>
                <p><i class="fa-solid fa-phone me-2 text-warning"></i>+1 234 567 890</p>
                <p><i class="fa-solid fa-envelope me-2 text-warning"></i>support@foodexpress.com</p>
            </div>
        </div>
        <hr class="my-4 bg-secondary">
        <div class="text-center">
            <p class="mb-0">&copy; <?php echo date("Y"); ?> FoodExpress. All rights reserved. Designed with <i class="fa-solid fa-heart text-danger"></i>.</p>
        </div>
    </div>
</footer>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Custom JS -->
<script src="/FoodExpress/assets/js/main.js"></script>

<!-- Flash Alerts using SweetAlert2 -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        <?php if (isset($_SESSION['success_message'])): ?>
            Swal.fire({
                title: 'Success!',
                text: '<?php echo $_SESSION['success_message']; ?>',
                icon: 'success',
                confirmButtonColor: '#ff5e3a'
            });
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            Swal.fire({
                title: 'Error!',
                text: '<?php echo $_SESSION['error_message']; ?>',
                icon: 'error',
                confirmButtonColor: '#ff5e3a'
            });
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>
    });
</script>

</body>
</html>
