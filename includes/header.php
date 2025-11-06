<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? generateMetaTitle($pageTitle) : generateMetaTitle('Latest Government Jobs, Results & Admit Cards') ?></title>
    <meta name="description" content="<?= isset($metaDescription) ? $metaDescription : 'Find latest Sarkari jobs, exam results, admit cards and syllabus. Stay updated with government job notifications and exam updates.' ?>">
    <?php if (isset($metaKeywords)): ?>
    <meta name="keywords" content="<?= $metaKeywords ?>">
    <?php endif; ?>
    <!-- Performance: Preconnects to critical third-party origins -->
    <link rel="preconnect" href="https://cdn.tailwindcss.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="preconnect" href="https://cdn.onesignal.com" crossorigin>
    <link rel="preconnect" href="https://pagead2.googlesyndication.com" crossorigin>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= SITE_URL ?>/assets/images/Examszfevicon.png">
    <link rel="apple-touch-icon" href="<?= SITE_URL ?>/assets/images/Examszfevicon.png">
    
    <!-- Sitemap discovery -->
    <link rel="sitemap" type="application/xml" title="Sitemap" href="<?= SITE_URL ?>/sitemap.xml.php">
    
    <!-- Tailwind CSS: prefer local build if available to avoid runtime cost -->
    <?php if (file_exists(__DIR__ . '/../assets/css/tailwind.min.css')): ?>
      <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/tailwind.min.css">
    <?php else: ?>
      <script src="https://cdn.tailwindcss.com?plugins=typography"></script>
      <script>
          tailwind.config = {
              theme: {
                  extend: {
                      colors: {
                          primary: '#1e40af',
                          secondary: '#dc2626',
                          accent: '#059669'
                      }
                  }
              }
          }
      </script>
    <?php endif; ?>
    
    <!-- Font Awesome (non-blocking load) -->
    <link rel="preload" as="style" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"></noscript>
    
    <!-- Custom CSS (use non-minified to avoid syntax issue, size is small) -->
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
    
    <?php
    // OneSignal SDK (render only when enabled and configured) — defer init further on mobile
    if (function_exists('getSetting') && (int)getSetting('onesignal_enabled', 0) === 1) {
        $osAppId = getSetting('onesignal_app_id');
        if (!empty($osAppId)) {
            $osSafariId = getSetting('onesignal_safari_web_id');
            $osSubdomain = getSetting('onesignal_subdomain');

            // Compute correct scope if the app is hosted in a subdirectory (e.g., /sarkari/)
            $parsedPath = rtrim(parse_url(SITE_URL, PHP_URL_PATH) ?: '/', '/');
            $scopePath = $parsedPath === '' ? '/' : ($parsedPath . '/');
            $swPath = $scopePath . 'OneSignalSDKWorker.js';
            $swUpdaterPath = $scopePath . 'OneSignalSDKUpdaterWorker.js';
            ?>
            <script>
                (function(){
                  function isMobile(){
                    return window.matchMedia && window.matchMedia('(max-width: 767px)').matches;
                  }
                  function onFirstInteraction(cb){
                    var done=false;
                    function wrap(){ if(done) return; done=true; cb(); document.removeEventListener('touchstart',wrap,{passive:true}); document.removeEventListener('scroll',wrap); document.removeEventListener('click',wrap); }
                    document.addEventListener('touchstart',wrap,{passive:true, once:true});
                    document.addEventListener('scroll',wrap,{once:true});
                    document.addEventListener('click',wrap,{once:true});
                  }
                  function loadOS(){
                    var s=document.createElement('script');
                    s.src='https://cdn.onesignal.com/sdks/OneSignalSDK.js';
                    s.async=true;
                    s.onload=function(){
                      window.OneSignalDeferred = window.OneSignalDeferred || [];
                      OneSignalDeferred.push(function(OneSignal){
                        OneSignal.init({
                          appId: "<?= htmlspecialchars($osAppId) ?>",
                          <?php if (!empty($osSafariId)): ?>
                          safari_web_id: "<?= htmlspecialchars($osSafariId) ?>",
                          <?php endif; ?>
                          notifyButton: { enable: true },
                          serviceWorkerParam: { scope: "<?= $scopePath ?>" },
                          serviceWorkerPath: "<?= $swPath ?>",
                          serviceWorkerUpdaterPath: "<?= $swUpdaterPath ?>",
                          <?php if (!empty($osSubdomain)): ?>
                          subdomainName: "<?= htmlspecialchars($osSubdomain) ?>",
                          <?php endif; ?>
                        });
                        if (OneSignal && OneSignal.User && OneSignal.User.PushSubscription) {
                          OneSignal.User.PushSubscription.getOptedIn().then(function(optedIn){
                            if (!optedIn && OneSignal.Slidedown && OneSignal.Slidedown.promptPush) {
                              OneSignal.Slidedown.promptPush();
                            }
                          });
                        }
                      });
                    };
                    document.head.appendChild(s);
                  }
                  // On mobile, wait for first interaction or longer idle
                  if (isMobile()) {
                    if ('requestIdleCallback' in window) {
                      requestIdleCallback(function(){ setTimeout(loadOS, 4000); }, { timeout: 6000 });
                    }
                    onFirstInteraction(loadOS);
                    setTimeout(loadOS, 10000); // ultimate fallback
                  } else {
                    if ('requestIdleCallback' in window) {
                      requestIdleCallback(loadOS, { timeout: 4000 });
                    } else {
                      setTimeout(loadOS, 3000);
                    }
                  }
                })();
            </script>
            <?php
        }
    }
    ?>
    
    <?php if (isset($additionalHead)): ?>
    <?= $additionalHead ?>
    <?php endif; ?>
    
    <!-- WebSite JSON-LD with SearchAction -->
    <?php 
      $siteUrl = rtrim(SITE_URL, '/');
      $websiteSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => 'Examsz',
        'url' => $siteUrl . '/',
        'potentialAction' => [
          '@type' => 'SearchAction',
          'target' => $siteUrl . '/search.php?q={search_term_string}',
          'query-input' => 'required name=search_term_string'
        ]
      ];
    ?>
    <script type="application/ld+json">
      <?= json_encode($websiteSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?>
    </script>
    <!-- Defer loading AdSense further on mobile to reduce TBT -->
    <script>
      (function(){
        function isMobile(){ return window.matchMedia && window.matchMedia('(max-width: 767px)').matches; }
        function onFirstInteraction(cb){ var f=false; function w(){ if(f) return; f=true; cb(); document.removeEventListener('touchstart',w,{passive:true}); document.removeEventListener('scroll',w); document.removeEventListener('click',w);} document.addEventListener('touchstart',w,{passive:true, once:true}); document.addEventListener('scroll',w,{once:true}); document.addEventListener('click',w,{once:true}); }
        function loadAds(){
          var s=document.createElement('script');
          s.async=true;
          s.src='https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-3803763924146690';
          s.crossOrigin='anonymous';
          document.head.appendChild(s);
        }
        if (isMobile()) {
          if ('requestIdleCallback' in window) {
            requestIdleCallback(function(){ setTimeout(loadAds, 5000); }, { timeout: 7000 });
          }
          onFirstInteraction(loadAds);
          setTimeout(loadAds, 12000);
        } else {
          if ('requestIdleCallback' in window) {
            requestIdleCallback(loadAds, { timeout: 4000 });
          } else {
            setTimeout(loadAds, 3000);
          }
        }
      })();
    </script>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-md sticky top-0 z-50">
        <nav class="container mx-auto px-4 py-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <a href="<?= SITE_URL ?>" aria-label="SarkariJobs Home" class="flex items-center group">
                        <img src="<?= SITE_URL ?>/assets/images/ExamszBlack.svg" alt="SarkariJobs" class="h-9 md:h-10 w-auto drop-shadow-sm transition-transform duration-200 group-hover:scale-[1.02]" decoding="async" fetchpriority="high">
                        <!-- Mobile-only concise tagline -->
                        <div class="ml-2 leading-tight sm:hidden">
                            <div class="text-[10px] text-gray-600">
                                Jobs Notifications | <b>Mock Tests</b>
                            </div>
                        </div>
                        <!-- Desktop/tablet tagline -->
                        <div class="ml-3 leading-tight hidden sm:block">
                            <div class="text-[11px] md:text-sm text-gray-600">
                                <span class="font-medium text-gray-800">Sarkari Jobs Notifications</span>
                                <span class="mx-1 text-gray-300">|</span>
                                <span class="font-medium text-blue-800">Mock Tests</span>
                            </div>
                        </div>
                    </a>
                </div>
                
                <!-- Mobile menu button -->
                <button id="mobile-menu-btn" class="md:hidden text-gray-600 hover:text-primary" aria-controls="mobile-menu" aria-expanded="false" aria-label="Toggle navigation">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                
                <!-- Desktop Navigation -->
                <div class="hidden md:flex items-center space-x-6">
                    <a href="<?= SITE_URL ?>" class="text-gray-700 hover:text-primary transition-colors <?= (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'text-primary font-semibold' : '' ?>">Home</a>
                    <a href="<?= SITE_URL ?>/jobs" class="text-gray-700 hover:text-primary transition-colors <?= (basename($_SERVER['PHP_SELF']) == 'jobs.php') ? 'text-primary font-semibold' : '' ?>">Jobs</a>
                    <a href="<?= SITE_URL ?>/results" class="text-gray-700 hover:text-primary transition-colors <?= (basename($_SERVER['PHP_SELF']) == 'results.php') ? 'text-primary font-semibold' : '' ?>">Results</a>
                    <a href="<?= SITE_URL ?>/admit" class="text-gray-700 hover:text-primary transition-colors <?= (basename($_SERVER['PHP_SELF']) == 'admit.php') ? 'text-primary font-semibold' : '' ?>">Admit Cards</a>
                    <a href="<?= SITE_URL ?>/syllabus" class="text-gray-700 hover:text-primary transition-colors <?= (basename($_SERVER['PHP_SELF']) == 'syllabus.php') ? 'text-primary font-semibold' : '' ?>">Syllabus</a>
                    <a href="<?= SITE_URL ?>/posts" class="text-gray-700 hover:text-primary transition-colors <?= (basename($_SERVER['PHP_SELF']) == 'posts.php') ? 'text-primary font-semibold' : '' ?>">Articles</a>
                    <a href="<?= SITE_URL ?>/tools.php" class="text-gray-700 hover:text-primary transition-colors <?= (basename($_SERVER['PHP_SELF']) == 'tools.php') ? 'text-primary font-semibold' : '' ?>">Tools</a>
                    <a href="https://mock.examsz.in" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-accent hover:text-green-700 font-semibold"><i class="fas fa-clipboard-check"></i> Mock Tests</a>

                </div>
            </div>
            
            <!-- Mobile Navigation -->
            <div id="mobile-menu" class="hidden md:hidden mt-4 pb-4 border-t border-gray-200">
                <div class="flex flex-col space-y-3 pt-4">
                    <a href="<?= SITE_URL ?>" class="text-gray-700 hover:text-primary transition-colors <?= (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'text-primary font-semibold' : '' ?>">Home</a>
                    <a href="<?= SITE_URL ?>/jobs" class="text-gray-700 hover:text-primary transition-colors <?= (basename($_SERVER['PHP_SELF']) == 'jobs.php') ? 'text-primary font-semibold' : '' ?>">Jobs</a>
                    <a href="<?= SITE_URL ?>/results" class="text-gray-700 hover:text-primary transition-colors <?= (basename($_SERVER['PHP_SELF']) == 'results.php') ? 'text-primary font-semibold' : '' ?>">Results</a>
                    <a href="<?= SITE_URL ?>/admit" class="text-gray-700 hover:text-primary transition-colors <?= (basename($_SERVER['PHP_SELF']) == 'admit.php') ? 'text-primary font-semibold' : '' ?>">Admit Cards</a>
                    <a href="<?= SITE_URL ?>/syllabus" class="text-gray-700 hover:text-primary transition-colors <?= (basename($_SERVER['PHP_SELF']) == 'syllabus.php') ? 'text-primary font-semibold' : '' ?>">Syllabus</a>
                    <a href="<?= SITE_URL ?>/posts" class="text-gray-700 hover:text-primary transition-colors <?= (basename($_SERVER['PHP_SELF']) == 'posts.php') ? 'text-primary font-semibold' : '' ?>">Articles</a>
                    <a href="<?= SITE_URL ?>/tools.php" class="text-gray-700 hover:text-primary transition-colors <?= (basename($_SERVER['PHP_SELF']) == 'tools.php') ? 'text-primary font-semibold' : '' ?>">Tools</a>
                    <a href="https://mock.examsz.in" target="_blank" rel="noopener" class="text-accent hover:text-green-700 font-semibold">Mock Tests</a>

                </div>
            </div>
        </nav>
    </header>
    <?php 
        if (function_exists('inferPageScopeFromRequest')) {
            [$scope, $slug] = inferPageScopeFromRequest();
            echo renderAd('header_banner', $scope, $slug, 1);
        }
    ?>
