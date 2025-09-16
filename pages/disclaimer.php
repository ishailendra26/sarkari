<?php
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/helpers.php';

$pageTitle = 'Disclaimer';
$metaDescription = 'Disclaimer for SarkariJobs Portal regarding information accuracy and liability.';
$additionalHead = '';

include __DIR__ . '/../includes/header.php';
?>

<section class="py-12 bg-white">
  <div class="container mx-auto px-4 max-w-4xl prose prose-blue">
    <h1>Disclaimer</h1>
    <p>The information on <?= htmlspecialchars(SITE_NAME) ?> is published for general information purposes only. We collect and present information from official sources; however, we do not make any warranties about the completeness, reliability, and accuracy of this information.</p>

    <p>Any action you take upon the information you find on this website is strictly at your own risk. We will not be liable for any losses and/or damages in connection with the use of our website.</p>

    <h2>External Links</h2>
    <p>From our website, you can visit other websites by following hyperlinks. While we strive to provide quality links, we have no control over the content and nature of these sites.</p>

    <h2>Consent</h2>
    <p>By using our website, you hereby consent to our disclaimer and agree to its terms.</p>

    <h2>Update</h2>
    <p>This site disclaimer was last updated on <?= date('d M Y') ?>. Should we update, amend or make any changes, those changes will be posted here.</p>
    Email: <a href="mailto:examsz.in@gmail.com">examsz.in@gmail.com</a>
  </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
