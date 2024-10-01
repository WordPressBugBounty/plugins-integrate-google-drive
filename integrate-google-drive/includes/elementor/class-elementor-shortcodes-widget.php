<?php

namespace IGD;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;

defined( 'ABSPATH' ) || exit();

class Shortcodes_Widget extends Widget_Base {

	public function get_name() {
		return 'igd_shortcodes';
	}

	public function get_title() {
		return __( 'Module Shortcodes', 'integrate-google-drive' );
	}

	public function get_icon() {
		return 'igd-shortcodes';
	}

	public function get_categories() {
		return [ 'integrate_google_drive' ];
	}

	public function get_keywords() {
		return [
			"google drive",
			"drive",
			"shortcode",
			"module",
			"cloud",
			"shortcode"
		];
	}

	public function get_script_depends() {
		return [
			'igd-frontend',
		];
	}

	public function get_style_depends() {
		return [
			'igd-frontend',
		];
	}

	public function register_controls() {

		$this->start_controls_section( '_section_module_shortcodes',
			[
				'label' => __( 'Module Shortcode', 'integrate-google-drive' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			] );


		$this->add_control( 'shortcode_id',
			[
				'label'       => __( 'Select Shortcode Module', 'integrate-google-drive' ),
				'type'        => Controls_Manager::SELECT,
				'label_block' => true,
				'options'     => [ '' => __( 'Select Shortcode', 'integrate-google-drive' ) ] + igd_get_shortcodes_array(),
			] );


		$this->end_controls_section();
	}

	public function render() {
		$settings     = $this->get_settings_for_display();
		$shortcode_id = $settings['shortcode_id'];

		$is_editor         = \Elementor\Plugin::$instance->editor->is_edit_mode();
		$shortcode_content = do_shortcode( '[integrate_google_drive id="' . $shortcode_id . '"]' );

		if ( ! empty( $shortcode_id ) ) {
			if ( $is_editor ) {
				printf( '<div class="igd-shortcode-preview">%s</div>', $shortcode_content );
			} else {
				echo $shortcode_content;
			}
		} else {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				Elementor::builder_empty_placeholder( $this->get_name() );
			}
		}
	}

}