<?php
/**
 * Render a block/PHC content section: plain text or table groups.
 * Expects: $sectionData (string|array)
 */
declare(strict_types=1);

$sectionData = $sectionData ?? '';

$renderTable = static function (array $columns, array $rows, bool $lastRowBold = false): void {
    echo '<div class="cmo-data-table-wrap"><table class="cmo-staff-table">';
    echo '<thead><tr>';
    foreach ($columns as $col) {
        echo '<th>' . Helper::e((string) $col) . '</th>';
    }
    echo '</tr></thead><tbody>';
    $count = count($rows);
    foreach ($rows as $i => $row) {
        $cells = array_values(is_array($row) ? $row : [$row]);
        $label = strtolower(trim((string) ($cells[0] ?? '')));
        $isTotal = $lastRowBold && ($i === $count - 1 || $label === 'total');
        echo $isTotal ? '<tr class="cmo-row-total">' : '<tr>';
        foreach ($cells as $cell) {
            echo '<td>' . Helper::e((string) $cell) . '</td>';
        }
        echo '</tr>';
    }
    echo '</tbody></table></div>';
};

if ($sectionData === null || $sectionData === '' || $sectionData === []) {
    echo '<div class="cmo-empty">Content will appear here once provided by CMO Jammu.</div>';
    return;
}

if (is_array($sectionData) && isset($sectionData['groups'])) {
    if (!empty($sectionData['note'])) {
        echo '<p>' . nl2br(Helper::e((string) $sectionData['note'])) . '</p>';
    }
    foreach ($sectionData['groups'] as $group) {
        if (!is_array($group)) {
            continue;
        }
        if (!empty($group['title'])) {
            echo '<h3 class="cmo-group-title">' . Helper::e((string) $group['title']) . '</h3>';
        }
        $cols = $group['columns'] ?? ['Category of the post', 'Sanctioned Strength', 'In-Position', 'Vacancy'];
        $renderTable($cols, $group['rows'] ?? [], true);
    }
    return;
}

if (is_array($sectionData) && isset($sectionData['sections'])) {
    if (!empty($sectionData['note'])) {
        echo '<p>' . nl2br(Helper::e((string) $sectionData['note'])) . '</p>';
    }
    foreach ($sectionData['sections'] as $section) {
        if (!is_array($section)) {
            continue;
        }
        if (!empty($section['title'])) {
            echo '<h3 class="cmo-group-title">' . Helper::e((string) $section['title']) . '</h3>';
        }
        if (!empty($section['text'])) {
            echo '<p>' . nl2br(Helper::e((string) $section['text'])) . '</p>';
        }
        if (!empty($section['columns'])) {
            $renderTable($section['columns'], $section['rows'] ?? []);
        }
    }
    return;
}

if (is_array($sectionData) && isset($sectionData['columns'])) {
    if (!empty($sectionData['note'])) {
        echo '<p>' . nl2br(Helper::e((string) $sectionData['note'])) . '</p>';
    }
    $renderTable($sectionData['columns'], $sectionData['rows'] ?? []);
    return;
}

echo '<p>' . nl2br(Helper::e((string) $sectionData)) . '</p>';
