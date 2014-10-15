<?php
	/*
		This program is free software: you can redistribute it and/or modify
		it under the terms of the GNU General Public License as published by
		the Free Software Foundation, either version 3 of the License, or
		(at your option) any later version.

		This program is distributed in the hope that it will be useful,
		but WITHOUT ANY WARRANTY; without even the implied warranty of
		MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
		GNU General Public License for more details.

		You should have received a copy of the GNU General Public License
		along with this program.  If not, see <http://www.gnu.org/licenses/>.
	*/

	// ============== Configuration begin  ==============

	$settings = array(

		// Directory to store uploaded files
		uploaddir => '.',

		// Display list uploaded files
		listfiles => true,

		// Allow users to delete files that they have uploaded (will enable sessions)
		allow_deletion => true,

		// Display file sizes
		listfiles_size => true,

		// Display file dates
		listfiles_date => true,

		// Display file dates format
		listfiles_date_format => 'F d Y H:i:s',

		// Randomize file names (number of 'false')
		random_name_len => 8,

		// Keep filetype information (if random name is activated)
		random_name_keep_type => true,

		// Random file name letters
		random_name_alphabet => 'qazwsxedcrfvtgbyhnujmikolp1234567890',

		// Display debugging information
		debug => false,

		// Complete URL to your directory (including tracing slash)
		url => 'http://strace.club/',

	);

	// ============== Configuration end  ==============

	$data = array();

	// Name of this file
	$data['scriptname'] = pathinfo(__FILE__, PATHINFO_BASENAME);

	// Use canonized path
	$data['uploaddir'] = realpath($settings['uploaddir']);

	// Maximum upload size, set by system
	$data['max_upload_size'] = ini_get('upload_max_filesize');

	if ($settings['allow_deletion']) {
		session_start();

		if (!isset($_SESSION['upload_user_id']))
			$_SESSION['upload_user_id'] = rand(1000, 9999);

		if (!isset($_SESSION['upload_user_files']))
			$_SESSION['upload_user_files'] = array();
	}

	if ($settings['debug']) {


		// Enabling error reporting
		error_reporting(E_ALL);
		error_reporting(1);

		// Displaying debug information
		echo '<h2>Debugging information: settings</h2>';
		echo '<pre>';
		print_r($settings);
		echo '</pre>';

		// Displaying debug information
		echo '<h2>Debugging information: data</h2>';
		echo '<pre>';
		print_r($data);
		echo '</pre>';
		echo '</pre>';

		// Displaying debug information
		echo '<h2>Debugging information: _SESSION</h2>';
		echo '<pre>';
		print_r($_SESSION);
		echo '</pre>';
	}

	function FormatSize ($bytes) {
		$units = array('B', 'KB', 'MB', 'GB', 'TB');

		$bytes = max($bytes, 0);
		$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
		$pow = min($pow, count($units) - 1);

		$bytes /= pow(1024, $pow);

		return ceil($bytes) . ' ' . $units[$pow];
	}

	function DiverseArray ($vector) {
		$result = array();
		foreach($vector as $key1 => $value1)
			foreach($value1 as $key2 => $value2)
				$result[$key2][$key1] = $value2;
		return $result;
	}

	function UploadFile ($file_data) {
		global $settings;
		global $data;
		global $_SESSION;

		$data['uploaded_file_name'] = basename($file_data['name']);
		$data['target_file_name'] = $file_data['uploaded_file_name'];
		if ($settings['random_name_len'] !== false) {
			do {
				$data['target_file_name'] = '';
				while (strlen($data['target_file_name']) < $settings['random_name_len'])
					$data['target_file_name'] .= $settings['random_name_alphabet'][rand(0, strlen($settings['random_name_alphabet']) - 1)];
				if ($settings['random_name_keep_type'])
					$data['target_file_name'] .= '.' . pathinfo($data['uploaded_file_name'], PATHINFO_EXTENSION);
			} while (file_exists($data['target_file_name']));
		}
		$data['upload_target_file'] = $data['uploaddir'] . DIRECTORY_SEPARATOR . $data['target_file_name'];
		$data['tmp_name'] = $file_data['tmp_name'];

		if (file_exists($data['upload_target_file'])) {
			echo 'File name already exists' . "\n";
			return;
		}

		if (move_uploaded_file($data['tmp_name'], $data['upload_target_file'])) {
			if ($settings['allow_deletion'])
				$_SESSION['upload_user_files'][] = $data['target_file_name'];
			echo $settings['url'] .  $data['target_file_name'] . "\n";
		} else {
			echo 'Error: unable to upload the file.';
		}
	}

	if (isset($_FILES['file'])) {
		if ($settings['debug']) {
			// Displaying debug information
			echo '<h2>Debugging information: data</h2>';
			echo '<pre>';
			print_r($data);
			echo '</pre>';
			// Displaying debug information
			echo '<h2>Debugging information: file</h2>';
			echo '<pre>';
			print_r($_FILES);
			echo '</pre>';
		}

		if (is_array($_FILES['file'])) {
			$file_array = DiverseArray($_FILES['file']);
			foreach ($file_array as $file_data)
				UploadFile($file_data);
		} else
			UploadFile($_FILES['file']);
		exit;
	}

	if ($settings['allow_deletion'])
		if (isset($_POST))
			if ($_POST['action'] === 'delete')
				if (in_array($_POST['target'], $_SESSION['upload_user_files']))
					if (file_exists($_POST['target'])) {
						unlink($_POST['target']);
						echo 'File has been removed';
						exit;
					}

	function ListFiles ($dir, $exclude) {
		$file_array = array();
		$dh = opendir($dir);
			while (false !== ($filename = readdir($dh)))
				if (is_file($filename) && !in_array($filename, $exclude))
					$file_array[filemtime($filename)] = $filename;
		ksort($file_array);
		$file_array = array_reverse($file_array, true);
		return $file_array;
	}

?>
<html lang="en-GB">
	<head>
		<meta charset="utf-8">
		<title>strace.club</title>
		<style media="screen">
			body {
				background: #111;
				margin: 0;
				color: #ddd;
				font-family: sans-serif;
			}

			h1 {
				display: block;
				background: rgba(255, 255, 255, 0.05);
				padding: 8px 16px;
				text-align: center;
				margin: 0;
			}

			form {
				display: block;
				background: rgba(255, 255, 255, 0.075);
				padding: 16px 16px;
				margin: 0;
			}

			p {
				display: block;
				background: rgba(255, 255, 255, 0.075);
				padding: 4px 16px;
				margin: 16px 0 0 0;
				text-align: center;
			}

			ul {
				display: block;
				margin: 0;
				padding: 0;
			}

			ul > li {
				display: block;
				margin: 0;
				padding: 0;
			}

			ul > li > a {
				display: block;
				margin: 0 0 1px 0;
				list-style: none;
				background: rgba(255, 255, 255, 0.1);
				padding: 8px 16px;
				text-decoration: none;
				color: inherit;
				opacity: 0.5;
			}

			ul > li > a > span {
				float: right;
				font-size: 90%;
			}

			ul > li > a:hover {
				opacity: 1;
			}
		</style>
	</head>
	<body>
		<h1>strace.club</h1>
		<form action="<?= $data['scriptname'] ?>" method="POST" enctype="multipart/form-data" class="dropzone" id="my-awesome-dropzone">
			Maximum upload size: <?php echo $data['max_upload_size']; ?><br />
			<input type="file" name="file[]" multiple required onchange="formname.submit();" />
		</form>
		<?php if ($settings['listfiles']) { ?>
			<p>Uploaded files:</p>
			<ul>
				<?php
					$file_array = ListFiles($settings['uploaddir'], array('.', '..', $data['scriptname']));
					foreach ($file_array as $mtime => $filename) {
						$file_info = array();

						if ($settings['listfiles_size'])
							$file_info[] = FormatSize(filesize($filename));

						if ($settings['listfiles_size'])
							$file_info[] = date($settings['listfiles_date_format'], $mtime);

						if ($settings['allow_deletion'])
							if (in_array($filename, $_SESSION['upload_user_files']))
								$file_info[] = '<form action="' . $data['scriptname'] . '" method="POST"><input type="hidden" name="target" value="' . $filename . '" /><input type="hidden" name="action" value="delete" /><button type="submit">delete</button></form>';

						$file_info = implode(', ', $file_info);

						if (strlen($file_info) > 0)
							$file_info = ' (' . $file_info . ')';

						echo "<li><a href=\"$filename\" target=\"_blank\">$filename<span>$file_info</span></a></li>";
					}
				?>
			</ul>
		<?php } ?>
		<a href="https://github.com/you"><img style="position: absolute; top: 0; right: 0; border: 0;" src="https://camo.githubusercontent.com/38ef81f8aca64bb9a64448d0d70f1308ef5341ab/68747470733a2f2f73332e616d617a6f6e6177732e636f6d2f6769746875622f726962626f6e732f666f726b6d655f72696768745f6461726b626c75655f3132313632312e706e67" alt="Fork me on GitHub" data-canonical-src="https://s3.amazonaws.com/github/ribbons/forkme_right_darkblue_121621.png"></a>
	</body>
</html>
