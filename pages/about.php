<?php
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/helpers.php';

$pageTitle = 'About Us';
$metaDescription = 'Learn about SarkariJobs Portal – your trusted source for latest government jobs, results, admit cards and syllabus.';
$additionalHead = '';

include __DIR__ . '/../includes/header.php';
?>

<section class="py-12 bg-white">
  <div class="container mx-auto px-4 max-w-4xl">
    <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">About Us</h1>
    <p class="text-gray-700 leading-relaxed mb-4">
      Examsz.in - Your Exam Success Partner Portal is dedicated to providing timely and accurate updates on government job notifications,
      exam results, admit cards, and exam syllabus. Our goal is to simplify access to official information with
      a clean and user-friendly interface.
    </p>
    <p class="text-gray-700 leading-relaxed mb-4">
      We aggregate data from official sources and present them with SEO-friendly URLs and intuitive navigation
      so that you can quickly find what you need.
    </p>
    <p class="text-gray-700 leading-relaxed">
      For feedback and partnerships, feel free to reach out via our Contact page.
      <br>
      Email: <a href="mailto:examsz.in@gmail.com">examsz.in@gmail.com</a>
    </p>
  </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
