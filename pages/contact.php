<?php
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/helpers.php';

$pageTitle = 'Contact Us';
$metaDescription = 'Contact SarkariJobs Portal. Get in touch for support, feedback, or partnership inquiries.';
$additionalHead = '';

include __DIR__ . '/../includes/header.php';
?>

<section class="py-12 bg-white">
  <div class="container mx-auto px-4 max-w-4xl">
    <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Contact Us</h1>
    <p class="text-gray-700 mb-6">We'd love to hear from you. Reach out via email or messaging channels below.</p>

    <div class="bg-gray-50 rounded-xl p-6 border border-gray-200">
      <ul class="space-y-3 text-gray-700">
        <li class="flex items-center gap-2"><i class="fas fa-envelope text-primary"></i> <span>Email: <a href="mailto:examsz.in@gmail.com">examsz.in@gmail.com</a></span></li>
        <li class="flex items-center gap-2"><i class="fab fa-telegram text-[#229ED9]"></i> <a class="text-primary hover:underline" href="<?= htmlspecialchars(TELEGRAM_URL) ?>" target="_blank" rel="noopener">Join Telegram</a></li>
        <li class="flex items-center gap-2"><i class="fab fa-whatsapp text-[#25D366]"></i> <a class="text-primary hover:underline" href="<?= htmlspecialchars(WHATSAPP_URL) ?>" target="_blank" rel="noopener">Join WhatsApp</a></li>
      </ul>
    </div>
  </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
