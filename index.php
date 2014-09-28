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
		random_name_len => 4,

		// Keep filetype information (if random name is activated)
		random_name_keep_type => true,

		// Random file name letters
		random_name_alphabet => 'qwertyuiopasdfghjklzxcvbnm',

		// Display debugging information
		debug => ($_SERVER['SERVER_NAME'] === 'localhost')

	);

	// ============== Configuration end  ==============

	$data = array();

	// Name of this file
	$data['scriptname'] = pathinfo(__FILE__, PATHINFO_BASENAME);

	// URL to upload page
	$data['pageurl'] = "http" . (($_SERVER['SERVER_PORT']==443) ? "s://" : "://") . $_SERVER['SERVER_NAME'] . dirname($_SERVER['REQUEST_URI']) . '/';

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


		echo '<pre>';
		print_r($data);
		echo '</pre>';


		if (move_uploaded_file($data['tmp_name'], $data['upload_target_file'])) {
			if ($settings['allow_deletion'])
				$_SESSION['upload_user_files'][] = $data['target_file_name'];
			echo $data['pageurl'] .  $data['upload_target_file'] . "\n";
			// echo 'File: <b>' . $data['uploaded_file_name'] . '</b> successfully uploaded:<br />';
			// echo 'Size: <b>'. number_format($_FILES['file']['size'] / 1024, 3, '.', '') .'KB</b><br />';
			// echo 'File /URL: <b><a href="http://'.$_SERVER['HTTP_HOST'].rtrim(dirname($_SERVER['REQUEST_URI']), '\\/').'/'.$data['upload_target_file'].'">http://'.$_SERVER['HTTP_HOST'].rtrim(dirname($_SERVER['REQUEST_URI']), '\\/').'/'.$data['upload_target_file'].'</a></b>';
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
?>
<html lang="en-GB">
	<head>
		<meta charset="utf-8">
		<title>Simple PHP Upload</title>
	</head>
	<body>
		<h1>Simple PHP Upload</h1>
		<p>
			Maximum upload size: <?php echo $data['max_upload_size']; ?>
		</p>
		<form action="<?= $data['scriptname'] ?>" method="POST" enctype="multipart/form-data" class="dropzone" id="my-awesome-dropzone">
			<div class="fallback">
				Choose File: <input type="file" name="file[]" multiple required /><br />
				<input type="submit" value="Upload" />
			</div>
		</form>
		<?php if ($settings['listfiles']) { ?>
			<strong>Uploaded files:</strong><br />
			<ul>
				<?php
					$dh = opendir($settings['uploaddir']);
					while (false !== ($filename = readdir($dh)))
						if (is_file($filename) && !in_array($filename, array('.', '..', $data['scriptname']))) {
							$file_info = array();

							if ($settings['listfiles_size'])
								$file_info[] = FormatSize(filesize($filename));

							if ($settings['listfiles_size'])
								$file_info[] = date($settings['listfiles_date_format'], filemtime($filename));

							if ($settings['allow_deletion'])
								if (in_array($filename, $_SESSION['upload_user_files']))
									$file_info[] = '<form action="' . $data['scriptname'] . '" method="POST"><input type="hidden" name="target" value="' . $filename . '" /><input type="hidden" name="action" value="delete" /><button type="submit">delete</button></form>';

							$file_info = implode(', ', $file_info);

							if (strlen($file_info) > 0)
								$file_info = ' (' . $file_info . ')';

							echo "<li><a href=\"$filename\">$filename</a>$file_info</li>";
						}
				?>
			</ul>
		<?php } ?>
	</body>
</html>
