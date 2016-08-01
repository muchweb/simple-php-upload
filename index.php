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

		// Website title. Displayed of top of the page.
		'title' => 'strace.club',

		// Description for this website
		'description' => 'This is libre file hosting. Code is available <a href="https://github.com/muchweb/simple-php-upload">on Github</a>.',

		// Upload directory. Could be absolute or relative.
		// Default: auto-detection
		'base_path' => dirname(__FILE__) . DIRECTORY_SEPARATOR,

		// Display list uploaded files
		// Default: true
		'listfiles' => true,

		// Allow users to delete files that they have uploaded (will enable sessions)
		// Default: true
		'allow_deletion' => true,

		// Allow users to mark files as hidden
		// Default: true
		'allow_private' => true,

		// Display file sizes
		// Default: true
		'listfiles_size' => true,

		// Display file dates
		// Default: true
		'listfiles_date' => true,

		// Display file dates format
		// Default: 'F d Y H:i:s'
		'listfiles_date_format' => 'F d Y H:i:s',

		// Randomize file names. Number for file name lenght or false to disable.
		// Default: 8
		'random_name_len' => 8,

		// Keep filetype (file extension) information (if random name is activated).
		// Default: true
		'random_name_keep_type' => true,

		// Letters that are used for random file name generation (alphabet).
		// Default: 'abcdefghijklmnopqrstuvwxyz0123456789'
		'random_name_alphabet' => 'abcdefghijklmnopqrstuvwxyz0123456789',

		// Display debugging information
		// Default: false
		'debug' => false,

		// Complete URL to your directory with trailing slash (!)
		// Default: autoDetectBaseUrl()
		'url' => 'http://localhost:8000/',

		// Amount of seconds that each file should be stored for (0 for no limit)
		// Default: 30 days (60 * 60 * 24 * 30)
		'time_limit' => 60 * 60 * 24 * 30,

		// Files that will be ignored
		'ignores' => array(
			'.',
			'..',
			'LICENSE',
			'README.md',
			basename($_SERVER['PHP_SELF']),
			'config.php',
		),

		// Language code
		// Default: 'en'
		'lang' => 'en',

		// Language direction
		// Default: 'ltr'
		'lang_dir' => 'ltr',

		// Privacy: Allow external references (the "fork me" ribbon)
		// Default: true
		'ribbon_enable' => true,
	);
	// =============={ Configuration End }==============

	// Load local config file if it exists.
	if (isReadableFile('config.php')) include('config.php');

	// Enabling error reporting
	if ($settings['debug']) {
		error_reporting(E_ALL);
		ini_set('display_startup_errors', 1);
		ini_set('display_errors', 1);
	}

	// Generated settings file.
	$data = array();

	$data['description'] = '';
	if (strlen($settings['description']) > 0)
		$data['description'] = $settings['description'] . '<br><br>';

	// Adding current script name to ignore list
	$data['ignores'] = $settings['ignores'];
	$data['ignores'][] = basename('index.php');

	// Use canonized path
	$data['uploaddir'] = realpath($settings['base_path']);

	// Is the directory there?
	if (!is_dir($data['uploaddir'])) {
		// Not found
		die(sprintf('[%s:%d]: Upload path "%s" is not a directory.', pathinfo(__FILE__, PATHINFO_BASENAME), __LINE__, $data['uploaddir']));
	} elseif (!is_readable($data['uploaddir'])) {
		// Not readable
		die(sprintf('[%s:%d]: Upload directory "%s" is not readable.', pathinfo(__FILE__, PATHINFO_BASENAME), __LINE__, $data['uploaddir']));
	} elseif (!is_writable($data['uploaddir'])) {
		// Not writable
		die(sprintf('[%s:%d]: Upload directory "%s" is not writable.', pathinfo(__FILE__, PATHINFO_BASENAME), __LINE__, $data['uploaddir']));
	}

	// Detect maximum upload size, allowed by server
	$data['max_upload_size'] = ini_get('upload_max_filesize');

	// If file deletion or private files are allowed, starting a session.
	// This is required for user authentification
	if ($settings['allow_deletion'] || $settings['allow_private']) {
		session_start();

		// Genereate random 'user id'
		if (!isset($_SESSION['upload_user_id']))
			$_SESSION['upload_user_id'] = mt_rand(100000, 999999);

		// Store list of files that were uploaded by this user
		if (!isset($_SESSION['upload_user_files']))
			$_SESSION['upload_user_files'] = array();
	}

	// If debug is enabled, logging all variables
	if ($settings['debug']) {
		// Displaying debug information
		echo '<h2>Settings:</h2>';
		echo '<pre>' . print_r($settings, true) . '</pre>';

		// Displaying debug information
		echo '<h2>Data:</h2>';
		echo '<pre>' . print_r($data, true) .  '</pre>';

		// Displaying debug information
		echo '<h2>SESSION:</h2>';
		echo '<pre>' . print_r($_SESSION, true) . '</pre>';
	}

	// Format file size
	function formatSize ($bytes) {
		$units = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');

		$bytes = max($bytes, 0);
		$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
		$pow = min($pow, count($units) - 1);

		$bytes /= pow(1024, $pow);

		return ceil($bytes) . ' ' . $units[$pow];
	}

	// Rotate a two-dimensional array. Used for file uploads
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
				while (strlen($file_data['target_file_name']) < $settings['random_name_len']) {
					$file_data['target_file_name'] .= $settings['random_name_alphabet'][mt_rand(0, strlen($settings['random_name_alphabet']) - 1)];
				}

				if ($settings['random_name_keep_type']) {
					$file_data['target_file_name'] .= '.' . pathinfo($file_data['uploaded_file_name'], PATHINFO_EXTENSION);
				}
			} while (isReadableFile($file_data['target_file_name']));
		}

		$file_data['upload_target_file'] = $data['uploaddir'] . DIRECTORY_SEPARATOR . $file_data['target_file_name'];

		// Do now allow to overwriting files
		if (isReadableFile($file_data['upload_target_file'])) {
			echo 'File name already exists' . "\n";
			return false;
		}

		// Moving uploaded file OK
		if (move_uploaded_file($file_data['tmp_name'], $file_data['upload_target_file'])) {
			if ($settings['listfiles'] && ($settings['allow_deletion'] || $settings['allow_private'])) {
				$_SESSION['upload_user_files'][] = $file_data['target_file_name'];
			}

			echo $settings['url'] .  $file_data['target_file_name'] . "\n";

			// Return target file name for later handling
			return $file_data['upload_target_file'];
		} else {
			echo 'Error: unable to upload the file.';
			return false;
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
			foreach ($file_array as $file_data) {
				$targetFile = uploadFile($file_data);
			} //END - foreach
		} else {
			$targetFile = uploadFile($_FILES['file']);
		}
		exit;
	}

	// Other file functions (delete, private).
	if (isset($_POST)) {
		if ($settings['allow_deletion'] && (isset($_POST['target'])) && isset($_POST['action']) && $_POST['action'] === 'delete') {
			deleteFile($_POST['target']);
		}

		if ($settings['allow_private'] && (isset($_POST['target'])) && isset($_POST['action']) && $_POST['action'] === 'privatetoggle') {
			markUnmarkHidden($_POST['target']);
		}
	}

	// List files in a given directory, excluding certain files
	function createArrayFromPath ($dir) {
		global $data;

		// Empty paths are not accepted
		if (empty($dir)) {
			die(sprintf('[%s:%d]: R.I.P.: Parameter "dir" cannot be empty.', __FUNCTION__, __LINE__));
		} // END - if

		$file_array = array();

		$dh = opendir($dir) or die(sprintf('[%s:%d]: R.I.P.: Cannot read directory "%s".', __FUNCTION__, __LINE__, $dir));

		while ($filename = readdir($dh)) {
			$fqfn = $dir . DIRECTORY_SEPARATOR . $filename;
			if (isReadableFile($fqfn) && !in_array($filename, $data['ignores'])) {
				$file_array[filemtime($fqfn)] = $filename;
			}
		} //END - while

		ksort($file_array);

		$file_array = array_reverse($file_array, true);

		return $file_array;
	}

	// Removes old files
	function removeOldFiles ($dir) {
		global $file_array, $settings;

		foreach ($file_array as $file) {
			$fqfn = $dir . DIRECTORY_SEPARATOR . $file;
			if ($settings['time_limit'] < time() - filemtime($fqfn)) {
				unlink($fqfn);
			}
		} //END - foreach
	}

	// Detects base URL
	function autoDetectBaseUrl () {
		// Detect protocol
		$protocol = 'http';
		if (
			((isset($_SERVER['HTTPS'])) && (strtolower($_SERVER['HTTPS']) == 'on')) ||
			((isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) && (strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) == 'https'))
		) $protocol = 'https';

		// Detect port
		$port = getenv('SERVER_PORT');
		if (
			(($port == 80) && ($protocol == 'http')) ||
			(($port == 443) && ($protocol == 'https'))
		) $port = '';

		// Detect server name
		$server_name = getenv('SERVER_NAME');
		if ($server_name === false) $server_name = 'localhost';

		// Construct base URL
		$base_url = sprintf(
			'%s://%s%s%s',
			$protocol,
			$server_name,
			$port,
			dirname(getenv('SCRIPT_NAME'))
		);

		return $base_url;
	}

	// Only read files if the feature is enabled
	if ($settings['listfiles']) {
		$file_array = createArrayFromPath($data['uploaddir']);

		// Removing old files
		if ($settings['time_limit'] > 0)
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
		<meta http-equiv="language" content="<?=$settings['lang']?>" />

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

			body > ul > li > a.uploaded_file {
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
		<form action="<?= $settings['url'] ?>" method="post" enctype="multipart/form-data" class="dropzone" id="simpleupload-form">
			<?=$data['description']?>
			Choose a file or Drag&amp;Drop. Maximum upload size is <?php echo $data['max_upload_size']; ?>.<br /><br />
			<input type="file" name="file[]" id="simpleupload-input" />
		</form>
		<?php if (($settings['listfiles']) && (count($file_array) > 0)) { ?>
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
							$url = str_replace('/./', '/', sprintf('%s%s/%s', $settings['url'], $data['uploaddir'], $filename));

							echo "<a class=\"uploaded_file\" href=\"$url\" target=\"_blank\">$filename<span>$file_info</span></a>";

							if ($file_owner) {
								if ($settings['allow_deletion'])
									echo '<form action="' . $settings['url'] . '" method="post"><input type="hidden" name="target" value="' . $filename . '" /><input type="hidden" name="action" value="delete" /><button type="submit">delete</button></form>';

								if ($settings['allow_private'])
									if ($file_private)
										echo '<form action="' . $settings['url'] . '" method="post"><input type="hidden" name="target" value="' . $filename . '" /><input type="hidden" name="action" value="privatetoggle" /><button type="submit">make public</button></form>';
									else
										echo '<form action="' . $settings['url'] . '" method="post"><input type="hidden" name="target" value="' . $filename . '" /><input type="hidden" name="action" value="privatetoggle" /><button type="submit">make private</button></form>';
							}

							echo "</li>";
						}
					}
				?>
			</ul>
		<?php
		}

		if ($settings['ribbon_enable']) {
		?>
			<a href="https://github.com/muchweb/simple-php-upload" target="_blank"><img style="position: absolute; top: 0; right: 0; border: 0;" src="https://camo.githubusercontent.com/38ef81f8aca64bb9a64448d0d70f1308ef5341ab/68747470733a2f2f73332e616d617a6f6e6177732e636f6d2f6769746875622f726962626f6e732f666f726b6d655f72696768745f6461726b626c75655f3132313632312e706e67" alt="Fork me on GitHub" data-canonical-src="https://s3.amazonaws.com/github/ribbons/forkme_right_darkblue_121621.png"></a>
		<?php
		}
		?>

		<script type="text/javascript">
		<!--
			// Init some variables to shorten code
			var target_form        = document.getElementById('simpleupload-form');
			var target_ul          = document.getElementById('simpleupload-ul');
			var target_input       = document.getElementById('simpleupload-input');
			var settings_listfiles = <?=($settings['listfiles'] ? 'true' : 'false')?>;

			/**
			 * Initializes the upload form
			 */
			function init () {
				// Register drag-over event listener
				target_form.addEventListener('dragover', function (event) {
					event.preventDefault();
				}, false);

				// ... and the drop event listener
				target_form.addEventListener('drop', handleFiles, false);

				// Register onchange-event function
				target_input.onchange = function () {
					addFileLi('Uploading...', '');
					target_form.submit();
				};
			}

			/**
			 * Adds given file in a new li-tag to target_ul list
			 *
			 * @param name Name of the file
			 * @param info Some more informations
			 */
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

			/**
			 * Handles given event for file upload
			 *
			 * @param event Event to handle file upload for
			 */
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

				xhr.open('post', '<?php echo $settings['url']; ?>', true);
				xhr.send(form);
			}

			// Initialize upload form
			init();

		//-->
		</script>
	</body>
</html>
