<?php
/**
* Plugin Name: My Custom Global Variables
* Plugin URI: https://www.newtarget.com/solutions/wordpress-websites
* Description: Easily create custom variables that can be accessed globally in Wordpress and PHP. Retrieval of information is extremely fast, with no database calls.
* Version: 1.1.2
* Author: new target, inc
* Author URI: https://www.newtarget.com
* License: GPL2
*/

class Custom_Global_Variables {

	private $file_path = '';

	// Constructor
	function __construct() {

		$this->just_path = WP_CONTENT_DIR . '/custom-global-variables/';
		$this->current_file =   md5( AUTH_KEY ) . '.json';
		$this->check_for_auth_key_change($this->just_path,$this->current_file);

		$this->file_path = WP_CONTENT_DIR . '/custom-global-variables/' . md5( AUTH_KEY ) . '.json';

		/* Retrieve locally the current definitions from the filesystem. */

		if ( file_exists( $this->file_path ) ) {

			$vars = file_get_contents( $this->file_path );

			if ( ! empty( $vars ) ) {
				$temp_vars = json_decode( $vars, true );
				// $temp_vars should be an array of strings, $name => $value
				$adj_vars = array();
				foreach ($temp_vars as $name => $val) {
					//  check the string value  and translate to approprite variable type and value.
					//  This value will be stored in the $GLOBALS array.
					$new_val = $this->cgv_translate_val( $val );
					$adj_vars[$name] =  $new_val;
				} //foreach
				// set the CGV $GLOBALS
				$GLOBALS['cgv'] = $adj_vars;

			}
			else {
				// No variables defined
				$GLOBALS['cgv'] = array();
			}
		}
		// Create the directory and file when it doesn't exist.
		else {

			if ( wp_mkdir_p( WP_CONTENT_DIR . '/custom-global-variables' ) ) {
				file_put_contents( $this->file_path, '' );
			}
			//if no file, we have no vars defined
			$vars = array();
			$GLOBALS['cgv'] = array();
		}

		// Add the menu item.
		add_action( 'admin_menu', array( &$this, 'add_menu' ) );

		// Setup the shortcode.
		add_shortcode( 'cgv', array( &$this, 'shortcode' ) );
	}

	// Adds the menu item under Settings
	function add_menu() {

		add_submenu_page(
		'options-general.php',
		'Custom Global Variables',
		'Custom Global Variables',
		'manage_options',
		'custom-global-variables',
		array( &$this, 'admin_page' )
		);
	} // function add_menu

	// Admin page
	function admin_page() {
		//this is the CGV editor

		// Terminate if the user isn't allowed to access the page.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'You do not have sufficient permissions.' );
		}

		wp_enqueue_style( 'custom-global-variables-style', plugins_url( 'style.css', __FILE__ ) );
		wp_enqueue_script( 'custom-global-variables-script', plugins_url( 'script.js', __FILE__ ), array( 'jquery' ) );

		// initialize $vars array
		if (! isset($vars) )
		$vars =array();

		// starting from $GLOBALS makes for extra work as $GLOBALS may contain special types and values that have to be translated to string.
		// $vars is the string values which should be fine.

		foreach ($GLOBALS['cgv'] as $name=>$val) {
			$vars[$name] = $this->cgv_translate_to_string($val);
		}


		// Save definitions upon submission.
		// there are three ways to post:
		// submit -> save
		// submit -> reset
		// toggle dump of $GLOBALS

		if ( isset($_POST['sub_button']) && 'Save' == $_POST['sub_button']) {
			if ( isset( $_POST['vars'] ) ) {
				if( isset( $_POST['cgv_nonce'] ) ){
					if ( !wp_verify_nonce( $_REQUEST['cgv_nonce'], 'cgv_nonce' ) ){
						// have nonce, but fails vaildation
						wp_die( 'You do not have sufficient permissions 2' );
						return FALSE;
					}
				}else {
					// no nonce returned
					wp_die( 'You do not have sufficient permissions 3' );
					return FALSE;
				}
				// clean up posted values
				//initialize output array
				$vars_post = array();

				foreach ( $_POST['vars'] as $var ) {
					$var['name'] = sanitize_text_field($var['name']); //eliminates nulls, zero values
					$var['val'] = sanitize_textarea_field($var['val']); //keeps nulls, zero values

					//Name must have at least one non-whitespace character
					//Val must have at least one character and can be whitespace
					if ( ! empty( $var['name'] && strlen($var['val']) > 0  )  ) {
						//posted values are escaped and so strip slashes needed
						//since name values become PHP variables, replace any remaining spaces with underscore
						$name =  stripslashes(  str_replace( ' ', '_', ltrim( trim( $var['name'] ) ) ) );
						$vars_post[ $name ] = stripslashes($var['val']);
					} //if not empty
				} //foreach

				//save encoded $vars_post to filesystem
				if ( file_put_contents( $this->file_path, json_encode( $vars_post ) ) !== false ) {
					// make $vars the same as string values posted
					$vars = $vars_post;

					echo '<div id="message" class="updated"><p>Your variables have successfully been saved.</p></div>';
				}
				else {
					//something went wrong with save
					echo '<div id="message" class="error"><p>Your variables could not be saved. Check to see if the following folder exists and has sufficient write permissions:</p><p><strong>' . WP_CONTENT_DIR . '/custom-global-variables' . '</strong></p></div>';
				} //if can save
			} //if $_POST['vars']
		} else { //if submit is save or not
			//is reset
			echo '<div id="message" class="updated"><p>Your variable changes have been discarded.</p></div>';
		} //if submit is save or not

		//start of display
		?>

		<div class="wrap">

		<h2>Custom Global Variables</h2>
		<div class="card">
		<h3>Usage</h3>
		<p>Display your variables using the shortcode syntax:</p>
		<p><code>[cgv <em>variable-name</em>]</code></p>
		<p>Or using the superglobal in PHP:</p>
		<p><code>&lt;?php echo $GLOBALS['cgv']['<em>variable-name</em>'] ?&gt;</code></p>
		</div>

		<div class="card">
		<h3>Define your variables</h3>
		<div id="cgv-show-hide">
		Hide
		</div> <!--cgv-show-hide-->
		<div id="cgv-variable-description">
		<p><strong>Variable Names</strong> may include letters, numbers, and underscores.</p>
		<p><strong>Variable Values</strong> may include any character, but some characters will be converted to htmlentities. HTML tags (anything between &lt; and &gt;) will be stripped.</p>
		<p>
		There are special cases where values will be converted or preserved:
		</p>
		<table id="cgv-description">
		<tbody>
		<tr>
		<td><strong>null</strong></td> <td>Nulls will be preserved and NOT treated as the string, &quot;null.&quot;</td>
		</tr>
		<tr>
		<td><strong>true/false</strong></td> <td>True and false will be treated as boolean.</td>
		</tr>
		<tr>
		<td><strong>Quoted strings: ("true")</strong></td> <td>Will always preserve the embeded quote characters and htmlentities.</td>
		</tr>
		<tr>
		<td><strong>Numbers: 0, 0.00, 77, 123.45, 1e10</strong></td> <td>Numbers will be saved as strings. PHP may convert these values to an appropriate type when used, but it is best to first cast to a type to ensure any subsquent display or calculations.
		<br>For Example:
		<br><code>&lt;?php $my_sales = (float)$GLOBALS['cgv']['total_sales']; ?&gt;</code>
		<//td>
		</tr>
		</tbody>
		</table>
		</div> <!--cgv-variable-description-->

		<form method="POST" action="">
		<input type="hidden" name="show_hide" value="
		<?php if ( isset($_POST['show_hide']) && ! empty($_POST['show_hide']) ) {
		echo $_POST['show_hide'];
		} else {
		echo 'show';
		}
		?>
		">
		<?php
		wp_nonce_field( 'cgv_nonce', 'cgv_nonce', false, true );
		?>
		<table id="custom-global-variables-table-definitions">
		<tbody>
		<tr>
		<th>#</th><th>Variable Name</th><th>&nbsp;</th><th>Value</th><th>Delete</th>
		</tr>
		<?php
		$i = 0;
		$cnt = 1;

		if ( !empty( $vars ) ) {
			?>

			<?php foreach ( $vars as $key => $val ) {  ?>
				<?php
				$key = esc_html($key);
				$val = esc_html($val);
				?>

				<tr>
				<?php $cnt = $i+1; ?>
				<td class="count"><?php echo $cnt.'.'; ?></td>
				<td class="name"><input autocomplete="off" name="vars[<?php echo $i ?>][name]" placeholder="name" type="text" value="<?php echo $key ?>"><span id="msg-name-<?php echo $i ?>"></span></td>
				<td class="equal"><span class="equals">=</span></td>
				<td class="value-input">
				<?php if ( strlen($val) <= 50 ) { ?>
					<input autocomplete="off" name="vars[<?php echo $i ?>][val]" placeholder="value" type="text" value="<?php echo  $val  ?>">
				<?php } else { // if val has tags display as textares
					?>
					<textarea name="vars[<?php echo $i ?>][val]" placeholder="value"><?php echo  $val  ?></textarea>
				<?php } //if tags or not ?>
				<span id="msg-val-<?php echo $i ?>"></span>
				</td>

				<td class="options">
				<img alt="delete" class="delete" src="<?php echo plugin_dir_url( __FILE__ ) ?>/delete.png">
				</td>
				</tr>

				<?php $i++;
			} //foreach;
			?>

		<?php } //if !empty vars
		?>

		<tr>
		<?php $cnt = $i+1; ?>
		<td class="count"><?php echo $cnt.'.'; ?></td>
		<td class="name"><input autocomplete="off" name="vars[<?php echo $i ?>][name]" placeholder="name" type="text"><span id="msg-name-<?php echo $i ?>"></span></td>
		<td class="equal"><span class="equals">=</span></td>
		<td><input autocomplete="off" name="vars[<?php echo $i ?>][val]" placeholder="value" type="text"><span id="msg-val-<?php echo $i ?>"></span></td>
		<td></td>
		</tr>
		</tbody>
		</table>

		<div id="display-vars">
		<p><input type="checkbox" name="display_vars" value="1"
		<?php if ( isset($_POST['display_vars']) && $_POST['display_vars'] ) echo ' checked '; ?>
		> Display var_dump of Custom Global Variables? </input>
		</div> <!--display-vars -->
		<p><input type="submit" name="sub_button" value="Save" class="button-primary"> <input type="submit" name="sub_button" value="Reset" class="button-primary"></p>
		</form>
		</div>
		</div>

		<div id="vars-display" style="margin:2em;float: left: width: 100%">
		<h3>Custom Global Variables</h3>
		<p>(The most recent variable will not display until saved again or page is refreshed.)</p>
		<p><pre>
		<?php var_dump($GLOBALS['cgv']); ?>
    <?php //var_dump($_POST['vars']); ?>
		</pre></p>
		</div> <!--display-vars-->
		<?php
	} // function admin_page

	// Shortcode for displaying values
	function shortcode( $params ) {
		$param0 = sanitize_text_field($params[0]);
		if ( ! empty( $GLOBALS['cgv'][ $param0 ])  ) {
			return wp_kses_post($GLOBALS['cgv'][ $param0 ]);
		}

		return false;
	} //function shortcode

	function check_for_auth_key_change($path,$current_file) {
		//if auth key has changed move old cgv file to current file
		//if this has been done multiple times, clean up old cgv files
	//	$path = WP_CONTENT_DIR . '/test-cgv/';
		if ( empty($path) || empty($current_file) ) return;

		$current_file_exists =  false;

		if (is_file($path.$current_file)) $current_file_exists = true;

		if ( is_dir($path) ) {
			if ($dh = @opendir($path)) {

				$files = array();
				if ($current_file_exists) {

					while (($file = readdir($dh)) !== false) {

						if ( 	filetype($path . $file) == 'file'   && stristr($file,'json') !== false && $file !== $current_file ) {
							$filename = $file;
							$time = filemtime($path.$file);
							$files[(string)$filename] = $time;
						}
					} // while
					if (count($files) > 0 ){
						//delete old files
						foreach( $files as $filename=>$time) {
							$unlink_ok = unlink($path.$filename);
						} //foreach
					} //if count

				} else { //if $current_file_exists or not

					//current_file does not exist
					$files = array();
					while (($file = readdir($dh)) !== false) {
						if ( filetype($path . $file) == 'file'  && stristr($file,'json') !== false ) {
							$filename = $file;
							$time = filemtime($path.$file);
							$files[(string)$filename] = $time;
						} //if file is a json file
					} // while

					if ( count($files) == 0 ) {
						//nothing to do
						return;

					} elseif ( count($files) == 1 )  {

						// count = 1
						$filename = key($files);
						$vars = json_decode(file_get_contents($path.$filename),true);
						if ( is_array($vars) && ! empty($vars) ) {
							$from = $path.$filename;
							$to = $path.$current_file;
							rename($from, $to);
						}

					} else { // if count

						//count > 1
						asort($files); //sort on file dates
						end($files); //start at the end for most recent file
						$count = count($files);

						$good_filename= '';
						for ($i=$count;$i >= 0;$i--) {
							$filename = key($files);
							$vars = json_decode(file_get_contents($path.$filename),true);
							if ( is_array($vars) && ! empty($vars) ) {
								$good_filename = $filename;
								break;
							} //if is valid json file
							prev($files);
						} // for $i

						if ( ! empty($good_filename) ) {

							$ren_ok = rename($path.$good_filename,$path.$current_file);

							unset($files[$good_filename]); //file is renamed and removed from list to be deleted

							reset($files);
							foreach( $files as $filename=>$time) {
								$unlink_ok = unlink($path.$filename);
							} //foreach

						} //if $good_file_name

					} // if count

				} //if current_file exists or not

			} //if directory can be opened
			closedir($dh);
		} // if $path is a directory
		return;

	} //function check_for_auth_key_change

	function cgv_translate_val($t_val) {
		$r_val ='';
		//$test_val = ltrim(rtrim(strtolower(stripslashes($t_val))));
		if ('null' === strtolower($t_val) ) {
			$r_val = NULL;
		} elseif ( 'true' === strtolower($t_val) ) {
			$r_val = true;
		} elseif ( 'false' === strtolower($t_val) ) {
			$r_val = false;
		} elseif ( '0' === $t_val ) {
			$r_val = '0';
		} elseif ( is_numeric((float)$t_val) && is_float((float)$t_val)) { //should handle 0.00 case
			$r_val = $t_val;
		} else {
			$r_val = wp_kses_post($t_val);
		}

		return $r_val;

	} // function cgv_translate_val
	function cgv_translate_to_string($t_val) {
		$r_val ='';
		if ( is_null($t_val) ) {
			$r_val = 'null';
		} elseif ( is_bool($t_val) && $t_val ) {
			$r_val = 'true';
		} elseif ( is_bool($t_val) && ! $t_val ) {
			$r_val = 'false';
		} elseif ( 0 === $t_val || '0' === $t_val ) {
			$r_val = '0';
		} elseif ( is_numeric($t_val) && is_float($t_val)) { //should handle 0.00 case
			$r_val = (string)$t_val;
		} else {
			$r_val = wp_kses_post($t_val);
		}

		return $r_val;

	} // function cgv_translate_to_string

} // end of class

$custom_global_variables = new Custom_Global_Variables;
