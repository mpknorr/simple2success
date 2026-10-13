<?php
// Run: php tests/mission1000.php. Uses SQLite for deterministic query fixtures;
// production MySQL/MariaDB and provider integration still require staging tests.
namespace Mission1000Test;
function check($condition, string $message): void {
    if (!$condition) throw new \RuntimeException($message);
    echo "PASS: $message\n";
}
require __DIR__ . '/../includes/onboarding.php';
require __DIR__ . '/../includes/followupTemplates.php';
// Namespace the unchanged policy so mysqli calls use the SQLite adapter below.
eval('namespace Mission1000Test;' . substr(file_get_contents(__DIR__ . '/../includes/followupPolicy.php'), 5));
class Rows {
    public array $rows;
    public function __construct(array $rows) { $this->rows = $rows; }
}
class Db {
    public $pdo;
    public int $affected = 0;
    public bool $fail = false;
    public function __construct() {
        $this->pdo = new \PDO('sqlite::memory:');
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }
}
function mysqli_query(Db $db, string $sql) {
    if ($db->fail) return false;
    $sql = str_replace('INSERT IGNORE', 'INSERT OR IGNORE', $sql);
    $sql = str_replace('NOW() - INTERVAL 24 HOUR', "datetime('2026-10-12 12:00:00', '-24 hours')", $sql);
    $sql = preg_replace('/TIMESTAMPDIFF\(DAY, (timestamp|step2_at), NOW\(\)\)/', "CAST(julianday('2026-10-12 12:00:00') - julianday($1) AS INTEGER)", $sql);
    $sql = str_replace('NOW()', "'2026-10-12 12:00:00'", $sql);
    if (str_starts_with(ltrim($sql), 'SELECT')) return new Rows($db->pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC));
    $db->affected = $db->pdo->exec($sql);
    return true;
}
function mysqli_num_rows(Rows $rows): int { return count($rows->rows); }
function mysqli_affected_rows(Db $db): int { return $db->affected; }
$db = new Db();
$db->pdo->exec('CREATE TABLE followup_contact_limits(user_id INTEGER PRIMARY KEY, last_attempt_at TEXT NOT NULL)');
$db->pdo->exec('CREATE TABLE followup_log(user_id INTEGER, sequence_id INTEGER, sent_at TEXT)');
$db->pdo->exec('CREATE TABLE followup_trigger_log(user_id INTEGER, sent_at TEXT)');
$db->pdo->exec('CREATE TABLE users(leadid INTEGER PRIMARY KEY, name TEXT, email TEXT, username TEXT, timestamp TEXT, step2_at TEXT)');
check(!\s2sSetupComplete(null) && !\s2sSetupComplete(''), 'No ID means setup incomplete');
check(\s2sNextAction('', true)['href'] === 'start.php#step1-action', 'Click without ID keeps registration reachable, not completed setup');
check(\s2sNextAction('6304013', false)['href'] === 'start.php#next-steps', 'Saved ID completes setup even without a tracked click');
check(s2sReserveFollowupContact($db, 1), 'First follow-up can reserve contact');
check(!s2sReserveFollowupContact($db, 1), 'Second worker/trigger is blocked by the reservation');
$db->pdo->exec("UPDATE followup_contact_limits SET last_attempt_at='2026-10-11 11:59:59' WHERE user_id=1");
check(s2sReserveFollowupContact($db, 1), 'Contact allowed after rolling 24-hour limit');
$db->pdo->exec("INSERT INTO followup_log VALUES(2, 1, '2026-10-12 01:00:00')");
check(!s2sReserveFollowupContact($db, 2), 'Pre-deployment sequence send blocks a new trigger');
$db->pdo->exec("INSERT INTO followup_trigger_log VALUES(3, '2026-10-12 01:00:00')");
check(!s2sReserveFollowupContact($db, 3), 'Trigger send blocks a scheduled sequence');
$db->fail = true;
check(!s2sReserveFollowupContact($db, 4), 'Database failure stops sending');
$db->fail = false;
$db->pdo->exec("INSERT INTO users VALUES
 (10,'A','a@example.test','','2026-10-11 10:00:00',NULL),
 (11,'B','b@example.test','','2026-09-01 10:00:00',NULL),
 (12,'C','c@example.test','123','2026-09-01 10:00:00','2026-10-11 10:00:00'),
 (13,'D','d@example.test','456','2026-09-01 10:00:00',NULL)");
check(array_column(mysqli_query($db, s2sDueFollowupRecipientsSql('lead', 7, 1))->rows, 'leadid') === [10], 'Only current lead offset is due; old backlog skipped');
check(array_column(mysqli_query($db, s2sDueFollowupRecipientsSql('member', 8, 1))->rows, 'leadid') === [12], 'Late activation starts member day 1; unknown legacy date is not guessed');
$db->pdo->exec("INSERT INTO followup_log VALUES(12,8,'2026-10-12 10:00:00')");
check(mysqli_query($db, s2sDueFollowupRecipientsSql('member', 8, 1))->rows === [], 'Sent sequence is not repeated');
check(count(\s2sTriggerTemplates()) === 3, 'Three versioned behaviour templates available');
$next = \s2sTriggerTemplates()['trigger_next_action_v2']['body'];
check(strpos($next, 'If you have already started') !== false && strpos($next, 'Step 3') !== false, 'Post-setup reminder also fits existing product customers');
echo "All mission tests passed. No external messages sent.\n";
