<?php
// add composer
require_once(get_template_directory().'/vendor/autoload.php');
require_once(get_template_directory().'/_php/_load.php');

// environment
function is_production()
{
    return (strpos($_SERVER['HTTP_HOST'], '.local') === false && strpos($_SERVER['HTTP_HOST'], 'close2dev') === false && strpos($_SERVER['HTTP_HOST'], '192.168.178') === false);
}

// block subscribers from admin
add_action('init', function () {
    if (is_admin() && !defined('DOING_AJAX') && current_user_can('subscriber')) {
        wp_redirect(home_url());
        die();
    }
});

// always enable "show hidden characters" in tinymce
add_filter('tiny_mce_before_init', function($settings) {
    $settings['visualchars_default_state'] = true;
    return $settings;
});

// disable email bug alerts
add_filter( 'recovery_mode_email', function( $email, $url ) {
    $email['to'] = 'unknown@local';
    return $email;
}, 10, 2 );

// always send mails on production to developer
if (!is_production()) {
    add_filter( 'wp_mail', function($data) {
        $data['to'] = isset($_SERVER['SERVER_ADMIN']) && $_SERVER['SERVER_ADMIN'] != '' && strpos($_SERVER['SERVER_ADMIN'], 'webmaster@') === false
            ? $_SERVER['SERVER_ADMIN']
            : 'support@close2.de';
        return $data;
    });
}

// add custom yoast separator
add_filter('wpseo_separator_options', function ($separators) {
    return array_merge($separators, [
        'sc-doubleslash' => '//'
    ]);
});

// remove privacy policy link from login form
add_filter('the_privacy_policy_link', '__return_empty_string');

// prevent resize of big images >2k (wp >= 5.3 by default creates -scaled versions when higher)
add_filter('big_image_size_threshold', '__return_false');

// remove text/javascript for validation
add_filter('script_loader_tag', function($tag, $handle)
{
    $tag = str_replace('script type=\'text/javascript\'', 'script', $tag);
    return $tag;
}, 10, 2);

/* js */
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_script('script',get_bloginfo('template_directory').'/_build/bundle.js', ['jquery']);
    wp_enqueue_script('jquery');
    wp_localize_script('script', 'vars', [
        'homeurl' => home_url(),
        'themePath' => get_template_directory_uri() . '/_js/modules/',
    ]);
});

/* css */
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('style',get_bloginfo('template_directory').'/_build/bundle.css');
});

// theme support for basic features
add_theme_support( 'title-tag' );
add_theme_support( 'automatic-feed-links' );
add_theme_support( 'post-thumbnails' );

// enable custom editor style
add_editor_style();

// add favicon
add_action('wp_head', function()
{
    echo '<link rel="icon" type="image/png" sizes="32x32" href="' . site_url() . '/favicon.png">';
});
add_action('admin_head', function()
{
    echo '<link rel="icon" type="image/png" sizes="32x32" href="' . site_url() . '/favicon.png">';
});

// add menus
add_action('init', function()
{
  register_nav_menus([
    'main-left-menu' => 'Main Left menu',
    'main-right-menu' => 'Main Right menu',
    'footer-menu' => 'Footer menu',
    'mobile-menu' => 'Mobile menu'
  ]);
});

// disable auto p
remove_filter( 'the_content', 'wpautop' );
remove_filter( 'the_excerpt', 'wpautop' );
remove_filter( 'acf_the_content', 'wpautop' );

// remove automatically added wordpress version from script
function wp_remove_version($src)
{	
    if(strpos($src, 'ver='))
    {
        $src = remove_query_arg( 'ver', $src );
    }
    // reload on every request on localhost
    if( !is_production() )
    {
        $src = add_query_arg('ver', mt_rand(1000,9999), $src);
    }	
    return $src;	
}
add_filter( 'style_loader_src', 'wp_remove_version', 9999 );
add_filter( 'script_loader_src', 'wp_remove_version', 9999 );

// disable user-sniffing (source: https://www.wp-tweaks.com/hackers-can-find-your-wordpress-username)
function redirect_to_home_if_author_parameter() {
    $is_author_set = get_query_var( 'author', '' );
    if ( $is_author_set != '' && !is_admin()) {
        wp_redirect( home_url(), 301 );
        exit;
    }
}
add_action( 'template_redirect', 'redirect_to_home_if_author_parameter' );
function disable_rest_endpoints ( $endpoints ) {
    if ( isset( $endpoints['/wp/v2/users'] ) ) {
        unset( $endpoints['/wp/v2/users'] );
    }
    if ( isset( $endpoints['/wp/v2/users/(?P<id>[\d]+)'] ) ) {
        unset( $endpoints['/wp/v2/users/(?P<id>[\d]+)'] );
    }
    return $endpoints;
}
add_filter( 'rest_endpoints', 'disable_rest_endpoints');

// disable category / tag / date / author / archive / attachments / search route
function disable_uneeded_archives() {
    if( is_category() || is_tag() || is_date() || is_author() || is_attachment() || is_search() )
    {
		header('Status: 404 Not Found');
		global $wp_query;
		$wp_query->set_404();
		status_header(404);
		nocache_headers();
	}
}
add_action('template_redirect', 'disable_uneeded_archives');

// disable media slugs from taking away page slugs
add_filter( 'wp_unique_post_slug_is_bad_attachment_slug', '__return_true' );

// remove emojis
function disable_emojis()
{
    remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
    remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
    remove_action( 'wp_print_styles', 'print_emoji_styles' );
    remove_action( 'admin_print_styles', 'print_emoji_styles' ); 
    remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
    remove_filter( 'comment_text_rss', 'wp_staticize_emoji' ); 
    remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
}
add_action( 'init', 'disable_emojis' );

// remove wordpress version number
remove_action('wp_head', 'wp_generator');

// remove text content editors on all pages (to fully use acf fields) #wordpress
add_action('admin_init', function()
{
    remove_post_type_support( 'post', 'editor' );
    remove_post_type_support( 'page', 'editor' );
});

// reenable custom meta box in posts removed by acf
add_filter('acf/settings/remove_wp_meta_box', '__return_false');

// enable svg upload
add_filter( 'upload_mimes', function($existing_mimes = [])
{
    $existing_mimes['vcf'] = 'text/x-vcard';
    $existing_mimes['svg'] = 'image/svg+xml';
    return $existing_mimes;
});
add_filter( 'wp_check_filetype_and_ext', function($data, $file, $filename, $mimes)
{
  $filetype = wp_check_filetype( $filename, $mimes );
  return [
      'ext' => $filetype['ext'],
      'type' => $filetype['type'],
      'proper_filename' => $data['proper_filename']
  ];
}, 10, 4 );