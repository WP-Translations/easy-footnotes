<?php
/**
 * Plugin Name: Easy Footnotes
 * Plugin URI: http://jasonyingling.me/easy-footnotes-wordpress/
 * Description: Easily add footnotes to your posts with a simple shortcode.
 * Version: 1.0.12
 * Author: Jason Yingling
 * Author URI: http://jasonyingling.me
 * License: GPL2
 */

 /*  Copyright 2017  Jason Yingling  (email : yingling017@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

class easyFootnotes {

	// Add label option using add_option if it does not already exist
	public $footnotes = array();
	public $footnoteCount = 0;
	public $prevPost;

	public function __construct() {
		$footnoteSettings = array(
			'footnoteLabel' => 'Footnotes',
			'useLabel' => false,
		);

		add_option('easy_footnotes_options', $footnoteSettings);
		add_shortcode( 'note', array($this, 'easy_footnote_shortcode') );
		add_filter('the_content', array($this, 'easy_footnote_after_content'), 20);
		add_action('wp_enqueue_scripts', array($this, 'register_qtip_scripts'));
		add_action('admin_menu', array($this, 'easy_footnotes_admin_actions'));
	}

	public function register_qtip_scripts() {
		wp_register_script( 'imagesloaded', plugins_url( '/assets/qtip/imagesloaded.pkgd.min.js' , __FILE__ ), null, false, true );
		wp_register_script( 'qtip', plugins_url( '/assets/qtip/jquery.qtip.min.js' , __FILE__ ), array('jquery', 'imagesloaded'), false, true );
		wp_register_script( 'qtipcall', plugins_url( '/assets/qtip/jquery.qtipcall.js' , __FILE__ ), array('jquery', 'qtip'), false, true );
		wp_register_style( 'qtipstyles', plugins_url( '/assets/qtip/jquery.qtip.min.css' , __FILE__ ), null, false, false );
		wp_register_style( 'easyfootnotescss', plugins_url( '/assets/easy-footnotes.css' , __FILE__ ), null, false, false );
	}

	public function easy_footnote_shortcode($atts, $content = null) {
		wp_enqueue_style( 'qtipstyles' );
		wp_enqueue_style( 'easyfootnotescss' );
		wp_enqueue_script( 'imagesloaded' );
		wp_enqueue_script( 'qtip' );
		wp_enqueue_script( 'qtipcall' );
		wp_enqueue_style( 'dashicons' );

		extract (shortcode_atts(array(
		), $atts));

		// Get an array of all of the footnotes
		$pattern = '/\[note\](.*?)\[\/note\]/';
		preg_match_all( $pattern, get_the_content(get_the_ID()), $shortcodes );

		// If the current content matches the first [note] set the count to 0. This prevents extra counting by themes using the_content
 		if ( $content === $shortcodes[1][0] ) {
			$count = 0;
		} else {
			$count = $this->footnoteCount;
		}

		// Increment the counter
		$count++;

		// Set the footnoteCount (This whole process needs reworked)
		$this->footnoteCount = $count;

		//$this->easy_footnote_count($this->footnoteCount, get_the_ID());
		$this->easy_footnote_content($content);

		if (is_singular() && is_main_query()) {
			$footnoteLink = '#easy-footnote-bottom-'.$this->footnoteCount;
		} else {
			$footnoteLink = get_permalink(get_the_ID()).'#easy-footnote-bottom-'.$this->footnoteCount;
		}

		$footnoteContent = "<span id='easy-footnote-".esc_attr($this->footnoteCount)."' class='easy-footnote-margin-adjust'></span><span class='easy-footnote'><a href='".esc_url($footnoteLink)."' title='".htmlspecialchars($content, ENT_QUOTES)."'><sup>".esc_html($this->footnoteCount)."</sup></a></span>";

		return $footnoteContent;
	}

	public function easy_footnote_content($content) {
		$this->footnotes[$this->footnoteCount] = $content;

		return $this->footnotes;
	}

	public function easy_footnote_count($count, $currentPost) {
		if ($this->prevPost != $currentPost) {
			$count = 0;
		}

		$this->prevPost = $currentPost;

		$count++;

		$this->footnoteCount = $count;

		return $this->footnoteCount;
	}

	public function easy_footnote_after_content($content) {
		if (is_singular() && is_main_query()) {
			$footnotesInsert = $this->footnotes;

			$footnoteCopy = '';

			$footnoteOptions = get_option('easy_footnotes_options');
			$useLabel = $footnoteOptions['useLabel'];
			$efLabel = $footnoteOptions['footnoteLabel'];

			foreach ($footnotesInsert as $count => $footnote) {
				$footnoteCopy .= '<li class="easy-footnote-single"><span id="easy-footnote-bottom-'.esc_attr($count).'" class="easy-footnote-margin-adjust"></span>'.wp_kses_post($footnote).'<a class="easy-footnote-to-top" href="'.esc_url('#easy-footnote-'.$count).'"></a></li>';
			}
			if (!empty($footnotesInsert)) {
				if ($useLabel === true) {
					$content .= '<div class="easy-footnote-title"><h4>'.esc_html($efLabel).'</h4></div><ol class="easy-footnotes-wrapper">'.$footnoteCopy.'</ol>';
				} else {
					$content .= '<ol class="easy-footnotes-wrapper">'.$footnoteCopy.'</ol>';
				}
			}


		}
		return $content;
	}

	// Functions to create Reading Time admin pages
	public function easy_footnotes_admin() {
	    include('easy-footnotes-admin.php');
	}

	public function easy_footnotes_admin_actions() {
		add_options_page("Easy Footnotes Settings", "Easy Footnotes", "manage_options", "easy-footnotes-settings", array($this, "easy_footnotes_admin"));
	}

}

$easyFootnotes = new easyFootnotes();
