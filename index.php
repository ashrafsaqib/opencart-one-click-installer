<?php
/**
 * OpenCart Automated Installer
 * * Logic:
 * 1. Scans 'sources/' directory for sets of files: .zip, .sql, .config.template, .admin.config.template.
 * 2. Unzips selected version to a temporary location.
 * 3. Renames the extracted folder to the user's chosen {name} (or moves files if no single root folder exists).
 * 4. Creates a MySQL database (Same name as folder).
 * 5. Imports the matching .sql dump.
 * 6. Reads .template files, replaces {name} (and optional db placeholders), and saves as config.php.
 */

// --- Configuration & Helpers ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('max_execution_time', 300); // Allow time for unzipping/importing

// --- HARDCODED CREDENTIALS ---
// Edit these values to match your server environment
define('INSTALLER_DB_HOST', 'localhost');
define('INSTALLER_DB_USER', 'root');
define('INSTALLER_DB_PASS', 'yourpassword');
// -----------------------------

$message = '';
$messageType = ''; // success, danger

function setMsg($msg, $type = 'info') {
    global $message, $messageType;
    $message = $msg;
    $messageType = $type;
}

// Recursive delete (cleanup)
function delTree($dir) {
   if (!file_exists($dir)) return true;
   $files = array_diff(scandir($dir), array('.','..'));
    foreach ($files as $file) {
      (is_dir("$dir/$file")) ? delTree("$dir/$file") : unlink("$dir/$file");
    }
    return rmdir($dir);
}

// Move directory contents
function moveDir($src, $dest) {
    if(!is_dir($dest)) mkdir($dest, 0755, true);
    $files = array_diff(scandir($src), array('.','..'));
    foreach ($files as $file) {
        rename("$src/$file", "$dest/$file");
    }
    delTree($src);
}

// Parse SQL dump split by delimiter
function importSql($conn, $sqlFile) {
    $query = '';
    $lines = file($sqlFile);
    foreach ($lines as $line) {
        // Skip comments
        if (substr($line, 0, 2) == '--' || $line == '') continue;
        
        $query .= $line;
        if (substr(trim($line), -1, 1) == ';') {
            if (!$conn->query($query)) {
                throw new Exception("SQL Error: " . $conn->error);
            }
            $query = '';
        }
    }
}

// --- Main Handler ---

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $installName = trim($_POST['install_name']);
    $versionBase = $_POST['version']; // Base filename without extension
    
    // Use Hardcoded Credentials
    $dbHost = INSTALLER_DB_HOST;
    $dbUser = INSTALLER_DB_USER;
    $dbPass = INSTALLER_DB_PASS;
    $dbName = $installName; // Forced: DB Name = Folder Name
    $dbPrefix = 'oc_'; // Default prefix

    $baseDir = __DIR__;
    $targetDir = $baseDir . DIRECTORY_SEPARATOR . $installName;
    $sourcesDir = $baseDir . DIRECTORY_SEPARATOR . 'sources';
    
    // File Paths
    $sourceZip      = $sourcesDir . DIRECTORY_SEPARATOR . $versionBase . '.zip';
    $sourceSql      = $sourcesDir . DIRECTORY_SEPARATOR . $versionBase . '.sql';
    $tplConfig      = $sourcesDir . DIRECTORY_SEPARATOR . $versionBase . '.config.template';
    $tplAdminConfig = $sourcesDir . DIRECTORY_SEPARATOR . $versionBase . '.admin.config.template';

    try {
        // 1. Validations
        if (empty($installName) || !preg_match('/^[a-zA-Z0-9_]+$/', $installName)) {
            throw new Exception("Invalid installation name. Use alphanumeric and underscores only.");
        }
        if (file_exists($targetDir)) {
            throw new Exception("Directory '$installName' already exists. Please choose another name or delete the folder.");
        }
        if (!file_exists($sourceZip)) throw new Exception("ZIP file missing: $versionBase.zip");
        if (!file_exists($sourceSql)) throw new Exception("SQL file missing: $versionBase.sql");
        if (!file_exists($tplConfig)) throw new Exception("Config template missing: $versionBase.config.template");
        if (!file_exists($tplAdminConfig)) throw new Exception("Admin Config template missing: $versionBase.admin.config.template");

        // 2. Unzip & Rename Logic
        $tempExtractDir = $baseDir . DIRECTORY_SEPARATOR . 'temp_' . uniqid();
        mkdir($tempExtractDir);

        $zip = new ZipArchive;
        if ($zip->open($sourceZip) === TRUE) {
            $zip->extractTo($tempExtractDir);
            $zip->close();
        } else {
            throw new Exception("Failed to unzip file.");
        }

        // Scan extracted files to handle folder structure
        $extractedItems = array_diff(scandir($tempExtractDir), array('.', '..'));
        $extractedItems = array_values($extractedItems); // Re-index

        // Logic: If zip contains a single folder, rename/move THAT folder. 
        // If it contains multiple files (zip bomb style), move ALL into target.
        if (count($extractedItems) === 1 && is_dir($tempExtractDir . '/' . $extractedItems[0])) {
            // It's a single folder (e.g., 'opencart-3.0.3.8'), rename it to target
            rename($tempExtractDir . '/' . $extractedItems[0], $targetDir);
            delTree($tempExtractDir); // Cleanup temp root
        } else {
            // It's loose files, move them into target dir
            moveDir($tempExtractDir, $targetDir);
        }

        // 3. Database Creation & Import
        $mysqli = new mysqli($dbHost, $dbUser, $dbPass);
        if ($mysqli->connect_error) {
            throw new Exception("Database Connection Failed: " . $mysqli->connect_error);
        }

        // Create DB
        $mysqli->query("CREATE DATABASE IF NOT EXISTS `$dbName`");
        $mysqli->select_db($dbName);

        // Import SQL
        importSql($mysqli, $sourceSql);
        $mysqli->close();

        // 4. Process Config Templates
        // Map of placeholders to replace
        $replacements = [
            '{name}'      => $installName,
        ];

        // Process Root Config
        $configContent = file_get_contents($tplConfig);
        $configContent = str_replace(array_keys($replacements), array_values($replacements), $configContent);
        file_put_contents($targetDir . '/config.php', $configContent);

        // Process Admin Config
        $adminConfigContent = file_get_contents($tplAdminConfig);
        $adminConfigContent = str_replace(array_keys($replacements), array_values($replacements), $adminConfigContent);
        file_put_contents($targetDir . '/admin/config.php', $adminConfigContent);

        setMsg("Installation '$installName' created successfully! <a href='$installName/' target='_blank' class='alert-link'>Click here to open</a>", "success");

    } catch (Exception $e) {
        if (isset($tempExtractDir) && is_dir($tempExtractDir)) delTree($tempExtractDir);
        setMsg($e->getMessage(), "danger");
    }
}

// --- View Logic ---

// Scan sources for valid sets (Must have .zip)
$sourcesPath = __DIR__ . '/sources/';
$availableVersions = [];

if (is_dir($sourcesPath)) {
    $zipFiles = glob($sourcesPath . '*.zip');
    foreach ($zipFiles as $f) {
        $baseName = basename($f, '.zip');
        // Optional: Check if other files exist for this version to be valid
        if (file_exists($sourcesPath . $baseName . '.sql') && 
            file_exists($sourcesPath . $baseName . '.config.template')) {
            $availableVersions[] = $baseName;
        }
    }
} else {
    @mkdir($sourcesPath);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OpenCart Automated Installer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; padding-top: 40px; }
        .card { box-shadow: 0 4px 6px rgba(0,0,0,0.1); border: none; }
        .header-icon { font-size: 2rem; color: #0d6efd; margin-right: 10px; }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            
            <div class="card mb-4">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-4">
                        <div class="header-icon">🛒</div>
                        <h2 class="h4 mb-0">OpenCart Instant Installer</h2>
                    </div>

                    <?php if (!is_dir($sourcesPath)): ?>
                        <div class="alert alert-warning">
                            Warning: <code>sources/</code> directory was missing. I created it for you. 
                        </div>
                    <?php endif; ?>

                    <?php if (empty($availableVersions) && is_dir($sourcesPath)): ?>
                        <div class="alert alert-warning">
                            <strong>No valid version sets found in <code>sources/</code>.</strong><br>
                            Ensure you have all 4 files for a version:<br>
                            <ul>
                                <li><code>[name].zip</code></li>
                                <li><code>[name].sql</code></li>
                                <li><code>[name].config.template</code></li>
                                <li><code>[name].admin.config.template</code></li>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if ($message): ?>
                        <div class="alert alert-<?= $messageType ?>"><?= $message ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Select Version</label>
                                <select name="version" class="form-select" required>
                                    <?php foreach ($availableVersions as $ver): ?>
                                        <option value="<?= htmlspecialchars($ver) ?>"><?= htmlspecialchars($ver) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Files scanned from /sources folder</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Installation Name</label>
                                <input type="text" name="install_name" class="form-control" placeholder="e.g. shop_v3" required pattern="[a-zA-Z0-9_]+">
                                <div class="form-text">Will be Folder Name AND Database Name.</div>
                            </div>
                        </div>

                        <hr>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg" <?= empty($availableVersions) ? 'disabled' : '' ?>>
                                Install OpenCart
                            </button>
                        </div>

                    </form>
                </div>
            </div>

            <div class="text-center text-muted small">
                <p>Note: DB credentials are hardcoded in this script.</p>
            </div>

        </div>
    </div>
</div>

</body>
</html>