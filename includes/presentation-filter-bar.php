<?php
/**
 * Shared presentation filter bar
 * Expects: $filters, $actionUrl, optional $showMemberFilter, $members
 */
declare(strict_types=1);

$filters = $filters ?? [];
$actionUrl = $actionUrl ?? '';
$showMemberFilter = $showMemberFilter ?? false;
$members = $members ?? [];
$period = $filters['period'] ?? '';
$search = $filters['search'] ?? '';
$from = $filters['from'] ?? '';
$to = $filters['to'] ?? '';
$memberId = $filters['member_id'] ?? '';
?>
<form method="get" action="<?= Helper::e($actionUrl) ?>" class="filter-bar card panel-card mb-4">
    <div class="card-body">
        <div class="filter-bar-row">
            <div class="filter-field">
                <label class="filter-label" for="filterPeriod">Period</label>
                <select name="period" class="filter-control sv-select" id="filterPeriod">
                    <option value="">All time</option>
                    <option value="today" <?= $period === 'today' ? 'selected' : '' ?>>Today</option>
                    <option value="week" <?= $period === 'week' ? 'selected' : '' ?>>This Week</option>
                    <option value="month" <?= $period === 'month' ? 'selected' : '' ?>>This Month</option>
                    <option value="range" <?= $period === 'range' ? 'selected' : '' ?>>Date Range</option>
                </select>
            </div>
            <div class="filter-field range-fields <?= $period === 'range' ? '' : 'd-none' ?>" id="rangeFromWrap">
                <label class="filter-label">From</label>
                <input type="date" name="from" class="form-control filter-control" value="<?= Helper::e($from) ?>">
            </div>
            <div class="filter-field range-fields <?= $period === 'range' ? '' : 'd-none' ?>" id="rangeToWrap">
                <label class="filter-label">To</label>
                <input type="date" name="to" class="form-control filter-control" value="<?= Helper::e($to) ?>">
            </div>
            <?php if ($showMemberFilter): ?>
            <div class="filter-field">
                <label class="filter-label">Member</label>
                <select name="member_id" class="filter-control sv-select">
                    <option value="">All members</option>
                    <?php foreach ($members as $m): ?>
                        <option value="<?= (int) $m['id'] ?>" <?= (string) $memberId === (string) $m['id'] ? 'selected' : '' ?>>
                            <?= Helper::e($m['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="filter-field filter-field-grow">
                <label class="filter-label">Search</label>
                <input type="search" name="search" class="form-control filter-control"
                       placeholder="Search title, notes, or file name…" value="<?= Helper::e($search) ?>">
            </div>
            <div class="filter-actions">
                <span class="filter-label filter-label-spacer" aria-hidden="true">&nbsp;</span>
                <div class="filter-actions-btns">
                    <button type="submit" class="btn btn-primary filter-btn">Filter</button>
                    <a href="<?= Helper::e($actionUrl) ?>" class="btn btn-outline-secondary filter-btn">Reset</a>
                </div>
            </div>
        </div>
    </div>
</form>
