<?php
namespace Mitsuba\Admin;
class UI {
	private $conn;
	private $mitsuba;

	function __construct($connection, &$mitsuba) {
		$this->conn = $connection;
		$this->mitsuba = $mitsuba;
	}

	function getBoardList($boards = "")
	{
		global $lang;
		if ($boards == "*")
		{
		?>
		<?php echo $lang['mod/boards']; ?>: <input type="checkbox" name="all" id="all" onClick="$('#boardSelect').toggle()" value=1 checked/> <?php echo $lang['mod/all']; ?><br/>
		<?php
		} else {
		?>
		<?php echo $lang['mod/boards']; ?>: <input type="checkbox" name="all" id="all" onClick="$('#boardSelect').toggle()" value=1/> <?php echo $lang['mod/all']; ?><br/>
		<?php
		}
		?>
		<fieldset id="boardSelect">
		<?php
		if (($boards != "*") && ($boards != "")) { $boards = substr($boards, 0, strlen($boards) - 1); }
		$result = $this->conn->query("SELECT * FROM boards;");
		while ($row = $result->fetch_assoc())
		{
		$checked = "";
		if (($boards !== "*") && ($boards !== ""))
		{
			if (in_array($boards, $row['short']))
			{
				$checked = " checked ";
			}
		}
		echo "<label for='boards'>/".$row['short']."/ - ".$row['name']."</label>";
		echo "<input type='checkbox' onClick='document.getElementById(\"all\").checked=false;' name='boards[]' value='".$row['short']."'".$checked."/>";
		}
		?>
		</fieldset>
		<?php
	}

	function startSection($title)
	{
		?>
		<div class="box-outer top-box">
		<div class="box-inner">
		<div class="boxbar"><h2><?php echo $title; ?></h2></div>
		<div class="boxcontent">
		<?php
	}

	function endSection()
	{
		?>
		</div>
		</div>
		</div>
		<?php
	}
}
?>