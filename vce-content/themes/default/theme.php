<?php
/*
Theme Name: default
*/

function footer() {
}


global $vce;

//add javascript for theme specific things
$vce->site->add_script($vce->site->theme_path . '/js/scripts.js','jquery');

//add stylesheet
$vce->site->add_style($vce->site->theme_path . '/css/style.css', 'ccce-theme-style');

//add stylesheet
$vce->site->add_style($vce->site->theme_path . '/css/pbc_style.css', 'pbc-style');