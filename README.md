# Simple PHP upload

Simple single-file PHP file upload (file share hosting) script.

> :warning: **Security warning**: There is no limit on file size or file type. Please make sure that file permissions are set right so nobody can execute uploaded executables. Or exscape your desired directory!

## Installation

Just drop a PHP file in any directory. It will work straight away

## Configuration

There are few options that you can change by editing the file itself:

- `$settings['uploaddir'] = '.';`
	Directory to store the uploaded files. Defaults to rurrect script directory

- `$settings['listfiles'] = true;`
	Option that will list all files in uploads directory. Enabled by default
