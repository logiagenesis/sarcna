<?php
declare(strict_types=1);

/**
 * The race test.
 *
 *   php tools/race-test.php [base-url]
 *
 * The site's central promise is that two people cannot take the same bed. Every
 * other test in this repository checks that one at a time. This one checks it
 * the way it actually breaks in the real world: a dozen browsers hitting the
 * same last bed in the same instant.
 *
 * It builds a private room type with exactly one bed, fires N genuinely
 * simultaneous booking requests at it from N separate sessions, and asserts
 * that exactly one wins. Then it does the same against the database directly,
 * bypassing the application entirely, to prove the guarantee is in the schema
 * and not merely in the PHP.
 *
 * Everything it creates is removed again.
 */

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Database;

if (PHP_SAPI !== 'cli') {
    exit("Command line only.\n");
}

// Never die silently. A test tool that stops with no output is worse than one
// that fails, because it reads as "nothing to report".
set_exception_handler(static function (\Throwable $e): void {
    fwrite(STDERR, sprintf("\n\033[31mThe race test stopped:\033[0m %s\n  %s:%d\n",
        $e->getMessage(), $e->getFile(), $e->getLine()));
    exit(1);
});

$base    = rtrim($argv[1] ?? 'http://127.0.0.1:8000', '/');
$racers  = 12;
$passed  = 0;
$failed  = 0;

function check(string $label, bool $ok, string $detail = ''): void
{
    global $passed, $failed;

    $ok ? $passed++ : $failed++;
    printf("  %s %s%s\n", $ok ? "\033[32mPASS\033[0m" : "\033[31mFAIL\033[0m", $label, $detail === '' ? '' : " — {$detail}");
}

printf("\nSARCNA 2027 — race test (%d simultaneous buyers, one bed)\n%s\n\n", $racers, str_repeat('=', 60));

/* ------------------------------------------------------------- fixture */

$night = \App\Services\AccommodationService::nights()[0] ?? null;

if ($night === null) {
    exit("No bookable nights configured.\n");
}

Database::run('DELETE FROM rate_limits');

/**
 * Clear any fixture a previous run left behind.
 *
 * This test builds a room with a fixed slug. If a run is interrupted — the
 * machine sleeps, the dev server dies, somebody presses ctrl-C — the room
 * survives, and every later run then dies on the duplicate slug with nothing
 * printed. Clearing first makes the tool safe to re-run, always.
 */
$stale = Database::first('SELECT id FROM room_types WHERE slug = "race-test-room"');

if ($stale !== null) {
    $staleType = (int) $stale['id'];

    echo "Clearing a fixture left behind by an interrupted run.\n";

    Database::run('DELETE FROM bookings WHERE room_type_id = ?', [$staleType]);
    Database::run('DELETE FROM booking_holds WHERE bed_id IN (SELECT id FROM beds WHERE room_unit_id IN (SELECT id FROM room_units WHERE room_type_id = ?))', [$staleType]);
    Database::run('DELETE FROM cart_items WHERE bed_id IN (SELECT id FROM beds WHERE room_unit_id IN (SELECT id FROM room_units WHERE room_type_id = ?))', [$staleType]);
    Database::run('DELETE FROM beds WHERE room_unit_id IN (SELECT id FROM room_units WHERE room_type_id = ?)', [$staleType]);
    Database::run('DELETE FROM room_units WHERE room_type_id = ?', [$staleType]);
    Database::run('DELETE FROM room_types WHERE id = ?', [$staleType]);
}

$typeId = Database::insert('room_types', [
    'name'            => 'RACE TEST room',
    'slug'            => 'race-test-room',
    'summary'         => 'Temporary room used by tools/race-test.php.',
    'beds_per_unit'   => 1,
    'bed_rate_cents'  => 10000,
    'sort_order'      => 999,
    'is_active'       => 1,
]);

$unitId = Database::insert('room_units', [
    'room_type_id' => $typeId,
    'name'         => 'RACE TEST unit',
    'code'         => 'RACE1',
    'is_active'    => 1,
]);

$bedId = Database::insert('beds', [
    'room_unit_id' => $unitId,
    'label'        => 'Bed 1',
    'sort_order'   => 1,
    'is_active'    => 1,
]);

printf("Built one room, one unit, one bed (#%d) for the night of %s.\n\n", $bedId, $night);

/* --------------------------------------- round 1: through the website */

echo "Round 1 — twelve separate browsers, all clicking at once\n";

$jars = [];
$multi = curl_multi_init();
$handles = [];

// Each racer needs its own session and its own CSRF token, fetched first.
for ($i = 0; $i < $racers; $i++) {
    $jar = tempnam(sys_get_temp_dir(), "race{$i}-");
    $jars[$i] = $jar;

    $ch = curl_init($base . '/accommodation/race-test-room');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEJAR      => $jar,
        CURLOPT_COOKIEFILE     => $jar,
        CURLOPT_PROXY          => '',
        CURLOPT_TIMEOUT        => 20,
    ]);

    $body  = (string) curl_exec($ch);
    curl_close($ch);

    if (preg_match('/name="_token" value="([^"]+)"/', $body, $m) !== 1) {
        exit("Could not read a CSRF token for racer {$i}.\n");
    }

    $post = http_build_query([
        '_token'     => $m[1],
        'mode'       => 'bed',
        'beds'       => 1,
        'nights'     => [$night],
        'guest_name' => "Racer {$i}",
    ]);

    $ch = curl_init($base . '/accommodation/race-test-room/book');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $post,
        CURLOPT_COOKIEJAR      => $jar,
        CURLOPT_COOKIEFILE     => $jar,
        CURLOPT_PROXY          => '',
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_FOLLOWLOCATION => false,
    ]);

    curl_multi_add_handle($multi, $ch);
    $handles[$i] = $ch;
}

// Release all twelve at the same moment.
$active = null;
do {
    $status = curl_multi_exec($multi, $active);
    if ($active) {
        curl_multi_select($multi, 1.0);
    }
} while ($active && $status === CURLM_OK);

foreach ($handles as $ch) {
    curl_multi_remove_handle($multi, $ch);
    curl_close($ch);
}
curl_multi_close($multi);

$holds = (int) Database::scalar(
    'SELECT COUNT(*) FROM booking_holds WHERE bed_id = ? AND night = ? AND expires_at > NOW()',
    [$bedId, $night]
);

check(
    "Exactly one of {$racers} simultaneous buyers holds the bed",
    $holds === 1,
    "{$holds} hold(s) on the bed"
);

$cartLines = (int) Database::scalar(
    'SELECT COUNT(*) FROM cart_items WHERE bed_id = ? AND night = ?',
    [$bedId, $night]
);

check(
    'Only that one buyer has it in their cart',
    $cartLines === 1,
    "{$cartLines} cart line(s)"
);

/* ------------------------------------ round 2: straight at the database */

echo "\nRound 2 — the same race, but bypassing the application entirely\n";

Database::run('DELETE FROM booking_holds WHERE bed_id = ?', [$bedId]);
Database::run('DELETE FROM cart_items WHERE bed_id = ?', [$bedId]);

$script = sys_get_temp_dir() . '/race-insert.php';
file_put_contents($script, <<<'PHP'
<?php
require '/home/user/sarcna/app/bootstrap.php';
use App\Core\Database;

[$bedId, $unitId, $typeId, $night, $n] = array_slice($argv, 1);

// Every worker tries to confirm the same bed for the same night at once.
try {
    Database::insert('bookings', [
        'reference'    => 'RACE-' . $n,
        'bed_id'       => (int) $bedId,
        'room_unit_id' => (int) $unitId,
        'room_type_id' => (int) $typeId,
        'night'        => $night,
        'guest_name'   => 'Racer ' . $n,
        'price_cents'  => 10000,
        'status'       => 'confirmed',
    ]);
    echo "won\n";
} catch (\PDOException $e) {
    echo $e->getCode() === '23000' ? "refused\n" : "error:" . $e->getCode() . "\n";
}
PHP);

$procs = [];
$pipes = [];

for ($i = 0; $i < $racers; $i++) {
    $cmd = sprintf(
        'php %s %d %d %d %s %d',
        escapeshellarg($script),
        $bedId, $unitId, $typeId, escapeshellarg($night), $i
    );

    $procs[$i] = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes[$i]);
}

$won = 0;
$refused = 0;
$other = 0;

foreach ($procs as $i => $proc) {
    $out = trim((string) stream_get_contents($pipes[$i][1]));
    fclose($pipes[$i][1]);
    fclose($pipes[$i][2]);
    proc_close($proc);

    if ($out === 'won') {
        $won++;
    } elseif ($out === 'refused') {
        $refused++;
    } else {
        $other++;
    }
}

check(
    "Exactly one of {$racers} concurrent writes is accepted",
    $won === 1,
    "{$won} accepted, {$refused} refused by the unique index"
);

check(
    'Every loser was refused cleanly, with no unexpected errors',
    $other === 0 && $refused === $racers - 1,
    "{$other} unexpected"
);

$live = (int) Database::scalar('SELECT COUNT(*) FROM bookings WHERE bed_id = ? AND active_night = ?', [$bedId, $night]);

check('The bed ends up with exactly one live booking', $live === 1, "{$live} live booking(s)");

/* ------------------------------- round 3: cancelling frees it instantly */

echo "\nRound 3 — cancelling puts the bed straight back on sale\n";

Database::run('UPDATE bookings SET status = "cancelled" WHERE bed_id = ?', [$bedId]);

$freeAgain = (int) Database::scalar('SELECT COUNT(*) FROM bookings WHERE bed_id = ? AND active_night IS NOT NULL', [$bedId]);
check('Cancelling clears the bed with no cleanup job', $freeAgain === 0);

$rebooked = false;

try {
    Database::insert('bookings', [
        'reference' => 'RACE-REBOOK', 'bed_id' => $bedId, 'room_unit_id' => $unitId,
        'room_type_id' => $typeId, 'night' => $night, 'guest_name' => 'Second guest',
        'price_cents' => 10000, 'status' => 'confirmed',
    ]);
    $rebooked = true;
} catch (\PDOException) {
    $rebooked = false;
}

check('Somebody else can immediately book the freed bed', $rebooked);

/* ------------------------------------------------------------ clean-up */

echo "\nCleaning up\n";

Database::run('DELETE FROM bookings WHERE bed_id = ?', [$bedId]);
Database::run('DELETE FROM booking_holds WHERE bed_id = ?', [$bedId]);
Database::run('DELETE FROM cart_items WHERE bed_id = ?', [$bedId]);
Database::run('DELETE FROM beds WHERE id = ?', [$bedId]);
Database::run('DELETE FROM room_units WHERE id = ?', [$unitId]);
Database::run('DELETE FROM room_types WHERE id = ?', [$typeId]);

foreach ($jars as $jar) {
    @unlink($jar);
}
@unlink($script);

$leftover = (int) Database::scalar('SELECT COUNT(*) FROM room_types WHERE slug = "race-test-room"')
    + (int) Database::scalar('SELECT COUNT(*) FROM bookings WHERE reference LIKE "RACE-%"');

check('The test removed everything it created', $leftover === 0);

printf("\n%s\n%d passed, %d failed.\n", str_repeat('=', 60), $passed, $failed);

exit($failed === 0 ? 0 : 1);
