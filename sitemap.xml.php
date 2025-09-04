<?php
require_once __DIR__ . '/src/config.php';
require_once __DIR__ . '/src/db.php';
require_once __DIR__ . '/src/helpers.php';
require_once __DIR__ . '/src/models/Job.php';
require_once __DIR__ . '/src/models/Result.php';
require_once __DIR__ . '/src/models/AdmitCard.php';
require_once __DIR__ . '/src/models/Syllabus.php';
require_once __DIR__ . '/src/models/Post.php';

header('Content-Type: application/xml');

$jobModel = new Job();
$resultModel = new Result();
$admitModel = new AdmitCard();
$syllabusModel = new Syllabus();
$postModel = new Post();

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <!-- Static Pages -->
    <url>
        <loc><?= SITE_URL ?>/</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    <url>
        <loc><?= SITE_URL ?>/jobs</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>0.9</priority>
    </url>
    <url>
        <loc><?= SITE_URL ?>/results</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>0.9</priority>
    </url>
    <url>
        <loc><?= SITE_URL ?>/admit</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>0.9</priority>
    </url>
    <url>
        <loc><?= SITE_URL ?>/syllabus</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
    <url>
        <loc><?= SITE_URL ?>/posts</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
    </url>

    <!-- Jobs -->
    <?php
    $jobs = $jobModel->getAll(1000, 0);
    foreach ($jobs as $job):
    ?>
    <url>
        <loc><?= SITE_URL ?>/job/<?= $job['slug'] ?></loc>
        <lastmod><?= date('Y-m-d', strtotime($job['updated_at'] ?: $job['published_at'])) ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
    <?php endforeach; ?>

    <!-- Results -->
    <?php
    $results = $resultModel->getAll(1000, 0);
    foreach ($results as $result):
    ?>
    <url>
        <loc><?= SITE_URL ?>/result/<?= $result['slug'] ?></loc>
        <lastmod><?= date('Y-m-d', strtotime($result['published_at'])) ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.7</priority>
    </url>
    <?php endforeach; ?>

    <!-- Admit Cards -->
    <?php
    $admits = $admitModel->getAll(1000, 0);
    foreach ($admits as $admit):
    ?>
    <url>
        <loc><?= SITE_URL ?>/admit/<?= $admit['slug'] ?></loc>
        <lastmod><?= date('Y-m-d', strtotime($admit['published_at'])) ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.7</priority>
    </url>
    <?php endforeach; ?>

    <!-- Syllabus -->
    <?php
    $syllabi = $syllabusModel->getAll(1000, 0);
    foreach ($syllabi as $syllabus):
    ?>
    <url>
        <loc><?= SITE_URL ?>/syllabus/<?= $syllabus['slug'] ?></loc>
        <lastmod><?= date('Y-m-d', strtotime($syllabus['published_at'])) ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.6</priority>
    </url>
    <?php endforeach; ?>

    <!-- Posts -->
    <?php
    $posts = $postModel->getAll(1000, 0);
    foreach ($posts as $post):
    ?>
    <url>
        <loc><?= SITE_URL ?>/post/<?= $post['slug'] ?></loc>
        <lastmod><?= date('Y-m-d', strtotime($post['updated_at'] ?: $post['published_at'])) ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.6</priority>
    </url>
    <?php endforeach; ?>
</urlset>
