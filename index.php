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

	// =============={ Configuration Begin }==============
	$settings = array(

		// Website title
		'title' => 'strace.club',

		// Directory to store uploaded files
		'uploaddir' => '.',

		// Display list uploaded files
		'listfiles' => true,

		// Allow users to delete files that they have uploaded (will enable sessions)
		'allow_deletion' => true,

		// Allow users to mark files as hidden
		'allow_private' => true,

		// Display file sizes
		'listfiles_size' => true,

		// Display file dates
		'listfiles_date' => true,

		// Display file dates format
		'listfiles_date_format' => 'F d Y H:i:s',

		// Randomize file names (number of 'false')
		'random_name_len' => 8,

		// Keep filetype information (if random name is activated)
		'random_name_keep_type' => true,

		// Random file name letters
		'random_name_alphabet' => 'qazwsxedcrfvtgbyhnujmikolp1234567890',

		// Display debugging information
		'debug' => false,

		// Complete URL to your directory (including tracing slash)
		'url' => 'http://strace.club/',

		// Amount of seconds that each file should be stored for (0 for no limit)
		// Default 30 days
		'time_limit' => 60 * 60 * 24 * 30,

		// Files that will be ignored
		'ignores' => array('.', '..', 'LICENSE', 'README.md'),
	);
	// =============={ Configuration End }==============

	// Enabling error reporting
	if ($settings['debug']) {
		error_reporting(E_ALL);
		ini_set('display_startup_errors',1);
		ini_set('display_errors',1);
	}

	$data = array();

	// Name of this file
	$data['scriptname'] = pathinfo(__FILE__, PATHINFO_BASENAME);

	// Adding current script name to ignore list
	$data['ignores'] = $settings['ignores'];
	$data['ignores'][] = $data['scriptname'];

	// Use canonized path
	$data['uploaddir'] = realpath($settings['uploaddir']);

	// Maximum upload size, set by system
	$data['max_upload_size'] = ini_get('upload_max_filesize');

	// If file deletion or private files are allowed, starting a session.
	// This is required for user authentification
	if ($settings['allow_deletion'] || $settings['allow_private']) {
		session_start();

		// 'User ID'
		if (!isset($_SESSION['upload_user_id']))
			$_SESSION['upload_user_id'] = rand(100000, 999999);

		// List of filenames that were uploaded by this user
		if (!isset($_SESSION['upload_user_files']))
			$_SESSION['upload_user_files'] = array();
	}

	// If debug is enabled, logging all variables
	if ($settings['debug']) {
		// Displaying debug information
		echo '<h2>Settings:</h2>';
		echo '<pre>';
		print_r($settings);
		echo '</pre>';

		// Displaying debug information
		echo '<h2>Data:</h2>';
		echo '<pre>';
		print_r($data);
		echo '</pre>';
		echo '</pre>';

		// Displaying debug information
		echo '<h2>SESSION:</h2>';
		echo '<pre>';
		print_r($_SESSION);
		echo '</pre>';
	}

	// Format file size
	function FormatSize ($bytes) {
		$units = array('B', 'KB', 'MB', 'GB', 'TB');

		$bytes = max($bytes, 0);
		$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
		$pow = min($pow, count($units) - 1);

		$bytes /= pow(1024, $pow);

		return ceil($bytes) . ' ' . $units[$pow];
	}

	// Rotating a two-dimensional array
	function DiverseArray ($vector) {
		$result = array();
		foreach ($vector as $key1 => $value1)
			foreach ($value1 as $key2 => $value2)
				$result[$key2][$key1] = $value2;
		return $result;
	}

	// Handling file upload
	function UploadFile ($file_data) {
		global $settings;
		global $data;
		global $_SESSION;

		$file_data['uploaded_file_name'] = basename($file_data['name']);
		$file_data['target_file_name'] = $file_data['uploaded_file_name'];

		// Generating random file name
		if ($settings['random_name_len'] !== false) {
			do {
				$file_data['target_file_name'] = '';
				while (strlen($file_data['target_file_name']) < $settings['random_name_len'])
					$file_data['target_file_name'] .= $settings['random_name_alphabet'][rand(0, strlen($settings['random_name_alphabet']) - 1)];
				if ($settings['random_name_keep_type'])
					$file_data['target_file_name'] .= '.' . pathinfo($file_data['uploaded_file_name'], PATHINFO_EXTENSION);
			} while (file_exists($file_data['target_file_name']));
		}
		$file_data['upload_target_file'] = $data['uploaddir'] . DIRECTORY_SEPARATOR . $file_data['target_file_name'];

		// Do now allow to overwriting files
		if (file_exists($file_data['upload_target_file'])) {
			echo 'File name already exists' . "\n";
			return;
		}

		// Moving uploaded file OK
		if (move_uploaded_file($file_data['tmp_name'], $file_data['upload_target_file'])) {
			if ($settings['allow_deletion'] || $settings['allow_private'])
				$_SESSION['upload_user_files'][] = $file_data['target_file_name'];
			echo $settings['url'] .  $file_data['target_file_name'] . "\n";
		} else {
			echo 'Error: unable to upload the file.';
		}
	}


	// Files are being POSEed. Uploading them one by one.
	if (isset($_FILES['file'])) {
		header('Content-type: text/plain');
		if (is_array($_FILES['file'])) {
			$file_array = DiverseArray($_FILES['file']);
			foreach ($file_array as $file_data)
				UploadFile($file_data);
		} else
			UploadFile($_FILES['file']);
		exit;
	}

	// Other file functions (delete, private).
	if (isset($_POST)) {
		if ($settings['allow_deletion'])
			if (isset($_POST['action']) && $_POST['action'] === 'delete')
				if (in_array(substr($_POST['target'], 1), $_SESSION['upload_user_files']) || in_array($_POST['target'], $_SESSION['upload_user_files']))
					if (file_exists($_POST['target'])) {
						unlink($_POST['target']);
						echo 'File has been removed';
						exit;
					}

		if ($settings['allow_private'])
			if (isset($_POST['action']) && $_POST['action'] === 'privatetoggle')
				if (in_array(substr($_POST['target'], 1), $_SESSION['upload_user_files']) || in_array($_POST['target'], $_SESSION['upload_user_files']))
					if (file_exists($_POST['target'])) {
						if ($_POST['target'][0] === '.') {
							rename($_POST['target'], substr($_POST['target'], 1));
							echo 'File has been made visible';
						} else {
							rename($_POST['target'], '.' . $_POST['target']);
							echo 'File has been hidden';
						}
						exit;
					}
	}

	// List files in a given directory, excluding certain files
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

	$file_array = ListFiles($settings['uploaddir'], $data['ignores']);

	// Removing old files
	foreach ($file_array as $file)
		if ($settings['time_limit'] < time() - filemtime($file))
			unlink($file);

	$file_array = ListFiles($settings['uploaddir'], $data['ignores']);

?>
<html lang="en-GB">
	<head>
		<meta charset="utf-8">
		<title><?=$settings['title']?></title>
		<style media="screen">
			body {
				background: #111;
				margin: 0;
				color: #ddd;
				font-family: sans-serif;
			}

			body > h1 {
				display: block;
				background: rgba(255, 255, 255, 0.05);
				padding: 8px 16px;
				text-align: center;
				margin: 0;
			}

			body > form {
				display: block;
				background: rgba(255, 255, 255, 0.075);
				padding: 16px 16px;
				margin: 0;
				text-align: center;
			}

			body > ul {
				display: block;
				padding: 0;
				max-width: 1000px;
				margin: 32px auto;
			}

			body > ul > li {
				display: block;
				margin: 0;
				padding: 0;
			}

			body > ul > li > a {
				display: block;
				margin: 0 0 1px 0;
				list-style: none;
				background: rgba(255, 255, 255, 0.1);
				padding: 8px 16px;
				text-decoration: none;
				color: inherit;
				opacity: 0.5;
			}

			body > ul > li > a:hover {
				opacity: 1;
			}

			body > ul > li > a:active {
				opacity: 0.5;
			}

			body > ul > li > a > span {
				float: right;
				font-size: 90%;
			}

			body > ul > li > form {
				display: inline-block;
				padding: 0;
				margin: 0;
			}

			body > ul > li.owned {
				margin: 8px;
			}

			body > ul > li > form > button {
				opacity: 0.5;
				display: inline-block;
				padding: 4px 16px;
				margin: 0;
				border: 0;
				background: rgba(255, 255, 255, 0.1);
				color: inherit;
			}

			body > ul > li > form > button:hover {
				opacity: 1;
			}

			body > ul > li > form > button:active {
				opacity: 0.5;
			}

			body > ul > li.uploading {
				animation: upanim 2s linear 0s infinite alternate;
			}

			@keyframes upanim {
				from {
					opacity: 0.3;
				}
				to {
					opacity: 0.8;
				}
			}
		</style>
	</head>
	<body>
		<h1><?=$settings['title']?></h1>
		<form action="<?= $data['scriptname'] ?>" method="POST" enctype="multipart/form-data" class="dropzone" id="simpleupload-form">
			Maximum upload size: <?php echo $data['max_upload_size']; ?><br />
			<input type="file" name="file[]" multiple required id="simpleupload-input"/>
		</form>
		<ul id="simpleupload-ul">
			<?php if ($settings['listfiles']) { ?>
				<?php
					foreach ($file_array as $mtime => $filename) {
						$file_info = array();
						$file_owner = false;
						$file_private = $filename[0] === '.';

						if ($settings['listfiles_size'])
							$file_info[] = FormatSize(filesize($filename));

						if ($settings['listfiles_size'])
							$file_info[] = date($settings['listfiles_date_format'], $mtime);

						if ($settings['allow_deletion'] || $settings['allow_private'])
							if (in_array(substr($filename, 1), $_SESSION['upload_user_files']) || in_array($filename, $_SESSION['upload_user_files']))
								$file_owner = true;

						$file_info = implode(', ', $file_info);

						if (strlen($file_info) > 0)
							$file_info = ' (' . $file_info . ')';

						$class = '';
						if ($file_owner)
							$class = 'owned';

						if (!$file_private || $file_owner) {
							echo "<li class=\"' . $class . '\">";

							echo "<a href=\"$filename\" target=\"_blank\">$filename<span>$file_info</span></a>";

							if ($file_owner) {
								if ($settings['allow_deletion'])
									echo '<form action="' . $data['scriptname'] . '" method="POST"><input type="hidden" name="target" value="' . $filename . '" /><input type="hidden" name="action" value="delete" /><button type="submit">delete</button></form>';

								if ($settings['allow_private'])
									if ($file_private)
										echo '<form action="' . $data['scriptname'] . '" method="POST"><input type="hidden" name="target" value="' . $filename . '" /><input type="hidden" name="action" value="privatetoggle" /><button type="submit">make public</button></form>';
									else
										echo '<form action="' . $data['scriptname'] . '" method="POST"><input type="hidden" name="target" value="' . $filename . '" /><input type="hidden" name="action" value="privatetoggle" /><button type="submit">make private</button></form>';
							}

							echo "</li>";
						}
					}
				?>
			<?php } ?>
		</ul>
		<a href="https://github.com/muchweb/simple-php-upload"><img style="position: absolute; top: 0; right: 0; border: 0;" src="https://camo.githubusercontent.com/38ef81f8aca64bb9a64448d0d70f1308ef5341ab/68747470733a2f2f73332e616d617a6f6e6177732e636f6d2f6769746875622f726962626f6e732f666f726b6d655f72696768745f6461726b626c75655f3132313632312e706e67" alt="Fork me on GitHub" data-canonical-src="https://s3.amazonaws.com/github/ribbons/forkme_right_darkblue_121621.png"></a>
		<script charset="utf-8">
			var target_form = document.getElementById('simpleupload-form'),
				target_ul = document.getElementById('simpleupload-ul'),
				target_input = document.getElementById('simpleupload-input');

			target_form.addEventListener('dragover', function (event) {
				event.preventDefault();
			}, false);

			function AddFileLi (name, info) {
				target_form.style.display = 'none';

				var new_li = document.createElement('li');
				new_li.className = 'uploading';

				var new_a = document.createElement('a');
				new_a.innerHTML = name;
				new_li.appendChild(new_a);

				var new_span = document.createElement('span');
				new_span.innerHTML = info;
				new_a.appendChild(new_span);

				target_ul.insertBefore(new_li, target_ul.firstChild);
			}

			function HandleFiles (event) {
				event.preventDefault();

				var i = 0,
					files = event.dataTransfer.files,
					len = files.length;

				var form = new FormData();

				for (; i < len; i++) {
					form.append('file[]', files[i]);
					AddFileLi(files[i].name, files[i].size + ' bytes');
				}

				var xhr = new XMLHttpRequest();
				xhr.onload = function() {
					window.location.reload();
				};

				xhr.open('post', '<?php echo $data['scriptname']; ?>', true);
				xhr.send(form);
			}

			target_form.addEventListener('drop', HandleFiles, false);

			document.getElementById('simpleupload-input').onchange = function () {
				AddFileLi('Uploading...', '');
				target_form.submit();
			};
		</script>
	</body>
</html>
