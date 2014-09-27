# Simple PHP upload

Simple single-file PHP file upload (file share hosting) script.

> :warning: **Security warning**: There is no limit on file size or file type. Please make sure that file permissions are set right so nobody can execute uploaded executables. Or exscape your desired directory!

## Installation

Just drop a PHP file in any directory. It will work straight away

## Configuration

There are few options that you can change by editing the file itself:


- `uploaddir` => `'.'`
	Directory to store uploaded files


- `listfiles` => `true`
	Display list uploaded files


- `listfiles_size` => `true`
	Display file sizes


- `listfiles_date` => `true`
	Display file dates


- `listfiles_date_format` => `'F d Y H:i:s'`
	Display file dates format


- `random_name_len` => `10`
	Randomize file names (number of 'false')


- `random_name_keep_type` => `true`
	Keep filetype information (if random name is activated)


- `random_name_alphabet` => `'qwertyuiodfgjkcvbnm'`
	Random file name letters


- `debug` => `false`
	Display debugging information



- `uploaddir => '.'`
	Directory to store the uploaded files. Defaults to rurrect script directory

- `listfiles => true`
	Option that will list all files in uploads directory. Enabled by default

- `debug => false`
	To display debugging information

## Usage options

- Through an interface:
	- Choose files via dialogue
	- Drop files, via HTML5 drag'and'drop (using [dropzone.js](http://www.dropzonejs.com/))
	- Basic HTML Form (if no JavaScript is suported)
- Upload using any compatible tool (like cURL)

	This example will upload a file and copy URL to clipboard:

	```bash
	curl -F "file=@file.jpg" your-host/sharing/ | xclip -sel clip
	```
