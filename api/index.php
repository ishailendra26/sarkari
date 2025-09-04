<?php
// API Front Controller for Sarkari Jobs Portal
// Versioned, read-only endpoints under /api/v1

require_once __DIR__ . '/../src/api_helpers.php';
require_once __DIR__ . '/../src/db.php';

api_handle_options_preflight();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = $_SERVER['REQUEST_URI'] ?? '/api';
$path = parse_url($uri, PHP_URL_PATH);

// Normalize path relative to /api
// Expected patterns: /api/v1/jobs, /api/v1/jobs/{slug}, ...
$segments = array_values(array_filter(explode('/', $path)));

// Find index of 'api' segment to support deployments under different base paths
$apiIndex = array_search('api', $segments, true);
if ($apiIndex === false) {
    api_error('not_found', 'Invalid API base path', 404);
}

// Support both /api/v1/... and /api/index.php/v1/...
$next = $segments[$apiIndex + 1] ?? null;
$offset = 1;
if ($next === 'index.php') {
    $offset = 2;
}
$version = $segments[$apiIndex + $offset] ?? null;
$resource = $segments[$apiIndex + $offset + 1] ?? null;
$idOrSlug = $segments[$apiIndex + $offset + 2] ?? null;

if ($version !== 'v1') {
    api_error('unsupported_version', 'This API only supports v1', 404);
}

if ($method !== 'GET') {
    api_error('method_not_allowed', 'Only GET requests are allowed for this API', 405, [
        'allow' => 'GET, OPTIONS'
    ]);
}

// Utilities
function category_id_by_slug(?string $slug): ?int {
    $slug = api_str($slug, 100);
    if (!$slug) return null;
    $db = getDB();
    $row = $db->fetchOne('SELECT id FROM categories WHERE slug = ?', [$slug]);
    return $row && isset($row['id']) ? (int)$row['id'] : null;
}

// Router
switch ($resource) {
    case 'jobs':
        require_once __DIR__ . '/../src/models/Job.php';
        $model = new Job();
        if ($idOrSlug) {
            // Detail endpoint: /api/v1/jobs/{slug}
            $row = $model->getBySlug($idOrSlug);
            if (!$row) {
                api_error('not_found', 'Job not found', 404);
            }
            api_json_response($row);
        } else {
            // List/Search endpoint: /api/v1/jobs
            [$page, $perPage, $offset] = api_pagination();
            $q = api_str($_GET['q'] ?? null, 200);
            $category = api_str($_GET['category'] ?? null, 100);

            if ($q !== null && mb_strlen($q) > 0 && mb_strlen($q) < 2) {
                api_error('validation_error', 'Query parameter q must be at least 2 characters', 422);
            }

            if ($q) {
                // Search mode
                if ($category) {
                    $categoryId = category_id_by_slug($category);
                    if ($categoryId === null) {
                        // No such category; return empty set
                        $payload = api_wrap_list([], $page, $perPage, 0);
                        api_json_response($payload);
                    }
                    $items = $model->searchByCategory($q, $categoryId, $perPage, $offset);
                    $total = $model->countSearchByCategory($q, $categoryId);
                } else {
                    $items = $model->search($q, $perPage, $offset);
                    $total = $model->countSearch($q);
                }
            } else {
                // Browsing mode
                $items = $model->getAll($perPage, $offset, $category);
                $total = $model->getCount($category);
            }

            $payload = api_wrap_list($items, $page, $perPage, $total);
            api_json_response($payload);
        }
        break;
    case 'results':
        require_once __DIR__ . '/../src/models/Result.php';
        $model = new Result();
        if ($idOrSlug) {
            $row = $model->getBySlug($idOrSlug);
            if (!$row) {
                api_error('not_found', 'Result not found', 404);
            }
            api_json_response($row);
        } else {
            [$page, $perPage, $offset] = api_pagination();
            $q = api_str($_GET['q'] ?? null, 200);

            if ($q !== null && mb_strlen($q) > 0 && mb_strlen($q) < 2) {
                api_error('validation_error', 'Query parameter q must be at least 2 characters', 422);
            }

            if ($q) {
                $items = $model->search($q, $perPage, $offset);
                $total = $model->countSearch($q);
            } else {
                $items = $model->getAll($perPage, $offset);
                $total = $model->getCount();
            }

            $payload = api_wrap_list($items, $page, $perPage, $total);
            api_json_response($payload);
        }
        break;
    case 'admit-cards':
        require_once __DIR__ . '/../src/models/AdmitCard.php';
        $model = new AdmitCard();
        if ($idOrSlug) {
            $row = $model->getBySlug($idOrSlug);
            if (!$row) {
                api_error('not_found', 'Admit Card not found', 404);
            }
            api_json_response($row);
        } else {
            [$page, $perPage, $offset] = api_pagination();
            $q = api_str($_GET['q'] ?? null, 200);

            if ($q !== null && mb_strlen($q) > 0 && mb_strlen($q) < 2) {
                api_error('validation_error', 'Query parameter q must be at least 2 characters', 422);
            }

            if ($q) {
                $items = $model->search($q, $perPage, $offset);
                $total = $model->countSearch($q);
            } else {
                $items = $model->getAll($perPage, $offset);
                $total = $model->getCount();
            }

            $payload = api_wrap_list($items, $page, $perPage, $total);
            api_json_response($payload);
        }
        break;
    case 'syllabi':
        require_once __DIR__ . '/../src/models/Syllabus.php';
        $model = new Syllabus();
        if ($idOrSlug) {
            $row = $model->getBySlug($idOrSlug);
            if (!$row) {
                api_error('not_found', 'Syllabus not found', 404);
            }
            api_json_response($row);
        } else {
            [$page, $perPage, $offset] = api_pagination();
            $q = api_str($_GET['q'] ?? null, 200);

            if ($q !== null && mb_strlen($q) > 0 && mb_strlen($q) < 2) {
                api_error('validation_error', 'Query parameter q must be at least 2 characters', 422);
            }

            if ($q) {
                $items = $model->search($q, $perPage, $offset);
                $total = $model->countSearch($q);
            } else {
                $items = $model->getAll($perPage, $offset);
                $total = $model->getCount();
            }

            $payload = api_wrap_list($items, $page, $perPage, $total);
            api_json_response($payload);
        }
        break;
    case 'posts':
        require_once __DIR__ . '/../src/models/Post.php';
        $model = new Post();
        if ($idOrSlug) {
            $row = $model->getBySlug($idOrSlug);
            if (!$row) {
                api_error('not_found', 'Post not found', 404);
            }
            api_json_response($row);
        } else {
            [$page, $perPage, $offset] = api_pagination();
            $q = api_str($_GET['q'] ?? null, 200);
            $category = api_str($_GET['category'] ?? null, 100);

            if ($q !== null && mb_strlen($q) > 0 && mb_strlen($q) < 2) {
                api_error('validation_error', 'Query parameter q must be at least 2 characters', 422);
            }

            if ($q) {
                // Note: category is ignored in search mode for posts
                $items = $model->search($q, $perPage, $offset);
                $total = $model->countSearch($q);
            } else {
                $items = $model->getAll($perPage, $offset, $category);
                $total = $model->getCount($category);
            }

            $payload = api_wrap_list($items, $page, $perPage, $total);
            api_json_response($payload);
        }
        break;

    default:
        api_error('not_found', 'Resource not found', 404);
}
