<?php
function addon_winner_toplist(&$addons_config, $ts3, $mysqlcon, $cfg, $dbname, $lang, &$db_cache) {
	$starttime = microtime(true);
    $sqlexec = '';
    $nowtime = time();

    if (!isset($addons_config['winner_toplist_active']['value']) || $addons_config['winner_toplist_active']['value'] !== '1') return;

    $weekago  = (int)$db_cache['job_check']['last_snapshot_id']['timestamp'] - 28;
    $monthago = (int)$db_cache['job_check']['last_snapshot_id']['timestamp'] - 120;
    if ($weekago < 1)  $weekago  += 121;
    if ($monthago < 1) $monthago += 121;

    $weekDay  = isset($addons_config['winner_toplist_day_week']['value']) ? (int)$addons_config['winner_toplist_day_week']['value'] : 0;
    $weekTime = isset($addons_config['winner_toplist_time_week']['value']) ? trim($addons_config['winner_toplist_time_week']['value']) : '';

    $monthDay  = isset($addons_config['winner_toplist_day_month']['value']) ? (int)$addons_config['winner_toplist_day_month']['value'] : 0;
    $monthTime = isset($addons_config['winner_toplist_time_month']['value']) ? trim($addons_config['winner_toplist_time_month']['value']) : '';

    $doWeek  = ($weekDay >= 1 && $weekDay <= 7 && is_valid_hhmm($weekTime)  && $nowtime >= compute_weekly_timestamp($weekDay, $weekTime));
    $doMonth = ($monthDay >= 1 && $monthDay <= 31 && is_valid_hhmm($monthTime) && $nowtime >= compute_monthly_timestamp($monthDay, $monthTime));

	if (!$doWeek && !$doMonth) return;

	$weekStartTs  = strtotime('monday this week 00:00:00');
	$monthStartTs = strtotime(date('Y-m-01 00:00:00'));

	if ($doWeek) {
		$stmt = $mysqlcon->prepare("SELECT 1 FROM `$dbname`.`addon_winner_toplist` WHERE `award`=1 AND `timestamp`>=? LIMIT 1");
		$stmt->execute([$weekStartTs]);
		if ($stmt->fetchColumn()) $doWeek = false;
	}
	if ($doMonth) {
		$stmt = $mysqlcon->prepare("SELECT 1 FROM `$dbname`.`addon_winner_toplist` WHERE `award`=2 AND `timestamp`>=? LIMIT 1");
		$stmt->execute([$monthStartTs]);
		if ($stmt->fetchColumn()) $doMonth = false;
	}

	enter_logfile(6,"addon_winner_toplist: doWeek:'{$doWeek}', doMonth:'{$doMonth}', weekDay:'{$weekDay}', monthDay:'{$monthDay}', weekTime:'{$weekTime}', monthTime:'{$monthTime}'");
    if (!$doWeek && !$doMonth) return;

    $userdata = $mysqlcon->query("SELECT `cldbid`,`id`,`count`,`idle` FROM `$dbname`.`user_snapshot` WHERE `id` IN ($weekago,$monthago)")->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);
    if ($userdata === false) {
        enter_logfile(2, "calc_userstats 6:" . print_r($mysqlcon->errorInfo(), true));
        return;
    }

    $byCldbid = [];
    foreach ($db_cache['all_user'] as $uuid => $userstats) {
        if (!empty($userstats['cldbid'])) {
            $byCldbid[(int)$userstats['cldbid']] = ['uuid' => $uuid, 'userstats' => $userstats];
        }
    }

    $topWeek = ['cldbid' => null, 'value' => -1, 'active' => -1, 'count' => -1, 'idle' => -1];
    $topMonth = ['cldbid' => null, 'value' => -1, 'active' => -1, 'count' => -1, 'idle' => -1];

    foreach ($userdata as $cldbid => $rows) {
		$cldbid = (int)$cldbid;
        if (!isset($byCldbid[$cldbid])) continue;

        $ids = array_column($rows, 'id');

        if ($doWeek) {
            $keyweek = array_search($weekago, $ids);
            if ($keyweek !== false && isset($rows[$keyweek]) && (int)$rows[$keyweek]['id'] === $weekago) {

                $count_week  = (int)$byCldbid[$cldbid]['userstats']['count'] - (int)$rows[$keyweek]['count'];
                $idle_week   = (int)$byCldbid[$cldbid]['userstats']['idle']  - (int)$rows[$keyweek]['idle'];
                $active_week = $count_week - $idle_week;

                if ($count_week < 0 || $count_week < $idle_week || $count_week > 691200) $count_week = 0;
                if ($idle_week  < 0 || $count_week < $idle_week || $idle_week  > 691200) $idle_week  = 0;
                if ($active_week< 0 || $count_week < $idle_week || $active_week> 691200) $active_week= 0;

				if($cfg['rankup_time_assess_mode']=="0") {
					$value_week=$count_week;
				} else {
					$value_week=$active_week;
				}
				if ($value_week > $topWeek['value'] || ($value_week === $topWeek['value'] && $cldbid < (int)$topWeek['cldbid'])) {
                    $topWeek = [
                        'cldbid' => $cldbid,
                        'uuid' => $byCldbid[$cldbid]['uuid'],
                        'value'  => $value_week,
                        'active' => $active_week,
                        'count'  => $count_week,
                        'idle'   => $idle_week
                    ];
                }
            }
        }

        if ($doMonth) {
            $keymonth = array_search($monthago, $ids);
            if ($keymonth !== false && isset($rows[$keymonth]) && (int)$rows[$keymonth]['id'] === $monthago) {

                $count_month  = (int)$byCldbid[$cldbid]['userstats']['count'] - (int)$rows[$keymonth]['count'];
                $idle_month   = (int)$byCldbid[$cldbid]['userstats']['idle']  - (int)$rows[$keymonth]['idle'];
                $active_month = $count_month - $idle_month;

                if ($idle_month  < 0 || $count_month < $idle_month || $idle_month  > 2764800) $idle_month  = 0;
                if ($count_month < 0 || $count_month < $idle_month || $count_month > 2764800) $count_month = 0;
                if ($active_month< 0 || $count_month < $idle_month || $active_month> 2764800) $active_month= 0;

				if($cfg['rankup_time_assess_mode']=="0") {
					$value_month=$count_month;
				} else {
					$value_month=$active_month;
				}
				if ($value_month > $topMonth['value'] || ($value_month === $topMonth['value'] && $cldbid < (int)$topMonth['cldbid'])) {
                    $topMonth = [
                        'cldbid' => $cldbid,
                        'uuid' => $byCldbid[$cldbid]['uuid'],
                        'value'  => $value_month,
                        'active' => $active_month,
                        'count'  => $count_month,
                        'idle'   => $idle_month
                    ];
                }
            }
        }
    }

	$allinsert = '';
    if ($doWeek && $topWeek['cldbid'] !== null) {
		enter_logfile(5,sprintf("addon_winner_toplist: Winner of the Week has been chosen. It's user '%s' (unique Client-ID: '%s').", $byCldbid[$topWeek['cldbid']]['userstats']['name'], $byCldbid[$topWeek['cldbid']]['uuid']));
		$allinsert .= "('{$byCldbid[$topWeek['cldbid']]['uuid']}','{$byCldbid[$topWeek['cldbid']]['userstats']['name']}',{$nowtime},'1','{$topWeek['count']}','{$topWeek['idle']}'),";
    }

    if ($doMonth && $topMonth['cldbid'] !== null) {
		enter_logfile(5,sprintf("addon_winner_toplist: Winner of the Month has been chosen. It's user '%s' (unique Client-ID: '%s').", $byCldbid[$topMonth['cldbid']]['userstats']['name'], $byCldbid[$topMonth['cldbid']]['uuid']));
		$allinsert .= "('{$byCldbid[$topMonth['cldbid']]['uuid']}','{$byCldbid[$topMonth['cldbid']]['userstats']['name']}',{$nowtime},'2','{$topMonth['count']}','{$topMonth['idle']}'),";
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

function compute_weekly_timestamp($day, $time) {
    list($hh, $mm) = explode(':', $time, 2);
    $hh = (int)$hh; $mm = (int)$mm;

    $todayMidnight  = mktime(0, 0, 0, date('n'), date('j'), date('Y'));
    $todayIso       = (int)date('N'); // 1..7
    $mondayMidnight = $todayMidnight - (($todayIso - 1) * 86400);

    $targetMidnight = $mondayMidnight + (($day - 1) * 86400);
    return $targetMidnight + ($hh * 3600) + ($mm * 60);
}

function compute_monthly_timestamp($day, $time) {
    list($hh, $mm) = explode(':', $time, 2);
    $hh = (int)$hh; $mm = (int)$mm;

    $year  = (int)date('Y');
    $month = (int)date('n');
    $daysInMonth = (int)date('t');
    if ($day > $daysInMonth) $day = $daysInMonth;

    return mktime($hh, $mm, 0, $month, $day, $year);
}
?>