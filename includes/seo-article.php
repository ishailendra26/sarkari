<?php
// Reusable SEO-friendly article block. Include this file and set $seoPageType to one of:
// 'jobs', 'posts', 'admit', 'syllabus'
// Optionally set $seoCustomTitle to override the H2 title.
if (!defined('SITE_URL')) {
    // Basic guard in case someone includes this directly
    require_once __DIR__ . '/../src/config.php';
}

$imgUrl = SITE_URL . '/assets/images/examszbanner.svg';
$type = isset($seoPageType) ? strtolower(trim($seoPageType)) : 'generic';
$titleMap = [
    'jobs' => 'Sarkari Exams & Sarkari Result 2025 – Your Complete Guide to Government Jobs in India',
    'posts' => 'Expert Insights and Updates by Examsz – Your Exam Success Partner',
    'results' => 'Latest Government Exam Results by Examsz – Your Exam Success Partner',
    'admit' => 'Admit Cards and Exam Readiness with Examsz – Your Exam Success Partner',
    'syllabus' => 'Syllabus Guidance and Study Plans by Examsz – Your Exam Success Partner',
    'generic' => 'Examsz – Your Exam Success Partner'
];

$h2 = isset($seoCustomTitle) && $seoCustomTitle ? $seoCustomTitle : ($titleMap[$type] ?? $titleMap['generic']);

// Reusable helpers for CTAs and shared sections
if (!function_exists('seo_get_intro_by_type')) {
    function seo_get_intro_by_type(string $type): string {
        switch ($type) {
            case 'jobs':
                return 'Get real-time updates on Sarkari Exams, Sarkari Result 2025, admit cards, and government job notifications—curated for faster decisions.';
            case 'results':
                return 'Find the latest Sarkari Results, merit lists, and cut-offs with official links and next-step guidance.';
            case 'admit':
                return 'Download the latest admit cards and hall tickets from official sources with essential exam-day instructions.';
            case 'syllabus':
                return 'Plan smarter with structured, official exam syllabuses and focused study guidance.';
            default:
                return 'Trusted resources, timely updates, and exam-focused guidance to help you succeed.';
        }
    }
}

if (!function_exists('seo_get_cta_for_type')) {
    function seo_get_cta_for_type(string $type): array {
        $base = rtrim(SITE_URL, '/');
        switch ($type) {
            case 'jobs':
                return ['label' => 'Browse Latest Jobs', 'href' => $base . '/jobs.php'];
            case 'results':
                return ['label' => 'View Latest Results', 'href' => $base . '/results.php'];
            case 'admit':
                return ['label' => 'Get Admit Cards', 'href' => $base . '/admit.php'];
            case 'syllabus':
                return ['label' => 'View Syllabus', 'href' => $base . '/syllabus.php'];
            default:
                return ['label' => 'Explore Updates', 'href' => $base . '/'];
        }
    }
}

if (!function_exists('seo_render_how_to_stay_updated')) {
    function seo_render_how_to_stay_updated(): void { ?>
      <h4>How to Stay Updated with Sarkari Exams & Results</h4>
      <ul>
        <li>Follow <a href="<?= rtrim(SITE_URL, '/') ?>/">Examsz.in</a> daily for authentic job and exam updates.</li>
        <li>Subscribe to alerts via email or your preferred notification channels.</li>
        <li>Always read the official eligibility and last-date details before applying.</li>
        <li>Download admit cards only from official sources linked on our <a href="<?= rtrim(SITE_URL, '/') ?>/admit.php">Admit Card page</a>.</li>
        <li>Track cut-off trends to refine your preparation strategy.</li>
      </ul>
    <?php }
}

if (!function_exists('seo_render_tips_to_crack')) {
    function seo_render_tips_to_crack(): void { ?>
      <h4>Tips to Crack Sarkari Exams</h4>
      <ul>
        <li><strong>Plan Smartly</strong> – Break down the syllabus into manageable parts.</li>
        <li><strong>Focus on Current Affairs</strong> – Especially for UPSC, SSC, and Banking exams.</li>
        <li><strong>Practice Daily</strong> – Mock tests and previous papers improve speed and accuracy.</li>
        <li><strong>Time Management</strong> – Balance strong and weak subjects during study and in the exam.</li>
        <li><strong>Stay Consistent</strong> – Regular study always beats last-minute cramming.</li>
      </ul>
    <?php }
}

// Social links helper (uses constants if available; fallbacks are placeholders to be updated)
if (!function_exists('seo_get_social_links')) {
    function seo_get_social_links(): array {
        $base = rtrim(SITE_URL, '/');
        $wa = defined('WHATSAPP_CHANNEL_URL') && WHATSAPP_CHANNEL_URL ? WHATSAPP_CHANNEL_URL : 'https://whatsapp.com/channel/0029VbB1u0GDeONDSSn6jC1a';
        $tg = defined('TELEGRAM_CHANNEL_URL') && TELEGRAM_CHANNEL_URL ? TELEGRAM_CHANNEL_URL : 'https://t.me/examszin';
        // Add basic UTM tagging
        $utm = 'utm_source=site&utm_medium=sticky_widget&utm_campaign=social_follow';
        $wa .= ((strpos($wa, '?') !== false) ? '&' : '?') . $utm;
        $tg .= ((strpos($tg, '?') !== false) ? '&' : '?') . $utm;
        return [
            'whatsapp' => $wa,
            'telegram' => $tg,
        ];
    }
}

?>
<section class="py-10 bg-white">
  <div class="container mx-auto px-4">
    <article class="bg-white rounded-xl shadow-md p-6 md:p-8">
      <div class="mb-6">
        <div class="w-full aspect-[16/9] bg-gray-100 rounded-xl overflow-hidden">
          <img src="<?= htmlspecialchars($imgUrl) ?>" alt="Examsz - Your Exam Success Partner" class="w-full h-full object-cover" loading="lazy">
        </div>
      </div>
      <div class="mb-6">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-800">
          <?= htmlspecialchars($h2) ?>
        </h2>
        <p class="text-gray-600 mt-1"><?= htmlspecialchars(seo_get_intro_by_type($type)) ?></p>
        <?php $cta = seo_get_cta_for_type($type); ?>
        <div class="mt-4">
          <a href="<?= htmlspecialchars($cta['href']) ?>" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded-lg shadow focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-1">
            <?= htmlspecialchars($cta['label']) ?>
          </a>
        </div>
      </div>

      <div class="prose max-w-none">
        <?php if ($type === 'jobs'): ?>
          <h3>Sarkari Exams & Sarkari Result 2025 – Your Complete Guide to Government Jobs in India</h3>
          <p>
            Government jobs in India—commonly known as <strong>Sarkari Naukri</strong>—are a dream for millions. They offer stability, respect, and long-term benefits that private jobs often can’t match. To achieve this goal, aspirants must stay updated with the latest <strong>Sarkari Exams</strong> notifications, <strong>Sarkari Result 2025</strong> updates, admit cards, cut-off marks, and final merit lists.
          </p>
          <p>
            If you’re searching for a trusted source to stay updated, <a href="<?= rtrim(SITE_URL, '/') ?>/" rel="noopener">Examsz.in</a> is one of the fastest-growing job and exam portals in India. It provides real-time notifications on government job vacancies, admit cards, results, and exam schedules—ensuring you never miss an important update.
          </p>

          <h4>What are Sarkari Exams?</h4>
          <p>
            Sarkari Exams are competitive entrance and recruitment tests conducted by central and state-level authorities to hire candidates for government departments. These exams test knowledge, aptitude, and skills required for prestigious roles in administration, finance, railways, education, and more.
          </p>
          <p><strong>Some of the top Sarkari Exams include:</strong></p>
          <ul>
            <li><strong>UPSC Civil Services Exams</strong> – For IAS, IPS, IFS, and other elite services.</li>
            <li><strong>SSC Exams</strong> – Including CGL, CHSL, and GD Constable.</li>
            <li><strong>Banking Exams</strong> – IBPS PO, Clerk, SBI PO, and RBI Grade B.</li>
            <li><strong>Railway Exams</strong> – RRB NTPC, ALP, and Group D.</li>
            <li><strong>Teaching Exams</strong> – CTET, UPTET, and state-level eligibility tests.</li>
            <li><strong>State PSC Exams</strong> – For state administrative and police services.</li>
          </ul>
          <p>
            By visiting <a href="<?= rtrim(SITE_URL, '/') ?>/" rel="noopener">Examsz.in</a>, you can access official notifications, detailed eligibility criteria, and application guidelines for all these exams in one place. Explore quick links to <a href="<?= rtrim(SITE_URL, '/') ?>/jobs.php">Latest Jobs</a>, <a href="<?= rtrim(SITE_URL, '/') ?>/admit.php">Admit Cards</a>, <a href="<?= rtrim(SITE_URL, '/') ?>/results.php">Results</a>, and <a href="<?= rtrim(SITE_URL, '/') ?>/syllabus.php">Syllabus</a>.
          </p>

          <h4>Why Sarkari Result Updates Are Crucial</h4>
          <p>
            After writing a government exam, every aspirant eagerly awaits the <strong>Sarkari Result</strong>. This result decides whether they progress to interviews, document verification, or final selection. Trusted portals like Examsz.in provide timely updates on:
          </p>
          <ul>
            <li>Written exam results and merit lists</li>
            <li>Category-wise cut-off marks</li>
            <li>Interview schedules and admit cards</li>
            <li>Final selection lists</li>
          </ul>
          <p>
            Having a reliable website bookmarked saves time and ensures you don’t miss crucial updates. Check the <a href="<?= rtrim(SITE_URL, '/') ?>/results.php">Results section</a> daily for the latest postings.
          </p>

          <h4>Benefits of Sarkari Naukri</h4>
          <ul>
            <li><strong>High Job Security</strong> – Stable career with minimal risk of sudden layoffs.</li>
            <li><strong>Competitive Salary & Perks</strong> – Pay scales with DA, HRA, and other allowances.</li>
            <li><strong>Post-Retirement Benefits</strong> – Pension schemes and gratuity ensure lifelong financial security.</li>
            <li><strong>Balanced Lifestyle</strong> – Fixed working hours with paid leaves and holidays.</li>
            <li><strong>Respect & Recognition</strong> – Government employees hold a respected place in society.</li>
          </ul>

          <h4>How to Stay Updated with Sarkari Exams & Results</h4>
          <ul>
            <li>Follow <a href="<?= rtrim(SITE_URL, '/') ?>/">Examsz.in</a> daily for authentic job and exam updates.</li>
            <li>Subscribe to alerts via email or your preferred notification channels.</li>
            <li>Always read the official eligibility and last-date details before applying.</li>
            <li>Download admit cards only from official sources linked on our <a href="<?= rtrim(SITE_URL, '/') ?>/admit.php">Admit Card page</a>.</li>
            <li>Track cut-off trends to refine your preparation strategy.</li>
          </ul>

          <h4>Tips to Crack Sarkari Exams</h4>
          <ul>
            <li><strong>Plan Smartly</strong> – Break down the syllabus into manageable parts.</li>
            <li><strong>Focus on Current Affairs</strong> – Especially for UPSC, SSC, and Banking exams.</li>
            <li><strong>Practice Daily</strong> – Mock tests and previous papers improve speed and accuracy.</li>
            <li><strong>Time Management</strong> – Balance strong and weak subjects during study and in the exam.</li>
            <li><strong>Stay Consistent</strong> – Regular study always beats last-minute cramming.</li>
          </ul>

          <h4>Conclusion</h4>
          <p>
            Sarkari Exams and Sarkari Results are not just about tests—they represent aspirations, hard work, and the future of millions of Indians. With the right preparation, dedication, and access to authentic information, your dream of a <strong>Sarkari Naukri</strong> is within reach.
          </p>
          <p>
            For the latest job alerts, exam notifications, admit cards, and <strong>Sarkari Result 2025</strong>, visit <a href="<?= rtrim(SITE_URL, '/') ?>/">Examsz.in</a>. Stay informed, stay ahead, and take your first step toward a successful government career today!
          </p>
        <?php elseif ($type === 'posts'): ?>
          <h3>Preparation Guides & Updates for Sarkari Exams</h3>
          <p>
            Make your preparation smarter with practical explainers, strategy guides, and timely updates—curated by <strong>Examsz – Your Exam Success Partner</strong>. We simplify complex notifications and turn them into clear action items you can use today.
          </p>
          <h4>What You’ll Find</h4>
          <ul>
            <li>Exam strategy by stage: Foundation, Revision, and Final Mock phases</li>
            <li>Topic-wise breakdowns with previous-year trend highlights</li>
            <li>Form-filling tips, eligibility clarifications, and error-avoidance checklists</li>
            <li>Revision frameworks and exam-day checklists</li>
          </ul>
          <h4>Quick Links</h4>
          <ul>
            <li><a href="<?= rtrim(SITE_URL, '/') ?>/syllabus.php">Official Syllabus Hub</a> – Build your plan the right way</li>
            <li><a href="<?= rtrim(SITE_URL, '/') ?>/jobs.php">Latest Jobs</a> – Align preparation with current openings</li>
            <li><a href="<?= rtrim(SITE_URL, '/') ?>/admit.php">Admit Cards</a> – Don’t miss hall-ticket releases</li>
            <li><a href="<?= rtrim(SITE_URL, '/') ?>/results.php">Results</a> – Track cut-offs and plan your next move</li>
          </ul>
          <p>
            Save this page and revisit weekly for fresh insights and revision prompts. Small, consistent improvements lead to big results in <strong>Sarkari Exams</strong>.
          </p>
          <?php // Shared guidance sections for Posts
            seo_render_how_to_stay_updated();
            seo_render_tips_to_crack();
          ?>
        <?php elseif ($type === 'results'): ?>
          <h3>Sarkari Result 2025 – Latest Merit Lists, Scorecards, and Next Steps</h3>
          <p>
            The <strong>Sarkari Result</strong> is the turning point of your exam journey. On this page, you’ll find the latest government exam results, merit lists, scorecards, and cut-off updates—curated by <strong>Examsz – Your Exam Success Partner</strong>. We link you directly to official portals and summarize what matters: selection status, category-wise cut-offs, normalization where applicable, and what to do next.
          </p>
          <h4>What You’ll Get Here</h4>
          <ul>
            <li>Direct official links to download results and scorecards</li>
            <li>Category-wise cut-off marks and normalization info</li>
            <li>Phase-wise results: Prelims, Mains, Skill/Physical, Final merit</li>
            <li>Next steps: Document verification, medicals, and joining formalities</li>
          </ul>
          <h4>How to Check Your Sarkari Result Faster</h4>
          <ul>
            <li>Keep your Application/Roll Number and Date of Birth ready</li>
            <li>Use the official link from our <a href="<?= rtrim(SITE_URL, '/') ?>/results.php">Results page</a></li>
            <li>Download and save a PDF copy for your records</li>
            <li>Compare your score with previous cut-offs to plan ahead</li>
          </ul>
          <p>
            Didn’t get the expected outcome? Don’t lose momentum. Re-align using our <a href="<?= rtrim(SITE_URL, '/') ?>/syllabus.php">Syllabus</a> and <a href="<?= rtrim(SITE_URL, '/') ?>/posts.php">Prep Guides</a>, and explore fresh opportunities on the <a href="<?= rtrim(SITE_URL, '/') ?>/jobs.php">Latest Jobs</a> page.
          </p>
          <?php // Shared guidance sections for Results
            seo_render_how_to_stay_updated();
            seo_render_tips_to_crack();
          ?>
        <?php elseif ($type === 'admit'): ?>
          <h3>Admit Card Updates – Download Hall Tickets for Sarkari Exams</h3>
          <p>
            Never miss your <strong>Sarkari Exam</strong> hall ticket. This page lists the latest admit cards with direct official links, release dates, and important exam-day instructions—curated by <strong>Examsz – Your Exam Success Partner</strong>.
          </p>
          <h4>Before You Download</h4>
          <ul>
            <li>Keep Registration/Application No. and Date of Birth ready</li>
            <li>Verify your details: name, photo, signature, and exam city</li>
            <li>Read permitted/blocked items for the exam hall carefully</li>
            <li>Print 2 copies and plan your route to the venue a day early</li>
          </ul>
          <h4>What Admit Cards Usually Mention</h4>
          <ul>
            <li>Exam date, reporting time, venue address, and shift</li>
            <li>Documents required for entry (Photo ID, photographs, etc.)</li>
            <li>COVID/discipline guidelines (if applicable)</li>
          </ul>
          <p>
            If any discrepancy is found, contact the official helpline immediately as per the notification. Continue your revision with focused <a href="<?= rtrim(SITE_URL, '/') ?>/syllabus.php">Syllabus</a> and practical tips from <a href="<?= rtrim(SITE_URL, '/') ?>/posts.php">Prep Guides</a>.
          </p>
        <?php elseif ($type === 'syllabus'): ?>
          <h3>Exam Syllabus – Plan Your Sarkari Exams Preparation the Smart Way</h3>
          <p>
            Every topper starts with the syllabus. Here you’ll find structured, official <strong>Sarkari Exams</strong> syllabuses for SSC, Banking, Railways, State Services, Teaching, Defense, and more—curated by <strong>Examsz – Your Exam Success Partner</strong>.
          </p>
          <h4>How to Use the Syllabus Effectively</h4>
          <ul>
            <li>Break down subjects into topics/subtopics and set weekly goals</li>
            <li>Prioritize high-weightage areas based on previous-year papers</li>
            <li>Follow a 3-phase flow: Learn → Practice → Perform</li>
            <li>Keep an error log to track weak areas and revise deliberately</li>
          </ul>
          <h4>Suggested 3-Phase Flow</h4>
          <ul>
            <li><strong>Learn</strong>: Concept notes, minimal resources, formula sheets</li>
            <li><strong>Practice</strong>: Topic-wise questions and mixed sets</li>
            <li><strong>Perform</strong>: Full-length mocks and detailed analysis</li>
          </ul>
          <p>
            Revisit this page frequently for updates from official authorities. Then move to <a href="<?= rtrim(SITE_URL, '/') ?>/posts.php">Prep Guides</a> for techniques and to <a href="<?= rtrim(SITE_URL, '/') ?>/jobs.php">Latest Jobs</a> to align your preparation with current opportunities.
          </p>
          <?php // Shared guidance sections for Syllabus
            seo_render_how_to_stay_updated();
            seo_render_tips_to_crack();
          ?>
        <?php else: ?>
          <p>
            Examsz – Your Exam Success Partner is committed to empowering aspirants with authentic updates, structured resources, and practical guidance. Explore jobs, admit cards, results, posts, and syllabuses across categories to plan your journey with confidence.
          </p>
        <?php endif; ?>
      </div>
    </article>
    <?php
      // JSON-LD Schema: WebPage/CollectionPage + Organization + BreadcrumbList
      $currentUrl = rtrim(SITE_URL, '/') . ($_SERVER['REQUEST_URI'] ?? '/');
      $webPageType = in_array($type, ['jobs','posts','admit','syllabus'], true) ? 'CollectionPage' : 'WebPage';
      $sectionNameMap = [
        'jobs' => 'Government Jobs',
        'posts' => 'Articles & Updates',
        'results' => 'Exam Results',
        'admit' => 'Admit Cards',
        'syllabus' => 'Exam Syllabus',
        'generic' => 'Exams'
      ];
      $descMap = [
        'jobs' => 'Sarkari Exams and Sarkari Result 2025 guide with the latest government job notifications, vacancies, last dates, eligibility, admit cards, results, and syllabus—curated by Examsz – Your Exam Success Partner.',
        'posts' => 'In-depth exam articles, updates, and preparation strategies for Sarkari Exams. Brought to you by Examsz – Your Exam Success Partner.',
        'results' => 'Sarkari Result 2025: latest government exam results, scorecards, merit lists, and cut-offs with official links and next-step guidance. From Examsz – Your Exam Success Partner.',
        'admit' => 'Sarkari Exams admit cards and hall tickets with official download links, release dates, and exam-day instructions. From Examsz – Your Exam Success Partner.',
        'syllabus' => 'Official Sarkari Exams syllabus references and smart study planning guidance for major government exams. Curated by Examsz – Your Exam Success Partner.',
        'generic' => 'Examsz – Your Exam Success Partner provides reliable exam information and preparation guidance.'
      ];

      $section = $sectionNameMap[$type] ?? $sectionNameMap['generic'];
      $description = $descMap[$type] ?? $descMap['generic'];

      $organization = [
        '@type' => 'Organization',
        'name' => 'Examsz - Your Exam Success Partner',
        'url' => rtrim(SITE_URL, '/'),
        'logo' => $imgUrl
      ];

      $breadcrumb = [
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
          [
            '@type' => 'ListItem',
            'position' => 1,
            'name' => 'Home',
            'item' => rtrim(SITE_URL, '/') . '/'
          ],
          [
            '@type' => 'ListItem',
            'position' => 2,
            'name' => $section,
            'item' => $currentUrl
          ]
        ]
      ];

      $pageSchema = [
        '@context' => 'https://schema.org',
        '@type' => $webPageType,
        'name' => $h2,
        'url' => $currentUrl,
        'description' => $description,
        'image' => $imgUrl,
        'isPartOf' => [
          '@type' => 'WebSite',
          'name' => 'Examsz',
          'url' => rtrim(SITE_URL, '/') . '/'
        ],
        'publisher' => $organization,
        'about' => [
          '@type' => 'Thing',
          'name' => 'Government Exams and Jobs'
        ]
      ];
    ?>
    <script type="application/ld+json">
      <?= json_encode([$pageSchema, $breadcrumb], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?>
    </script>
  </div>
</section>

<?php // Sticky Social Connect Widget for jobs, results, admit, syllabus, posts
  if (in_array($type, ['jobs','results','admit','syllabus','posts'], true)):
    $social = seo_get_social_links();
?>
  <div id="exz-social-widget" class="fixed bottom-4 right-4 z-50">
    <div class="bg-white/95 backdrop-blur rounded-2xl shadow-xl border border-gray-200/70 w-72 max-w-[90vw] overflow-hidden transition-all" style="will-change: transform, opacity;">
      <div class="flex items-center justify-between px-4 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white">
        <div class="flex items-center gap-2">
          <img src="<?= htmlspecialchars($imgUrl) ?>" alt="Examsz" class="w-6 h-6 rounded" loading="lazy">
          <span class="font-semibold">Stay Updated</span>
        </div>
        <button id="exz-widget-toggle" type="button" class="text-white/90 hover:text-white transition" aria-label="Minimize widget">
          <svg id="exz-icon-minus" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" /></svg>
          <svg id="exz-icon-plus" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
        </button>
      </div>
      <div id="exz-widget-body" class="p-4 space-y-3">
        <p class="text-sm text-gray-700">Get instant alerts on jobs, results, admit cards and more. Join our channels:</p>
        <div class="flex gap-3">
          <a href="<?= htmlspecialchars($social['whatsapp']) ?>" target="_blank" rel="noopener nofollow" class="flex-1 inline-flex items-center justify-center gap-2 bg-green-500 hover:bg-green-600 text-white font-medium px-3 py-2 rounded-lg shadow focus:outline-none focus:ring-2 focus:ring-green-300">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5"><path d="M12 2C6.486 2 2 6.33 2 11.667c0 1.83.52 3.552 1.423 5.04L2 22l5.468-1.396A10.14 10.14 0 0 0 12 21.333C17.514 21.333 22 17.003 22 11.667 22 6.33 17.514 2 12 2Zm5.678 14.28c-.236.665-1.374 1.24-1.9 1.292-.485.047-1.095.067-1.769-.108-.408-.106-.931-.303-1.61-.593-2.832-1.233-4.669-4.11-4.811-4.3-.142-.19-1.15-1.53-1.15-2.92 0-1.39.73-2.07.988-2.35.258-.28.565-.35.754-.35.189 0 .377.002.543.01.175.007.41-.066.643.49.236.568.805 1.963.876 2.104.071.142.118.307.022.497-.094.19-.142.307-.283.472-.142.165-.298.37-.426.498-.142.141-.29.294-.125.576.165.283.734 1.205 1.577 1.95 1.085.948 2.005 1.242 2.288 1.382.283.142.449.118.614-.047.165-.165.707-.824.897-1.107.189-.283.377-.236.629-.142.26.094 1.642.774 1.92.915.283.142.472.212.543.33.071.118.071.685-.165 1.35Z"/></svg>
            WhatsApp
          </a>
          <a href="<?= htmlspecialchars($social['telegram']) ?>" target="_blank" rel="noopener nofollow" class="flex-1 inline-flex items-center justify-center gap-2 bg-sky-500 hover:bg-sky-600 text-white font-medium px-3 py-2 rounded-lg shadow focus:outline-none focus:ring-2 focus:ring-sky-300">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5"><path d="M9.036 15.47 8.87 19.6c.332 0 .476-.143.648-.314l3.107-2.985 4.285 3.146c.786.435 1.349.207 1.566-.729l2.837-12.67c.29-1.215-.439-1.69-1.2-1.394L3.51 9.8c-1.17.455-1.153 1.109-.199 1.402l4.55 1.376 10.574-6.664c.498-.302.952-.135.579.167L9.036 15.47Z"/></svg>
            Telegram
          </a>
        </div>
        <p class="text-[11px] text-gray-500">We send only important updates. You can mute or leave anytime.</p>
      </div>
    </div>
  </div>
  <script>
    (function(){
      try {
        var widget = document.getElementById('exz-social-widget');
        if (!widget) return;
        var body = document.getElementById('exz-widget-body');
        var toggle = document.getElementById('exz-widget-toggle');
        var iconMinus = document.getElementById('exz-icon-minus');
        var iconPlus = document.getElementById('exz-icon-plus');
        var key = 'exzSocialWidgetMinimized';
        var minimized = localStorage.getItem(key) === '1';
        var setState = function(min){
          if (min){
            body.style.display = 'none';
            iconMinus.classList.add('hidden');
            iconPlus.classList.remove('hidden');
          } else {
            body.style.display = '';
            iconMinus.classList.remove('hidden');
            iconPlus.classList.add('hidden');
          }
        };
        setState(minimized);
        toggle.addEventListener('click', function(){
          minimized = !minimized;
          localStorage.setItem(key, minimized ? '1' : '0');
          setState(minimized);
        });
      } catch(e) { /* no-op */ }
    })();
  </script>
<?php endif; ?>
