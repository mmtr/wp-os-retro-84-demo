<?php
/*
 * Runs once inside Playground, after OpenStation is installed and the
 * theme ZIP is written to /tmp/theme.zip. Leaves the visitor on a
 * furnished desk wearing the theme, with nothing to dismiss first.
 */
require_once '/wordpress/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/file.php';

$user = get_user_by( 'login', 'admin' );
wp_set_current_user( $user->ID );
wp_update_user( array( 'ID' => $user->ID, 'display_name' => 'Webmaster' ) );
update_option( 'blogname', 'OpenStation' );

// The desktop theme, installed the way Preferences > Themes does it.
$theme = openstation_desktop_theme_install_from_zip( '/tmp/theme.zip' );
if ( is_wp_error( $theme ) ) {
	throw new Exception( $theme->get_error_message() );
}

// OpenStation on, every first-run intro already seen, and the
// Dashboard showing its boxes rather than the welcome panel.
update_user_meta( $user->ID, 'show_welcome_panel', 0 );
update_user_meta( $user->ID, 'desktop_mode_mode', '1' );
update_user_meta(
	$user->ID,
	'desktop_mode_seen_intros',
	array( 'activation-nudge', 'activation-welcome', 'openstation-rebrand', 'shell-tour', 'usage-feedback' )
);

// The theme with its recommended settings, as the Apply button in
// Preferences > Themes would leave them.
$slug     = $theme['slug'];
$rec      = (array) ( $theme['manifest']['recommendedOsSettings'] ?? array() );
$settings = array(
	'desktopTheme'                => $slug,
	'appliedThemeRecommendations' => array( $slug ),
);
foreach ( array( 'dockSize', 'desktopLayout', 'dockPlacement', 'windowRadius', 'adminBarMode', 'dockRailRenderer', 'windowReveal', 'windowRevealDuration' ) as $key ) {
	if ( isset( $rec[ $key ] ) ) {
		$settings[ $key ] = $rec[ $key ];
	}
}
if ( ! empty( $rec['wallpaper'] ) ) {
	$settings['wallpaper'] = 'desktop-theme/' . $slug . '/' . $rec['wallpaper'];
}
if ( ! empty( $rec['accentColor'] ) ) {
	$settings['accent']       = 'custom';
	$settings['customAccent'] = $rec['accentColor'];
}
if ( ! empty( $rec['navPlacement'] ) ) {
	$settings['navPlacement'] = $rec['navPlacement'];
}
update_user_meta( $user->ID, 'desktop_mode_os_settings', openstation_sanitize_os_settings( $settings ) );

// A must-use plugin for the demo's lifetime.
wp_mkdir_p( WPMU_PLUGIN_DIR );
file_put_contents(
	WPMU_PLUGIN_DIR . '/demo-desk.php',
	<<<'PHP'
<?php
// The demo resets on every visit and cannot update itself, so it
// reports nothing to update.
add_filter(
	'pre_site_transient_update_core',
	fn() => (object) array( 'last_checked' => time(), 'version_checked' => get_bloginfo( 'version' ), 'updates' => array(), 'translations' => array() )
);
foreach ( array( 'update_plugins', 'update_themes' ) as $transient ) {
	add_filter(
		"pre_site_transient_$transient",
		fn() => (object) array( 'last_checked' => time(), 'checked' => array(), 'response' => array(), 'translations' => array() )
	);
}

// The widget column lives in the browser's storage, which every
// Playground on the same origin shares. Clear it on the first page of
// each visit, so the desk starts from the theme's own column rather
// than what another Playground left behind.
add_action(
	'admin_head',
	function () {
		if ( get_option( 'demo_desk_cleared' ) ) {
			return;
		}
		update_option( 'demo_desk_cleared', 1 );
		echo "<script>try{localStorage.removeItem('desktop-mode-widgets');localStorage.removeItem('desktop-mode-widgets-geometry');}catch(e){}</script>";
	}
);

// Arrange the first view, once per visit: the Dashboard clear of the
// desktop icons, and the Documents folder beside it with the post about
// this demo selected, so its side pane previews it.
add_action(
	'admin_footer',
	function () {
		$folder = (int) get_option( 'demo_documents_folder' );
		$about  = (int) get_option( 'demo_about_post' );
		if ( ! $folder || get_option( 'demo_documents_opened' ) || 'openstation' !== ( $_GET['page'] ?? '' ) ) {
			return;
		}
		update_option( 'demo_documents_opened', 1 );
		$file   = wp_json_encode( array( 'type' => 'folder', 'ref' => (string) $folder, 'title' => 'Documents', 'icon' => 'dashicons-portfolio', 'previewUrl' => '', 'exists' => true ) );
		$script = <<<'JS'
addEventListener( 'load', function () {
	wp.os.whenReady( function () {
		setTimeout( arrange, 800 );
	} );
} );
function arrange() {
	var wm = wp.os.windowManager;
	var dash = wm.getAll().find( function ( w ) {
		return w.config.baseId === 'index-php' || w.config.title === 'Dashboard';
	} );
	if ( dash ) {
		var area = dash.element.parentElement, box = area.getBoundingClientRect(), right = 0;
		document.querySelectorAll( '.os-icon, .os-file-tile' ).forEach( function ( icon ) {
			if ( ! icon.closest( '.os-window' ) ) {
				right = Math.max( right, icon.getBoundingClientRect().right - box.left );
			}
		} );
		var left = Math.round( right + 12 );
		dash.element.style.left = left + 'px';
		if ( left + dash.element.offsetWidth > area.clientWidth - 16 ) {
			dash.element.style.width = Math.max( 480, area.clientWidth - 16 - left ) + 'px';
		}
	}
	wp.os.files.open( wp.os.files.resolve( FOLDER_FILE ) ).then( function () {
		var w = wm.getById( 'os-folder-FOLDER_ID' );
		if ( ! w ) {
			return;
		}
		var e = w.element, p = e.parentElement, tries = 0;
		e.style.left = Math.max( 16, p.clientWidth - e.offsetWidth - 40 ) + 'px';
		e.style.top = Math.round( p.clientHeight * 0.2 ) + 'px';
		( function select() {
			var tile = e.querySelector( 'os-tile[data-file-ref="ABOUT_ID"]' );
			if ( tile ) {
				tile.click();
			} else if ( tries++ < 20 ) {
				setTimeout( select, 150 );
			}
		} )();
	} );
}
JS;
		echo '<script>' . strtr( $script, array( 'FOLDER_FILE' => $file, 'FOLDER_ID' => $folder, 'ABOUT_ID' => $about ) ) . '</script>';
	}
);
PHP
);

// Something to look at.
$posts = array(
	'Welcome to the information superhighway' => 'Pull up a chair, plug in the modem and wait for the screech. This is my corner of the web.',
	'Ten screensavers that changed my life'    => 'Flying toasters, a starfield, a maze in 3D. Ranked, with no regrets.',
	'Under construction: my first homepage'    => 'Animated GIFs, a hit counter and a guestbook. What more could a site need?',
	'It is now safe to turn off your computer' => 'A short essay on endings, and on the orange text that taught us patience.',
	'Notes on desktop patterns'                => 'Fifty percent grey, Tiles, Houndstooth and the eternal teal.',
);
$ids = array();
foreach ( $posts as $title => $body ) {
	$ids[ $title ] = wp_insert_post(
		array(
			'post_title'   => $title,
			'post_content' => $body,
			'post_status'  => 'publish',
			'post_author'  => $user->ID,
		)
	);
}
foreach ( array( 'About me', 'Guestbook', 'Links' ) as $title ) {
	wp_insert_post(
		array(
			'post_title'  => $title,
			'post_type'   => 'page',
			'post_status' => 'publish',
			'post_author' => $user->ID,
		)
	);
}
// A short note about the demo, first in the Documents folder.
$name  = $theme['manifest']['name'];
$about = wp_insert_post(
	array(
		'post_title'   => 'About this demo',
		'post_status'  => 'publish',
		'post_author'  => $user->ID,
		'post_content' => '<!-- wp:paragraph --><p>This is WordPress, running entirely in your browser. Every visit starts a fresh site, and nothing you change here is saved.</p><!-- /wp:paragraph -->'
			. '<!-- wp:paragraph --><p>The desktop is <a href="https://openstation.me">WordPress OpenStation</a>, a plugin that turns wp-admin into a desktop with windows, a dock and files on the desk. This look is its <a href="' . esc_url( 'https://github.com/mmtr/wp-os-' . sanitize_title( $name ) ) . '">' . esc_html( $name ) . '</a> theme.</p><!-- /wp:paragraph -->'
			. '<!-- wp:paragraph --><p>WordPress runs in the browser with <a href="https://wordpress.org/playground/">WordPress Playground</a>, and this page is hosted on <a href="https://spacefast.com">Spacefast</a>.</p><!-- /wp:paragraph -->'
			. '<!-- wp:paragraph --><p>Go ahead and click around.</p><!-- /wp:paragraph -->',
	)
);
update_option( 'demo_about_post', $about );

$folder = openstation_files_create_folder( $user->ID, array( 'name' => 'Documents' ) );
if ( ! is_wp_error( $folder ) ) {
	update_option( 'demo_documents_folder', $folder );
	openstation_files_place_at_next_free_slot( $user->ID, $folder, 'post', (string) $about );
	openstation_files_place( $user->ID, 0, 'folder', (string) $folder, array( 'x' => 0, 'y' => 2 ) );
	foreach ( array_slice( $ids, 0, 3 ) as $id ) {
		openstation_files_place_at_next_free_slot( $user->ID, $folder, 'post', (string) $id );
	}
}
openstation_files_place( $user->ID, 0, 'post', (string) $ids['Welcome to the information superhighway'], array( 'x' => 0, 'y' => 3 ) );
