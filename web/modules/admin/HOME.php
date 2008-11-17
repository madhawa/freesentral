<?
global $module,$method,$action;

if(!$method)
	$method = strtolower($module);

if(substr($method,0,4) == "add_")
	$method = str_replace("add_","edit_",$method);

if($method == "edit_admin")
	$method = "edit_user";

if($method == "manage")
	$method = "home";

if($action)
	$call = $method.'_'.$action;
else
	$call = $method;

$call = strtolower($call);

if($call != "home")
	print '<div class="content wide">';
$call();
if($call != "home")
	print '</div>';

function home()
{
	print '<table><tr><td class="topvalign">';
	print '<div class="homecontent">';
	//$actions = array("&module=auto_attendant&method=wizard"=>array());

	print '<table class="hometable" cellspacing="0" cellpadding="0">';
		print '<tr>';
			print '<td>
							<table class="home_opt" cellspacing="0" cellpadding="0" onClick="location.href=\'main.php?module=auto_attendant&method=wizard\'">
								<tr onMouseover="this.bgColor=\'#dcf0f2\'" onMouseout="this.bgColor=\'#EEEEEE\'">';
									print '<td class="hometable">';
										print '<img src="images/auto-attendant.png"/>';
									print '</td>';
									print '<td class="hometable description">';
										print 'Auto Attendant';
									print '</td>';
								print '</tr>
							</table>
					</td>';
					print '<td>
								<table class="home_opt" cellspacing="0" cellpadding="0" onClick="location.href=\'main.php?module=extensions&method=add_extension\'">
									<tr onMouseover="this.bgColor=\'#dcf0f2\'" onMouseout="this.bgColor=\'#EEEEEE\'">';
										print '<td class="hometable">';
											print '<img src="images/extension.png"/>';
										print '</td>';
										print '<td class="hometable description">';
												print 'Add Extension';
										print '</td>';
									print '</tr>
								</table>
						</td>';
		print '</tr>';

		print '<tr>';
			print '<td>
							<table class="home_opt" cellspacing="0" cellpadding="0" onClick="location.href=\'main.php?module=outbound&method=add_gateway\'">
								<tr onMouseover="this.bgColor=\'#dcf0f2\'" onMouseout="this.bgColor=\'#EEEEEE\'">';
									print '<td class="hometable">';
										print '<img src="images/gateways.png"/>';
									print '</td>';
									print '<td class="hometable description">';
										print 'Add Gateway';
									print '</td>';
								print '</tr>
							</table>
					</td>';
			print '<td>
							<table class="home_opt" cellspacing="0" cellpadding="0" onClick="location.href=\'main.php?module=address_book&&method=add_short_name\'">
								<tr onMouseover="this.bgColor=\'#dcf0f2\'" onMouseout="this.bgColor=\'#EEEEEE\'">';
									print '<td class="hometable">';
										print '<img src="images/address_book.png"/>';
									print '</td>';
									print '<td class="hometable description">';
										print 'New Address Book entry';
									print '</td>';
								print '</tr>
							</table>
					</td>';
		print '</tr>';

		print '<tr>';
			print '<td>
							<table class="home_opt" cellspacing="0" cellpadding="0" onClick="location.href=\'main.php?module=outbound&method=add_dial_plan\'">
								<tr onMouseover="this.bgColor=\'#dcf0f2\'" onMouseout="this.bgColor=\'#EEEEEE\'">';
									print '<td class="hometable">';
										print '<img src="images/dial_plan.png"/>';
									print '</td>';
									print '<td class="hometable description">';
										print 'Add Dial Plan';
									print '</td>';
								print '</tr>
							</table>
				</td>';
			print '<td>
							<table class="home_opt" cellspacing="0" cellpadding="0" onClick="location.href=\'main.php?module=dids&method=add_did\'">
								<tr onMouseover="this.bgColor=\'#dcf0f2\'" onMouseout="this.bgColor=\'#EEEEEE\'">';
									print '<td class="hometable">';
										print '<img src="images/dids.png"/>';
									print '</td>';
									print '<td class="hometable description">';
										print 'Add DID';
									print '</td>';
								print '</tr>
							</table>
				</td>';
		print '</tr>';
	print '</table>';
	print '</div>';
	print '</td><td class="topvalign">';
	print '<div class="copac copachome">';
	$status = exec("service yate status");
	print '<div class="titlu">SYSTEM STATUS</div>';
	print '<div class="systemstatus"> '.
			
		'
			<div style="float:right;"> Today, '.date('h:i a').'

		</div>Yate: '.$status;
	print '</div>';
print '<br/><br/>';
	print '</td></tr></table>';
}

/*
function home()
{
	print '<div class="title wide">:: Ongoing Calls ::</div>';
	print '<div class="content wide">';
	ongoing_calls(5);
	print '</div>';
	print '<div class="title wide">:: Logs ::</div>';
	print '<div class="content wide">';
	logs(5);
	print '</div>';
}*/

function logs($lim = NULL)
{
	global $limit,$page;

	$use_limit = ($lim) ? $lim : $limit;

	if(!$lim)
	{
		$total = getparam("total");
		$actionlog = new ActionLog;
		$total = $actionlog->fieldSelect("count(*)");
		items_on_page();
		pages($total);
	}

	$logs = Model::selection("actionlog",NULL,"date DESC",$use_limit,$page);
	tableOfObjects($logs,array("function_select_date:date"=>"date", "function_select_time:time"=>"date","performer", "log"),"log");
}

function ongoing_calls($lim = NULL)
{
	global $limit,$page;

	$use_limit = ($lim) ? $lim : $limit;
	$total = getparam("total");
	$call_log = new Call_Log;
	$total = $call_log->fieldSelect("count(*)",array("ended"=>false));
	if(!$lim)
	{
		items_on_page();
		pages($total);
	}
	$columns = array("time"=>true, "chan"=>false, "address"=>false, "direction"=>false, "billid"=>false, "caller"=>true, "called"=>true, "duration"=>true, "billtime"=>false, "ringtime"=>false, "status"=>true, "reason"=>false, "ended"=>false);

	$formats = array();
	foreach($columns as $key=>$display)
	{
		if(!(getparam("col_".$key)=="on" || $display == true))
			continue;
		if($key != "time")
			array_push($formats, $key);
		else{
			$formats["function_select_date:date"] = "time";
			$formats["function_select_time:time"] = "time"; 
		}
	}
	$call_logs = Model::selection("call_log",array("ended"=>false), "time DESC", $use_limit, $page);

	if(!$total)
		$total = count($call_logs);
	if($total)
		if($total != 1)
			print "There are ".$total." ongoing calls in the system.<br/><br/>";
		else
			print "There is 1 ongoing call in the system.<br/><br/>";

	tableOfObjects($call_logs,$formats, "ongoing call");
}
?>