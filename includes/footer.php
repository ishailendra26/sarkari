    <!-- Footer -->
    <?php 
        if (function_exists('inferPageScopeFromRequest')) {
            [$scope, $slug] = inferPageScopeFromRequest();
            echo renderAd('footer_banner', $scope, $slug, 1);
        }
    ?>
    <footer class="bg-gray-800 text-white py-12">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <div class="mb-4">
                        <img src="<?= SITE_URL ?>/assets/images/ExamszWhite.svg" alt="SarkariJobs" class="h-8 w-auto opacity-95">
                    </div>
                    <p class="text-gray-400 mb-4">Your trusted source for latest government job notifications, results, and exam updates.</p>
                    <div class="flex space-x-4">
                        <a href="https://facebook.com/examsz" class="text-gray-400 hover:text-white"><i class="fab fa-facebook"></i></a>
                        <a href="https://x.com/examszin" class="text-gray-400 hover:text-white"><i class="fab fa-twitter"></i></a>
                        <a href="<?= htmlspecialchars(defined('TELEGRAM_URL') ? TELEGRAM_URL : '#') ?>" target="_blank" rel="noopener" class="text-gray-400 hover:text-white"><i class="fab fa-telegram"></i></a>
                        <a href="<?= htmlspecialchars(defined('WHATSAPP_URL') ? WHATSAPP_URL : '#') ?>" target="_blank" rel="noopener" class="text-gray-400 hover:text-white"><i class="fab fa-whatsapp"></i></a>
                    </div>
                </div>
                
                <div>
                    <h4 class="text-lg font-semibold mb-4">Quick Links</h4>
                    <ul class="space-y-2">
                        <li><a href="<?= SITE_URL ?>/jobs" class="text-gray-400 hover:text-white">Latest Jobs</a></li>
                        <li><a href="<?= SITE_URL ?>/results" class="text-gray-400 hover:text-white">Results</a></li>
                        <li><a href="<?= SITE_URL ?>/admit" class="text-gray-400 hover:text-white">Admit Cards</a></li>
                        <li><a href="<?= SITE_URL ?>/syllabus" class="text-gray-400 hover:text-white">Syllabus</a></li>
                    </ul>
                </div>
                
                
                
                <div>
                    <h4 class="text-lg font-semibold mb-4">Company</h4>
                    <ul class="space-y-2">
                        <li><a href="<?= SITE_URL ?>/pages/social-connect.php" class="text-gray-400 hover:text-white">Social Connect</a></li>
                        <li><a href="<?= SITE_URL ?>/pages/about.php" class="text-gray-400 hover:text-white">About Us</a></li>
                        <li><a href="<?= SITE_URL ?>/pages/contact.php" class="text-gray-400 hover:text-white">Contact Us</a></li>
                        <li><a href="<?= SITE_URL ?>/pages/terms.php" class="text-gray-400 hover:text-white">Terms &amp; Conditions</a></li>
                        <li><a href="<?= SITE_URL ?>/pages/privacy.php" class="text-gray-400 hover:text-white">Privacy Policy</a></li>
                        <li><a href="<?= SITE_URL ?>/pages/disclaimer.php" class="text-gray-400 hover:text-white">Disclaimer</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="text-lg font-semibold mb-4">Contact</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><i class="fas fa-envelope mr-2"></i>contact@examsz.in</li>
                        <li><i class="fas fa-phone mr-2"></i>+91 8418001111</li>
                        <li><i class="fas fa-map-marker-alt mr-2"></i>India</li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-gray-700 mt-8 pt-8 text-center text-gray-400">
                <p>&copy; <?= date('Y') ?> Examsz | All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="<?= SITE_URL ?>/assets/js/main.js"></script>
    
    <?php if (isset($additionalScripts)): ?>
    <?= $additionalScripts ?>
    <?php endif; ?>
</body>
</html>
