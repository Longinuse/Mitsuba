<?php
session_start();
if (!file_exists("./config.php"))
{
header("Location: ./install.php");
}

include("config.php");
include("inc/mitsuba.php");
include("inc/strings/imgboard.strings.php");

if (!empty($_POST['mode']))
{
	$return_url = "./";
	if (!empty($_POST['board']))
	{
		$return_url = "./".$_POST['board']."/";
	}
	$conn = new mysqli($db_host, $db_username, $db_password, $db_database);
	$mitsuba = new Mitsuba($conn);
	$mod = 0;
	$mod_type = 0;
	if ((!empty($_GET['mod'])) && ($_GET['mod']>=1))
	{
		if ((!empty($_POST['board'])) || ($mitsuba->common->isBoard($_POST['board'])))
		{
			$mitsuba->admin->canBoard($_POST['board']);
			$mod = 1;
			if (!empty($_SESSION['type'])) { $mod_type = $_SESSION['type']; }
			if ($_GET['mod']==1)
			{
				$return_url = "mod.php?/board&b=".$_POST['board'];
			} else {
				$mod = 2;
			}
		}
	}
	$mode = $_POST['mode'];
	switch($mode)
	{
		case "regist":
			$filename = null;
			if (empty($_POST['board']))
			{
			?>
				<html>
				<head>
				<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
				<title><?php echo $lang['img/error']; ?></title>
				</head>
				<body>
			<?php
				echo "<center><h1>".$lang['img/no_board']."</h1></center></body></html>";
				exit;
			}
			$board = $_POST['board'];
			if (($mod == 0) && ($mitsuba->common->isWhitelisted($_SERVER['REMOTE_ADDR']) < 1))
			{
				$mitsuba->common->banMessage($board);
				$mitsuba->common->warningMessage();
			}
			$ignoresizelimit = 0;
			if ($mod >= 1)
			{
				if ((!empty($_POST['ignoresizelimit'])) && ($_POST['ignoresizelimit']==1) && ($mod_type >= 1))
				{
					$ignoresizelimit = 1;
				}
			}
			?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title><?php echo $lang['img/updating_index']; ?></title>
</head>
<body>
<center><h1><?php echo $lang['img/updating_index']; ?></h1></center>
			<?php
			if (!$mitsuba->common->isBoard($_POST['board']))
			{
				echo "<h1>".$lang['img/board_no_exists']."</h1></body></html>"; exit;
			}
			
			
			$md5 = "";
			$bdata = $mitsuba->common->getBoardData($_POST['board']);
			if (($bdata['hidden'] == 1) && ($mod_type < 1))
			{
				echo "<h1>".$lang['img/board_no_exists']."</h1></body></html>"; exit;
			}
			
			if (($mod_type < 1) && ($bdata['captcha'] == 1) && (empty($_SESSION['captcha']) || empty($_POST['captcha']) || strtolower(trim($_POST['captcha'])) != $_SESSION['captcha']))
			{
				$_SESSION['captcha'] = "";
				echo "<h1>".$lang['img/wrong_captcha']."</h1></body></html>"; exit;
			}
			$_SESSION['captcha'] = "";

			if (strlen($_POST['com']) > $bdata['maxchars'])
			{
				echo "<h1>".sprintf($lang['img/comment_too_long'],strlen($_POST['com']),$bdata['maxchars'])."</h1></body></html>"; exit;
			}
			if ($mod_type < 1)
			{
				$mitsuba->board->checkSpam($_POST['com'], $_POST['board']);
			}
			if ((!empty($_POST['embed'])) && (!empty($_FILES['upfile']['tmp_name'])))
			{
				echo "<center><h1>".$lang['img/choose_one']."</h1></center></body></html>";
				exit;
			}
			if (($mitsuba->common->isWhitelisted($_SERVER['REMOTE_ADDR']) != 2) && (($mod == 0) || ($mod_type==0)))
			{
				if ((empty($_POST['resto'])) || ($_POST['resto']==0))
				{
					$mitsuba->board->checkThreadDate($bdata, $return_url);
				}
				$mitsuba->board->checkPostDate($bdata, $return_url);
			}
			$mime = "";
			if (!empty($_POST['embed']))
			{
				$filename = $mitsuba->checkEmbed($bdata, $_POST['embed'], $return_url);
			} else {
				if ((empty($_FILES['upfile']['tmp_name'])) && (!empty($_FILES['upfile']['name'])))
				{
					echo "<h1>".$lang['img/file_too_big']." [<a href='".$return_url."'>".$lang['img/return']."</a>]</h1></body></html>";
					exit;
				}
				$gen_thumb = 0;
				if (!empty($_FILES['upfile']['tmp_name']))
				{
					$target_path = "./".$board."/src/";
					$file_size = $_FILES['upfile']['size'];
					if (($file_size > $bdata['filesize']) && ($ignoresizelimit != 1))
					{
						echo "<h1>".$lang['img/file_too_big']." [<a href='".$return_url."'>".$lang['img/return']."</a>]</h1></body></html>";
						exit;
					}
					if (!($nfo = $mitsuba->common->isFile($_FILES['upfile']['tmp_name'], $bdata['extensions'])))
					{
						echo "<h1>".$lang['img/file_not_img']." [<a href='".$return_url."'>".$lang['img/return']."</a>]</h1></body></html>";
						exit;
					}
					$mime = $nfo['mimetype'];
					$ext = ".".$nfo['extension'];
					$fileid = time() . mt_rand(10000000, 999999999);
					$filename = $fileid . $ext; 
					$target_path .= $filename;
					$md5 = md5_file($_FILES['upfile']['tmp_name']);
					if (($bdata['nodup'] == 1) && (($mod == 0) || ($mod_type == 0)))
					{
						$isit = $conn->query("SELECT * FROM posts WHERE filehash='".$md5."' AND board='".$_POST['board']."'");
						if ($isit->num_rows >= 1)
						{
							echo "<h1>".$lang['img/file_duplicate']." [<a href='".$return_url."'>".$lang['img/return']."</a>]</h1></body></html>";
							exit;
						}
					}
					if(move_uploaded_file($_FILES['upfile']['tmp_name'], $target_path)) {
						if ($nfo['image']==1) { $gen_thumb = 1; }
						printf($lang['img/file_uploaded'], basename( $_FILES['upfile']['name']));
					} else {
						echo $lang['img/upload_error'];
						$filename = "";
					}
				}
			}
			$name = $lang['img/anonymous'];
			if (!empty($bdata['anonymous']))
			{
				$name = $bdata['anonymous'];
			}
			if ((!empty($_POST['name'])) && (($bdata['noname'] == 0) || (($mod >= 1) && ($mod_type >= 2)))) { $name = $_POST['name']; }
			$resto = 0;
			if (isset($_POST['resto'])) { $resto = $_POST['resto']; }
			$password = "";
			if (empty($_POST['pwd']))
			{
				if (isset($_COOKIE['password']))
				{
					$password = $_COOKIE['password'];
				} else {
					$password = $mitsuba->common->randomPassword();
				}
			} else {
				$password = $_POST['pwd'];
			}
			$thumb_w = 0;
			$thumb_h = 0;
			if ((substr($filename, 0, 6) != "embed:") && ($gen_thumb == 1))
			{
				if (!empty($_FILES['upfile']['tmp_name']))
				{
					if ($resto != 0)
					{
						$returned = $mitsuba->common->thumb($board, $fileid.$ext, 125);
						if ((empty($returned['width'])) || (empty($returned['height'])))
						{
							unlink($target_path);
							echo "<h1>".$lang['img/no_thumb']."</h1></body></html>"; exit;
						}
						$thumb_w = $returned['width'];
						$thumb_h = $returned['height'];
					} else {
						$returned = $mitsuba->common->thumb($board, $fileid.$ext);
						if ((empty($returned['width'])) || (empty($returned['height'])))
						{
							unlink($target_path);
							echo "<h1>".$lang['img/no_thumb']."</h1></body></html>"; exit;
						}
						$thumb_w = $returned['width'];
						$thumb_h = $returned['height'];
					}
				}
			}
			$capcode = 0;
			$raw = 0;
			$sticky = 0;
			$lock = 0;
			$nolimit = 0;
			$nofile = 0;
			$fake_id = "";
			$cc_text = "";
			$cc_color = "";
			if (!empty($_POST['name'])) { setcookie("mitsuba_name", $_POST['name'], time() + 86400*256); } else { setcookie("mitsuba_name","", time() + 86400*256); }
			if ((!empty($_POST['email'])) && ($_POST['email'] != "sage")) { setcookie("mitsuba_email", $_POST['email'], time() + 86400*256); } else { setcookie("mitsuba_email","", time() + 86400*256); }
			if (!empty($_POST['fake_id'])) { setcookie("mitsuba_fakeid", $_POST['fake_id'], time() + 86400*256); } else { setcookie("mitsuba_fakeid","", time() + 86400*256); }
			
			if (($mod >= 1) && ($mod_type>=2))
			{
				if ((!empty($_POST['nolimit'])) && ($_POST['nolimit']==1))
				{
					$nolimit = 1;
				}
				if ((!empty($_POST['capcode'])) && ($_POST['capcode']==1))
				{
					$capcode = $mod_type;
				} elseif ((!empty($_POST['capcode'])) && ($_POST['capcode']==2) && (!empty($_POST['cc_text'])) && (!empty($_POST['cc_color'])))
				{
					$capcode = 5;
					$cc_text = $_POST['cc_text'];
					$cc_color = $_POST['cc_color'];
				}
				if ((!empty($_POST['raw'])) && ($_POST['raw']==1))
				{
					$raw = 1;
				}
				if ((!empty($_POST['nofile'])) && ($_POST['nofile']==1))
				{
					$nofile = 1;
				}
				if ((!empty($_POST['sticky'])) && ($_POST['sticky']==1))
				{
					$sticky = 1;
				}
				if ((!empty($_POST['lock'])) && ($_POST['lock']==1))
				{
					$lock = 1;
				}
				if (!empty($_POST['fake_id']))
				{
					$fake_id = $_POST['fake_id'];
				}
			}
			$spoiler = 0;
			if ((!empty($_POST['spoiler'])) && ($_POST['spoiler'] == 1) && ($bdata['spoilers'] == 1) && (substr($filename, 0, 6) != "embed:"))
			{
				$spoiler = 1;
			}
			setcookie("password", $password, time() + 86400*256);
			$embed = 0;
			if (substr($filename, 0, 6) != "embed:")
			{
				$fname = $_FILES['upfile']['name'];
				$filename = "";
				if (empty($_FILES['upfile']['tmp_name']))
				{
					$fname = "";
				} else {
					$filename = $fileid.$ext;
				}
			} else {
				$embed = 1;
				$fname = "embed";
			}
			$redirect = 0;
			if ($mod == 1)
			{
				$redirect = 1;
			}
			$is = $mitsuba->posting->addPost($_POST['board'], $name, $_POST['email'], $_POST['sub'], $_POST['com'], $password, $filename, $fname, $mime, $resto, $md5, $thumb_w, $thumb_h, $spoiler, $embed, $mod_type, $capcode, $raw, $sticky, $lock, $nolimit, $nofile, $fake_id, $cc_text, $cc_color, $redirect);
			if ($is == -16)
			{
					echo "<h1>".$lang['img/board_no_exists']."</h1></body></html>"; exit;
			}
			break;
		case "usrform":
			if (!empty($_POST['delete']))
			{
				$onlyimgdel = 0;
				$password = "";
				if (empty($_POST['board']))
				{
					echo "<h1>".$lang['img/no_board']."</h1></body></html>";
					exit;
				}
				$board = $_POST['board'];
				$mitsuba->common->banMessage($board);
				$password = "";
				if ($mod == 0)
				{
					if (isset($_COOKIE['password'])) { $password = $_COOKIE['password']; }
					if (!empty($_POST['pwd'])) { $password = $_POST['pwd']; }
				}
				if ((isset($_POST['onlyimgdel']) && ($_POST['onlyimgdel'] == "on"))) { $onlyimgdel = 1; }
				foreach ($_POST as $key => $value)
				{
					if ($value == "delete")
					{
						$keys = explode("/", substr($key, 4));
						$done = $mitsuba->posting->deletePost($keys[0], $keys[1], $password, $onlyimgdel, $mod_type);
						if ($done == -1) {
							echo sprintf($lang["img/post_bad_password"],$key).".<br />";
						} elseif ($done == -2) {
							echo sprintf($lang["img/post_not_found"],$key)."<br />";
						} elseif ($done == -3) {
							echo sprintf($lang["img/post_no_image"],$key)."<br />";
						} elseif ($done == -4) {
							echo sprintf($lang["img/post_wait_more"],$key).".<br />";
						} elseif ($done == 1) {
							echo sprintf($lang["img/post_deleted_image"],$key).".<br />";
						} elseif ($done == 2) {
							echo sprintf($lang["img/post_deleted"],$key).".<br />";
						}
						if ($done == -16)
						{
							echo "<h1>".$lang['img/board_no_exists']."</h1></body></html>"; exit;
						}
					}
				}
				echo '<meta http-equiv="refresh" content="2;URL='."'".$return_url."index.html'".'">';
			} elseif (!empty($_POST['report'])) {
				if (empty($_POST['board']))
				{
					echo "<h1>".$lang['img/no_board']."</h1></body></html>";
					exit;
				}
				$board = $_POST['board'];
				$mitsuba->common->banMessage($board);
				foreach ($_POST as $key => $value)
				{
					if ($value == "delete")
					{
						$done = $mitsuba->posting->reportPost($_POST['board'], $key, $_POST['reason']);
						if ($done == 1)
						{
							echo sprintf($lang['img/post_reported'], $key)."<br />";
						}
					}
				}
				if ($mod == 1)
				{
					echo '<meta http-equiv="refresh" content="2;URL='."'./mod.php?/board&b=".$_POST['board']."'".'">';
				} else {
					echo '<meta http-equiv="refresh" content="1;URL='."'".$return_url."index.html'".'">';
				}
			}
			break;
		case "usrapp":
			//$_POST['email']; $_POST['msg'];
			if (!empty($_POST['msg']))
			{
				$msg = $conn->real_escape_string(htmlspecialchars($_POST['msg']));
				$email = $conn->real_escape_string(htmlspecialchars($_POST['email']));
				$ip = $_SERVER['REMOTE_ADDR'];
				$ban = $mitsuba->common->isBanned($ip, $_POST['board']);
				$ban_id = $ban['id'];
				$range = 0;
				if (!empty($bandata['start_ip'])) { $range = 1; }
				$conn->query("INSERT INTO appeals (created, ban_id, ip, msg, email, rangeban) VALUES (".time().", ".$ban_id.", '".$ip."', '".$msg."', '".$email."', ".$range.")");
				echo $lang['img/appeal_sent'];
			}
			break;
	}
	mysqli_close($conn);
} else {

}
?>
</body>
</html>
