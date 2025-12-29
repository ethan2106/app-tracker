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
    $allowed = ['mozilla', 'nodejs', 'python', '7zip', 'vlc', 'git'];
    if (in_array($source, $allowed)) {
        return true;
    }
    // Check for GitHub repo: owner/repo
    if (preg_match('/^[A-Za-z0-9_.-]+\/[A-Za-z0-9_.-]+$/', $source)) {
        return true;
    }
    return false;
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

// Function to get download URL for an app source
function getDownloadUrl($source) {
    if (strpos($source, '/') !== false) {
        // GitHub repo: owner/repo
        list($owner, $repo) = explode('/', $source, 2);
        return "https://github.com/$owner/$repo/releases/latest";
    } else {
        switch ($source) {
            case 'mozilla':
                return 'https://www.mozilla.org/firefox/download/';
            case 'nodejs':
                return 'https://nodejs.org/';
            case 'python':
                return 'https://www.python.org/downloads/';
            case '7zip':
                return 'https://www.7-zip.org/download.html';
            case 'vlc':
                return 'https://www.videolan.org/vlc/download-windows.html';
            case 'git':
                return 'https://gitforwindows.org/';
            default:
                return null;
        }
    }
}

function getDownloadUrlForApp($app) {
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
    $stmt = $pdo->prepare("INSERT INTO apps (name, version, update_source, update_type, update_url, update_regex) VALUES (?, ?, ?, ?, ?, ?)");
    return $stmt->execute([$name, $version, $update_source, $update_type, $update_url, $update_regex]);
}

// Function to update an app
function updateApp($id, $name, $version, $update_source = null, $update_type = null, $update_url = null, $update_regex = null) {
    global $pdo;
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
    if (!is_valid_update_source($source)) {
        error_log("Invalid update source: $source");
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
    } else {
        switch ($source) {
            case 'mozilla':
                $data = http_get_json('https://product-details.mozilla.org/1.0/firefox_versions.json');
                if ($data && isset($data['LATEST_FIREFOX_VERSION'])) {
                    return $data['LATEST_FIREFOX_VERSION'];
                }
                break;
            case 'nodejs':
                $data = http_get_json('https://nodejs.org/dist/index.json');
                if ($data) {
                    $lts_versions = array_filter($data, function($v) {
                        return isset($v['lts']) && $v['lts'] !== false;
                    });
                    if (!empty($lts_versions)) {
                        // Get latest LTS by date
                        usort($lts_versions, function($a, $b) {
                            return strtotime($b['date']) - strtotime($a['date']);
                        });
                        return $lts_versions[0]['version'];
                    }
                }
                break;
            case 'python':
                $data = http_get_json('https://www.python.org/api/v2/downloads/release/?is_published=yes&is_prerelease=false');
                if ($data) {
                    $latest = null;
                    $latest_date = 0;
                    foreach ($data as $release) {
                        $name = $release['name'];
                        if (preg_match('/[abc]\d*$/', $name)) continue; // Skip pre-releases
                        $release_date = strtotime($release['release_date']);
                        if ($release_date > $latest_date) {
                            $latest = $name;
                            $latest_date = $release_date;
                        }
                    }
                    return $latest;
                }
                break;
            case '7zip':
                $html = file_get_contents('https://www.7-zip.org/', false, stream_context_create(['http' => ['timeout' => 10, 'user_agent' => 'App-Tracker/1.0']]));
                if ($html && preg_match('/Download 7-Zip (\d+\.\d+)/', $html, $matches)) {
                    return $matches[1];
                }
                break;
            case 'vlc':
                // Scrape download page first (most reliable for stable versions)
                $html = http_get_text('https://www.videolan.org/vlc/download-windows.html', [], 15);
                if ($html && preg_match('/vlc-([\d\.]+)-win32\.exe/', $html, $matches)) {
                    return $matches[1];
                }
                // Fallback to GitHub releases
                $data = http_get_json('https://api.github.com/repos/videolan/vlc/releases/latest', ['Accept: application/vnd.github.v3+json']);
                if ($data && isset($data['tag_name'])) {
                    return $data['tag_name'];
                }
                // Last fallback to tags (filter out non-version tags)
                $data = http_get_json('https://api.github.com/repos/videolan/vlc/tags', ['Accept: application/vnd.github.v3+json']);
                if ($data) {
                    foreach ($data as $tag) {
                        $name = $tag['name'];
                        // Skip non-version tags like 'svn-trunk', 'master', etc.
                        if (preg_match('/^\d+\.\d+/', $name)) {
                            return $name;
                        }
                    }
                }
                break;
            case 'git':
                // Use Git for Windows releases
                $data = http_get_json('https://api.github.com/repos/git-for-windows/git/releases/latest', ['Accept: application/vnd.github.v3+json']);
                if ($data && isset($data['tag_name'])) {
                    return $data['tag_name'];
                }
                break;
        }
    }
    return null;
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
    
    $latest_raw = $is_custom
        ? getLatestVersionCustom($app['update_url'], $app['update_regex'])
        : getLatestVersion($app['update_source']);
    if ($latest_raw) {
        $latest_norm = normalize_version($latest_raw);
        $update_check = isUpdateAvailable($app['version'], $latest_raw);
        $update_available = is_bool($update_check) ? ($update_check ? 1 : 0) : null;
        $stmt = $pdo->prepare("UPDATE apps SET latest_version = ?, latest_version_norm = ?, update_available = ?, last_checked = NOW(), last_error = NULL WHERE id = ?");
        $stmt->execute([$latest_raw, $latest_norm, $update_available, $id]);
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
