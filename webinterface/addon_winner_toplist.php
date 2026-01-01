<?PHP
require_once('_preload.php');

try {
	require_once('_nav.php');
	require_once('../other/load_addons_config.php');
	$addons_config = load_addons_config($mysqlcon,$lang,$cfg,$dbname);

	if ($mysqlcon->exec("INSERT INTO `$dbname`.`csrf_token` (`token`,`timestamp`,`sessionid`) VALUES ('$csrf_token','".time()."','".session_id()."')") === false) {
		$err_msg = print_r($mysqlcon->errorInfo(), true);
		$err_lvl = 3;
	}

	if (($db_csrf = $mysqlcon->query("SELECT * FROM `$dbname`.`csrf_token` WHERE `sessionid`='".session_id()."'")->fetchALL(PDO::FETCH_UNIQUE|PDO::FETCH_ASSOC)) === false) {
		$err_msg = print_r($mysqlcon->errorInfo(), true);
		$err_lvl = 3;
	}

	if(($groupslist = $mysqlcon->query("SELECT * FROM `$dbname`.`groups` ORDER BY `sortid`,`sgidname` ASC")->fetchAll(PDO::FETCH_UNIQUE|PDO::FETCH_ASSOC)) === false) {
		$err_msg = print_r($mysqlcon->errorInfo(), true);
		$err_lvl = 3;
	}

	$winner_toplist_active = 0;

	if (isset($_POST['update']) && isset($db_csrf[$_POST['csrf_token']])) {
		if (isset($_POST['winner_toplist_active'])) $winner_toplist_active = 1;

		if(!isset($err_lvl) || $err_lvl < 3) {
			$sqlexec = $mysqlcon->prepare("INSERT INTO `$dbname`.`addons_config` (`param`,`value`) VALUES ('winner_toplist_active', :winner_toplist_active), ('winner_toplist_day_week', :winner_toplist_day_week), ('winner_toplist_time_week', :winner_toplist_time_week), ('winner_toplist_day_month', :winner_toplist_day_month), ('winner_toplist_time_month', :winner_toplist_time_month), ('winner_toplist_group_mode', :winner_toplist_group_mode), ('winner_toplist_group_week', :winner_toplist_group_week), ('winner_toplist_group_month', :winner_toplist_group_month) ON DUPLICATE KEY UPDATE `value`=VALUES(`value`); DELETE FROM `$dbname`.`csrf_token` WHERE `token`= :csrf_token");
			$sqlexec->bindParam(':winner_toplist_active', $winner_toplist_active, PDO::PARAM_STR);
			$sqlexec->bindParam(':winner_toplist_day_week', $_POST['winner_toplist_day_week'], PDO::PARAM_STR);
			$sqlexec->bindParam(':winner_toplist_time_week', $_POST['winner_toplist_time_week'], PDO::PARAM_STR);
			$sqlexec->bindParam(':winner_toplist_day_month', $_POST['winner_toplist_day_month'], PDO::PARAM_STR);
			$sqlexec->bindParam(':winner_toplist_time_month', $_POST['winner_toplist_time_month'], PDO::PARAM_STR);
			$sqlexec->bindParam(':winner_toplist_group_mode', $_POST['winner_toplist_group_mode'], PDO::PARAM_STR);
			$sqlexec->bindParam(':winner_toplist_group_week', $_POST['winner_toplist_group_week'], PDO::PARAM_STR);
			$sqlexec->bindParam(':winner_toplist_group_month', $_POST['winner_toplist_group_month'], PDO::PARAM_STR);
			$sqlexec->bindParam(':csrf_token', $_POST['csrf_token']);
			$sqlexec->execute();
			
			if ($sqlexec->errorCode() != 0) {
				$err_msg = print_r($sqlexec->errorInfo(), true);
				$err_lvl = 3;
			} else {
				$err_msg = $lang['wisvsuc']." ".sprintf($lang['wisvres'], '<span class="item-margin"><form class="btn-group" name="restart" action="bot.php" method="POST"><input type="hidden" name="csrf_token" value="'.$csrf_token.'"><button type="submit" class="btn btn-primary" name="restart"><i class="fas fa-sync"></i><span class="item-margin">'.$lang['wibot7'].'</span></button></form></span>');
				$err_lvl = NULL;
			}
		}

		$addons_config['winner_toplist_active']['value'] = $winner_toplist_active;
		$addons_config['winner_toplist_day_week']['value'] = $_POST['winner_toplist_day_week'];
		$addons_config['winner_toplist_time_week']['value'] = $_POST['winner_toplist_time_week'];
		$addons_config['winner_toplist_day_month']['value'] = $_POST['winner_toplist_day_month'];
		$addons_config['winner_toplist_time_month']['value'] = $_POST['winner_toplist_time_month'];
		$addons_config['winner_toplist_group_mode']['value'] = $_POST['winner_toplist_group_mode'];
		$addons_config['winner_toplist_group_week']['value'] = $_POST['winner_toplist_group_week'];
		$addons_config['winner_toplist_group_month']['value'] = $_POST['winner_toplist_group_month'];
	} elseif(isset($_POST['update'])) {
		echo '<div class="alert alert-danger alert-dismissible">',$lang['errcsrf'],'</div>';
		rem_session_ts3();
		exit;
	}
	
	list($hour_week, $minute_week) = explode(':', $addons_config['winner_toplist_time_week']['value']);
	list($hour_month, $minute_month) = explode(':', $addons_config['winner_toplist_time_month']['value']);
	?>
			<div id="page-wrapper" class="webinterface_addon_winner_toplist">
	<?PHP if(isset($err_msg)) error_handling($err_msg, $err_lvl); ?>
				<div class="container-fluid">
					<div class="row">
						<div class="col-lg-12">
							<h1 class="page-header">
								<?php echo $lang['addonwitopl']; ?>
							</h1>
						</div>
					</div>
					<form class="form-horizontal" name="update" method="POST">
					<input type="hidden" name="csrf_token" value="<?PHP echo $csrf_token; ?>">
					<div class="form-horizontal">
						<div class="row">
							<div class="col-md-12">
								<div class="form-group">
									<label class="col-sm-12 pointer" data-toggle="modal" data-target="#addonwitopldesc"><?php echo $lang['wihladm0']; ?><i class="help-hover fas fa-question-circle"></i></label>
									<div class="panel-body">
									</div>
								</div>
							</div>
							<div class="col-md-3">
							</div>
							<div class="col-md-6">
								<div class="form-group">
									<label class="col-sm-4 control-label" data-toggle="modal" data-target="#stag0014"><?php echo $lang['stag0013']; ?><i class="help-hover fas fa-question-circle"></i></label>
									<div class="col-sm-8">
									<?PHP if ($addons_config['winner_toplist_active']['value'] == '1') {
										echo '<input class="switch-animate" type="checkbox" checked data-size="mini" name="winner_toplist_active" value="',$winner_toplist_active,'">';
									} else {
										echo '<input class="switch-animate" type="checkbox" data-size="mini" name="winner_toplist_active" value="',$winner_toplist_active,'">';
									} ?>
									</div>
								</div>
								<div class="row">&nbsp;</div>
								<div class="row">&nbsp;</div>
							</div>
							<div class="col-md-3">
							</div>

							<div class="col-md-12">
								<div class="panel panel-default">
									<div class="panel-body">
										<div class="form-group">
											<label class="col-sm-2 control-label" data-toggle="modal" data-target="#addonchtoplzz001d"><?php echo $lang['addonchtoplzz001'],' (',$lang['addonchtoplzz006'],')'; ?><i class="help-hover fas fa-question-circle"></i></label>
											<div class="col-sm-10">
												<select class="selectpicker show-tick form-control" name="winner_toplist_day_week">
												<?PHP
												echo '<option value="1"'; if($addons_config['winner_toplist_day_week']['value']=="1") echo ' selected="selected"'; echo '>',$lang['daymonday'],'</option>';
												echo '<option value="2"'; if($addons_config['winner_toplist_day_week']['value']=="2") echo ' selected="selected"'; echo '>',$lang['daytuesday'],'</option>';
												echo '<option value="3"'; if($addons_config['winner_toplist_day_week']['value']=="3") echo ' selected="selected"'; echo '>',$lang['daywednesday'],'</option>';
												echo '<option value="4"'; if($addons_config['winner_toplist_day_week']['value']=="4") echo ' selected="selected"'; echo '>',$lang['daythursday'],'</option>';
												echo '<option value="5"'; if($addons_config['winner_toplist_day_week']['value']=="5") echo ' selected="selected"'; echo '>',$lang['dayfriday'],'</option>';
												echo '<option value="6"'; if($addons_config['winner_toplist_day_week']['value']=="6") echo ' selected="selected"'; echo '>',$lang['daysaturday'],'</option>';
												echo '<option value="7"'; if($addons_config['winner_toplist_day_week']['value']=="7") echo ' selected="selected"'; echo '>',$lang['daysunday'],'</option>';
												?>
												</select>
											</div>
										</div>
										<div class="form-group">
											<label class="col-sm-2 control-label" data-toggle="modal" data-target="#addonchtoplzz002d"><?php echo $lang['addonchtoplzz002'],' (',$lang['addonchtoplzz006'],')'; ?><i class="help-hover fas fa-question-circle"></i></label>
											<div class="col-sm-5">
												<input type="text" class="form-control" name="winner_toplist_time_week_hours" data-time-group="week" title="<?php echo $lang['addonchdescdesc31'].': '; ?>" value="<?php echo $hour_week; ?>">
												<script>
												$("input[name='winner_toplist_time_week_hours']").TouchSpin({
													min: 0,
													max: 23,
													verticalbuttons: true,
													prefix: 'Hour:'
												});
												</script>
											</div>
											<div class="col-sm-5">
												<input type="text" class="form-control" name="winner_toplist_time_week_minutes" data-time-group="week" title="<?php echo $lang['addonchdescdesc31'].': '; ?>" value="<?php echo $minute_week; ?>">
												<script>
												$("input[name='winner_toplist_time_week_minutes']").TouchSpin({
													min: 0,
													max: 59,
													verticalbuttons: true,
													prefix: 'Minutes:'
												});
												</script>
											</div>
											<input type="hidden" id="winner_time_week" name="winner_toplist_time_week" data-time-group="week" value="12:00">
										</div>
									</div>
								</div>
								<div class="panel panel-default">
									<div class="panel-body">
										<div class="form-group">
											<label class="col-sm-2 control-label" data-toggle="modal" data-target="#addonchtoplzz003d"><?php echo $lang['addonchtoplzz001'],' (',$lang['addonchtoplzz007'],')'; ?><i class="help-hover fas fa-question-circle"></i></label>
											<div class="col-sm-10">
												<input type="text" class="form-control" name="winner_toplist_day_month" title="<?php echo $lang['addonchdescdesc31'].': '.date('Y-m-d H:i:s', $addons_config['channelinfo_toplist_lastupdate']['value']); ?>" value="<?php echo $addons_config['winner_toplist_day_month']['value']; ?>">
												<script>
												$("input[name='winner_toplist_day_month']").TouchSpin({
													min: 0,
													max: 31,
													verticalbuttons: true,
													prefix: 'Day:'
												});
												</script>
											</div>
										</div>
										<div class="form-group">
											<label class="col-sm-2 control-label" data-toggle="modal" data-target="#addonchtoplzz004d"><?php echo $lang['addonchtoplzz002'],' (',$lang['addonchtoplzz007'],')'; ?><i class="help-hover fas fa-question-circle"></i></label>
											<div class="col-sm-5">
												<input type="text" class="form-control" name="winner_toplist_time_month_hours" data-time-group="month" title="<?php echo $lang['addonchdescdesc31'].': '; ?>" value="<?php echo $hour_month; ?>">
												<script>
												$("input[name='winner_toplist_time_month_hours']").TouchSpin({
													min: 0,
													max: 23,
													verticalbuttons: true,
													prefix: 'Hour:'
												});
												</script>
											</div>
											<div class="col-sm-5">
												<input type="text" class="form-control" name="winner_toplist_time_month_minutes" data-time-group="month" title="<?php echo $lang['addonchdescdesc31'].': '; ?>" value="<?php echo $minute_month; ?>">
												<script>
												$("input[name='winner_toplist_time_month_minutes']").TouchSpin({
													min: 0,
													max: 59,
													verticalbuttons: true,
													prefix: 'Minutes:'
												});
												</script>
											</div>
											<input type="hidden" id="winner_time_month" name="winner_toplist_time_month" data-time-group="month" value="12:00">
										</div>
									</div>
								</div>
								<div class="panel-body">
									<div class="form-group">
										<label class="col-sm-2 control-label" data-toggle="modal" data-target="#addonchtoplzz005d"><?php echo $lang['addonchtoplzz005'],' ',$lang['wigrpt2']; ?><i class="help-hover fas fa-question-circle"></i></label>
										<div class="col-sm-10">
											<select class="selectpicker show-tick form-control" id="basic" name="winner_toplist_group_mode">
											<?PHP
											echo '<option data-icon="fas fa-ban fa w" value="1"'; if($addons_config['winner_toplist_group_mode']['value']=="0") echo ' selected="selected"'; echo '>',$lang['wihladmrs0'],'</option>';
											echo '<option data-divider="true"></option>';
											echo '<option data-icon="fas fa-user-minus" value="2"'; if($addons_config['winner_toplist_group_mode']['value']=="1") echo ' selected="selected"'; echo '>',$lang['addonchtoplzz0051'],'</option>';
											echo '<option data-icon="fas fa-user-plus" value="3"'; if($addons_config['winner_toplist_group_mode']['value']=="2") echo ' selected="selected"'; echo '>',$lang['addonchtoplzz0052'],'</option>';
											?>
											</select>
										</div>
									</div>
									<div class="form-group">
										<label class="col-sm-2 control-label" data-toggle="modal" data-target="#addonchtoplzz006d"><?php echo $lang['wigrpt2'],' (',$lang['addonchtoplzz006'],')'; ?><i class="help-hover fas fa-question-circle"></i></label>
										<div class="col-sm-10">
											<select class="selectpicker form-control" data-dropup-auto="false" data-live-search="true" data-actions-box="true" name="winner_toplist_group_week">
											<?PHP
											foreach ($groupslist as $groupID => $groupParam) {
												if (!empty($addons_config['winner_toplist_group_week']['value']) && $groupID==$addons_config['winner_toplist_group_week']['value']) $selected=" selected"; else $selected="";
												if (isset($groupParam['iconid']) && $groupParam['iconid'] != 0) $iconid=$groupParam['iconid']."."; else $iconid="placeholder.png";
												if ($groupParam['type'] == 0) $disabled=" disabled"; else $disabled="";
												if ($groupParam['type'] == 0) $grouptype=" [TEMPLATE GROUP]"; else $grouptype="";
												if ($groupParam['type'] == 2) $grouptype=" [QUERY GROUP]";
												if ($groupID != 0) {
												echo '<option data-content="<span class=\'item-margin\'><img src=\'../tsicons/',$iconid,$groupParam['ext'],'\' width=\'16\' height=\'16\'></span><span class=\'item-margin\'>',$groupParam['sgidname'],'</span><span class=\'text-muted small item-margin\'>SGID:&nbsp;',$groupID,$grouptype,'</span>" value="',$groupID,'"',$selected,$disabled,'></option>';
												}
											}
											?>
											</select>
										</div>
									</div>
									<div class="form-group">
										<label class="col-sm-2 control-label" data-toggle="modal" data-target="#addonchtoplzz007d"><?php echo $lang['wigrpt2'],' (',$lang['addonchtoplzz007'],')'; ?><i class="help-hover fas fa-question-circle"></i></label>
										<div class="col-sm-10">
											<select class="selectpicker form-control" data-dropup-auto="false" data-live-search="true" data-actions-box="true" name="winner_toplist_group_month">
											<?PHP
											foreach ($groupslist as $groupID => $groupParam) {
												if (!empty($addons_config['winner_toplist_group_month']['value']) && $groupID==$addons_config['winner_toplist_group_month']['value']) $selected=" selected"; else $selected="";
												if (isset($groupParam['iconid']) && $groupParam['iconid'] != 0) $iconid=$groupParam['iconid']."."; else $iconid="placeholder.png";
												if ($groupParam['type'] == 0) $disabled=" disabled"; else $disabled="";
												if ($groupParam['type'] == 0) $grouptype=" [TEMPLATE GROUP]"; else $grouptype="";
												if ($groupParam['type'] == 2) $grouptype=" [QUERY GROUP]";
												if ($groupID != 0) {
												echo '<option data-content="<span class=\'item-margin\'><img src=\'../tsicons/',$iconid,$groupParam['ext'],'\' width=\'16\' height=\'16\'></span><span class=\'item-margin\'>',$groupParam['sgidname'],'</span><span class=\'text-muted small item-margin\'>SGID:&nbsp;',$groupID,$grouptype,'</span>" value="',$groupID,'"',$selected,$disabled,'></option>';
												}
											}
											?>
											</select>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="row">&nbsp;</div>
						<div class="row">
							<div class="text-center">
								<button type="submit" class="btn btn-primary" name="update"><i class="fas fa-save"></i><span class="item-margin"><?php echo $lang['wisvconf']; ?></span></button>
							</div>
						</div>
						<div class="row">&nbsp;</div>
					</div>
					</form>
				</div>
			</div>
		</div>
	<div class="modal fade" id="addonwitopldesc" tabindex="-1">
	  <div class="modal-dialog">
		<div class="modal-content">
		  <div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title"><?php echo $lang['addonwitopl']; ?></h4>
		  </div>
		  <div class="modal-body">
			<?php echo $lang['addonwitopldesc']; ?>
		  </div>
		  <div class="modal-footer">
			<button type="button" class="btn btn-default" data-dismiss="modal"><?PHP echo $lang['stnv0002']; ?></button>
		  </div>
		</div>
	  </div>
	</div>
	<div class="modal fade" id="addonchtoplzz001d" tabindex="-1">
	  <div class="modal-dialog">
		<div class="modal-content">
		  <div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title"><?php echo $lang['addonchtoplzz001'],' (',$lang['addonchtoplzz006'],')'; ?></h4>
		  </div>
		  <div class="modal-body">
			<?php echo $lang['addonchtoplzz001d']; ?>
		  </div>
		  <div class="modal-footer">
			<button type="button" class="btn btn-default" data-dismiss="modal"><?PHP echo $lang['stnv0002']; ?></button>
		  </div>
		</div>
	  </div>
	</div>
	<div class="modal fade" id="addonchtoplzz002d" tabindex="-1">
	  <div class="modal-dialog">
		<div class="modal-content">
		  <div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title"><?php echo $lang['addonchtoplzz002'],' (',$lang['addonchtoplzz006'],')'; ?></h4>
		  </div>
		  <div class="modal-body">
			<?php echo $lang['addonchtoplzz002d']; ?>
		  </div>
		  <div class="modal-footer">
			<button type="button" class="btn btn-default" data-dismiss="modal"><?PHP echo $lang['stnv0002']; ?></button>
		  </div>
		</div>
	  </div>
	</div>
	<div class="modal fade" id="addonchtoplzz003d" tabindex="-1">
	  <div class="modal-dialog">
		<div class="modal-content">
		  <div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title"><?php echo $lang['addonchtoplzz001'],' (',$lang['addonchtoplzz007'],')'; ?></h4>
		  </div>
		  <div class="modal-body">
			<?php echo $lang['addonchtoplzz003d']; ?>
		  </div>
		  <div class="modal-footer">
			<button type="button" class="btn btn-default" data-dismiss="modal"><?PHP echo $lang['stnv0002']; ?></button>
		  </div>
		</div>
	  </div>
	</div>
	<div class="modal fade" id="addonchtoplzz004d" tabindex="-1">
	  <div class="modal-dialog">
		<div class="modal-content">
		  <div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title"><?php echo $lang['addonchtoplzz002'],' (',$lang['addonchtoplzz007'],')'; ?></h4>
		  </div>
		  <div class="modal-body">
			<?php echo $lang['addonchtoplzz004d']; ?>
		  </div>
		  <div class="modal-footer">
			<button type="button" class="btn btn-default" data-dismiss="modal"><?PHP echo $lang['stnv0002']; ?></button>
		  </div>
		</div>
	  </div>
	</div>
	<div class="modal fade" id="addonchtoplzz005d" tabindex="-1">
	  <div class="modal-dialog">
		<div class="modal-content">
		  <div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title"><?php echo $lang['addonchtoplzz005'],' ',$lang['wigrpt2']; ?></h4>
		  </div>
		  <div class="modal-body">
			<?php echo $lang['addonchtoplzz005d']; ?>
		  </div>
		  <div class="modal-footer">
			<button type="button" class="btn btn-default" data-dismiss="modal"><?PHP echo $lang['stnv0002']; ?></button>
		  </div>
		</div>
	  </div>
	</div>
	<div class="modal fade" id="addonchtoplzz006d" tabindex="-1">
	  <div class="modal-dialog">
		<div class="modal-content">
		  <div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title"><?php echo $lang['wigrpt2'],' (',$lang['addonchtoplzz006'],')'; ?></h4>
		  </div>
		  <div class="modal-body">
			<?php echo $lang['addonchtoplzz006d']; ?>
		  </div>
		  <div class="modal-footer">
			<button type="button" class="btn btn-default" data-dismiss="modal"><?PHP echo $lang['stnv0002']; ?></button>
		  </div>
		</div>
	  </div>
	</div>
	<div class="modal fade" id="addonchtoplzz007d" tabindex="-1">
	  <div class="modal-dialog">
		<div class="modal-content">
		  <div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title"><?php echo $lang['wigrpt2'],' (',$lang['addonchtoplzz007'],')'; ?></h4>
		  </div>
		  <div class="modal-body">
			<?php echo $lang['addonchtoplzz007d']; ?>
		  </div>
		  <div class="modal-footer">
			<button type="button" class="btn btn-default" data-dismiss="modal"><?PHP echo $lang['stnv0002']; ?></button>
		  </div>
		</div>
	  </div>
	</div>
	<script>
	$('form[data-toggle="validator"]').validator({
		custom: {
			pattern: function ($el) {
				var pattern = new RegExp($el.data('pattern'));
				return pattern.test($el.val());
			}
		},
		delay: 100,
		errors: {
			pattern: "There should be an error in your value, please check all could be right!"
		}
	});
	$("[name='winner_toplist_active']").bootstrapSwitch();

	function pad2(n) {
	  n = parseInt(n, 10) || 0;
	  return String(n).padStart(2, '0');
	}

	function bindAllTimePickers() {
	  $('[data-time-group]').each(function () {
		var group = $(this).data('time-group');
		var $hour = $('input[name$="_hours"][data-time-group="' + group + '"]');
		var $minute = $('input[name$="_minutes"][data-time-group="' + group + '"]');
		var $hidden = $('input[type="hidden"][data-time-group="' + group + '"]');

		function sync() {
		  var h = pad2($hour.val());
		  var m = pad2($minute.val());
		  $hour.val(h);
		  $minute.val(m);
		  $hidden.val(h + ':' + m);
		}

		$hour.add($minute).on('change keyup touchspin.on.stopspin', sync);
		sync();
	  });
	}

	bindAllTimePickers();
	</script>
	</body>
	</html>
<?PHP
} catch(Throwable $ex) { }
?>