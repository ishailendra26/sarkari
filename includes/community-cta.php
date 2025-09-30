<?php
// Community CTA partial: Telegram, WhatsApp, Bookmark
?>
<section class="py-6 bg-white">
  <div class="container mx-auto px-4">
    <div class="rounded-2xl p-6 md:p-8 bg-gradient-to-r from-blue-50 via-indigo-50 to-purple-50 border border-blue-100 shadow-sm">
      <div class="flex flex-col md:flex-row items-center gap-4 md:gap-6 justify-between">
        <div class="text-center md:text-left">
          <h2 class="text-2xl md:text-3xl font-extrabold text-gray-900">Stay Updated Daily</h2>
          <p class="text-gray-700 mt-1">Join our channels and bookmark the page for instant job alerts, admit cards, results, and posts.</p>
        </div>
        <div class="flex flex-wrap items-center justify-center gap-3">
          <a href="<?= htmlspecialchars(TELEGRAM_URL) ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-[#229ED9] text-white font-semibold hover:opacity-90 shadow">
            <i class="fab fa-telegram-plane text-lg"></i>
            <span>Join Telegram</span>
          </a>
          <a href="<?= htmlspecialchars(WHATSAPP_URL) ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-[#25D366] text-white font-semibold hover:opacity-90 shadow">
            <i class="fab fa-whatsapp text-lg"></i>
            <span>Join WhatsApp</span>
          </a>
        </div>
      </div>
    </div>
  </div>
</section>
<?php // Bookmark button removed per request ?>
