        </main>
    </div>

    <!-- Mobile Menu Toggle (hidden by default, can be shown on mobile) -->
    <div id="mobile-menu" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 lg:hidden">
        <div class="fixed inset-y-0 left-0 w-64 bg-white shadow-lg">
            <div class="p-4">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-bold text-gray-800">Menu</h2>
                    <button onclick="toggleMobileMenu()" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <nav>
                    <ul class="space-y-2">
                        <li><a href="index.php" class="flex items-center p-3 text-gray-700 hover:bg-gray-100 rounded-lg"><i class="fas fa-tachometer-alt mr-3"></i>Dashboard</a></li>
                        <li><a href="jobs.php" class="flex items-center p-3 text-gray-700 hover:bg-gray-100 rounded-lg"><i class="fas fa-briefcase mr-3"></i>Manage Jobs</a></li>
                        <li><a href="results.php" class="flex items-center p-3 text-gray-700 hover:bg-gray-100 rounded-lg"><i class="fas fa-trophy mr-3"></i>Manage Results</a></li>
                        <li><a href="admit-cards.php" class="flex items-center p-3 text-gray-700 hover:bg-gray-100 rounded-lg"><i class="fas fa-id-card mr-3"></i>Manage Admit Cards</a></li>
                        <li><a href="syllabi.php" class="flex items-center p-3 text-gray-700 hover:bg-gray-100 rounded-lg"><i class="fas fa-book mr-3"></i>Manage Syllabus</a></li>
                        <li><a href="categories.php" class="flex items-center p-3 text-gray-700 hover:bg-gray-100 rounded-lg"><i class="fas fa-tags mr-3"></i>Categories</a></li>
                        <li><a href="settings.php" class="flex items-center p-3 text-gray-700 hover:bg-gray-100 rounded-lg"><i class="fas fa-cog mr-3"></i>Settings</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        function toggleMobileMenu() {
            const menu = document.getElementById('mobile-menu');
            menu.classList.toggle('hidden');
        }

        // Close mobile menu when clicking outside
        document.getElementById('mobile-menu').addEventListener('click', function(e) {
            if (e.target === this) {
                toggleMobileMenu();
            }
        });

        // Confirm delete actions
        function confirmDelete(message = 'Are you sure you want to delete this item?') {
            return confirm(message);
        }
    </script>
</body>
</html>
