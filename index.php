<?php
	$settings = array();

	// Directory to store the uploaded files
	$settings['uploaddir'] = '.';

	// List uploaded files
	$settings['listfiles'] = '.';

	// Relative path to this file (don't edit)
	$settings['scriptpath'] = $_SERVER['PHP_SELF'];

	// Name of this file (don't edit)
	$settings['scriptname'] = pathinfo(__FILE__, PATHINFO_FILENAME) . '.php';

	if (isset($_FILES['fileup']) && strlen($_FILES['fileup']['name']) > 1) {
		$upload_file_name = basename($_FILES['fileup']['name']);
		$uploadpath = $settings['uploaddir'] . DIRECTORY_SEPARATOR . $upload_file_name;
		$page_url = $_SERVER['HTTP_HOST'].rtrim(dirname($_SERVER['REQUEST_URI']), '\\/');

		if (move_uploaded_file($_FILES['fileup']['tmp_name'], $uploadpath)) {
			echo 'http://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['REQUEST_URI']), '\\/') . '/' . $uploadpath;
			exit;
			// echo 'File: <b>' . $upload_file_name . '</b> successfully uploaded:<br />';
			// echo 'Size: <b>'. number_format($_FILES['fileup']['size'] / 1024, 3, '.', '') .'KB</b><br />';
			// echo 'File /URL: <b><a href="http://'.$_SERVER['HTTP_HOST'].rtrim(dirname($_SERVER['REQUEST_URI']), '\\/').'/'.$uploadpath.'">http://'.$_SERVER['HTTP_HOST'].rtrim(dirname($_SERVER['REQUEST_URI']), '\\/').'/'.$uploadpath.'</a></b>';
		} else {
			echo 'Error: unable to upload the file.';
			exit;
		}
	}
?>
<html>
	<head>
		<title>Upload <?=$settings['scriptname']?></title>
		<script src="./dropzone.js"></script>
	</head>
	<body>
		<form action="<?= $settings['scriptpath'] ?>" method="POST" enctype="multipart/form-data" class="dropzone" id="my-awesome-dropzone">
			Choose File: <input type="file" name="fileup" /><br />
			<input type="submit" value="Upload" />
		</form>
		<? if ($settings['listfiles']) { ?>
			<strong>Uploaded files:</strong><br />
			<ul>
				<?
					$dh = opendir($settings['uploaddir']);
					while (false !== ($filename = readdir($dh)))
						if (!in_array($filename, array('.', '..', $settings['scriptname'])))
							echo "<li><a href=\"$filename\">$filename</a></li>";
				?>
			</ul>
		<? } ?>
	</body>
</html>
