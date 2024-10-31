<?php
/*
Plugin Name: Notify Unconfirmed Subscribers
Plugin URI: http://techie-buzz.com/wordpress-plugins/notify-unconfirmed-subscribers-plugin-release.html
Description: Allows you to notify unconfirmed subscribers from FeedBurner email subscriptions and more that they have still not subscribed to your feed, allowing you to compose a personalized message that is sent to them. Send a message now using <a href="tools.php?page=notify-unconfirmed-subscribers/notify-unconfirmed-subscribers.php">Notify Unconfirmed Subscribers</a>.
Version: 1.3.1
Author: Keith Dsouza
Author URI: http://keithdsouza.com/

Copyright 2007  Keith Dsouza  (email : dsouza.keith at gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
**/

if (!defined('ABSPATH')) die("Aren't you supposed to come here via WP-Admin?");

set_time_limit(0);
require_once ('lib/class.feedburner.com.php');

@ define('NUS_PAGE', 'notify-uncofirmed-subscribers/notify-unconfirmed-subscribers.php');
@ define('NUS_COOKIE_DIR', 'notify-uncofirmed-subscribers/cookies/');
@ define('NUS_TEMP_TABLE', 'wp_temp_nus_subs');
@ define('NUS_TRACKER_TABLE', 'wp_nus_tracker');

$task = '';
if (isset ($_REQUEST['nus_task'])) {
	$task = $_REQUEST['nus_task'];
}

if (isset ($wpdb)) {
	nus_init();
}
$nus_performer = null;

function notify_unconfirmed_subscribers() {
	global $task;
  nus_init();
  nus_show_plugin_details();
	switch ($task) {
		case 'fetch' :
			fetch_unconfirmed_subscribers();
			break;
		case 'preparesend' :
			show_preparesend();
			break;
		case 'showmessageform' :
			$nusFeedId = $_REQUEST['nus_feed_id'];
			$nusFeedName = $_REQUEST['nus_feed_name'];
			show_message_form($nusFeedId, $nusFeedName);
			break;
		case 'sendmessage' :
			send_nus_message();
			break;
		case 'propogatemessage' :
			break;
		default :
			show_login_form();
	}
}

function fetch_unconfirmed_subscribers() {
	can_use_curl();
	show_fetching('Please wait while the unconfirmed subscribers are being fetched.');
	global $wpdb, $nus_performer;
	$wpdb->query("TRUNCATE table " . NUS_TEMP_TABLE);
	$perform = $_REQUEST['perform'];
	$username = $_REQUEST['nus_username'];
	$password = $_REQUEST['nus_password'];
  $account_type = $_REQUEST['account_type'];
	$nus_performer = new nus_feedBurner($username, $password);
	$unconfirmedFeedEmail = 0;
	$feederEmails = '';
	if ($nus_performer->login()) {
		$feeders = $nus_performer->get_unconfirmed_subscribers();
		foreach ($feeders as $key => $value) {
			$feedName = mysql_real_escape_string($key);
			$feedID = $value['id'];
			$emails = $value['emails'];
      $feederEmails = '';
			if (count($emails) > 0) {
				$unconfirmedFeedEmail++;
				foreach ($emails as $email) {
					$feederEmails .= $email . ",";
				}
				$feederEmails = substr($feederEmails, 0, strlen($feederEmails) - 1);
				$wpdb->query("INSERT into " . NUS_TEMP_TABLE . "(nus_feed_id, nus_feed_name, nus_feed_emails, nus_added_date)" .
				"VALUES ('$feedID', '$feedName', '$feederEmails', now())");
			}
		}

		if ($unconfirmedFeedEmail > 0) {
			$message = "We fetched " . count($feeders) . " feeds from your account " .
			"out of which we found unconfirmed subscribers in $unconfirmedFeedEmail feeds. Please use the links given below to send them a message.";
		} else {
			$message = "We fetched " . count($feeders) . " feeds from your account." .
			"We could not find any subscribers who have not confirmed their subscriptions.";
		}
		
		$nus_performer->delete_cookies();
		unset($nus_performer);
		$url = "tools.php?page=" . NUS_PAGE;
		nus_redirect($url, 'preparesend', $message, $unconfirmedFeedEmail);
	} 
	else {
		echo "<script language='JavaScript' type='text/javascript'>";
		echo 	"jQuery('#nus_fetching').hide()";
		echo "</script>";
		echo "<div id='message'  class='updated fade'>";
		echo "<br/><strong style='color:red'>OOPS!! Something went wrong while fetching unconfirmed subscribers. Error details given below.</strong><br/><br/>";
		echo "</div>";
		$nus_performer->print_error();
		show_login_form();
		$nus_performer->delete_cookies();
		unset($nus_performer);
	}
	
}

function show_fetching($message) {
	echo '<div id="nus_fetching"><img align="left" valign="middle" hspace="20" src="'.trailingslashit ( WP_PLUGIN_URL . '/' . dirname ( plugin_basename ( __FILE__ ) ) ).'img/rotate.gif" /><strong>'.$message.'</strong></div>';
}



function show_preparesend() {
	global $wpdb;
	$message = $_REQUEST['message'];
	$unconfirmedFeedEmail = $_REQUEST['unconfirmedFeedEmail'];
	if ($message) {
		echo '<div id="message"  class="updated fade">';
		echo "<br /><strong>" . $message . "</strong><br /><br/ >";
		echo "</div>";
	}

	$feeders = $wpdb->get_results("SELECT nus_id, nus_feed_id, nus_feed_name from wp_temp_nus_subs");
	echo '<div class="wrap">';
  $feedcount = count($feeders);
	if ($feedcount > 0) {
    echo "<h4>There are $feedcount Feeds in your Account with Unconfirmed Email Subscribers. Please click on the links to send a notification message to them.</h4>";
		foreach ($feeders as $feeds) {
			echo "<a href=\"" . wp_nonce_url("tools.php?page=" . NUS_PAGE . "&nus_task=showmessageform&nus_feed_id=" . $feeds->nus_feed_id . "&nus_feed_name=" . $feeds->nus_feed_name, 'notify-unconfirmed-subscribers') . "\">Click Here</a> to send messages to unconfirmed users from <strong>" . $feeds->nus_feed_name .
			"</strong>. <br />";
		}
	} else {
		echo "There are no unconfirmed subscribers <a href=\"" . wp_nonce_url("tools.php?page=" . NUS_PAGE, 'notify-unconfirmed-subscribers') . "\">click here</a> to see if there are any.";
	}
	echo '</div>';

}

function show_login_form() {
	can_use_curl();
?>
      <h3>Please follow the onscreen instructions to notify unconfirmed subscribers.</h3>
			<form name="nus_login" method="post" action="tools.php?page=<?php echo NUS_PAGE; ?>" onsubmit="return nus_validate();">
			<?php wp_nonce_field('notify-unconfirmed-subscribers'); ?>
			<input type="hidden" name="nus_task" id="nus_task" value="fetch" />
			<input type="hidden" name="perform" id="perform" value="feedburner" />
			<div class="wrap">


			<table class="editform" width="100%" cellspacing="2" cellpadding="5">
				<tr>
          <td colspan="2"><em>Fetching unconfirmed subscribers from old Feedburner accounts has been disabled since version 1.3.0. Please migrate your old FeedBurner account to Google before using this tool.</em><br /><br /></td>
        </tr>
        <tr>
          <td colspan="2">Enter your Google Feedburner Account username and password below, we will use it to get your unconfirmed subscribers, no username and password will be stored. Swear!!!.<br /><br /></td>
        </tr>
        <tr>
					<td width="15%"><label for="nus_fbusername"><?php _e('Feedburner username:', 'notify_unconfirmed_subscribers'); ?></label></td>
					<td><input type="text" id="nus_username" name="nus_username" value="" style="width: 70%" /></td>
				</tr>
				<tr>
					<td width="15%"><label for="nus_fbupassword"><?php _e('Feedburner password:', 'notify_unconfirmed_subscribers'); ?></label></td>
					<td><input type="password" id="nus_password" name="nus_password" value="" style="width: 70%" /><br /><br /></td>
				</tr>
				<tr>
					<td colspan="2"><input type="submit" name="Submit" onclick="this.disabled=true;this.form.submit();" value="<?php _e('Get Unconfirmed Subscribers') ?> &raquo;" /></td>
				</tr>
				<tr>
					<td colspan="2"><br /><strong>P.S. This may take some time, please be patient after you have clicked the button.</strong></td>
				</tr>
			</table>
			</div>
			</form>
<?php
}

function nus_show_plugin_details() {
?>
  <div class="wrap">
    <h2>Notify Unconfirmed Subscribers</h2>
    <h5>Notify Unconfirmed Subscribers helps users to send a message to unconfirmed email subscribers of your Feeds, you can find more information about the plugin on the <a href="http://techie-buzz.com/wordpress-plugins/notify-unconfirmed-subscribers-plugin-release.html" target="_blank">release page</a>, report a bug in the <a href="http://forum.techie-buzz.com/forum.php?id=6&page" target="_blank">NUS Forum</a>. If you feel this plugin has helped you, consider donating a small amount towards development expenses. <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=keithdsouza@msn.com&currency_code=&amount=&return=&item_name=WordPress+Plugin+Development+Donation NUS" target="_blank" title="Opens in New Window">Donate Now</a>. NUS is developed by <a href="http://keithdsouza.com">Keith Dsouza</a>.</h5>
  </div>
<?php
}

function show_message_form($nusFeedId, $nusFeedName) {
	global $wpdb, $user_ID;

	if (!$nusFeedId) {
		echo "<br /><br />";
		echo '<div class="wrap">';
		echo "<strong>OOPS no feed selected. We cannot process your request";
		echo '</div>';
		return false;
	}

	$toEmail = $wpdb->get_row("SELECT nus_feed_emails from " . NUS_TEMP_TABLE . " where nus_feed_id = '$nusFeedId'");
	$feederEmails = '';
	if (count($toEmail) > 0) {
		$feederEmails = $toEmail->nus_feed_emails;
	}
	echo "<br /><br />";
	echo '<div class="wrap">';
	echo "<strong>Currently sending message for Unconfirmed subscribers of the feed $nusFeedName.";
	echo "The message will be sent to the following email addresses</strong><br /><br />";
	echo str_replace(",", ", ", $feederEmails);
	echo '</div>';

	$user = get_userdata($user_ID);
	$user_niceName = '';
	if (!empty ($user->display_name))
		$user_niceName = $user->display_name;
	else
		$user_niceName = $user->user_nicename;

	$user_email = $user->user_email;
?>
      <form name="nus_send_form" method="post" action="tools.php?page=<?php echo NUS_PAGE; ?>">
			<?php wp_nonce_field('notify-unconfirmed-subscribers'); ?>
			<input type="hidden" name="nus_task" id="nus_task" value="sendmessage" />
			<input type="hidden" name="nus_feed_id" id="nus_feed_id" value="<?php echo $nusFeedId; ?>" />
			<input type="hidden" name="nus_feed_name" id="nus_feed_name" value="<?php echo $nusFeedName; ?>" />
			<br /><br />
			<div class="wrap">
			<table class="editform" width="100%" cellspacing="2" cellpadding="5">
				<tr>
					<td width="15%"><label for="nus_fromname"><?php _e('From Name:', 'notify_unconfirmed_subscribers'); ?></label></td>
					<td><input type="text" id="nus_fromname" name="nus_fromname" value="<?php echo $user_niceName;?>" style="width: 70%" /></td>
				</tr>
				<tr>
					<td width="15%"><label for="nus_fromemail"><?php _e('From Email:', 'notify_unconfirmed_subscribers'); ?></label></td>
					<td><input type="text" id="nus_fromemail" name="nus_fromemail" value="<?php echo $user_email;?>" style="width: 70%" /></td>
				</tr>
				<tr>
					<td width="15%"><label for="nus_subject"><?php _e('Subject:', 'notify_unconfirmed_subscribers'); ?></label></td>
					<td><input type="text" id="nus_subject" name="nus_subject" value="Hello from <?php echo $nusFeedName;?>" style="width: 70%" /></td>
				</tr>
        <tr>
          <td></td>
          <td><strong>Supported HTML tags &lt;b&gt; or &lt;strong&gt;, &lt;i&gt; or &lt;em&gt; and &lt;a&gt; . Please do not insert &lt;br&gt;  or &lt;p&gt;  tags, the plugin will automatically add line breaks to the messages.</strong></td>
        </tr>
				<tr>
					<td width="15%"><label for="nus_message"><?php _e('Message:', 'notify_unconfirmed_subscribers'); ?></label></td>
					<td>
  				<textarea name="nus_message" rows="12" cols="60" wrap="on"></textarea>
  			  </td>
				</tr>
				<tr>
					<td colspan="2"><input type="submit" name="Submit" onclick="this.disabled=true;this.form.submit();" value="<?php _e('Send message to Unconfirmed Subscribers') ?> &raquo;" /></td>
				</tr>
				<tr>
					<td colspan="2"><strong>This may take some time, please be patient after you have clicked the button.</strong></td>
				</tr>
			</table>
			</div>
			</form>
<?php


}

function send_nus_message() {
	global $wpdb;
	$nusFeedId = $_REQUEST['nus_feed_id'];
	$nusFeedName = $_REQUEST['nus_feed_name'];
	echo "<br /><br />";
	if (!$nusFeedId) {
		echo '<div class="wrap">';
		echo "<strong>OOPS no feed selected. We cannot process your request";
		echo '</div>';
		return false;
	}
	$nusFromName = trim($_REQUEST['nus_fromname']);
	$nusFromEmail = trim($_REQUEST['nus_fromemail']);
	$nusSubject = trim($_REQUEST['nus_subject']);
	$nusMessage = trim($_REQUEST['nus_message']);
  $nusMessage = str_replace("\n", "<p />", $nusMessage);

	if (!$nusFromName || !$nusFromEmail || !$nusSubject || !$nusMessage) {
		echo '<div id="message"  class="updated fade">';
		echo "<br />OH NO you did not fill in the required fields, all fields are compulsory please fill them. " .
		"Fill the form below once again so we can continue<br /><br />";
		echo '</div>';
		show_message_form($nusFeedId, $nusFeedName);
		return false;
	}

	$toEmail = $wpdb->get_row("SELECT nus_feed_emails from " . NUS_TEMP_TABLE . " where nus_feed_id = '$nusFeedId'");
	if (count($toEmail) <= 0) {
		echo '<div id="message"  class="updated fade">';
		echo "<br />We did not find any unconfirmed subscribers for this feed. Lets do the rest of it. " .
		"<a href=\"" . wp_nonce_url("tools.php?page=" . NUS_PAGE . "" .
		"&nus_task=preparesend", 'notify-unconfirmed-subscribers') . "\">Click here</a> so that we can do the other feeds.<br /><br />";
		echo '</div>';
		return false;
	}

	$feederEmails = $toEmail->nus_feed_emails;
	echo "<br /><br />";
	echo '<div class="wrap">';
	echo "<strong>Currently sending message for Unconfirmed subscribers of the feed $nusFeedName.";
	echo "The message will be sent to the following email addresses</strong><br /><br />";
	echo str_replace(",", ", ", $feederEmails);

	$sendToEmails = explode(",", $feederEmails);
	$howManyToSend = count($sendToEmails);
	$sentMessage = 0;
	$notSent = 0;
	foreach ($sendToEmails as $email) {
		$done = $wpdb->get_row("SELECT nus_tracker_id, nus_status from " . NUS_TRACKER_TABLE . " WHERE nus_email = '$email' " .
		"AND nus_feed_id = '$nusFeedId'");
		if (count($done) <= 0) {
			if ($done->nus_status) {
				continue;
			}
			$headers = "MIME-Version: 1.0\r\n";
			$headers .= "Content-type: text/html; charset=uts-8\r\n";
			$headers .= "From: $nusFromName <$nusFromEmail>\r\n";
			//echo "SENDING A EMAIL with $headers to $email for a subjsct $nusSubject and the message $nusMessage";
			wp_mail($email, $nusSubject, $nusMessage, $headers);
			$wpdb->query("INSERT INTO " . NUS_TRACKER_TABLE . " (nus_feed_id, nus_email, nus_status, nus_sent_date)  " .
			"VALUES ('$nusFeedId', '$email', 1, now())");

			$sentMessage++;
		} else {
			$notSent++;
		}
		$wpdb->query("Delete from " . NUS_TEMP_TABLE . " where nus_feed_id = '$nusFeedId'");
	}
	if ($sentMessage > 0) {
		echo "<br /><br /><strong>Task Status</strong><br /><br />We were able to " .
		"send the message to $sentMessage out of the $howManyToSend emails. " .
		"$notSent out of the $howManyToSend were already sent a message earlier.";
	} else {
		echo "<br /><br /><strong>Task Status</strong><br /><br />We were <strong>NOT</strong> able to " .
		"send the message to $sentMessage out of the $howManyToSend emails." .
		"$notSent out of the $howManyToSend were already sent a message earlier.";
	}

	echo '</div>';
	echo "<br /><br />";
	echo '<div class="wrap">';
	echo "<br /><br />";
	echo "Send this message for the other feeds. <a href=\"" . wp_nonce_url("tools.php?page=" . NUS_PAGE . "" .
	"&nus_task=preparesend", 'notify-unconfirmed-subscribers') . "\">Click here</a>.";
	echo "<br /><br />";
	echo '</div>';
}

function nus_redirect($url, $nus_task, $message, $unconfirmedFeedEmail) {
?>
		<form name="nus_redirect" method="post" location="<?php echo $url;?>">
			<?php wp_nonce_field('notify-unconfirmed-subscribers'); ?>
			<input type="hidden" name="nus_task" value="<?php echo $nus_task;?>"/>
			<input type="hidden" name="message" value="<?php echo $message;?>"/>
			<input type="hidden" name="unconfirmedFeedEmail" value="<?php echo $unconfirmedFeedEmail;?>"/>
		</form>
		<script language="Javascript">
			document.nus_redirect.submit();
		</script>
<?php


}

/** Install related functions **/
function nus_init() {
   global $wpdb;
   $result = mysql_list_tables(DB_NAME);
   $tables = array ();
   while ($row = mysql_fetch_row($result)) {
     $tables[] = $row[0];
   }
   if (!in_array(NUS_TEMP_TABLE, $tables) || !in_array(NUS_TRACKER_TABLE, $tables)) {
     nus_install();
   }
   alter_tables_if_required();
}

//alters the earlier versions of the table to use varchar instead of integers it earlier used to track feed ids
function alter_tables_if_required() {
  global $wpdb;
  $options = get_option('nus_altered_table');
  $altercolumns = false;
  if(!$options) {
    $altercolumns = true;
  }

  if($altercolumns) {
    $wpdb->query("ALTER TABLE " . NUS_TEMP_TABLE ." MODIFY  nus_feed_id varchar(255) NOT NULL");
    $wpdb->query("ALTER TABLE " . NUS_TRACKER_TABLE ." MODIFY  nus_feed_id varchar(255) NOT NULL");
    add_option('nus_altered_table', 'altered');
  }


}

function nus_install() {
	global $wpdb;
	//$result = $wpdb->query("DROP TABLE if exists " . NUS_TEMP_TABLE);
	//$result = $wpdb->query("DROP TABLE if exists " . NUS_TRACKER_TABLE);
	$result = $wpdb->query("CREATE TABLE " . NUS_TEMP_TABLE . " (" .
	"nus_id INT(10) NOT NULL AUTO_INCREMENT," .
	"nus_feed_id varchar(255) NOT NULL," .
	"nus_feed_name VARCHAR(255) NOT NULL," .
	"nus_feed_emails MEDIUMTEXT NOT NULL default ''," .
	"nus_added_date date NOT NULL," .
	"PRIMARY KEY(nus_id)" .
	")");

	$result = $wpdb->query("CREATE TABLE " . NUS_TRACKER_TABLE . " (" .
	"nus_tracker_id INT(10) NOT NULL AUTO_INCREMENT," .
	"nus_feed_id varchar(255) NOT NULL," .
	"nus_email VARCHAR(255) NOT NULL," .
	"nus_status SMALLINT(0) NOT NULL default '1'," .
	"nus_sent_date date NOT NULL," .
	"PRIMARY KEY(nus_tracker_id)
										)");

	if (!$result) {
		return false;
	}
}

function can_use_curl() {
	if (function_exists('curl_init')) {
		return true;
	}
	else {
		echo '<div id="message"  class="updated fade">';
		echo '<em>cUrl is disabled on this blog. Even though I use in-built code from WordPress for HTTP requests, this may not work for you. Before you can contact me about this not working, please contact your system administrator and ask them to enable cUrl support for you. If you do decide to contact me, please mention that you saw this message. Rest assured I am still working on adding support for blogs which do no support cUrl, but there is nothing much I can do about it right now.</em>';
		echo '</div>';
		return false;
	}
}

function nus_manage_page() {
	add_submenu_page('tools.php', 'Notify Unconfirmed Subscriptions', 'Notify Unconfirmed Subscriptions', 0, 'notify-uncofirmed-subscribers/notify-unconfirmed-subscribers.php', 'notify_unconfirmed_subscribers');
}

function nus_curl_cookie_location($handle) {
	global $nus_performer;
	if($nus_performer)
		$nus_performer->add_cookie_to_CURL($handle);
}


function nus_add_script() {
	//only add if we are on our own page
  if($_REQUEST['page'] == NUS_PAGE) {
?>
			<script type="text/javascript" language="JavaScript">
      
			function nus_validate() {
				if(isBlank(jQuery("#nus_login input:nus_username").val()) || isBlank(jQuery("#nus_login input:nus_password").val())) {
					alert("Enter you Feedburner username and password before submitting the form");
					return false;
				}
				else {
					jQuery("#nus_login").submit();
				}
				return false;
			}
			
			function isBlank(s)
			{
				var len,k,flg;
				flg=true;
				if(s!=null)
				{
					len=s.length;
					for(k=0;k<len;k++)
					{
						if(s.substring(k,k+1)!=" ")
							flg=false;
					}
				}
				return flg;
			}
		</script>
<?php
  }
}

add_action('admin_menu', 'nus_manage_page');
add_action('http_api_curl', 'nus_curl_cookie_location');
//add_action('admin_head', 'nus_add_script');
//wp_enqueue_script('jquery');
?>
