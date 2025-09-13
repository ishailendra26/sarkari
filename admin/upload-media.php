<?php
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/helpers.php';
requireLogin();
$pageTitle = 'Upload Media';
include __DIR__ . '/includes/header.php';
?>

<?php
  // Prepare recent uploads list
  $uploadsDir = realpath(__DIR__ . '/../uploads');
  $uploadsUrl = rtrim(SITE_URL ?? '', '/') . '/uploads';
  $mediaFiles = [];

  if ($uploadsDir && is_dir($uploadsDir)) {
    // Get all non-hidden files in uploads directory
    $all = glob($uploadsDir . '/*');
    if ($all) {
      // Map files to metadata and sort by modified time (desc)
      foreach ($all as $absPath) {
        if (!is_file($absPath)) continue;
        $basename = basename($absPath);
        // Skip dotfiles just in case
        if ($basename[0] === '.') continue;
        $ext = strtolower(pathinfo($basename, PATHINFO_EXTENSION));
        $mediaFiles[] = [
          'name' => $basename,
          'mtime' => filemtime($absPath) ?: 0,
          'size' => filesize($absPath) ?: 0,
          'ext' => $ext,
        ];
      }
      usort($mediaFiles, function($a, $b){ return $b['mtime'] <=> $a['mtime']; });
      // Limit to most recent 60 for performance
      $mediaFiles = array_slice($mediaFiles, 0, 60);
    }
  }
?>

<div class="container mx-auto px-4 py-8">
  <div class="bg-white shadow rounded p-6">
    <h1 class="text-2xl font-semibold mb-4">Upload Media</h1>
    <p class="text-gray-600 mb-6">Upload images or documents and get a public URL you can paste anywhere in the admin.</p>

    <div class="space-y-4">
      <div>
        <label class="form-label">Choose File</label>
        <input type="file" id="media_file" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" class="form-input p-1" />
        <p class="text-sm text-gray-500 mt-1">Allowed: jpg, jpeg, png, pdf, doc, docx. Max 5MB.</p>
      </div>

      <div class="flex items-center gap-3">
        <button type="button" id="media_upload_btn" class="btn btn-primary">
          <i class="fas fa-upload mr-2"></i>Upload
        </button>
        <span id="media_status" class="text-sm text-gray-500"></span>
      </div>

      <div id="media_result" class="hidden">
        <label class="form-label">File URL</label>
        <div class="flex gap-3 items-center">
          <input type="url" id="media_url" class="form-input" readonly />
          <button type="button" id="copy_btn" class="btn btn-secondary"><i class="fas fa-copy mr-2"></i>Copy</button>
        </div>
        <div id="media_preview_wrap" class="mt-3 hidden">
          <img id="media_preview" src="" alt="Preview" class="h-32 rounded border object-cover" />
        </div>
      </div>
    </div>
  </div>
</div>

<div class="container mx-auto px-4 pb-8">
  <div class="bg-white shadow rounded p-6">
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-xl font-semibold">Recent Uploads</h2>
      <span class="text-sm text-gray-500"><?php echo count($mediaFiles); ?> items</span>
    </div>

    <?php if (!empty($mediaFiles)) : ?>
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
        <?php foreach ($mediaFiles as $file):
          $url = $uploadsUrl . '/' . rawurlencode($file['name']);
          $isImage = in_array($file['ext'], ['jpg','jpeg','png']);
          $sizeKB = $file['size'] ? ceil($file['size'] / 1024) : 0;
        ?>
          <div class="border rounded p-3 flex flex-col gap-2">
            <div class="w-full aspect-[4/3] bg-gray-50 border rounded flex items-center justify-center overflow-hidden">
              <?php if ($isImage): ?>
                <img src="<?php echo htmlspecialchars($url); ?>" alt="<?php echo htmlspecialchars($file['name']); ?>" class="object-cover w-full h-full" loading="lazy" />
              <?php else: ?>
                <div class="text-center p-4">
                  <div class="text-4xl">📄</div>
                  <div class="text-xs text-gray-500 mt-1 uppercase">.<?php echo htmlspecialchars($file['ext']); ?></div>
                </div>
              <?php endif; ?>
            </div>
            <div class="min-h-[2.5rem]">
              <a href="<?php echo htmlspecialchars($url); ?>" target="_blank" class="text-sm font-medium text-blue-600 hover:underline break-all"><?php echo htmlspecialchars($file['name']); ?></a>
            </div>
            <div class="text-xs text-gray-500">Size: <?php echo $sizeKB; ?> KB</div>
            <div class="flex gap-2 mt-auto">
              <a href="<?php echo htmlspecialchars($url); ?>" target="_blank" class="btn btn-secondary btn-sm">Open</a>
              <button type="button" class="btn btn-primary btn-sm copy-media-url" data-url="<?php echo htmlspecialchars($url); ?>">Copy URL</button>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="text-gray-500">No uploads found yet. Once you upload files, they will appear here.</p>
    <?php endif; ?>
  </div>
  <p class="text-xs text-gray-400 mt-2">Showing up to the most recent 60 files from <code>/uploads/</code>.</p>
</div>

<script>
(function(){
  const fileInput = document.getElementById('media_file');
  const btn = document.getElementById('media_upload_btn');
  const statusEl = document.getElementById('media_status');
  const resultWrap = document.getElementById('media_result');
  const urlInput = document.getElementById('media_url');
  const copyBtn = document.getElementById('copy_btn');
  const prevWrap = document.getElementById('media_preview_wrap');
  const prevImg = document.getElementById('media_preview');

  if (!btn) return;

  btn.addEventListener('click', async function(){
    if (!fileInput.files || !fileInput.files[0]) { statusEl.textContent = 'Choose a file first'; return; }
    statusEl.textContent = 'Uploading...';
    resultWrap.classList.add('hidden');
    prevWrap.classList.add('hidden');

    const fd = new FormData();
    fd.append('file', fileInput.files[0]);
    try {
      const res = await fetch('upload.php', { method: 'POST', body: fd, credentials: 'same-origin' });
      const data = await res.json();
      if (data && data.success && data.url) {
        statusEl.textContent = 'Uploaded';
        urlInput.value = data.url;
        resultWrap.classList.remove('hidden');
        const ext = ((data.filename || '').split('.').pop() || '').toLowerCase();
        if (['jpg','jpeg','png'].includes(ext)) {
          prevImg.src = data.url;
          prevWrap.classList.remove('hidden');
        }
      } else {
        statusEl.textContent = (data && data.error) ? data.error : 'Upload failed';
      }
    } catch (e) {
      statusEl.textContent = 'Upload error';
    }
  });

  copyBtn.addEventListener('click', function() {
    if (!urlInput.value) return;
    urlInput.select();
    urlInput.setSelectionRange(0, 99999);
    try {
      document.execCommand('copy');
      statusEl.textContent = 'URL copied!';
    } catch (e) {
      navigator.clipboard.writeText(urlInput.value).then(() => {
        statusEl.textContent = 'URL copied!';
      }).catch(() => {
        statusEl.textContent = 'Copy failed';
      });
    }
  });

  // Handle copy buttons in recent uploads grid (event delegation)
  document.addEventListener('click', function(e) {
    const target = e.target;
    if (target && target.classList && target.classList.contains('copy-media-url')) {
      const url = target.getAttribute('data-url');
      if (!url) return;
      // Try modern clipboard API first
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(url).then(() => {
          statusEl.textContent = 'URL copied!';
        }).catch(() => {
          statusEl.textContent = 'Copy failed';
        });
      } else {
        // Fallback using temporary input
        const tmp = document.createElement('input');
        tmp.value = url;
        document.body.appendChild(tmp);
        tmp.select();
        tmp.setSelectionRange(0, 99999);
        try {
          document.execCommand('copy');
          statusEl.textContent = 'URL copied!';
        } catch (err) {
          statusEl.textContent = 'Copy failed';
        }
        document.body.removeChild(tmp);
      }
    }
  });

})();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
