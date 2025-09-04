<?php
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/helpers.php';

$pageTitle = 'Social Connect';
$metaDescription = 'Connect with SarkariJobs on social platforms. Stay updated with latest jobs, results, admit cards, and syllabus.';
$additionalHead = '';

include __DIR__ . '/../includes/header.php';
?>

<section class="py-12 bg-white">
  <div class="container mx-auto px-4 max-w-5xl">
    <div class="mb-8">
      <h1 class="text-3xl md:text-4xl font-bold text-gray-900">Connect with SarkariJobs</h1>
      <p class="mt-2 text-gray-600">Follow us to never miss updates on government jobs, results, admit cards, and syllabus.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <a href="<?= SITE_URL ?>" class="p-5 border rounded-lg hover:shadow transition flex items-center justify-between">
        <div>
          <div class="font-semibold text-gray-800">Website</div>
          <div class="text-gray-500 text-sm">Visit our homepage</div>
        </div>
        <i class="fas fa-globe text-primary text-xl"></i>
      </a>

      <a href="https://www.youtube.com/@examsz" target="_blank" rel="noopener" class="p-5 border rounded-lg hover:shadow transition flex items-center justify-between">
        <div>
          <div class="font-semibold text-gray-800">YouTube</div>
          <div class="text-gray-500 text-sm">Video updates and guides</div>
        </div>
        <i class="fab fa-youtube text-red-600 text-xl"></i>
      </a>

      <a href="<?= htmlspecialchars(defined('WHATSAPP_URL') ? WHATSAPP_URL : 'https://whatsapp.com/channel/0029Vb6ShUtCxoB4OoquV03Z') ?>" target="_blank" rel="noopener" class="p-5 border rounded-lg hover:shadow transition flex items-center justify-between">
        <div>
          <div class="font-semibold text-gray-800">WhatsApp Channel</div>
          <div class="text-gray-500 text-sm">Instant notifications</div>
        </div>
        <i class="fab fa-whatsapp text-green-600 text-xl"></i>
      </a>

      <a href="<?= htmlspecialchars(defined('TELEGRAM_URL') ? TELEGRAM_URL : 'https://t.me/examszin') ?>" target="_blank" rel="noopener" class="p-5 border rounded-lg hover:shadow transition flex items-center justify-between">
        <div>
          <div class="font-semibold text-gray-800">Telegram</div>
          <div class="text-gray-500 text-sm">Join our Telegram</div>
        </div>
        <i class="fab fa-telegram text-sky-600 text-xl"></i>
      </a>

      <a href="https://instagram.com/examsz" target="_blank" rel="noopener" class="p-5 border rounded-lg hover:shadow transition flex items-center justify-between">
        <div>
          <div class="font-semibold text-gray-800">Instagram</div>
          <div class="text-gray-500 text-sm">Short updates and stories</div>
        </div>
        <i class="fab fa-instagram text-pink-600 text-xl"></i>
      </a>

      <a href="https://x.com/examszin" target="_blank" rel="noopener" class="p-5 border rounded-lg hover:shadow transition flex items-center justify-between">
        <div>
          <div class="font-semibold text-gray-800">X (Twitter)</div>
          <div class="text-gray-500 text-sm">Quick alerts</div>
        </div>
        <i class="fab fa-x-twitter text-gray-800 text-xl"></i>
      </a>

      <a href="https://www.facebook.com/examsz" target="_blank" rel="noopener" class="p-5 border rounded-lg hover:shadow transition flex items-center justify-between">
        <div>
          <div class="font-semibold text-gray-800">Facebook</div>
          <div class="text-gray-500 text-sm">Community updates</div>
        </div>
        <i class="fab fa-facebook text-blue-600 text-xl"></i>
      </a>

      <a href="https://www.linkedin.com/company/examsz" target="_blank" rel="noopener" class="p-5 border rounded-lg hover:shadow transition flex items-center justify-between">
        <div>
          <div class="font-semibold text-gray-800">LinkedIn</div>
          <div class="text-gray-500 text-sm">Professional updates</div>
        </div>
        <i class="fab fa-linkedin text-blue-700 text-xl"></i>
      </a>

      <a href="https://in.pinterest.com/examsz/" target="_blank" rel="noopener" class="p-5 border rounded-lg hover:shadow transition flex items-center justify-between">
        <div>
          <div class="font-semibold text-gray-800">Pinterest</div>
          <div class="text-gray-500 text-sm">Visual guides</div>
        </div>
        <i class="fab fa-pinterest text-red-600 text-xl"></i>
      </a>

      <a href="https://www.quora.com/profile/Examsz" target="_blank" rel="noopener" class="p-5 border rounded-lg hover:shadow transition flex items-center justify-between">
        <div>
          <div class="font-semibold text-gray-800">Quora</div>
          <div class="text-gray-500 text-sm">Q&A and tips</div>
        </div>
        <i class="fab fa-quora text-red-700 text-xl"></i>
      </a>

      <a href="mailto:contact@examsz.in" class="p-5 border rounded-lg hover:shadow transition flex items-center justify-between">
        <div>
          <div class="font-semibold text-gray-800">Email (Support)</div>
          <div class="text-gray-500 text-sm">contact@examsz.in</div>
        </div>
        <i class="fas fa-envelope text-primary text-xl"></i>
      </a>

      <a href="mailto:admin@examsz.in" class="p-5 border rounded-lg hover:shadow transition flex items-center justify-between">
        <div>
          <div class="font-semibold text-gray-800">Email (Admin)</div>
          <div class="text-gray-500 text-sm">admin@examsz.in</div>
        </div>
        <i class="fas fa-envelope text-primary text-xl"></i>
      </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mt-10 items-center">
      <div>
        <h2 class="text-2xl font-semibold text-gray-900 mb-2">Short Link</h2>
        <p class="text-gray-600 mb-4">Share our website with friends preparing for exams.</p>
        <div class="bg-gray-50 border rounded p-4 font-mono text-sm select-all"><?= htmlspecialchars(SITE_URL) ?></div>
      </div>
      <div class="text-center">
        <h2 class="text-2xl font-semibold text-gray-900 mb-2">Scan To Visit</h2>
        <p class="text-gray-600 mb-4">Scan this QR to open our website on your phone.</p>
        <img src="<?= SITE_URL ?>/assets/images/ExamszQr.png" alt="QR to visit" class="mx-auto h-44 w-44 object-contain">
        <p class="text-gray-500 text-sm mt-3">High-quality updates with clean interface.</p>
      </div>
    </div>

  </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
