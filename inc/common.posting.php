<?php

function deletePost($conn, $board, $postno, $password, $onlyimgdel = 0)
{
	
	if (is_numeric($postno))
	{
		$board = mysqli_real_escape_string($conn, $board);
		if (!isBoard($conn, $board))
		{
			return -16;
		}
		$result = mysqli_query($conn, "SELECT * FROM posts_".$board." WHERE id=".$postno);
		if (mysqli_num_rows($result) == 1)
		{
			$postdata = mysqli_fetch_assoc($result);
			if (md5($password) == $postdata['password'])
			{
				if ($onlyimgdel == 1)
				{
					if (!empty($postdata['filename']))
					{
						mysqli_query($conn, "UPDATE posts_".$board." SET filename='deleted' WHERE id=".$postno.";");
						if ($postdata['resto'] != 0)
						{
							generateView($conn, $board, $postdata['resto']);
							generateView($conn, $board);
						} else {
							generateView($conn, $board, $postno);
							generateView($conn, $board);
						}
						return 1; //done-image
					} else {
						return -3;
					}
				} else {
					if ($postdata['resto'] == 0) //we'll have to delete whole thread
					{
						mysqli_query($conn, "DELETE FROM posts_".$board." WHERE resto=".$postno.";");
						mysqli_query($conn, "DELETE FROM posts_".$board." WHERE id=".$postno.";");
						unlink("./".$board['short']."/res/".$row['id'].".html");
						//generateView($conn, $board, $postno);
						generateView($conn, $board);
						return 2; //done post
					} else {
						mysqli_query($conn, "DELETE FROM posts_".$board." WHERE id=".$postno.";");
						generateView($conn, $board, $postdata['resto']);
						generateView($conn, $board);
						return 2;
					}
				}
			} else {
				return -1; //wrong password
			}
		} else {
			return -2;
		}
	} else {
		return -2;
	}
}

function addPost($conn, $board, $name, $email, $subject, $comment, $password, $filename, $orig_filename, $resto = null)
{
	if (!isBoard($conn, $board))
	{
		return -16;
	}
	if (!is_numeric($resto))
	{
		$resto = 0;
	}
	if (($resto == 0) && (empty($filename)))
	{
		echo "<center><h1>Error: No file selected.</h1><br /><a href='./".$board."'>RETURN</a></center>";
		return;
	}
	
	if ((empty($filename)) && (empty($comment)))
	{
		echo "<center><h1>Error: No file selected.</h1><br /><a href='./".$board."'>RETURN</a></center>";
		return;
	}
	$thread = "";
	$tinfo = "";
	if ($resto != 0)
	{
		$thread = mysqli_query($conn, "SELECT * FROM posts_".$board." WHERE id=".$resto);
		if (mysqli_num_rows($thread) == 0)
		{
			echo "<center><h1>Error: Cannot reply to thread because thread does not exist.</h1><br /><a href='./".$board."'>RETURN</a></center>";
			return;
		}
		
		$tinfo = mysqli_fetch_assoc($thread);
		if ($tinfo['locked'] == 1)
		{
			echo "<center><h1>Error: This thread is locked.</h1><br /><a href='./".$board."'>RETURN</a></center>";
			return;
		}
		
	}
	$lastbumped = time();
	$trip = "";
	$name = processString($conn, $name, 1);
	if (isset($name['trip']))
	{
		$trip = $name['trip'];
		$name = $name['name'];
	}
	mysqli_query($conn, "INSERT INTO posts_".$board." (date, name, trip, email, subject, comment, password, orig_filename, filename, resto, ip, lastbumped, filehash, sticky, sage, locked, capcode, raw)".
	"VALUES (".time().", '".$name."', '".$trip."', '".processString($conn, $email)."', '".processString($conn, $subject)."', '".preprocessComment($conn, $comment)."', '".md5($password)."', '".processString($conn, $orig_filename)."', '".$filename."', ".$resto.", '".$_SERVER['REMOTE_ADDR']."', ".$lastbumped.", 'abc', 0, 0, 0, 0, 0)");
	$id = mysqli_insert_id($conn);
	if ($resto != 0)
	{
		if (($email == "sage") || ($email == "nokosage") || ($email == "nonokosage") || ($tinfo['sage'] == 1))
		{
		
		} else {
			mysqli_query($conn, "UPDATE posts_".$board." SET lastbumped=".time()." WHERE id=".$resto);
		}
	
	
	}
	if (($email == "nonoko") || ($email == "nonokosage"))
	{
		echo '<meta http-equiv="refresh" content="2;URL='."'./".$board."/index.html'".'">';
		
	} else {
		if ($resto != 0)
		{
			echo '<meta http-equiv="refresh" content="2;URL='."'./".$board."/res/".$resto.".html#p".$id."".'">';
		} else {
			echo '<meta http-equiv="refresh" content="2;URL='."'./".$board."/res/".$id.".html'".'">';
			
		}
	}
	if ($resto == 0)
	{
		generateView($conn, $board, $id);
	} else {
		generateView($conn, $board, $resto);
	}
	generateView($conn, $board);
}

function reportPost($conn, $board, $id, $reason)
{
	if (is_numeric($id))
	{
		$board = mysqli_real_escape_string($conn, $board);
		if (!isBoard($conn, $board))
		{
			return -16;
		}
		$result = mysqli_query($conn, "SELECT * FROM posts_".$board." WHERE id=".$id);
		if (mysqli_num_rows($result) == 1)
		{
			$postdata = mysqli_fetch_assoc($result);
			$result = mysqli_query($conn, "SELECT * FROM reports WHERE reported_post=".$id." AND board='".$board."'");
			if (mysqli_num_rows($result) == 0)
			{
				$reason = mysqli_real_escape_string($conn, $reason);
				mysqli_query($conn, "INSERT INTO reports (reporter_ip, reported_post, reason, created, board) VALUES ('".$_SERVER['REMOTE_ADDR']."', ".$id.", '".$reason."', ".time().", '".$board."')");
			} else {
				return 1;
			}
		} else {
			return -15;
		}
	} else {
		return -15;
	}
}

?>