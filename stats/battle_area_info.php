<?PHP
session_start();
$starttime = microtime(true);

require_once('../other/config.php');
require_once('../ts3_lib/TeamSpeak3.php');
require_once('../lang.php');
require_once('../other/session.php');

if(!isset($_SESSION['tsuid'])) {
	$hpclientip = ip2long($_SERVER['REMOTE_ADDR']);
	set_session_ts3($hpclientip, $ts['voice'], $mysqlcon, $dbname);
}
require_once('nav.php');
?>
        <div id="page-wrapper">
<?PHP if(isset($err_msg)) error_handling($err_msg, 3); ?>
            <div class="container-fluid">

                <!-- Page Heading -->
                <div class="row">
                    <div class="col-lg-12">
                        <h1 class="page-header">
                            Battle Area
                            <div class="btn-group">
                            <a href="#myModal3" data-toggle="modal" class="btn btn-primary">
                                <span class="glyphicon glyphicon-info-sign" aria-hidden="true"></span>
                            </a>
                        </div>
                        </h1>
                    </div>
                    <div class="row">
                        <div class="col-lg-3 col-md-6">
                            <div class="panel panel-info">
                                <div class="panel-heading">
                                    <center><img src="../icons/BattleSite/Main_Grey.png" class="img-responsive"></center>
                                    <div class="clearfix"></div>
                                </div>
                                <a href="battle_area.php">
                                    <div class="panel-footer">
                                        <span class="pull-left">Main Site</span>
                                        <span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
                                        <div class="clearfix"></div>
                                    </div>
                                </a>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="panel panel-success">
                                <div class="panel-heading">
                                    <center><img src="../icons/BattleSite/Top10_Grey.png" class="img-responsive"></center>
                                    <div class="clearfix"></div>
                                </div>
                                <a href="battle_area_top.php">
                                    <div class="panel-footer">
                                        <span class="pull-left">Top Battlers</span>
                                        <span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
                                        <div class="clearfix"></div>
                                    </div>
                                </a>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="panel panel-warning">
                                <div class="panel-heading">
                                    <center><img src="../icons/BattleSite/Reward_Grey.png" class="img-responsive"></center>
                                    <div class="clearfix"></div>
                                </div>
                                <a href="battle_area_reward.php">
                                    <div class="panel-footer">
                                        <span class="pull-left">Rewards</span>
                                        <span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
                                        <div class="clearfix"></div>
                                    </div>
                                </a>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="panel panel-red">
                                <div class="panel-heading">
                                    <center><img src="../icons/BattleSite/Info_Colour.png" class="img-responsive"></center>
                                    <div class="clearfix"></div>
                                </div>
                                <a href="battle_area_info.php">
                                    <div class="panel-footer">
                                        <span class="pull-left">Detailed Battle System Description</span>
                                        <span class="pull-right"><i class="fa fa-arrow-circle-right"></i></span>
                                        <div class="clearfix"></div>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-8 col-lg-offset-2">
                            <div class="panel panel-primary">
                                <div class="panel-heading">
                                    <div class="panel-title">Battle Log</div>
                                </div>
                                <div class="row">
                                    <div class="col-xs-3">
                                        <i class="fa-5x"><span class="glyphicon glyphicon-fire" area-hidden="true"></span></i>
                                    </div>
                                    <div class="panel-body">
                                        <p><strong><21.10.2015 - 17:00></strong> Battle between <strong><a href="#myModal4" data-toggle="modal">Sicarius</a></strong> and <strong><a href="#myModal5" data-toggle="modal">Maxi</a></strong> started! Battle Number <a>#1337</a></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /.container-fluid -->

        </div>
        <!-- /#page-wrapper -->

    </div>
    <!-- /#wrapper -->
</body>

</html>