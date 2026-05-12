<?php
/**
 * SQL Übungsbereich
 */

// --- DATENBANK VERBINDUNG & LOGIK ---
$sql_files = glob("*.sql");
$selected_sql = $_GET['sql'] ?? ($sql_files[0] ?? null);

$error = '';
$results = [];
$query = $_POST['query'] ?? '';
$db = null;

if ($selected_sql && in_array($selected_sql, $sql_files)) {
    $db_file = str_replace('.sql', '.db', $selected_sql);
    try {
        if (!file_exists($db_file) || filesize($db_file) === 0) {
            $temp_db = new SQLite3($db_file);
            $sql_content = file_get_contents($selected_sql);
            $temp_db->exec($sql_content);
            $temp_db->close();
        }
        $db = new SQLite3($db_file, SQLITE3_OPEN_READONLY);
    } catch (Exception $e) {
        $error = "Systemfehler: " . $e->getMessage();
    }
}

// SQL Sicherheits-Filter
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db && !empty($query)) {
    $clean_query = preg_replace('/(--.*)|(\/\*.*\*\/)/s', '', $query);
    $blacklist = ['DROP', 'DELETE', 'UPDATE', 'INSERT', 'ALTER', 'CREATE', 'ATTACH', 'DETACH', 'REPLACE'];
    $forbidden_found = false;
    foreach ($blacklist as $word) {
        if (preg_match('/\b' . $word . '\b/i', $clean_query)) {
            $forbidden_found = true;
            $error = "Sicherheits-Verstoß: Der Befehl '$word' ist nicht erlaubt!";
            break;
        }
    }

    if (!$forbidden_found) {
        $res = @$db->query($query);
        if ($res === false) {
            $error = "SQL-Fehler: " . $db->lastErrorMsg();
        } else {
            while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
                $results[] = $row;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>SQL Übungsbereich</title>
    <style>
        :root { --primary-blue: #14508c; --border-dark: #d1d5db; --border-light: #e5e7eb; }
        body { font-family: 'Segoe UI', system-ui, sans-serif; background: #f0f2f5; margin: 0; display: flex; flex-direction: column; min-height: 100vh; }
        
        /* Header & Footer (Design aus didakt_index.php) */
        header { background: #fff; padding: 10px 40px; display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; border-bottom: 1px solid var(--border-dark); gap: 20px; }
        .header-title h1 { margin: 0; font-size: 1.25rem; color: var(--primary-blue); }
        
        .container { padding: 30px 40px; flex: 1; }
        .card { background: #fff; padding: 25px; border-radius: 8px; border: 1px solid var(--border-dark); box-shadow: 0 2px 10px rgba(0,0,0,0.05); }

        .nav-tabs { display: flex; gap: 5px; margin-bottom: 20px; }
        .tab { padding: 10px 20px; text-decoration: none; background: #e2e8f0; color: #475569; border-radius: 6px; font-weight: 600; font-size: 0.9rem; transition: all 0.2s; }
        .tab:hover { background: #cbd5e1; }
        .tab.active { background: var(--primary-blue); color: white; }

        /* Eingabefeld exakt auf Breite */
        textarea { 
            width: 100%; height: 120px; font-family: 'Consolas', monospace; padding: 15px; 
            border: 2px solid var(--border-light); border-radius: 6px; box-sizing: border-box; 
            margin-bottom: 15px; outline: none; display: block;
        }
        textarea:focus { border-color: var(--primary-blue); }
        
        .btn { padding: 10px 25px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; transition: filter 0.2s; }
        .btn-primary { background: var(--primary-blue); color: white; }
        .btn-copy { background: #64748b; color: white; margin-top: 15px; }

        .table-wrapper { overflow-x: auto; margin-top: 25px; border: 1px solid var(--border-light); border-radius: 6px; }
        table { border-collapse: collapse; width: 100%; font-size: 0.95rem; }
        th, td { border-bottom: 1px solid var(--border-light); padding: 12px 15px; text-align: left; }
        th { background: #f8fafc; color: var(--primary-blue); }

        .error-box { background: #fef2f2; color: #b91c1c; padding: 15px; border-radius: 6px; border: 1px solid #fee2e2; margin: 15px 0; }
        .badge { background: #10b981; color: white; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; vertical-align: middle; margin-left: 10px; }
        
        footer { background-color: var(--primary-blue); color: white; padding: 40px 0; margin-top: 60px; }
        .footer-content { max-width: 1320px; margin: 0 auto; padding: 0 40px; display: flex; justify-content: space-between; align-items: center; }
        .footer-link { color: #89b1d8; text-decoration: none; }
    </style>
</head>
<body>

<header>
    <div><img src="logo.png" style="height:55px;" onerror="this.style.visibility='hidden'"></div>
    <div class="header-title">
        <h1>SQL Übungsbereich</h1>
    </div>
    
</header>

<div class="container">
    <nav class="nav-tabs">
        <?php foreach ($sql_files as $file): 
            $name = basename($file, ".sql");
            $activeClass = ($file === $selected_sql) ? 'active' : '';
        ?>
            <a href="?sql=<?php echo urlencode($file); ?>" class="tab <?php echo $activeClass; ?>">
                <?php echo htmlspecialchars(strtoupper($name)); ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="card">
        <?php if ($selected_sql): 
            $db_name = basename($selected_sql, ".sql");
            $img = $db_name . ".png";
        ?>
            <?php if (file_exists($img)): ?>
                <details style="margin-bottom: 20px;">
                    <summary style="cursor:pointer; color: var(--primary-blue); font-weight: 600;">🔍 Relationenschema anzeigen</summary>
                    <img src="<?php echo $img; ?>" style="max-width: 100%; height: auto; border: 1px solid var(--border-light); border-radius: 6px; margin-top:10px;">
                </details>
            <?php endif; ?>

            <form method="post">
                <textarea name="query" id="sqlQuery" spellcheck="false" placeholder="SELECT * FROM ..."><?php echo htmlspecialchars($query); ?></textarea>
                <button type="submit" class="btn btn-primary">Abfrage ausführen</button>
            </form>

            <?php if ($error): ?>
                <div class="error-box"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if (!empty($results)): ?>
                <div class="table-wrapper">
                    <table id="resultTable">
                        <thead>
                            <tr>
                                <?php foreach (array_keys($results[0]) as $col): ?>
                                    <th><?php echo htmlspecialchars($col); ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results as $row): ?>
                                <tr>
                                    <?php foreach ($row as $val): ?>
                                        <td><?php echo htmlspecialchars($val ?? 'NULL'); ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <button type="button" class="btn btn-copy" onclick="copyToClipboard()">📋 Abfrage und Ergebnis kopieren</button>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<footer>
    <div class="footer-content">
        <div>
            <h4 style="margin:0 0 10px 0;">Lizenz</h4>
            © <?= date('Y') ?> MMBbS Hannover | <a href="https://github.com/herr-nm/MMBbS_SQL-Uebungsbereich" target="_blank" class="footer-link">Neumann</a> | <a href="https://www.gnu.org/licenses/agpl-3.0.html" target="_blank" class="footer-link">GNU AGPL v3</a>
        </div>
        <div style="text-align: right;">
            <h4 style="margin:0 0 10px 0;">Kontakt</h4>
            <a href="mailto:info@mmbbs.de" class="footer-link">info@mmbbs.de</a><br>
            <a href="https://www.mmbbs.de" target="_blank" class="footer-link">www.mmbbs.de</a>
        </div>
    </div>
</footer>

<script>
function copyToClipboard() {
    const query = document.getElementById('sqlQuery').value;
    const table = document.getElementById('resultTable');
    if(!table) return;

    let asciiTable = "";
    const rows = Array.from(table.rows);
    const colWidths = [];

    // 1. Spaltenbreiten berechnen
    rows.forEach(row => {
        Array.from(row.cells).forEach((cell, i) => {
            const len = cell.innerText.length;
            if (!colWidths[i] || len > colWidths[i]) colWidths[i] = len;
        });
    });

    // 2. ASCII Tabelle bauen
    const separator = "+" + colWidths.map(w => "-".repeat(w + 2)).join("+") + "+";
    
    asciiTable += separator + "\n";
    rows.forEach((row, rowIndex) => {
        asciiTable += "| ";
        Array.from(row.cells).forEach((cell, i) => {
            asciiTable += cell.innerText.padEnd(colWidths[i]) + " | ";
        });
        asciiTable += "\n";
        if (rowIndex === 0 || rowIndex === rows.length - 1) {
            asciiTable += separator + "\n";
        }
    });

    const finalOutput = "SQL-ABFRAGE:\n" + query + "\n\nERGEBNIS:\n" + asciiTable;

    navigator.clipboard.writeText(finalOutput).then(() => {
        alert("SQL-Abfrage und Ergebnis-Tabelle wurden kopiert!");
    }).catch(err => {
        console.error('Fehler beim Kopieren: ', err);
    });
}
</script>
</body>
</html>