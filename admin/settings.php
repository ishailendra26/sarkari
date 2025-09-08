<?php
require_once '../src/config.php';
require_once '../src/db.php';
require_once '../src/helpers.php';
require_once '../src/notifications.php';

requireLogin();

$db = getDB();
$success = '';
$error = '';

// Get current settings
$settings = [];
$stmt = $db->query("SELECT * FROM settings");
$settingsData = $stmt->fetchAll();
foreach ($settingsData as $setting) {
    $settings[$setting['k']] = $setting['v'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle OneSignal test notification without changing settings
    if (isset($_POST['onesignal_test'])) {
        $res = onesignal_send_notification('Test Notification', 'This is a test from Admin Settings.', SITE_URL, getSetting('default_post_thumbnail'));
        if ($res['success']) {
            $success = 'Test notification sent successfully. Check your subscribed browser/device.';
        } else {
            $msg = is_array($res['response']) ? json_encode($res['response']) : (string)$res['response'];
            $error = 'Failed to send test notification: ' . htmlspecialchars($msg);
        }
        // Refresh current settings view
        $settings = [];
        $stmt = $db->query("SELECT * FROM settings");
        $settingsData = $stmt->fetchAll();
        foreach ($settingsData as $setting) { 
            $settings[$setting['k']] = $setting['v']; 
        }
    } else {
        $site_name = sanitizeInput($_POST['site_name'] ?? '');
        $site_description = sanitizeInput($_POST['site_description'] ?? '');
        $contact_email = sanitizeInput($_POST['contact_email'] ?? '');
        $admin_email = sanitizeInput($_POST['admin_email'] ?? '');
        $items_per_page = (int)($_POST['items_per_page'] ?? 10);
        $maintenance_mode = isset($_POST['maintenance_mode']) ? 1 : 0;
        $meta_keywords = sanitizeInput($_POST['meta_keywords'] ?? '');
        $google_analytics = sanitizeInput($_POST['google_analytics'] ?? '');
        $default_post_thumbnail = sanitizeInput($_POST['default_post_thumbnail'] ?? '');
        $facebook_url = sanitizeInput($_POST['facebook_url'] ?? '');
        $twitter_url = sanitizeInput($_POST['twitter_url'] ?? '');
        $youtube_url = sanitizeInput($_POST['youtube_url'] ?? '');
        $telegram_url = sanitizeInput($_POST['telegram_url'] ?? '');
        
        // OneSignal fields
        $onesignal_enabled = isset($_POST['onesignal_enabled']) ? 1 : 0;
        $onesignal_app_id = sanitizeInput($_POST['onesignal_app_id'] ?? '');
        $onesignal_api_key = sanitizeInput($_POST['onesignal_api_key'] ?? '');
        $onesignal_safari_web_id = sanitizeInput($_POST['onesignal_safari_web_id'] ?? '');
        $onesignal_subdomain = sanitizeInput($_POST['onesignal_subdomain'] ?? '');

        if ($site_name && $site_description && $contact_email) {
            $settingsToUpdate = [
                // General
                'site_name' => $site_name,
                'site_description' => $site_description,
                'contact_email' => $contact_email,
                'admin_email' => $admin_email,
                'items_per_page' => $items_per_page,
                'maintenance_mode' => $maintenance_mode,
                // SEO
                'meta_keywords' => $meta_keywords,
                'google_analytics' => $google_analytics,
                // Legacy compatibility keys
                'analytics_code' => $google_analytics,
                // Media
                'default_post_thumbnail' => $default_post_thumbnail,
                // Social (new)
                'facebook_url' => $facebook_url,
                'twitter_url' => $twitter_url,
                'youtube_url' => $youtube_url,
                'telegram_url' => $telegram_url,
                // Social (legacy compatibility)
                'social_facebook' => $facebook_url,
                'social_twitter' => $twitter_url,
                'social_telegram' => $telegram_url,
                // OneSignal
                'onesignal_enabled' => $onesignal_enabled,
                'onesignal_app_id' => $onesignal_app_id,
                'onesignal_api_key' => $onesignal_api_key,
                'onesignal_safari_web_id' => $onesignal_safari_web_id,
                'onesignal_subdomain' => $onesignal_subdomain,
            ];
            
            foreach ($settingsToUpdate as $key => $value) {
                $stmt = $db->query("INSERT INTO settings (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = ?", 
                                  [$key, $value, $value]);
            }
            
            $success = 'Settings updated successfully!';
            
            // Refresh settings
            $settings = [];
            $stmt = $db->query("SELECT * FROM settings");
            $settingsData = $stmt->fetchAll();
            foreach ($settingsData as $setting) {
                $settings[$setting['k']] = $setting['v'];
            }
        } else {
            $error = 'Please fill all required fields';
        }
    }
}

$pageTitle = 'Settings';
include 'includes/header.php';
?>
        <div class="max-w-4xl mx-auto">
            <div class="bg-white rounded-lg shadow-md p-6 md:p-8">
                <?php if ($success): ?>
                <div class="alert alert-success mb-6">
                    <i class="fas fa-check-circle mr-2"></i><?= $success ?>
                </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                <div class="alert alert-error mb-6">
                    <i class="fas fa-exclamation-circle mr-2"></i><?= $error ?>
                </div>
                <?php endif; ?>

                <form method="POST" data-validate class="space-y-8">
                    <!-- General Settings -->
                    <div>
                        <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-cog text-primary mr-2"></i>General Settings
                        </h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="md:col-span-2">
                                <label class="form-label">Site Name *</label>
                                <input type="text" name="site_name" class="form-input" required 
                                       value="<?= htmlspecialchars($settings['site_name'] ?? 'SarkariJobs Portal') ?>"
                                       placeholder="SarkariJobs Portal">
                            </div>
                            
                            <div class="md:col-span-2">
                                <label class="form-label">Site Description *</label>
                                <textarea name="site_description" rows="3" class="form-input" required 
                                          placeholder="Brief description of your site..."><?= htmlspecialchars($settings['site_description'] ?? 'Latest Government Jobs, Results, Admit Cards and Syllabus') ?></textarea>
                            </div>
                            
                            <div>
                                <label class="form-label">Contact Email *</label>
                                <input type="email" name="contact_email" class="form-input" required 
                                       value="<?= htmlspecialchars($settings['contact_email'] ?? '') ?>"
                                       placeholder="contact@sarkari.com">
                            </div>
                            
                            <div>
                                <label class="form-label">Admin Email</label>
                                <input type="email" name="admin_email" class="form-input" 
                                       value="<?= htmlspecialchars($settings['admin_email'] ?? '') ?>"
                                       placeholder="admin@sarkari.com">
                            </div>
                            
                            <div>
                                <label class="form-label">Items Per Page</label>
                                <select name="items_per_page" class="form-input">
                                    <option value="10" <?= ($settings['items_per_page'] ?? 10) == 10 ? 'selected' : '' ?>>10</option>
                                    <option value="20" <?= ($settings['items_per_page'] ?? 10) == 20 ? 'selected' : '' ?>>20</option>
                                    <option value="50" <?= ($settings['items_per_page'] ?? 10) == 50 ? 'selected' : '' ?>>50</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="form-label">Maintenance Mode</label>
                                <div class="flex items-center mt-2">
                                    <input type="checkbox" name="maintenance_mode" id="maintenance_mode" 
                                           class="mr-2" <?= ($settings['maintenance_mode'] ?? 0) ? 'checked' : '' ?>>
                                    <label for="maintenance_mode" class="text-gray-700">Enable maintenance mode</label>
                                </div>
                                <small class="text-gray-500">Site will show maintenance page to visitors</small>
                            </div>
                        </div>
                    </div>

                    <!-- SEO Settings -->
                    <div class="border-t pt-8">
                        <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-search text-green-600 mr-2"></i>SEO Settings
                        </h2>
                        
                        <div class="grid grid-cols-1 gap-6">
                            <div>
                                <label class="form-label">Meta Keywords</label>
                                <input type="text" name="meta_keywords" class="form-input" 
                                       value="<?= htmlspecialchars($settings['meta_keywords'] ?? '') ?>"
                                       placeholder="sarkari jobs, government jobs, exam results">
                            </div>
                            
                            <div>
                                <label class="form-label">Google Analytics ID</label>
                                <input type="text" name="google_analytics" class="form-input" 
                                       value="<?= htmlspecialchars($settings['google_analytics'] ?? ($settings['analytics_code'] ?? '')) ?>"
                                       placeholder="G-XXXXXXXXXX">
                            </div>

                            <div class="md:col-span-2">
                                <label class="form-label">Default Post Thumbnail URL</label>
                                <input type="url" name="default_post_thumbnail" class="form-input"
                                       value="<?= htmlspecialchars($settings['default_post_thumbnail'] ?? '') ?>"
                                       placeholder="https://example.com/default-post-thumb.jpg">
                                <p class="text-xs text-gray-500 mt-1">Used when a post doesn't have its own thumbnail.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Social Media -->
                    <div class="border-t pt-8">
                        <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-share-alt text-blue-600 mr-2"></i>Social Media
                        </h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="form-label">Facebook URL</label>
                                <input type="url" name="facebook_url" class="form-input" 
                                       value="<?= htmlspecialchars($settings['facebook_url'] ?? ($settings['social_facebook'] ?? '')) ?>"
                                       placeholder="https://facebook.com/yourpage">
                            </div>
                            
                            <div>
                                <label class="form-label">Twitter URL</label>
                                <input type="url" name="twitter_url" class="form-input" 
                                       value="<?= htmlspecialchars($settings['twitter_url'] ?? ($settings['social_twitter'] ?? '')) ?>"
                                       placeholder="https://twitter.com/yourhandle">
                            </div>
                            
                            <div>
                                <label class="form-label">YouTube URL</label>
                                <input type="url" name="youtube_url" class="form-input" 
                                       value="<?= htmlspecialchars($settings['youtube_url'] ?? '') ?>"
                                       placeholder="https://youtube.com/yourchannel">
                            </div>
                            
                            <div>
                                <label class="form-label">Telegram URL</label>
                                <input type="url" name="telegram_url" class="form-input" 
                                       value="<?= htmlspecialchars($settings['telegram_url'] ?? ($settings['social_telegram'] ?? '')) ?>"
                                       placeholder="https://t.me/yourchannel">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Notifications (OneSignal) -->
                    <div class="border-t pt-8">
                        <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-bell text-red-600 mr-2"></i>Push Notifications (OneSignal)
                        </h2>
                        <p class="text-sm text-gray-600 mb-4">Enable web push notifications. Create a OneSignal app, then paste your App ID and REST API Key here. Ensure your site runs on HTTPS in production.</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="md:col-span-2 flex items-center">
                                <input type="checkbox" id="onesignal_enabled" name="onesignal_enabled" class="mr-2" <?= ((int)($settings['onesignal_enabled'] ?? 0) === 1) ? 'checked' : '' ?>>
                                <label for="onesignal_enabled" class="text-gray-700">Enable OneSignal</label>
                            </div>
                            <div>
                                <label class="form-label">OneSignal App ID</label>
                                <input type="text" name="onesignal_app_id" class="form-input" value="<?= htmlspecialchars($settings['onesignal_app_id'] ?? '') ?>" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx">
                            </div>
                            <div>
                                <label class="form-label">OneSignal REST API Key</label>
                                <input type="password" name="onesignal_api_key" class="form-input" value="<?= htmlspecialchars($settings['onesignal_api_key'] ?? '') ?>" placeholder="REST API Key">
                            </div>
                            <div>
                                <label class="form-label">Safari Web ID (optional)</label>
                                <input type="text" name="onesignal_safari_web_id" class="form-input" value="<?= htmlspecialchars($settings['onesignal_safari_web_id'] ?? '') ?>" placeholder="web.onesignal.auto.xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx">
                            </div>
                            <div>
                                <label class="form-label">Subdomain (optional)</label>
                                <input type="text" name="onesignal_subdomain" class="form-input" value="<?= htmlspecialchars($settings['onesignal_subdomain'] ?? '') ?>" placeholder="your-subdomain (for onesignal.com prompt)">
                            </div>
                            <div class="md:col-span-2 flex items-center gap-3">
                                <button type="submit" name="onesignal_test" value="1" class="btn btn-secondary">
                                    <i class="fas fa-paper-plane mr-2"></i>Send Test Notification
                                </button>
                                <small class="text-gray-500">Subscribe on the public site first using the bell icon, then click to send a test.</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex gap-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-2"></i>Save Settings
                        </button>
                        
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-times mr-2"></i>Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

<?php include 'includes/footer.php'; ?>
