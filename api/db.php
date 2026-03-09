<?php
/**
 * ProfileGen - Vercel Blob Database Wrapper
 * Uses Vercel Blob for persistent storage, with local JSON fallback
 */

// Vercel Blob Configuration
// Set these in Vercel Dashboard > Environment Variables
define('BLOB_STORE_URL', getenv('BLOB_STORE_URL') ?: '');
define('BLOB_STORE_TOKEN', getenv('BLOB_STORE_TOKEN') ?: '');
define('BLOB_NAME', 'profiles.json');

// Local development fallback - use local JSON file
define('USE_LOCAL_FALLBACK', getenv('VERCEL') !== '1');

if (USE_LOCAL_FALLBACK) {
    define('LOCAL_DATA_FILE', __DIR__ . '/data/profiles.json');
}

/**
 * Get all profiles from storage
 */
function get_all_profiles() {
    if (USE_LOCAL_FALLBACK) {
        return get_all_profiles_local();
    }
    return get_all_profiles_blob();
}

/**
 * Get a single profile by username
 */
function get_profile($username) {
    if (USE_LOCAL_FALLBACK) {
        return get_profile_local($username);
    }
    return get_profile_blob($username);
}

/**
 * Create a new profile
 */
function create_profile($data) {
    if (USE_LOCAL_FALLBACK) {
        return create_profile_local($data);
    }
    return create_profile_blob($data);
}

/**
 * Update a profile
 */
function update_profile($username, $data) {
    if (USE_LOCAL_FALLBACK) {
        return update_profile_local($username, $data);
    }
    return update_profile_blob($username, $data);
}

/**
 * Delete a profile
 */
function delete_profile($username) {
    if (USE_LOCAL_FALLBACK) {
        return delete_profile_local($username);
    }
    return delete_profile_blob($username);
}

// ============================================
// Vercel Blob Implementation
// ============================================

function get_all_profiles_blob() {
    $data = fetch_blob();
    return $data['profiles'] ?? [];
}

function get_profile_blob($username) {
    $profiles = get_all_profiles_blob();
    foreach ($profiles as $p) {
        if ($p['username'] === $username) {
            return $p;
        }
    }
    return null;
}

function create_profile_blob($data) {
    $profiles = get_all_profiles_blob();
    $data['id'] = uniqid();
    $data['created_at'] = date('Y-m-d H:i:s');
    $profiles[] = $data;
    save_blob($profiles);
    return $data;
}

function update_profile_blob($username, $data) {
    $profiles = get_all_profiles_blob();
    foreach ($profiles as &$p) {
        if ($p['username'] === $username) {
            $p = array_merge($p, $data);
            $p['updated_at'] = date('Y-m-d H:i:s');
            break;
        }
    }
    save_blob($profiles);
}

function delete_profile_blob($username) {
    $profiles = get_all_profiles_blob();
    $profiles = array_filter($profiles, fn($p) => $p['username'] !== $username);
    save_blob(array_values($profiles));
}

function fetch_blob() {
    if (empty(BLOB_STORE_URL)) {
        return ['profiles' => []];
    }
    
    $ch = curl_init(BLOB_STORE_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . BLOB_STORE_TOKEN
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        return json_decode($response, true) ?: ['profiles' => []];
    }
    return ['profiles' => []];
}

function save_blob($profiles) {
    if (empty(BLOB_STORE_URL)) {
        return;
    }
    
    $json = json_encode(['profiles' => $profiles], JSON_PRETTY_PRINT);
    
    // Use PUT to update the blob
    $ch = curl_init(BLOB_STORE_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . BLOB_STORE_TOKEN,
        'Content-Type: application/json'
    ]);
    curl_exec($ch);
    curl_close($ch);
}

// ============================================
// Local JSON File Implementation (Fallback)
// ============================================

function get_all_profiles_local() {
    $file = LOCAL_DATA_FILE;
    if (!file_exists($file)) {
        // Create directory if needed
        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return [];
    }
    $content = file_get_contents($file);
    $data = json_decode($content, true);
    return $data['profiles'] ?? [];
}

function get_profile_local($username) {
    $profiles = get_all_profiles_local();
    foreach ($profiles as $p) {
        if ($p['username'] === $username) {
            return $p;
        }
    }
    return null;
}

function create_profile_local($data) {
    $profiles = get_all_profiles_local();
    $data['id'] = uniqid();
    $data['created_at'] = date('Y-m-d H:i:s');
    $profiles[] = $data;
    save_profiles_local($profiles);
    return $data;
}

function update_profile_local($username, $data) {
    $profiles = get_all_profiles_local();
    foreach ($profiles as &$p) {
        if ($p['username'] === $username) {
            $p = array_merge($p, $data);
            $p['updated_at'] = date('Y-m-d H:i:s');
            break;
        }
    }
    save_profiles_local($profiles);
}

function delete_profile_local($username) {
    $profiles = get_all_profiles_local();
    $profiles = array_filter($profiles, fn($p) => $p['username'] !== $username);
    save_profiles_local(array_values($profiles));
}

function save_profiles_local($profiles) {
    $file = LOCAL_DATA_FILE;
    $dir = dirname($file);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $json = json_encode(['profiles' => $profiles], JSON_PRETTY_PRINT);
    file_put_contents($file, $json);
}

// Alias for compatibility
function get_db() {
    return (object)['profiles' => get_all_profiles()];
}
