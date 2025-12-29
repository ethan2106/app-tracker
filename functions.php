<?php
// functions.php - Database and logic functions
// Load .env if exists
if (file_exists(__DIR__ . '/.env')) {
    $env = parse_ini_file(__DIR__ . '/.env');
    foreach ($env as $key => $value) {
        putenv("$key=$value");
    }
}

global $pdo;

function csrf_token() {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return '';
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    $token = csrf_token();
    if ($token === '') {
        return '';
    }
    return '<input type="hidden" name="csrf_token" value="' .
        htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

function verify_csrf() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return true;
    }
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return false;
    }
    $token = $_POST['csrf_token'] ?? '';
    $stored = $_SESSION['csrf_token'] ?? '';
    return $token && $stored && hash_equals($stored, $token);
}

// Helper function for HTTP GET with JSON
function http_get_json($url, $headers = [], $timeout = 10) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_USERAGENT, 'App-Tracker/1.0');
    // Add GitHub token if available
    $github_token = getenv('GITHUB_TOKEN');
    if ($github_token && strpos($url, 'api.github.com') !== false) {
        $headers[] = 'Authorization: Bearer ' . $github_token;
    }
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
    curl_setopt($ch, CURLOPT_HEADER, true); // Include headers in response
    
    $response = curl_exec($ch);
    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);
        error_log("cURL failed for $url: $error");
        return null;
    }
    
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $error = curl_error($ch);
    
    $body = substr($response, $header_size);
    $response_headers = substr($response, 0, $header_size);
    
    if ($error || $http_code !== 200) {
        $log_msg = "HTTP GET failed for $url: HTTP $http_code, Error: $error";
        if ($http_code == 403) {
            $log_msg .= ", Headers: " . $response_headers;
        }
        error_log($log_msg);
        return null;
    }
    
    return json_decode($body, true);
}

// Helper function for HTTP GET returning raw text
function http_get_text($url, $headers = [], $timeout = 10) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_USERAGENT, 'App-Tracker/1.0');
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
    
    $response = curl_exec($ch);
    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);
        error_log("cURL failed for $url: $error");
        return null;
    }
    
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    if ($error || $http_code !== 200) {
        error_log("HTTP GET failed for $url: HTTP $http_code, Error: $error");
        return null;
    }
    
    return $response;
}

// Function to normalize version string
function normalize_version($version) {
    if (!$version) return null;
    // Remove 'v' or 'V' prefix
    $version = ltrim($version, 'vV');
    // Remove 'Python ' prefix if present
    $version = str_replace('Python ', '', $version);
    // Remove leading/trailing whitespace
    $version = trim($version);
    // Normalize version numbers: remove leading zeros in components (e.g., 25.01 -> 25.1)
    $version = preg_replace_callback('/(\d+)\.(\d+)/', function($matches) {
        return $matches[1] . '.' . ltrim($matches[2], '0');
    }, $version);
    // Extract X.Y.Z pattern, preferring semantic versions
    if (preg_match('/(\d+(?:\.\d+)+)/', $version, $matches)) {
        return $matches[1];
    }
    return $version;
}

// Function to check if update is available
function isUpdateAvailable($installed, $latest) {
    if (!$installed || !$latest) return null;
    $installed_norm = normalize_version($installed);
    $latest_norm = normalize_version($latest);
    if (!$installed_norm || !$latest_norm) {
        return null;
    }
    return version_compare($installed_norm, $latest_norm, '<');
}

// Function to validate update_source
function is_valid_update_source($source) {
    if (!$source) {
        return false;
    }
    // Check for GitHub repo: owner/repo
    if (preg_match('/^[A-Za-z0-9_.-]+\/[A-Za-z0-9_.-]+$/i', $source)) {
        return true;
    }
    return getUpdateProvider($source) !== null;
}

function normalize_update_source($source) {
    if ($source === null) {
        return null;
    }
    $source = trim($source);
    if ($source === '') {
        return null;
    }
    $source_lower = strtolower($source);
    if (preg_match('~github\.com/([A-Za-z0-9_.-]+)/([A-Za-z0-9_.-]+)~i', $source, $matches)) {
        return strtolower($matches[1] . '/' . $matches[2]);
    }
    $alias_provider = getUpdateProviderAlias($source_lower);
    if ($alias_provider) {
        return $alias_provider;
    }
    return $source_lower;
}

function getLatestVersionCustom($url, $regex) {
    if (!$url || !$regex) {
        return null;
    }
    $text = http_get_text($url, [], 15);
    if (!$text) {
        return null;
    }
    $matched = @preg_match($regex, $text, $matches);
    if ($matched === 1) {
        if (isset($matches[1])) {
            return $matches[1];
        }
        if (isset($matches[0])) {
            return $matches[0];
        }
    }
    return null;
}

function getUpdateProvider($provider_key) {
    global $pdo;
    static $cache = [];
    if (!$provider_key) {
        return null;
    }
    $provider_key = strtolower(trim($provider_key));
    if (array_key_exists($provider_key, $cache)) {
        return $cache[$provider_key];
    }
    $stmt = $pdo->prepare("SELECT * FROM update_providers WHERE provider_key = ?");
    $stmt->execute([$provider_key]);
    $provider = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    $cache[$provider_key] = $provider;
    return $provider;
}

function getUpdateProviderAlias($alias) {
    global $pdo;
    static $cache = [];
    if (!$alias) {
        return null;
    }
    $alias = strtolower(trim($alias));
    if (array_key_exists($alias, $cache)) {
        return $cache[$alias];
    }
    $stmt = $pdo->prepare("SELECT provider_key FROM update_provider_aliases WHERE alias = ?");
    $stmt->execute([$alias]);
    $provider_key = $stmt->fetchColumn();
    $provider_key = $provider_key ? strtolower($provider_key) : null;
    $cache[$alias] = $provider_key;
    return $provider_key;
}

function getLatestVersionFromProvider($provider, $text = null) {
    if (!$provider || empty($provider['latest_regex'])) {
        return null;
    }
    if ($text === null) {
        $text = getProviderPageText($provider);
    }
    if (!$text) {
        return null;
    }
    $matched = @preg_match($provider['latest_regex'], $text, $matches);
    if ($matched === 1) {
        if (isset($matches[1])) {
            return $matches[1];
        }
        if (isset($matches[0])) {
            return $matches[0];
        }
    }
    return null;
}

function getLatestDownloadUrlFromProvider($provider, $text = null) {
    if (!$provider) {
        return null;
    }
    if ($text === null) {
        $text = getProviderPageText($provider);
    }
    if (!empty($provider['download_regex'])) {
        if (!$text) {
            return $provider['download_url'] ?? null;
        }
        $matched = @preg_match($provider['download_regex'], $text, $matches);
        if ($matched === 1) {
            if (isset($matches[1])) {
                return $matches[1];
            }
            if (isset($matches[0])) {
                return $matches[0];
            }
        }
    }
    return $provider['download_url'] ?? null;
}

function getProviderPageText($provider) {
    if (!$provider || empty($provider['latest_url'])) {
        return null;
    }
    return http_get_text($provider['latest_url'], [], 15);
}

function getLatestGithubDownloadUrl($source) {
    list($owner, $repo) = explode('/', $source, 2);
    $fallback = "https://github.com/$owner/$repo/releases/latest";
    $data = http_get_json("https://api.github.com/repos/$owner/$repo/releases/latest", ['Accept: application/vnd.github.v3+json']);
    if (!$data) {
        return $fallback;
    }
    $assets = $data['assets'] ?? [];
    $preferred = ['msi', 'exe', 'zip'];
    foreach ($preferred as $ext) {
        foreach ($assets as $asset) {
            $name = strtolower($asset['name'] ?? '');
            $url = $asset['browser_download_url'] ?? null;
            if (!$url || !$name) {
                continue;
            }
            if (preg_match('/\.(sig|sha256|sha256sum|asc|txt)$/i', $name)) {
                continue;
            }
            if (preg_match('/\.' . preg_quote($ext, '/') . '$/i', $name)) {
                return $url;
            }
        }
    }
    if (!empty($data['html_url'])) {
        return $data['html_url'];
    }
    return $fallback;
}

// Function to get download URL for an app source
function getDownloadUrl($source) {
    $source = normalize_update_source($source);
    if (!$source) {
        return null;
    }
    if (strpos($source, '/') !== false) {
        return getLatestGithubDownloadUrl($source);
    }
    $provider = getUpdateProvider($source);
    return $provider['download_url'] ?? null;
}

function getDownloadUrlForApp($app) {
    if (!empty($app['latest_download_url'])) {
        return $app['latest_download_url'];
    }
    if (!empty($app['update_url'])) {
        return $app['update_url'];
    }
    return getDownloadUrl($app['update_source']);
}

// Function to get all apps
function getApps() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM apps ORDER BY name");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get apps with errors
function getAppsWithErrors() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM apps WHERE last_error IS NOT NULL ORDER BY last_checked DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get a single app by ID
function getApp($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM apps WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Function to add an app
function addApp($name, $version, $update_source = null, $update_type = null, $update_url = null, $update_regex = null) {
    global $pdo;
    $update_source = normalize_update_source($update_source);
    $stmt = $pdo->prepare("INSERT INTO apps (name, version, update_source, update_type, update_url, update_regex) VALUES (?, ?, ?, ?, ?, ?)");
    return $stmt->execute([$name, $version, $update_source, $update_type, $update_url, $update_regex]);
}

// Function to update an app
function updateApp($id, $name, $version, $update_source = null, $update_type = null, $update_url = null, $update_regex = null) {
    global $pdo;
    $update_source = normalize_update_source($update_source);
    $stmt = $pdo->prepare("UPDATE apps SET name = ?, version = ?, update_source = ?, update_type = ?, update_url = ?, update_regex = ? WHERE id = ?");
    return $stmt->execute([$name, $version, $update_source, $update_type, $update_url, $update_regex, $id]);
}

// Function to delete an app
function deleteApp($id) {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM apps WHERE id = ?");
    return $stmt->execute([$id]);
}

// Function to get latest version from source
function getLatestVersion($source) {
    $source = normalize_update_source($source);
    if (!is_valid_update_source($source)) {
        error_log("Invalid update source: " . ($source ?? 'null'));
        return null;
    }
    
    if (strpos($source, '/') !== false) {
        // GitHub repo: owner/repo
        list($owner, $repo) = explode('/', $source, 2);
        $url = "https://api.github.com/repos/$owner/$repo/releases/latest";
        $data = http_get_json($url, ['Accept: application/vnd.github.v3+json']);
        if ($data && isset($data['tag_name'])) {
            return $data['tag_name'];
        }
        // Fallback to tags if no releases
        $url = "https://api.github.com/repos/$owner/$repo/tags";
        $data = http_get_json($url, ['Accept: application/vnd.github.v3+json']);
        if ($data && isset($data[0]['name'])) {
            return $data[0]['name'];
        }
        return null;
    }
    $provider = getUpdateProvider($source);
    $provider_text = getProviderPageText($provider);
    return getLatestVersionFromProvider($provider, $provider_text);
}

// Function to check for updates
function checkForUpdates($id, $force = false) {
    global $pdo;
    $app = getApp($id);
    $is_custom = $app && (($app['update_type'] ?? null) === 'custom' || (!empty($app['update_url']) && !empty($app['update_regex'])));
    if (!$app || (!$is_custom && !$app['update_source'])) {
        return "Source de mise à jour non configurée pour cette app.";
    }
    
    // Cache: if checked less than 10 minutes ago, skip (unless force)
    if (!$force && $app['last_checked']) {
        $last_checked = strtotime($app['last_checked']);
        if (time() - $last_checked < 600) { // 10 minutes
            return "Vérification récente, ignorée.";
        }
    }
    
    $source = normalize_update_source($app['update_source'] ?? null);
    $latest_raw = $is_custom
        ? getLatestVersionCustom($app['update_url'], $app['update_regex'])
        : getLatestVersion($source);
    if ($latest_raw) {
        $latest_norm = normalize_version($latest_raw);
        $update_check = isUpdateAvailable($app['version'], $latest_raw);
        $update_available = is_bool($update_check) ? ($update_check ? 1 : 0) : null;
        $latest_download_url = null;
        if ($is_custom && !empty($app['update_url'])) {
            $latest_download_url = $app['update_url'];
        } elseif (strpos($source, '/') !== false) {
            $latest_download_url = getLatestGithubDownloadUrl($source);
        } else {
            $provider = getUpdateProvider($source);
            $provider_text = getProviderPageText($provider);
            $latest_download_url = getLatestDownloadUrlFromProvider($provider, $provider_text);
        }
        $stmt = $pdo->prepare("UPDATE apps SET latest_version = ?, latest_version_norm = ?, update_available = ?, latest_download_url = ?, last_checked = NOW(), last_error = NULL WHERE id = ?");
        $stmt->execute([$latest_raw, $latest_norm, $update_available, $latest_download_url, $id]);
        $status = $update_available === 1 ? "MAJ disponible" : ($update_available === 0 ? "À jour" : "Inconnu");
        return "Dernière version trouvée : $latest_raw ($status)";
    } else {
        $error_msg = "Impossible de récupérer la dernière version.";
        $stmt = $pdo->prepare("UPDATE apps SET last_error = ?, last_checked = NOW() WHERE id = ?");
        $stmt->execute([$error_msg, $id]);
        return $error_msg;
    }
}
?>
