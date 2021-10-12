<html>
<head><?php wp_head(); ?> </head>
<body>
<?php
if( have_posts() )
{
    while( have_posts() )
    {
        the_post();
        the_title();
        the_content();
    }
}
wp_footer();
?>
</body>
</html>