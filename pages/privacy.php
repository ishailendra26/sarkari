<?php
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/helpers.php';

$pageTitle = 'Privacy Policy';
$metaDescription = 'Privacy Policy of SarkariJobs Portal explaining data usage and protection.';
$additionalHead = '';

include __DIR__ . '/../includes/header.php';
?>

<section class="py-12 bg-white">
  <div class="container mx-auto px-4 max-w-4xl prose prose-blue">
    <h1>Privacy Policy</h1>
    <p>At <?= htmlspecialchars(SITE_NAME) ?>, we respect your privacy. This Privacy Policy explains what information we collect and how we use it.</p>

    <h2>Information We Collect</h2>
    <p>We may collect non-personal information such as browser type, referral source, and usage patterns to improve our services. If you contact us, we may store your contact details to respond.</p>

    <h2>Cookies</h2>
    <p>We may use cookies to enhance user experience and analyze traffic. You can control cookie usage through your browser settings.</p>

    <h2>Third-Party Links</h2>
    <p>Our site may contain links to external websites. We are not responsible for the content or privacy practices of those sites.</p>

    <h2>Updates</h2>
    <p>We may update this Privacy Policy from time to time. Continued use of the website implies acceptance of the updated policy.</p>

    <h2>Contact</h2>
    <p>For questions about this Privacy Policy, please visit our <a href="<?= SITE_URL ?>/pages/contact.php">Contact</a> page.</p>
  </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
