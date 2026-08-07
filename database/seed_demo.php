<?php
/**
 * Seed demo team members + sample images.
 * Run: php database/seed_demo.php
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';

function makeAvatar(string $path, string $initials, array $rgb): void
{
    $size = 256;
    $img = imagecreatetruecolor($size, $size);
    imagealphablending($img, true);
    $bg = imagecolorallocate($img, $rgb[0], $rgb[1], $rgb[2]);
    $white = imagecolorallocate($img, 255, 255, 255);
    imagefilledrectangle($img, 0, 0, $size, $size, $bg);

    $text = strtoupper($initials);
    $fontCandidates = [
        'C:\\Windows\\Fonts\\segoeui.ttf',
        'C:\\Windows\\Fonts\\arial.ttf',
        '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
    ];
    $font = null;
    foreach ($fontCandidates as $candidate) {
        if (is_file($candidate)) {
            $font = $candidate;
            break;
        }
    }

    if ($font !== null) {
        $fontSize = 84;
        $bbox = imagettfbbox($fontSize, 0, $font, $text);
        $tw = abs($bbox[2] - $bbox[0]);
        $th = abs($bbox[7] - $bbox[1]);
        $x = (int) (($size - $tw) / 2) - (int) $bbox[0];
        $y = (int) (($size + $th) / 2);
        imagettftext($img, $fontSize, 0, $x, $y, $white, $font, $text);
    } else {
        // Fallback: draw large block letters via scaled built-in font
        $font = 5;
        $tw = imagefontwidth($font) * strlen($text);
        $th = imagefontheight($font);
        $tmp = imagecreatetruecolor($tw + 4, $th + 4);
        $tbg = imagecolorallocate($tmp, $rgb[0], $rgb[1], $rgb[2]);
        imagefilledrectangle($tmp, 0, 0, $tw + 4, $th + 4, $tbg);
        imagestring($tmp, $font, 2, 2, $text, $white);
        $scale = 12;
        $dw = ($tw + 4) * $scale;
        $dh = ($th + 4) * $scale;
        imagecopyresampled(
            $img,
            $tmp,
            (int) (($size - $dw) / 2),
            (int) (($size - $dh) / 2),
            0,
            0,
            $dw,
            $dh,
            $tw + 4,
            $th + 4
        );
        imagedestroy($tmp);
    }

    imagejpeg($img, $path, 92);
    imagedestroy($img);
}

function makePhoto(string $path, array $rgb): void
{
    $w = 800;
    $h = 600;
    $img = imagecreatetruecolor($w, $h);
    $bg = imagecolorallocate($img, $rgb[0], $rgb[1], $rgb[2]);
    $accent = imagecolorallocatealpha($img, 255, 255, 255, 100);
    imagefilledrectangle($img, 0, 0, $w, $h, $bg);
    // Soft geometric accent — no name text burned into the photo
    imagefilledellipse($img, (int) ($w * 0.72), (int) ($h * 0.28), 220, 220, $accent);
    imagejpeg($img, $path, 88);
    imagedestroy($img);
}

$pdo = Database::getConnection();

// Clear previous demo uploads/members (keep admin)
$memberIds = $pdo->query("SELECT id FROM users WHERE role = 'member'")->fetchAll(PDO::FETCH_COLUMN);
foreach ($memberIds as $mid) {
    $stmt = $pdo->prepare('SELECT image FROM uploads WHERE user_id = ?');
    $stmt->execute([(int) $mid]);
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $file) {
        $p = UPLOAD_DIR . $file;
        if (is_file($p)) {
            @unlink($p);
        }
    }
}
$pdo->exec("DELETE FROM users WHERE role = 'member'");

$demoMembers = [
    ['Rahul Sharma', 'rahul', [15, 118, 110], 'important'],
    ['Anita Desai', 'anita', [0, 102, 204], 'normal'],
    ['Vikram Patel', 'vikram', [194, 65, 12], 'important'],
    ['Sara Khan', 'sara', [124, 58, 237], 'normal'],
];

$password = password_hash('Member@123', PASSWORD_BCRYPT);
$created = 0;

foreach ($demoMembers as $i => $demo) {
    [$name, $username, $color, $primaryFlag] = $demo;
    $parts = explode(' ', $name);
    $initials = substr($parts[0], 0, 1) . substr($parts[1] ?? $parts[0], 0, 1);

    $profileName = 'demo_' . $username . '.jpg';
    makeAvatar(PROFILE_DIR . $profileName, $initials, $color);

    $stmt = $pdo->prepare(
        'INSERT INTO users (name, username, password, profile, role, status, created_at)
         VALUES (?, ?, ?, ?, ?, 1, DATE_SUB(NOW(), INTERVAL ? DAY))'
    );
    $stmt->execute([$name, $username, $password, $profileName, 'member', 10 - $i]);
    $userId = (int) $pdo->lastInsertId();

    // 3–5 sample uploads per member
    $count = 3 + ($i % 3);
    for ($n = 0; $n < $count; $n++) {
        $filename = time() . '_' . random_int(100000, 999999) . '_' . $username . $n . '.jpg';
        // slight color variance
        $c = [
            max(0, min(255, $color[0] + ($n * 18))),
            max(0, min(255, $color[1] - ($n * 10))),
            max(0, min(255, $color[2] + ($n * 12))),
        ];
        makePhoto(UPLOAD_DIR . $filename, $c);

        $flag = ($n === 0) ? $primaryFlag : (($n % 2 === 0) ? 'important' : 'normal');
        $desc = $flag === 'important'
            ? 'Priority capture from field visit'
            : 'Routine site photo';

        $ins = $pdo->prepare(
            'INSERT INTO uploads (user_id, image, flag, description, created_at)
             VALUES (?, ?, ?, ?, DATE_SUB(NOW(), INTERVAL ? HOUR))'
        );
        $ins->execute([$userId, $filename, $flag, $desc, ($i * 8) + ($n * 5) + 2]);
    }
    $created++;
}

echo "Seeded {$created} demo members with profile + upload images.\n";
echo "Member login example: rahul / Member@123\n";
