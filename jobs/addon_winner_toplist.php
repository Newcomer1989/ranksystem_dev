<?php
function addon_winner_toplist(&$addons_config, $ts3, $mysqlcon, $cfg, $dbname, $lang, &$db_cache) {
	$starttime = microtime(true);
	$sqlexec = '';
	$nowtime = time();

	if (!isset($addons_config['winner_toplist_active']['value']) || $addons_config['winner_toplist_active']['value'] !== '1') return;

	$weekDay = isset($addons_config['winner_toplist_day_week']['value']) ? (int)$addons_config['winner_toplist_day_week']['value'] : 0;
	$weekTime = isset($addons_config['winner_toplist_time_week']['value']) ? trim($addons_config['winner_toplist_time_week']['value']) : '';

	$monthDay = isset($addons_config['winner_toplist_day_month']['value']) ? (int)$addons_config['winner_toplist_day_month']['value'] : 0;
	$monthTime = isset($addons_config['winner_toplist_time_month']['value']) ? trim($addons_config['winner_toplist_time_month']['value']) : '';

	$periods = [];

	$weekConfigured = ($weekDay >= 1 && $weekDay <= 7 && is_valid_hhmm($weekTime));
	$monthConfigured = ($monthDay >= 1 && $monthDay <= 31 && is_valid_hhmm($monthTime));

	if ($weekConfigured) {
		$weekCurrentTs  = compute_weekly_timestamp($weekDay, $weekTime, 0);
		$weekPreviousTs = compute_weekly_timestamp($weekDay, $weekTime, -1);
		$weekNextTs	 = compute_weekly_timestamp($weekDay, $weekTime, 1);

		$periods[] = [
			'key' => 'week_previous',
			'award' => 1,
			'periodTimestamp' => $weekPreviousTs,
			'nextTimestamp' => $weekCurrentTs,
			'lengthSnapshots' => 28,
			'maxSeconds' => 691200,
			'useLiveStats' => false,
			'shouldProcess' => ($nowtime >= $weekPreviousTs)
		];

		if ($nowtime >= $weekCurrentTs && $nowtime <= ($weekCurrentTs + 3600)) {
			$periods[] = [
				'key' => 'week_current',
				'award' => 1,
				'periodTimestamp' => $weekCurrentTs,
				'nextTimestamp' => $weekNextTs,
				'lengthSnapshots' => 28,
				'maxSeconds' => 691200,
				'useLiveStats' => true,
				'shouldProcess' => true
			];
		}
	}

	if ($monthConfigured) {
		$monthCurrentTs = compute_monthly_timestamp($monthDay, $monthTime, 0);
		$monthPreviousTs = compute_monthly_timestamp($monthDay, $monthTime, -1);
		$monthNextTs = compute_monthly_timestamp($monthDay, $monthTime, 1);

		$periods[] = [
			'key' => 'month_previous',
			'award' => 2,
			'periodTimestamp' => $monthPreviousTs,
			'nextTimestamp' => $monthCurrentTs,
			'lengthSnapshots' => 120,
			'maxSeconds' => 2764800,
			'useLiveStats' => false,
			'shouldProcess' => ($nowtime >= $monthPreviousTs)
		];

		if ($nowtime >= $monthCurrentTs && $nowtime <= ($monthCurrentTs + 3600)) {
			$periods[] = [
				'key' => 'month_current',
				'award' => 2,
				'periodTimestamp' => $monthCurrentTs,
				'nextTimestamp' => $monthNextTs,
				'lengthSnapshots' => 120,
				'maxSeconds' => 2764800,
				'useLiveStats' => true,
				'shouldProcess' => true
			];
		}
	}

	$periods = array_values(array_filter($periods, function ($period) {
		return !empty($period['shouldProcess']);
	}));

	if (empty($periods)) return;

	$logInfo = [];
	foreach ($periods as $p) {
		$logInfo[] = sprintf('%s@%s', $p['key'], $p['periodTimestamp']);
	}
	enter_logfile(6, "addon_winner_toplist periods: " . implode(', ', $logInfo));

	$activePeriods = [];
	foreach ($periods as $period) {
		$rangeEnd = null;
		if (isset($period['nextTimestamp'])) {
			$rangeEnd = $period['nextTimestamp'];
		} elseif ($period['key'] === 'week_previous' || $period['key'] === 'week_current') {
			$rangeEnd = $period['periodTimestamp'] + 604800;
		} else {
			$rangeEnd = $period['periodTimestamp'] + 2678400;
		}

		$alreadyDone = period_already_processed($mysqlcon, $dbname, $period['award'], $period['periodTimestamp'], $rangeEnd);
		if ($alreadyDone) continue;

		$activePeriods[] = $period;
	}

	if (empty($activePeriods)) return;

	$byCldbid = [];
	foreach ($db_cache['all_user'] as $uuid => $userstats) {
		if (!empty($userstats['cldbid'])) {
			$byCldbid[(int)$userstats['cldbid']] = ['uuid' => $uuid, 'userstats' => $userstats];
		}
	}

	if (empty($byCldbid)) return;

	$requiredSnapshotIds = [];
	foreach ($activePeriods as $idx => $period) {
		$offset = $period['useLiveStats'] ? 0 : compute_snapshot_offset($period['periodTimestamp'], $nowtime);
		$activePeriods[$idx]['snapshotOffset'] = $offset;

		$baseId = shift_snapshot_id((int)$db_cache['job_check']['last_snapshot_id']['timestamp'], $offset);
		$agoId  = shift_snapshot_id($baseId, $period['lengthSnapshots']);

		$activePeriods[$idx]['baseSnapshotId'] = $baseId;
		$activePeriods[$idx]['agoSnapshotId']  = $agoId;

		if ($period['useLiveStats']) {
			$requiredSnapshotIds[] = $agoId;
		} else {
			$requiredSnapshotIds[] = $baseId;
			$requiredSnapshotIds[] = $agoId;
		}
	}

	$requiredSnapshotIds = array_values(array_unique($requiredSnapshotIds));

	if (empty($requiredSnapshotIds)) return;

	$idPlaceholders = implode(',', $requiredSnapshotIds);
	$userdataRaw = $mysqlcon->query("SELECT `cldbid`,`id`,`count`,`idle` FROM `$dbname`.`user_snapshot` WHERE `id` IN ($idPlaceholders)")->fetchAll(PDO::FETCH_ASSOC);
	if ($userdataRaw === false) {
		enter_logfile(2, "calc_userstats 6:" . print_r($mysqlcon->errorInfo(), true));
		return;
	}

	$userdata = [];
	foreach ($userdataRaw as $row) {
		$cldbid = (int)$row['cldbid'];
		$sid = (int)$row['id'];
		if (!isset($userdata[$cldbid])) $userdata[$cldbid] = [];
		$userdata[$cldbid][$sid] = $row;
	}

	$allinsert = '';

	foreach ($activePeriods as $period) {
		enter_logfile(5,"period: ".print_r($period, true));
		enter_logfile(5,"periodsconf: " . implode(', ', $logInfo));
		$top = ['cldbid' => null, 'value' => -1, 'active' => -1, 'count' => -1, 'idle' => -1];

		foreach ($byCldbid as $cldbid => $info) {
			if ($period['useLiveStats']) {
				$currentCount = (int)$info['userstats']['count'];
				$currentIdle = (int)$info['userstats']['idle'];
			} else {
				if (!isset($userdata[$cldbid][$period['baseSnapshotId']])) continue;
				$currentCount = (int)$userdata[$cldbid][$period['baseSnapshotId']]['count'];
				$currentIdle = (int)$userdata[$cldbid][$period['baseSnapshotId']]['idle'];
			}

			if (!isset($userdata[$cldbid][$period['agoSnapshotId']])) continue;

			$agoCount = (int)$userdata[$cldbid][$period['agoSnapshotId']]['count'];
			$agoIdle = (int)$userdata[$cldbid][$period['agoSnapshotId']]['idle'];

			$countDiff = $currentCount - $agoCount;
			$idleDiff = $currentIdle - $agoIdle;
			$activeDiff = $countDiff - $idleDiff;

			if ($countDiff < 0 || $countDiff < $idleDiff || $countDiff > $period['maxSeconds']) $countDiff = 0;
			if ($idleDiff  < 0 || $countDiff < $idleDiff || $idleDiff  > $period['maxSeconds']) $idleDiff  = 0;
			if ($activeDiff< 0 || $countDiff < $idleDiff || $activeDiff> $period['maxSeconds']) $activeDiff= 0;

			if($cfg['rankup_time_assess_mode']=="0") {
				$value=$countDiff;
			} else {
				$value=$activeDiff;
			}

			if ($value > $top['value']) {
				$top = [
					'cldbid' => $cldbid,
					'uuid'   => $info['uuid'],
					'value'  => $value,
					'active' => $activeDiff,
					'count'  => $countDiff,
					'idle'   => $idleDiff
				];
			}
		}

		if ($top['cldbid'] !== null) {
			$userName = $byCldbid[$top['cldbid']]['userstats']['name'];
			if ($period['award'] === 1) {
				enter_logfile(5,sprintf("addon_winner_toplist: Winner of the Week (%s) has been chosen. It's user '%s' (unique Client-ID: '%s').", $period['key'], $userName, $byCldbid[$top['cldbid']]['uuid']));
			} else {
				enter_logfile(5,sprintf("addon_winner_toplist: Winner of the Month (%s) has been chosen. It's user '%s' (unique Client-ID: '%s').", $period['key'], $userName, $byCldbid[$top['cldbid']]['uuid']));
			}

			$allinsert .= "('{$byCldbid[$top['cldbid']]['uuid']}','{$userName}',{$period['periodTimestamp']},'{$period['award']}','{$top['count']}','{$top['idle']}'),";
		}
	}

	if ($allinsert != '') {
		$allinsert = substr($allinsert, 0, -1);
		$sqlexec .= "INSERT INTO `$dbname`.`addon_winner_toplist` (`uuid`,`name`,`timestamp`,`award`,`count`,`idle`) VALUES $allinsert ON DUPLICATE KEY UPDATE `uuid`=VALUES(`uuid`),`timestamp`=VALUES(`timestamp`),`award`=VALUES(`award`);\n";
	}

	enter_logfile(5,"addon_winner_toplist needs: ".(number_format(round((microtime(true) - $starttime), 5),5)));
	return($sqlexec);
}

function is_valid_hhmm($s) {
	if (!is_string($s) || !preg_match('/^\d{1,2}:\d{2}$/', $s)) return false;
	list($h, $m) = explode(':', $s, 2);
	$h = (int)$h; $m = (int)$m;

	return ($h >= 0 && $h <= 23 && $m >= 0 && $m <= 59);
}

function compute_weekly_timestamp($day, $time, $weekOffset = 0) {
	list($hh, $mm) = explode(':', $time, 2);
	$hh = (int)$hh; $mm = (int)$mm;

	$todayMidnight  = mktime(0, 0, 0, date('n'), date('j'), date('Y'));
	$todayIso = (int)date('N'); // 1..7
	$mondayMidnight = $todayMidnight - (($todayIso - 1) * 86400);

	$targetMidnight = $mondayMidnight + (($day - 1 + ($weekOffset * 7)) * 86400);
	return $targetMidnight + ($hh * 3600) + ($mm * 60);
}

function compute_monthly_timestamp($day, $time, $monthOffset = 0) {
	list($hh, $mm) = explode(':', $time, 2);
	$hh = (int)$hh; $mm = (int)$mm;

	$baseDate = new DateTime('first day of this month');
	if ($monthOffset !== 0) {
		$baseDate->modify(($monthOffset > 0 ? '+' : '').$monthOffset.' month');
	}

	$year = (int)$baseDate->format('Y');
	$month = (int)$baseDate->format('n');
	$daysInMonth = (int)date('t', mktime(0, 0, 0, $month, 1, $year));
	if ($day > $daysInMonth) $day = $daysInMonth;

	return mktime($hh, $mm, 0, $month, $day, $year);
}

function shift_snapshot_id($id, $diff) {
	$id = (int)$id - (int)$diff;
	$id %= 121;
	if ($id < 1) $id += 121;
	return $id;
}

function compute_snapshot_offset($targetTimestamp, $nowtime) {
	if ($nowtime <= $targetTimestamp) return 0;
	return (int)floor(($nowtime - $targetTimestamp) / 21600);
}

function period_already_processed($mysqlcon, $dbname, $award, $startTs, $endTs) {
	$stmt = $mysqlcon->prepare("SELECT 1 FROM `$dbname`.`addon_winner_toplist` WHERE `award`=? AND `timestamp`=? LIMIT 1");
	$stmt->execute([$award, $startTs]);
	if ($stmt->fetchColumn()) return true;

	if ($endTs !== null) {
		$stmt = $mysqlcon->prepare("SELECT 1 FROM `$dbname`.`addon_winner_toplist` WHERE `award`=? AND `timestamp`>=? AND `timestamp`<? LIMIT 1");
		$stmt->execute([$award, $startTs, $endTs]);
		if ($stmt->fetchColumn()) return true;
	}

	return false;
}
?>