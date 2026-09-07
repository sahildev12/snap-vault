<?php
/**
 * Interactive MAP — clickable blocks
 */
declare(strict_types=1);

$blocks = BlockMap::all();
$mapImg = Helper::asset('assets/images/map/jammu-blocks.png');

$hotspots = [
    'pallanwala'    => '2,30 22,28 25,42 22,62 14,72 4,70 1.5,48',
    'chowki-choura' => '30,6 48,7 48,20 34,22 26,16',
    'akhnoor'       => '20,20 48,18 50,38 44,50 24,52 18,38',
    'kot-bhalwal'   => '48,12 62,10 64,32 56,38 47,28',
    'dansal'        => '60,8 78,10 78,34 66,38 58,24',
    'marh'          => '40,40 54,38 56,58 46,62 38,52',
    'jammu'         => '54,36 78,34 78,58 62,62 52,50',
    'sohanjana'     => '42,56 66,54 66,72 48,74 40,66',
    'rs-pura'       => '28,70 52,68 54,92 34,93 24,82',
    'bishnah'       => '52,68 78,66 78,92 54,93 50,80',
];
?>
<link href="<?= Helper::asset('assets/css/map-pages.css') ?>" rel="stylesheet">

<div class="imap-wrap">
    <div class="imap-stage" id="imapStage">
        <div class="imap-canvas">
        <img class="imap-base"
             src="<?= Helper::e($mapImg) ?>"
             alt="Jammu blocks map — Prepared by NIC J&amp;K"
             width="1024"
             height="745"
             draggable="false">

        <svg class="imap-hotspots" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">
            <?php foreach ($blocks as $slug => $block): ?>
                <?php
                $name = (string) ($block['name'] ?? $slug);
                $poly = $hotspots[$slug] ?? '';
                if ($poly === '') {
                    continue;
                }
                ?>
                <a href="<?= Helper::e(BlockMap::blockUrl($slug)) ?>">
                    <polygon class="imap-hotspot" points="<?= Helper::e($poly) ?>">
                        <title><?= Helper::e($name) ?></title>
                    </polygon>
                </a>
            <?php endforeach; ?>
        </svg>

        <?php foreach ($blocks as $slug => $block): ?>
            <?php
            $x = (float) ($block['marker']['x'] ?? 50);
            $y = (float) ($block['marker']['y'] ?? 50);
            $name = (string) ($block['name'] ?? $slug);
            ?>
            <a class="imap-marker"
               href="<?= Helper::e(BlockMap::blockUrl($slug)) ?>"
               style="left: <?= Helper::e((string) $x) ?>%; top: <?= Helper::e((string) $y) ?>%;"
               title="<?= Helper::e('Open Block ' . $name) ?>">
                <span class="imap-marker-pin" aria-hidden="true"></span>
                <span class="imap-marker-label"><?= Helper::e($name) ?></span>
            </a>
        <?php endforeach; ?>
        </div>
    </div>

    <div class="imap-legend" aria-label="All blocks">
        <?php foreach ($blocks as $slug => $block): ?>
            <a href="<?= Helper::e(BlockMap::blockUrl($slug)) ?>">
                <i class="bi bi-geo-alt-fill" aria-hidden="true"></i>
                <?= Helper::e((string) ($block['name'] ?? $slug)) ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>
