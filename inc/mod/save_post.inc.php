<?php
if (!defined("IN_MOD"))
{
	die("Nah, I won't serve that file to you.");
}
reqPermission(2);
		if ((!empty($_POST['b'])) && (!empty($_POST['p'])) && (isBoard($conn, $_POST['b'])) && (is_numeric($_POST['p'])) && (!empty($_POST['text'])))
		{
			$result = $conn->query("SELECT * FROM posts_".$_POST['b']." WHERE id=".$_POST['p']);
			if ($result->num_rows == 1)
			{
				$row = $result->fetch_assoc();
				$raw = 0;
				if ((isset($_POST['raw'])) && ($_POST['raw'] == 1))
				{
					$raw = 1;
				}
				$conn->query("UPDATE posts_".$_POST['b']." SET comment='".preprocessComment($conn, $_POST['text'])."', raw=".$raw." WHERE id=".$_POST['p']);
				$resto = $row['resto'];
				if ($row['resto'] == 0)
				{
					generateView($conn, $_POST['b'], $row['id']);
					$resto = $row['id'];
				} else {
					generateView($conn, $_POST['b'], $row['resto']);
				}
				generateView($conn, $_POST['b']);
				?>
				<div class="box-outer top-box">
	<div class="box-inner">
	<div class="boxbar"><h2><?php echo $lang['mod/post_updated']; ?></h2></div>
	</div>
	</div>
	</div>
	<meta http-equiv="refresh" content="2;URL='?/board&b=<?php echo $_POST['b']; ?>&t=<?php echo $resto; ?>#p<?php echo $row['id']; ?>'" />
				<?php
			}
		}
?>