<?php

require_once __DIR__ . '/vendor/autoload.php';
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

function get_css_to_inline_styles_object() {
	
	// create instance
	return new CssToInlineStyles();
	
}
