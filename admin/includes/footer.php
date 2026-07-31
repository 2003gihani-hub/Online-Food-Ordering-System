<?php
// Prevent direct access
if (count(get_included_files()) === 1) {
    exit("Direct access not permitted.");
}
?>
        </div> <!-- End of admin-container -->
        
        <footer class="bg-white border-top py-3 mt-auto">
            <div class="container-fluid text-center">
                <span class="text-muted small">&copy; <?php echo date("Y"); ?> FoodExpress Admin Panel. Owner Portal Hub.</span>
            </div>
        </footer>
    </div> <!-- /#page-content-wrapper -->
</div> <!-- /#wrapper -->

<!-- Bootstrap 5 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Custom Sidebar Toggle script -->
<script>
    document.getElementById("menu-toggle").addEventListener("click", function(e) {
        e.preventDefault();
        document.getElementById("wrapper").classList.toggle("toggled");
    });

    // Alert Handling
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
