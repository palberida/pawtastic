<?php

// Prune old Metabot chat-image files to cap disk growth.
//
// Deletes image files under storage/app/metabot_media/ older than the retention
// window and nulls the matching metabot_events.media_path so the inbox renders
// the message cleanly without a broken image. The metabot_events ROW is kept —
// only the media file + its path reference are removed.
//
// Run from project root:
//   php scripts/prune_metabot_media.php            # prune
//   php scripts/prune_metabot_media.php --dry-run  # report only, delete nothing
//
// Cron (daily, e.g. 03:30):
//   30 3 * * * cd /path/to/app && php scripts/prune_metabot_media.php >> scripts/log_prune_metabot_media.txt 2>&1

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Retention window in days. Overridable via .env, defaults to 30.
$retentionDays = (int) ($_ENV['METABOT_MEDIA_RETENTION_DAYS'] ?? 30);
$dryRun        = in_array('--dry-run', $argv, true);

// storage/app — same root WhatsAppClient::putMedia() writes relative paths under.
$storageAppDir = __DIR__ . '/../storage/app/';

$dbHost = $_ENV['DB_HOST'] ?? 'localhost';
$dbName = $_ENV['DB_DATABASE'] ?? 'database';
$dbUser = $_ENV['DB_USERNAME'] ?? 'root';
$dbPass = $_ENV['DB_PASSWORD'] ?? '';

$dsn = "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

$stamp = date('Y-m-d H:i:s');
echo "[$stamp] prune_metabot_media: retention={$retentionDays}d"
    . ($dryRun ? ' (dry-run)' : '') . "\n";

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);

    // Rows whose media is past the retention window.
    $select = $pdo->prepare("
        SELECT id, media_path
        FROM metabot_events
        WHERE media_path IS NOT NULL
          AND created_at < (NOW() - INTERVAL :days DAY)
    ");
    $select->bindValue(':days', $retentionDays, PDO::PARAM_INT);
    $select->execute();
    $rows = $select->fetchAll();

    $deleted = 0;
    $missing = 0;
    $bytes   = 0;

    foreach ($rows as $row) {
        $full = $storageAppDir . $row['media_path'];

        if (is_file($full)) {
            $size = (int) @filesize($full);
            if ($dryRun) {
                $bytes   += $size;
                $deleted++;
            } elseif (@unlink($full)) {
                $bytes   += $size;
                $deleted++;
            } else {
                echo "  WARN: could not delete {$full}\n";
            }
        } else {
            // File already gone — still null the path below to reflect reality.
            $missing++;
        }
    }

    // Null the path on every past-window row in one pass. Idempotent, and also
    // clears rows whose file was already missing.
    if (!$dryRun && count($rows) > 0) {
        $update = $pdo->prepare("
            UPDATE metabot_events
            SET media_path = NULL
            WHERE media_path IS NOT NULL
              AND created_at < (NOW() - INTERVAL :days DAY)
        ");
        $update->bindValue(':days', $retentionDays, PDO::PARAM_INT);
        $update->execute();
        $cleared = $update->rowCount();
    } else {
        $cleared = count($rows);
    }

    $mb = number_format($bytes / 1048576, 2);
    echo "  candidates={" . count($rows) . "} filesDeleted={$deleted}"
        . " alreadyMissing={$missing} pathsCleared={$cleared} freed={$mb}MB\n";
    echo "  done.\n";
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
