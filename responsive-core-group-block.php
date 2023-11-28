<?php
namespace SethRubenstein;
use WP_HTML_Tag_Processor;
/**
 * Plugin Name:       Responsive Core Group Block
 * Description:       Responsive controls for the core/group block. 
 * Version:           1.0.0
 * Requires at least: 6.3
 * Requires PHP:      8.1
 * Author:            Seth Rubenstein
 * Author URI:        https://sethrubenstein.info
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       sethrubenstein-responsive-core-group-block
 *
 */


class Responsive_Core_Group_Block {
	public static $block_name = 'core/group';
	public $block_json;
	public $editor_script_handle;
	public $style_handle;
	
	public function __construct() {
		$block_json_file = __DIR__ . '/build/block.json';
		$this->block_json = \wp_json_file_decode( $block_json_file, array( 'associative' => true ) );
		$this->block_json['file'] = wp_normalize_path( realpath( $block_json_file ) );

		add_action('init', array($this, 'init_assets'));
		add_action('enqueue_block_editor_assets', array($this, 'enqueue_editor_script'));
		add_action('enqueue_block_assets', array($this, 'enqueue_style'));

		add_filter( 'block_type_metadata', array($this, 'add_responsive_attributes'), 100, 1 );
		add_filter( 'render_block', array($this, 'render_responsive_group_block'), 100, 2 );
	}

	/**
	 * @hook init
	 * @return void
	 */
	public function init_assets() {
		$this->editor_script_handle = register_block_script_handle( $this->block_json, 'editorScript' );
		$this->style_handle    = register_block_style_handle( $this->block_json, 'style' );
	}

	/**
	 * @hook enqueue_block_editor_assets
	 * @return void
	 */
	public function enqueue_editor_script() {
		wp_enqueue_script( $this->editor_script_handle );
	}

	/**
	 * @hook enqueue_block_assets
	 * @return void
	 */
	public function enqueue_style() {
		wp_enqueue_style( $this->style_handle );
	}

	/**
	* Register additional attributes for the core-group block.
	* @hook block_type_metadata 100, 1
	* @param mixed $metadata
	* @return mixed
	*/
	public function add_responsive_attributes( $metadata ) {
		if ( self::$block_name !== $metadata['name'] ) {
			return $metadata;
		}

		if ( ! array_key_exists( 'responsiveContainerQuery', $metadata['attributes'] ) ) {
			$metadata['attributes']['responsiveContainerQuery'] = array(
				'type'    => 'object',
				'default' => array(
					'hideOnDesktop' => false,
					'hideOnTablet'  => false,
					'hideOnMobile'  => false,
				),
			);
		}
		
		return $metadata;
	}

	/**
	 * @hook render_block 100, 2
	 * @param mixed $block_content
	 * @param mixed $block
	 * @return mixed
	 */
	public function render_responsive_group_block( $block_content, $block ) {
		if ( self::$block_name !== $block['blockName'] || is_admin() ) {
			return $block_content;
		}

		$responsive_options = array_key_exists('responsiveContainerQuery', $block['attrs']) ? $block['attrs']['responsiveContainerQuery'] : array();
		if ( empty( $responsive_options ) ) {
			return $block_content;
		}
		
		$hide_on_desktop = array_key_exists('hideOnDesktop', $responsive_options) ? $responsive_options['hideOnDesktop'] : false;
		$hide_on_tablet = array_key_exists('hideOnTablet', $responsive_options) ? $responsive_options['hideOnTablet'] : false;
		$hide_on_mobile = array_key_exists('hideOnMobile', $responsive_options) ? $responsive_options['hideOnMobile'] : false;

		$w = new WP_HTML_Tag_Processor( $block_content );
		if ( $w->next_tag() ) {
			if ( $hide_on_desktop ) {
				$w->set_attribute( 'data-hide-on-desktop', 'true' );
			}
			if ( $hide_on_tablet ) {
				$w->set_attribute( 'data-hide-on-tablet', 'true' );
			}
			if ( $hide_on_mobile ) {
				$w->set_attribute( 'data-hide-on-mobile', 'true' );
			}
		}
		
		return $w->get_updated_html();
	}
}

$Responsive_Core_Group_Block = new Responsive_Core_Group_Block();

