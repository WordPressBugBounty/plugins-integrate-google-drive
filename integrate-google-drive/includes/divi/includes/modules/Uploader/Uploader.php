<?php

namespace IGD\Divi;

use IGD\Shortcode;

class Uploader extends \ET_Builder_Module {

	public $slug = 'igd_uploader';
	public $vb_support = 'on';

	protected $module_credits = array(
		'module_uri' => 'https://softlabbd.com/integrate-google-drive/',
		'author'     => 'SoftLab',
		'author_uri' => 'https://softlabbd.com/',
	);

	public function init() {
		$this->name = esc_html__( 'Google Drive Uploader', 'integrate-google-drive' );


		$this->settings_modal_toggles = [
			'general' => [
				'toggles' => [
					'main_content' => __('Module Configuration', 'integrate-google-drive'),
				],
			],
		];

		$this->advanced_fields = [
			'background'     => false,
			'borders'        => false,
			'box_shadow'     => false,
			'button'         => false,
			'filters'        => false,
			'fonts'          => false,
			'margin_padding' => false,
			'text'           => false,
			'link_options'   => false,
			'height'         => false,
			'scroll_effects' => false,
			'animation'      => false,
			'transform'      => false,
		];
	}

	public function get_fields() {
		return array(
			'data' => array(
				'label'           => esc_html__( 'Configure Module', 'integrate-google-drive' ),
				'type'            => 'igd_configure',
				'option_category' => 'configuration',
				'description'     => esc_html__( 'Configure the module', 'integrate-google-drive' ),
				'default'         => '{"isInit":true,"status":"on","type":"uploader","folders":[],"showFiles":true,"showFolders":true,"moduleWidth": "100%", "moduleHeight": "auto","showUploadLabel":true,"uploadLabelText":"Upload Files","fileNumbers":1000,"sort":{"sortBy":"name","sortDirection":"asc"},"view":"list","maxFileSize":"","preview":"true","download":true,"displayFor":"everyone","displayUsers":["everyone"],"displayExcept":[]}',
				'toggle_slug'     => 'main_content',
			),
		);
	}

	public function render( $attrs, $content = null, $render_slug = null ) {
		$data = json_decode( html_entity_decode($this->props['data']), true );

		return Shortcode::instance()->render_shortcode( [], $data );
	}
}

new Uploader;
