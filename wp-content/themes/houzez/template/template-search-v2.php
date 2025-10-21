<?php
/** 
 * Template Name: Search Results V2
 */
get_header();

// Proper way to include external scripts in WordPress
wp_enqueue_script(
    'axios',
    'https://cdnjs.cloudflare.com/ajax/libs/axios/1.6.2/axios.min.js',
    array(), // No dependencies
    '1.6.2',
    true
);

// Enqueue system-script.js with axios as dependency
wp_enqueue_script(
    'system-script',
    get_template_directory_uri() . '/template-parts/search-v2/js/system-script.js',
    array('jquery', 'axios'), // Depends on jQuery and axios
    filemtime(get_template_directory() . '/template-parts/search-v2/js/system-script.js'),
    true
);

get_template_part('template-parts/search-v2/parts/form'); 


get_footer();