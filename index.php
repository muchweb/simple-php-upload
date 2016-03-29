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

		// Language code
		'lang' => 'en',

		// Language direction
		'lang_dir' => 'ltr',

		// Remove old files?
		'remove_old_files' => true,

		// Privacy: Allow external references (the "fork me" ribbon)
		'allow_external_refs' => true,
	);
	// =============={ Configuration End }==============

	// Is the local config file there?
	if (isReadableFile('config-local.php')) {
		// Load it then
		include('config-local.php');
	}

	// Enabling error reporting
	if ($settings['debug']) {
		error_reporting(E_ALL);
		ini_set('display_startup_errors',1);
		ini_set('display_errors',1);
	}

	$data = array();

	// Name of this file
	$data['scriptname'] = $settings['url'] . '/' . pathinfo(__FILE__, PATHINFO_BASENAME);

	// Adding current script name to ignore list
	$data['ignores'] = $settings['ignores'];
	$data['ignores'][] = basename($data['scriptname']);

	// Use canonized path
	$data['uploaddir'] = realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . $settings['uploaddir']);

	// Maximum upload size, set by system
	$data['max_upload_size'] = ini_get('upload_max_filesize');

	// If file deletion or private files are allowed, starting a session.
	// This is required for user authentification
	if ($settings['allow_deletion'] || $settings['allow_private']) {
		session_start();

		// 'User ID'
		if (!isset($_SESSION['upload_user_id']))
			$_SESSION['upload_user_id'] = mt_rand(100000, 999999);

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
	function formatSize ($bytes) {
		$units = array('B', 'KB', 'MB', 'GB', 'TB');

		$bytes = max($bytes, 0);
		$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
		$pow = min($pow, count($units) - 1);

		$bytes /= pow(1024, $pow);

		return ceil($bytes) . ' ' . $units[$pow];
	}

	// Rotating a two-dimensional array
	function diverseArray ($vector) {
		$result = array();
		foreach ($vector as $key1 => $value1)
			foreach ($value1 as $key2 => $value2)
				$result[$key2][$key1] = $value2;
		return $result;
	}

	// Handling file upload
	function uploadFile ($file_data) {
		global $settings, $data;

		$file_data['uploaded_file_name'] = basename($file_data['name']);
		$file_data['target_file_name'] = $file_data['uploaded_file_name'];

		// Generating random file name
		if ($settings['random_name_len'] !== false) {
			do {
				$file_data['target_file_name'] = '';
				while (strlen($file_data['target_file_name']) < $settings['random_name_len'])
					$file_data['target_file_name'] .= $settings['random_name_alphabet'][mt_rand(0, strlen($settings['random_name_alphabet']) - 1)];
				if ($settings['random_name_keep_type'])
					$file_data['target_file_name'] .= '.' . pathinfo($file_data['uploaded_file_name'], PATHINFO_EXTENSION);
			} while (isReadableFile($file_data['target_file_name']));
		}
		$file_data['upload_target_file'] = $data['uploaddir'] . DIRECTORY_SEPARATOR . $file_data['target_file_name'];

		// Do now allow to overwriting files
		if (isReadableFile($file_data['upload_target_file'])) {
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

	// Delete file
	function deleteFile ($file) {
		global $data;

		if (in_array(substr($file, 1), $_SESSION['upload_user_files']) || in_array($file, $_SESSION['upload_user_files'])) {
			$fqfn = $data['uploaddir'] . DIRECTORY_SEPARATOR . $file;
			if (!in_array($file, $data['ignores']) && isReadableFile($fqfn)) {
				unlink($fqfn);
				echo 'File has been removed';
				exit;
			}
		}
	}

	// Mark/unmark file as hidden
	function markUnmarkHidden ($file) {
		global $data;

		if (in_array(substr($file, 1), $_SESSION['upload_user_files']) || in_array($file, $_SESSION['upload_user_files'])) {
			$fqfn = $data['uploaddir'] . DIRECTORY_SEPARATOR . $file;
			if (!in_array($file, $data['ignores']) && isReadableFile($fqfn)) {
				if (substr($file, 0, 1) === '.') {
					rename($fqfn, substr($fqfn, 1));
					echo 'File has been made visible';
				} else {
					rename($fqfn, $data['uploaddir'] . DIRECTORY_SEPARATOR . '.' . $file);
					echo 'File has been hidden';
				}
				exit;
			}
		}
	}

	// Checks if the given file is a file and is readable
	function isReadableFile ($file) {
		return (is_file($file) && is_readable($file));
	}

	// Files are being POSEed. Uploading them one by one.
	if (isset($_FILES['file'])) {
		header('Content-type: text/plain');
		if (is_array($_FILES['file'])) {
			$file_array = diverseArray($_FILES['file']);
			foreach ($file_array as $file_data)
				uploadFile($file_data);
		} else
			uploadFile($_FILES['file']);
		exit;
	}

	// Other file functions (delete, private).
	if (isset($_POST)) {
		if ($settings['allow_deletion'] && (isset($_POST['target'])) && isset($_POST['action']) && $_POST['action'] === 'delete')
			deleteFile($_POST['target']);

		if ($settings['allow_private'] && (isset($_POST['target'])) && isset($_POST['action']) && $_POST['action'] === 'privatetoggle')
			markUnmarkHidden($_POST['target']);
	}

	// List files in a given directory, excluding certain files
	function createArrayFromPath ($dir) {
		global $data;

		$file_array = array();
		$dh = opendir($dir);
			while ($filename = readdir($dh)) {
				$fqfn = $dir . DIRECTORY_SEPARATOR . $filename;
				if (isReadableFile($fqfn) && !in_array($filename, $data['ignores']))
					$file_array[filemtime($fqfn)] = $filename;
			}

		ksort($file_array);
		$file_array = array_reverse($file_array, true);
		return $file_array;
	}

	// Removes old files
	function removeOldFiles ($dir) {
		global $file_array, $settings;

		foreach ($file_array as $file) {
			$fqfn = $dir . DIRECTORY_SEPARATOR . $file;
			if ($settings['time_limit'] < time() - filemtime($fqfn))
				unlink($fqfn);
		}
	}

	// Only read files if the feature is enabled
	if ($settings['listfiles']) {
		$file_array = createArrayFromPath($data['uploaddir']);

		// Removing old files
		if ($settings['remove_old_files'])
			removeOldFiles($data['uploaddir']);

		$file_array = createArrayFromPath($data['uploaddir']);
	}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?=$settings['lang']?>" lang="<?=$settings['lang']?>" dir="<?=$settings['lang_dir']?>">
	<head>
		<meta http-equiv="content-type" content="text/html;charset=UTF-8" />
		<meta http-equiv="content-style-type" content="text/css" />
		<meta http-equiv="content-script-type" content="text/javascript" />
		<meta http-equiv="language" content="de" />

		<meta name="robots" content="noindex" />
		<meta name="referrer" content="origin-when-crossorigin" />
		<title><?=$settings['title']?></title>
		<style type="text/css" media="screen">
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
		<form action="<?= $data['scriptname'] ?>" method="post" enctype="multipart/form-data" class="dropzone" id="simpleupload-form">
			Maximum upload size: <?php echo $data['max_upload_size']; ?><br />
			<input type="file" name="file[]" id="simpleupload-input" />
		</form>
		<?php if ($settings['listfiles']) { ?>
			<ul id="simpleupload-ul">
				<?php
					foreach ($file_array as $mtime => $filename) {
						$fqfn = $data['uploaddir'] . DIRECTORY_SEPARATOR . $filename;
						$file_info = array();
						$file_owner = false;
						$file_private = $filename[0] === '.';

						if ($settings['listfiles_size'])
							$file_info[] = formatSize(filesize($fqfn));

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

							// Create full-qualified URL and clean it a bit
							$url = str_replace('/./', '/', sprintf('%s%s/%s', $settings['url'], $settings['uploaddir'], $filename));

							echo "<a href=\"$url\" target=\"_blank\">$filename<span>$file_info</span></a>";

							if ($file_owner) {
								if ($settings['allow_deletion'])
									echo '<form action="' . $data['scriptname'] . '" method="post"><input type="hidden" name="target" value="' . $filename . '" /><input type="hidden" name="action" value="delete" /><button type="submit">delete</button></form>';

								if ($settings['allow_private'])
									if ($file_private)
										echo '<form action="' . $data['scriptname'] . '" method="post"><input type="hidden" name="target" value="' . $filename . '" /><input type="hidden" name="action" value="privatetoggle" /><button type="submit">make public</button></form>';
									else
										echo '<form action="' . $data['scriptname'] . '" method="post"><input type="hidden" name="target" value="' . $filename . '" /><input type="hidden" name="action" value="privatetoggle" /><button type="submit">make private</button></form>';
							}

							echo "</li>";
						}
					}
				?>
			</ul>
		<?php
		}

		if ($settings['allow_external_refs']) {
		?>
			<a href="https://github.com/muchweb/simple-php-upload" target="_blank"><img style="position: absolute; top: 0; right: 0; border: 0;" src="https://camo.githubusercontent.com/38ef81f8aca64bb9a64448d0d70f1308ef5341ab/68747470733a2f2f73332e616d617a6f6e6177732e636f6d2f6769746875622f726962626f6e732f666f726b6d655f72696768745f6461726b626c75655f3132313632312e706e67" alt="Fork me on GitHub" data-canonical-src="https://s3.amazonaws.com/github/ribbons/forkme_right_darkblue_121621.png"></a>
		<?php
		} else {
		?>
			<a href="https://github.com/muchweb/simple-php-upload" target="_blank">Fork me on GitHub</a>
		<?php
		}
		?>
		<script type="text/javascript">
		<!--
			var target_form  = document.getElementById('simpleupload-form');
			var target_ul= document.getElementById('simpleupload-ul');
			var target_input = document.getElementById('simpleupload-input');
			var settings_listfiles = <?=($settings['listfiles'] ? 'true' : 'false')?>;

			target_form.addEventListener('dragover', function (event) {
				event.preventDefault();
			}, false);

			function addFileLi (name, info) {
				if (settings_listfiles == false) {
					return;
				}
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

			function handleFiles (event) {
				event.preventDefault();

				var files = event.dataTransfer.files;

				var form = new FormData();

				for (var i = 0; i < files.length; i++) {
					form.append('file[]', files[i]);
					addFileLi(files[i].name, files[i].size + ' bytes');
				}

				var xhr = new XMLHttpRequest();
				xhr.onload = function() {
					window.location.reload();
				};

				xhr.open('post', '<?php echo $data['scriptname']; ?>', true);
				xhr.send(form);
			}

			target_form.addEventListener('drop', handleFiles, false);

			document.getElementById('simpleupload-input').onchange = function () {
				addFileLi('Uploading...', '');
				target_form.submit();
			};
		//-->
		</script>
	</body>
</html>
