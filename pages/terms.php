<?php
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/helpers.php';

$pageTitle = 'Terms and Conditions';
$metaDescription = 'Terms and Conditions for using SarkariJobs Portal.';
$additionalHead = '';

include __DIR__ . '/../includes/header.php';
?>

<section class="py-12 bg-white">
  <div class="container mx-auto px-4 max-w-4xl prose prose-blue">
    <h1>Terms and Conditions</h1>
    <p>Welcome to <?= htmlspecialchars(SITE_NAME) ?>. By accessing or using our website, you agree to be bound by these Terms and Conditions. If you do not agree, please do not use the website.</p>

    <h2>Use of Information</h2>
    <p>We aggregate information from official sources. While we strive for accuracy, we do not guarantee completeness or timeliness. Always verify information from official notifications.</p>

    <h2>Limitation of Liability</h2>
    <p>We are not liable for any loss or damage arising from the use of information provided on this site.</p>

    <h2>Changes</h2>
    <p>We may update these terms at any time. Continued use of the site implies acceptance of the updated terms.</p>

    <h2>Contact</h2>
    <p>For any queries about these Terms, please visit our <a href="<?= SITE_URL ?>/pages/contact.php">Contact</a> page.</p>
  </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
