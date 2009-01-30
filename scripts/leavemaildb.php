#!/usr/bin/php -q
<?
/* Simple voicemail recorder for the Yate PHP interface

   To use it you must re-route calls to offline or non-responding numbers to:
   external/nodata/leavemail.php
*/
require_once("libyate.php");
require_once("libvoicemail.php");
require_once("lib_queries.php");

/* Always the first action to do */
Yate::Init();

/* Uncomment next line to get debugging messages */
//Yate::Debug(true);

$ourcallid = "leavemaildb/" . uniqid(rand(),1);
$partycallid = "";
$state = "call";
$mailbox = "";
$user = "";
$file = "";

$vm_func_for_dir = "getCustomVoicemailDir";
Yate::Debug(true);

/* Check if the user exists and prepare a filename if so */
function checkUser($called,$caller)
{
    global $mailbox;
    global $user;
    global $file;

	$query = "SELECT count(*) as count FROM extensions WHERE extension='$called'";
	$res = query_to_array($query);
	if(!$res[0]["count"])
		return false;

    $user = vmGetVoicemailDir($called);
    if (!is_dir($user))
		mkdir($user);

    $mailbox = $called;
    $file = vmBuildNewFilename($caller);
    return true;
}

/* Perform machine status transitions */
function setState($newstate)
{
    global $ourcallid;
    global $state;
    global $vm_base;
    global $mailbox;
    global $user;
    global $file;

    // are we exiting?
    if ($state == "")
	return;

    Yate::Debug("setState('$newstate') state: $state");

    if ($newstate == $state)
	return;

    switch ($newstate) {
	case "novmail":
	    $m = new Yate("chan.attach");
	    $m->params["source"] = "wave/play/$vm_base/novmail.au";
	    $m->params["notify"] = $ourcallid;
	    $m->Dispatch();
	    break;
	case "greeting":
	    $m = new Yate("chan.attach");
	    if (is_file("$user/greeting.au"))
			$m->params["source"] = "wave/play/$user/greeting.au";
	    else
			$m->params["source"] = "wave/play/$vm_base/nogreeting.au";
	    $m->params["notify"] = $ourcallid;
	    $m->Dispatch();
	    break;
	case "beep":
	    $m = new Yate("chan.attach");
	    $m->params["source"] = "wave/play/$vm_base/beep.au";
	    $m->params["notify"] = $ourcallid;
	    $m->Dispatch();
	    break;
	case "record":
	    $m = new Yate("chan.attach");
	    $m->params["source"] = "wave/play/-";
	    $m->params["consumer"] = "wave/record/$user/$file";
	    $m->params["maxlen"] = 160000;
	    $m->params["notify"] = $ourcallid;
	    $m->Dispatch();
	    $m = new Yate("user.update");
	    $m->id = "";
	    $m->params["user"] = $mailbox;
	    $m->Dispatch();
	    break;
	case "goodbye":
	    $m = new Yate("chan.attach");
	    $m->params["source"] = "tone/congestion";
	    $m->params["consumer"] = "wave/record/-";
	    $m->params["maxlen"] = 32000;
	    $m->params["notify"] = $ourcallid;
	    $m->Dispatch();
	    break;
    }
    $state = $newstate;
}

/* Handle EOF of wave files */
function gotNotify($reason)
{
    global $state;

    Yate::Debug("gotNotify('$reason') state: $state");
    if ($reason == "replaced")
	return;

    switch ($state) {
	case "goodbye":
	    setState("");
	    break;
	case "greeting":
	    setState("beep");
	    break;
	case "beep":
	    setState("record");
	    break;
	default:
	    setState("goodbye");
	    break;
    }
}

/* Install filtered handler for the wave end notify messages */
Yate::Install("chan.notify",100,"targetid",$ourcallid);

/* The main loop. We pick events and handle them */
while ($state != "") {
    $ev=Yate::GetEvent();
    /* If Yate disconnected us then exit cleanly */
    if ($ev === false)
	break;
    /* No need to handle empty events in this application */
    if ($ev === true)
	continue;
    /* If we reached here we should have a valid object */
    switch ($ev->type) {
	case "incoming":
	    switch ($ev->name) {
		case "call.execute":
		    $partycallid = $ev->GetValue("id");
		    $ev->params["targetid"] = $ourcallid;
		    $ev->handled = true;
		    /* We must ACK this message before dispatching a call.answered */
		    $ev->Acknowledge();

		    /* Check if the mailbox exists, answer only if that's the case */
		    if (checkUser($ev->GetValue("called"),$ev->GetValue("caller"))) {
			$m = new Yate("call.answered");
			$m->params["id"] = $ourcallid;
			$m->params["targetid"] = $partycallid;
			$m->Dispatch();
			setState("greeting");
		    }
		    else
			/* Play a message and exit - don't answer the call */
			setState("novmail");

		    // we already ACKed this message
		    $ev = false;
		    break;

		case "chan.notify":
		    gotNotify($ev->GetValue("reason"));
		    $ev->handled = true;
		    break;
	    }
	    /* This is extremely important.
	       We MUST let messages return, handled or not */
	    if ($ev)
		$ev->Acknowledge();
	    break;
	case "answer":
	    // Yate::Debug("PHP Answered: " . $ev->name . " id: " . $ev->id);
	    break;
	case "installed":
	    // Yate::Debug("PHP Installed: " . $ev->name);
	    break;
	case "uninstalled":
	    // Yate::Debug("PHP Uninstalled: " . $ev->name);
	    break;
	default:
	    // Yate::Output("PHP Event: " . $ev->type);
    }
}

Yate::Output("PHP: bye!");

/* vi: set ts=8 sw=4 sts=4 noet: */
?>
