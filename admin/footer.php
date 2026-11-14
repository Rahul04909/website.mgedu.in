                    <!-- Container-fluid closed -->
                    </div>
                <!-- Content section closed -->
            </section>
            <!-- Content-wrapper closed -->
        </div>

        <footer class="main-footer mg-admin-footer">
            <div class="mg-footer-container">
                <!-- Left: Copyright -->
                <div class="mg-footer-left">
                    <div class="mg-footer-copy">
                        &copy; <?= date('Y') ?> <span><?= htmlspecialchars(env('APP_NAME', 'MG Education & Social Development Organization')) ?></span>. All rights reserved.
                    </div>
                </div>

                <!-- Right: Developer Credit -->
                <div class="mg-footer-right">
                    <span>Designed &amp; Developed by</span>
                    <a href="https://www.prayagcomputer.in" target="_blank" class="mg-dev-credit-badge" title="Developer Contact: +91-8059982049">
                        <i class="fas fa-code"></i>
                        <span>Rahul Dhiman</span>
                    </a>
                </div>
            </div>
        </footer>

        <script>
            function logout() {
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You will be logged out!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, log me out!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Logged out!',
                            text: 'You have been successfully logged out.',
                            icon: 'success',
                            showConfirmButton: false,
                            timer: 1500
                        }).then(() => {
                            window.location.href = 'logout.php';
                        });
                    }
                });
            }

        </script>

        <!-- Wrapper closed -->
    </div>
    <!-- Body closed -->
</body>

</html>