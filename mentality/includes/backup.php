<?php
// includes/backup.php - creates a full, restorable .sql backup of the database using plain PHP.
//
// WHO MAY USE IT: change this one line to ['admin'] if only administrators should be allowed.
define('BACKUP_ROLES', ['admin']);
define('BACKUP_DIR', dirname(__DIR__) . '/backups');      // same folder that tools/backup.bat uses

function can_backup(): bool {
    return in_array($_SESSION['role'] ?? '', BACKUP_ROLES, true);
}

// Creates the folder, and a .htaccess so nobody can download backups by typing the address in the browser.
function ensure_backup_dir(): void {
    if (!is_dir(BACKUP_DIR)) { mkdir(BACKUP_DIR, 0755, true); }
    $ht = BACKUP_DIR . '/.htaccess';
    if (!is_file($ht)) { file_put_contents($ht, "Require all denied\n"); }
}

function backup_list(): array {
    ensure_backup_dir();
    $out = [];
    foreach (glob(BACKUP_DIR . '/mentality_*.sql') ?: [] as $f) {
        $out[] = ['name' => basename($f), 'size' => filesize($f), 'time' => filemtime($f)];
    }
    usort($out, fn($a, $b) => $b['time'] <=> $a['time']);
    return $out;
}

function format_bytes(int $b): string {
    if ($b >= 1048576) { return round($b / 1048576, 2) . ' MB'; }
    if ($b >= 1024)    { return round($b / 1024, 1) . ' KB'; }
    return $b . ' bytes';
}

// Writes the backup file and returns ['name' => ..., 'size' => ...]. Throws an exception if something fails.
function create_backup(PDO $pdo): array {
    ensure_backup_dir();
    @set_time_limit(180);

    $db   = (string)$pdo->query('SELECT DATABASE()')->fetchColumn();
    $name = 'mentality_' . date('Ymd_His') . '.sql';
    $path = BACKUP_DIR . '/' . $name;
    $fh   = fopen($path, 'wb');
    if (!$fh) { throw new RuntimeException('Cannot write to the backups folder.'); }

    $q     = fn($id) => '`' . str_replace('`', '``', $id) . '`';
    $strip = fn($sql) => preg_replace('/DEFINER=`[^`]*`@`[^`]*`\s*/i', '', (string)$sql);   // makes the file restorable by any MySQL user

    try {
        fwrite($fh, "-- Mentality database backup\n-- Database: $db\n-- Created:  " . date('Y-m-d H:i:s') . "\n"
                  . "-- Restore:  phpMyAdmin > select the database > Import tab, or double-click tools\\restore.bat\n\n");
        fwrite($fh, "SET NAMES utf8mb4;\nSET time_zone = '+08:00';\nSET FOREIGN_KEY_CHECKS = 0;\nSET UNIQUE_CHECKS = 0;\n\n");

        // ---- 1) tables and their data
        $tables = [];
        foreach ($pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_NUM) as $r) { $tables[] = $r[0]; }
        foreach ($tables as $t) {
            $create = $pdo->query('SHOW CREATE TABLE ' . $q($t))->fetch(PDO::FETCH_NUM)[1];
            fwrite($fh, "-- Table $t\nDROP TABLE IF EXISTS " . $q($t) . ";\n" . $strip($create) . ";\n\n");

            $rows = $pdo->query('SELECT * FROM ' . $q($t));
            $cols = null; $batch = [];
            while ($row = $rows->fetch(PDO::FETCH_ASSOC)) {
                if ($cols === null) { $cols = implode(',', array_map($q, array_keys($row))); }
                $vals = [];
                foreach ($row as $v) { $vals[] = ($v === null) ? 'NULL' : $pdo->quote((string)$v); }
                $batch[] = '(' . implode(',', $vals) . ')';
                if (count($batch) >= 200) {
                    fwrite($fh, 'INSERT INTO ' . $q($t) . ' (' . $cols . ") VALUES\n" . implode(",\n", $batch) . ";\n");
                    $batch = [];
                }
            }
            if ($batch) { fwrite($fh, 'INSERT INTO ' . $q($t) . ' (' . $cols . ") VALUES\n" . implode(",\n", $batch) . ";\n"); }
            fwrite($fh, "\n");
        }

        // ---- 2) functions and stored procedures
        foreach (['FUNCTION' => 'Create Function', 'PROCEDURE' => 'Create Procedure'] as $kind => $key) {
            foreach ($pdo->query("SHOW $kind STATUS WHERE Db = " . $pdo->quote($db))->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $row = $pdo->query("SHOW CREATE $kind " . $q($r['Name']))->fetch(PDO::FETCH_ASSOC);
                if (empty($row[$key])) { continue; }
                fwrite($fh, "-- " . ucfirst(strtolower($kind)) . " {$r['Name']}\nDROP $kind IF EXISTS " . $q($r['Name']) . ";\nDELIMITER $$\n"
                          . $strip($row[$key]) . "$$\nDELIMITER ;\n\n");
            }
        }

        // ---- 3) views (after the functions they may use)
        foreach ($pdo->query("SHOW FULL TABLES WHERE Table_type = 'VIEW'")->fetchAll(PDO::FETCH_NUM) as $r) {
            $row = $pdo->query('SHOW CREATE VIEW ' . $q($r[0]))->fetch(PDO::FETCH_ASSOC);
            fwrite($fh, "-- View {$r[0]}\nDROP VIEW IF EXISTS " . $q($r[0]) . ";\n" . $strip($row['Create View']) . ";\n\n");
        }

        // ---- 4) triggers LAST, so they do not fire while the data is being restored
        foreach ($pdo->query('SHOW TRIGGERS')->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $row = $pdo->query('SHOW CREATE TRIGGER ' . $q($r['Trigger']))->fetch(PDO::FETCH_ASSOC);
            if (empty($row['SQL Original Statement'])) { continue; }
            fwrite($fh, "-- Trigger {$r['Trigger']}\nDROP TRIGGER IF EXISTS " . $q($r['Trigger']) . ";\nDELIMITER $$\n"
                      . $strip($row['SQL Original Statement']) . "$$\nDELIMITER ;\n\n");
        }

        fwrite($fh, "SET FOREIGN_KEY_CHECKS = 1;\nSET UNIQUE_CHECKS = 1;\n-- End of backup\n");
    } catch (Throwable $e) {
        fclose($fh);
        @unlink($path);                         // never leave a half-written backup behind
        throw $e;
    }
    fclose($fh);
    return ['name' => $name, 'size' => (int)filesize($path)];
}
