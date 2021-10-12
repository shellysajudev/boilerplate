
<!DOCTYPE html>
<html <?php language_attributes(); ?> class="font-custom responsive-typography selection-color scrollbar">
<head>
    <meta charset="<?php echo get_bloginfo('charset'); ?>" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, minimum-scale=1" />
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<header>
    <?php
    wp_nav_menu([
        'theme_location' => 'main-menu'  
    ]);
    ?>
</header>
<main>