<?php
/**
 * Automatic postMessage scripts calculation for Kirki controls.
 *
 * @package     Kirki
 * @category    Modules
 * @author      Aristeides Stathopoulos
 * @copyright   Copyright (c) 2017, Aristeides Stathopoulos
 * @license     http://opensource.org/licenses/https://opensource.org/licenses/MIT
 * @since       3.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds styles to the customizer.
 */
class Kirki_Modules_PostMessage {

	/**
	 * The script.
	 *
	 * @access protected
	 * @since 3.0.0
	 * @var string
	 */
	protected $script = '';

	/**
	 * Constructor.
	 *
	 * @access public
	 * @since 3.0.0
	 */
	public function __construct() {
		add_action( 'customize_preview_init', array( $this, 'postmessage' ) );
	}

	/**
	 * Enqueues the postMessage script
	 * and adds variables to it using the wp_localize_script function.
	 * The rest is handled via JS.
	 */
	public function postmessage() {

		wp_enqueue_script( 'kirki_auto_postmessage', trailingslashit( Kirki::$url ) . 'modules/postmessage/postmessage.js', array( 'jquery', 'customize-preview' ), false, true );
		$fields = Kirki::$fields;
		foreach ( $fields as $field ) {
			if ( isset( $field['transport'] ) && 'postMessage' === $field['transport'] && isset( $field['js_vars'] ) && ! empty( $field['js_vars'] ) && is_array( $field['js_vars'] ) && isset( $field['settings'] ) ) {
				$this->script .= $this->script( $field );
			}
		}
		$this->script = apply_filters( 'kirki/postmessage/script', $this->script );
		wp_add_inline_script( 'kirki_auto_postmessage', $this->script, 'after' );

	}

	/**
	 * Generates script for a single field.
	 *
	 * @access protected
	 * @since 3.0.0
	 * @param array $args The arguments.
	 */
	protected function script( $args ) {

		$script = 'wp.customize(\'' . $args['settings'] . '\',function(value){value.bind(function(newval){';
		// append unique style tag if not exist
		// The style ID.
		$style_id = 'kirki-postmessage-' . str_replace( array( '[', ']' ), '', $args['settings'] );
		$script .= 'if(null===document.getElementById(\'' . $style_id . '\')||\'undefined\'===typeof document.getElementById(\'' . $style_id . '\')){jQuery(\'head\').append(\'<style id="' . $style_id . '"></style>\');}';

		// Add anything we need before the main script.
		$script .= $this->before_script( $args );

		$field = array(
			'scripts' => array(),
		);
		// Loop through the js_vars and generate the script.
		foreach ( $args['js_vars'] as $key => $js_var ) {
			if ( isset( $js_var['function'] ) && 'html' === $js_var['function'] ) {
				$script .= $this->script_html_var( $js_var );
				continue;
			}
			$js_var['index_key'] = $key;
			$callback = $this->get_callback( $args );
			if ( is_callable( $callback ) ) {
				$field['scripts'][ $key ] = call_user_func_array( $callback, array( $js_var, $args ) );
				continue;
			}
			$field['scripts'][ $key ] = $this->script_var( $js_var );
		}
		$combo_extra_script = '';
		$combo_css_script   = '';
		foreach ( $field['scripts'] as $script_array ) {
			$combo_extra_script .= $script_array['script'];
			$combo_css_script   .= ( 'css' !== $combo_css_script ) ? $script_array['css'] : '';
		}
		$text = ( 'css' === $combo_css_script ) ? 'css' : '\'' . $combo_css_script . '\'';
		$script .= $combo_extra_script . 'jQuery(\'#' . $style_id . '\').text(' . $text . ');';
		$script .= '});});';
		return $script;
	}

	/**
	 * Generates script for a single js_var when using "html" as function.
	 *
	 * @access protected
	 * @since 3.0.0
	 * @param array $args  The arguments for this js_var.
	 */
	protected function script_html_var( $args ) {

		$script  = ( isset( $args['choice'] ) ) ? 'newval=newval[\'' . $args['choice'] . '\'];' : '';
		$script .= 'jQuery(\'' . $args['element'] . '\').html(newval);';
		if ( isset( $args['attr'] ) ) {
			$script = 'jQuery(\'' . $args['element'] . '\').attr(\'' . $args['attr'] . '\',newval);';
		}
		return $script;
	}

	/**
	 * Generates script for a single js_var.
	 *
	 * @access protected
	 * @since 3.0.0
	 * @param array $args  The arguments for this js_var.
	 */
	protected function script_var( $args ) {
		$script = '';
		$property_script = '';

		$value_key = 'newval' . $args['index_key'];
		$property_script .= $value_key . '=newval;';

		$args = $this->get_args( $args );

		// Apply callback to the value if a callback is defined.
		if ( ! empty( $args['js_callback'][0] ) ) {
			$script .= $value_key . '=' . $args['js_callback'][0] . '(' . $value_key . ',' . $args['js_callback'][1] . ');';
		}

		// Apply the value_pattern.
		if ( '' !== $args['value_pattern'] ) {
			$script .= $this->value_pattern_replacements( $value_key, $args );
		}

		// Tweak to add url() for background-images.
		if ( 'background-image' === $args['property'] ) {
			$script .= 'if(-1===' . $value_key . '.indexOf(\'url(\')){' . $value_key . '=\'url("\'+' . $value_key . '+\'");}';
		}

		// Apply prefix.
		$value = $value_key;
		if ( '' !== $args['prefix'] ) {
			$value = $args['prefix'] . '+' . $value_key;
		}
		$css = $args['element'] . '{' . $args['property'] . ':\'+' . $value . '+\'' . $args['units'] . $args['suffix'] . ';}';
		if ( isset( $args['media_query'] ) ) {
			$css = $args['media_query'] . '{' . $css . '}';
		}
		return array(
			'script' => $property_script . $script,
			'css'    => $css,
		);
	}

	/**
	 * Processes script generation for fields that save an array.
	 *
	 * @access protected
	 * @since 3.0.0
	 * @param array $args  The arguments for this js_var.
	 */
	protected function script_var_array( $args ) {

		$script = 'css=\'\';';
		$property_script = '';

		// Define choice.
		$choice  = ( isset( $args['choice'] ) && '' !== $args['choice'] ) ? $args['choice'] : '';
		$script .= ( '' !== $choice ) ? 'choice=\'' . $choice . '\';' : '';

		$value_key = 'newval' . $args['index_key'];
		$property_script .= $value_key . '=newval;';

		$args = $this->get_args( $args );

		// Apply callback to the value if a callback is defined.
		if ( ! empty( $args['js_callback'][0] ) ) {
			$script .= $value_key . '=' . $args['js_callback'][0] . '(' . $value_key . ',' . $args['js_callback'][1] . ');';
		}
		$script .= '_.each(' . $value_key . ', function(subValue,subKey){';

		// Apply the value_pattern.
		if ( '' !== $args['value_pattern'] ) {
			$script .= $this->value_pattern_replacements( 'subValue', $args );
		}

		// Tweak to add url() for background-images.
		if ( '' === $choice || 'background-image' === $choice ) {
			$script .= 'if(\'background-image\'===\'' . $args['property'] . '\'||\'background-image\'===subKey){';
			$script .= 'if(-1===subValue.indexOf(\'url(\')){subValue=\'url("\'+subValue+\'")\';}';
			$script .= '}';
		}

		// Apply prefix.
		$value = $value_key;
		if ( '' !== $args['prefix'] ) {
			$value = '\'' . $args['prefix'] . '\'+subValue';
		}

		// Mostly used for padding, margin & position properties.
		$direction_script  = 'if(_.contains([\'top\',\'bottom\',\'left\',\'right\'],subKey)){';
		$direction_script .= 'css+=\'' . $args['element'] . '{' . $args['property'] . '-\'+subKey+\':\'+subValue+\'' . $args['units'] . $args['suffix'] . ';}\';}';
		// Allows us to apply this just for a specific choice in the array of the values.
		if ( '' !== $choice ) {
			$choice_is_direction = ( false !== strpos( $choice, 'top' ) || false !== strpos( $choice, 'bottom' ) || false !== strpos( $choice, 'left' ) || false !== strpos( $choice, 'right' ) );
			$script .= 'choice=\'' . $choice . '\';';
			$script .= 'if(\'' . $choice . '\'===subKey){';
			$script .= ( $choice_is_direction ) ? $direction_script . 'else{' : '';
			$script .= 'css+=\'' . $args['element'] . '{' . $args['property'] . ':\'+subValue+\';}\';';
			$script .= ( $choice_is_direction ) ? '}' : '';
			$script .= '}';
		} else {
			$script .= $direction_script . 'else{';

			// This is where most object-based fields will go.
			$script .= 'css+=\'' . $args['element'] . '{\'+subKey+\':\'+subValue+\'' . $args['units'] . $args['suffix'] . ';}\';';
			$script .= '}';
		}
		$script .= '});';

		if ( isset( $args['media_query'] ) ) {
			$script .= 'css=\'' . $args['media_query'] . '{\'+css+\'}\';';
		}

		return array(
			'script' => $property_script . $script,
			'css'    => 'css',
		);
	}

	/**
	 * Processes script generation for typography fields.
	 *
	 * @access protected
	 * @since 3.0.0
	 * @param array $args  The arguments for this js_var.
	 */
	protected function script_var_typography( $args ) {

		$script = '';
		$css    = '';

		// Load the font using WenFontloader.
		// This is a bit ugly because wp_add_inline_script doesn't allow adding <script> directly.
		$webfont_loader = 'sc=\'a\';jQuery(\'head\').append(sc.replace(\'a\',\'<\')+\'script>if(!_.isUndefined(WebFont)){WebFont.load({google:{families:["\'+fontFamily.replace( /\"/g, \'&quot;\' )+\':\'+variant+subsetsString+\'"]}});}\'+sc.replace(\'a\',\'<\')+\'/script>\');';

		// Add the css.
		$css_build_array = array(
			'font-family'    => 'fontFamily',
			'font-size'      => 'fontSize',
			'line-height'    => 'lineHeight',
			'letter-spacing' => 'letterSpacing',
			'word-spacing'   => 'wordSpacing',
			'text-align'     => 'textAlign',
			'text-transform' => 'textTransform',
			'color'          => 'color',
			'font-weight'    => 'fontWeight',
			'font-style'     => 'fontStyle',
		);
		$choice_condition = ( isset( $args['choice'] ) && '' !== $args['choice'] && isset( $css_build_array[ $args['choice'] ] ) );
		$script .= ( ! $choice_condition ) ? $webfont_loader : '';
		foreach ( $css_build_array as $property => $var ) {
			if ( $choice_condition && $property !== $args['choice'] ) {
				continue;
			}
			$script .= ( $choice_condition && 'font-family' === $args['choice'] ) ? $webfont_loader : '';
			$css .= 'css+=(\'\'!==' . $var . ')?\'' . $args['element'] . '\'+\'{' . $property . ':\'+' . $var . '+\'}\':\'\';';
		}

		$script .= $css;
		if ( isset( $args['media_query'] ) ) {
			$script .= 'css=\'' . $args['media_query'] . '{\'+css+\'}\';';
		}
		return array(
			'script' => $script,
			'css'    => 'css',
		);
	}

	/**
	 * Adds anything we need before the main script.
	 *
	 * @access private
	 * @since 3.0.0
	 * @param array $args The field args.
	 * @return string;
	 */
	private function before_script( $args ) {

		$script = '';

		if ( isset( $args['type'] ) ) {
			switch ( $args['type'] ) {
				case 'kirki-typography':
					$script .= 'fontFamily=(_.isUndefined(newval[\'font-family\']))?\'\':newval[\'font-family\'];';
					$script .= 'variant=(_.isUndefined(newval.variant))?400:newval.variant;';
					$script .= 'subsets=(_.isUndefined(newval.subsets))?[]:newval.subsets;';
					$script .= 'subsetsString=(_.isObject(newval.subsets))?\':\'+newval.subsets.join(\',\'):\'\';';
					$script .= 'fontSize=(_.isUndefined(newval[\'font-size\']))?\'\':newval[\'font-size\'];';
					$script .= 'lineHeight=(_.isUndefined(newval[\'line-height\']))?\'\':newval[\'line-height\'];';
					$script .= 'letterSpacing=(_.isUndefined(newval[\'letter-spacing\']))?\'\':newval[\'letter-spacing\'];';
					$script .= 'wordSpacing=(_.isUndefined(newval[\'word-spacing\']))?\'\':newval[\'word-spacing\'];';
					$script .= 'textAlign=(_.isUndefined(newval[\'text-align\']))?\'\':newval[\'text-align\'];';
					$script .= 'textTransform=(_.isUndefined(newval[\'text-transform\']))?\'\':newval[\'text-transform\'];';
					$script .= 'color=(_.isUndefined(newval.color))?\'\':newval.color;';
					$script .= 'fontWeight=(!_.isObject(variant.match(/\d/g)))?400:variant.match(/\d/g).join(\'\');';
					$script .= 'fontStyle=(-1!==newval.variant.indexOf(\'italic\'))?\'italic\':\'normal\';';
					$script .= 'css=\'\';';
					break;
			}
		}
		return $script;
	}

	/**
	 * Sanitizes the arguments and makes sure they are all there.
	 *
	 * @access private
	 * @since 3.0.0
	 * @param array $args The arguments.
	 * @return array
	 */
	private function get_args( $args ) {

		// Make sure everything is defined to avoid "undefined index" errors.
		$args = wp_parse_args( $args, array(
			'element'       => '',
			'property'      => '',
			'prefix'        => '',
			'suffix'        => '',
			'units'         => '',
			'js_callback'   => array( '', '' ),
			'value_pattern' => '',
		));

		// Element should be a string.
		if ( is_array( $args['element'] ) ) {
			$args['element'] = implode( ',', $args['element'] );
		}

		// Make sure arguments that are passed-on to callbacks are strings.
		if ( is_array( $args['js_callback'] ) && isset( $args['js_callback'][1] ) && is_array( $args['js_callback'][1] ) ) {
			$args['js_callback'][1] = wp_json_encode( $args['js_callback'][1] );
		}
		return $args;

	}

	/**
	 * Returns script for value_pattern & replacements.
	 *
	 * @access private
	 * @since 3.0.0
	 * @param string $value   The value placeholder.
	 * @param array  $js_vars The js_vars argument.
	 * @return string         The script.
	 */
	private function value_pattern_replacements( $value, $js_vars ) {
		$script = '';
		$alias  = $value;
		if ( isset( $js_vars['pattern_replace'] ) ) {
			$script .= 'settings=window.wp.customize.get();';
			foreach ( $js_vars['pattern_replace'] as $search => $replace ) {
				$replace = '\'+settings["' . $replace . '"]+\'';
				$value = str_replace( $search, $replace, $js_vars['value_pattern'] );
				$value = trim( $value, '+' );
			}
		}
		$value_compiled = str_replace( '$', '\'+' . $alias . '+\'', $value );
		$value_compiled = trim( $value_compiled, '+' );
		return $script . $alias . '=\'' . $value_compiled . '\';';
	}

	/**
	 * Get the callback function/method we're going to use for this field.
	 *
	 * @access private
	 * @since 3.0.0
	 * @param array $args The field args.
	 * @return string|array A callable function or method.
	 */
	protected function get_callback( $args ) {

		switch ( $args['type'] ) {
			case 'kirki-background':
			case 'kirki-dimensions':
			case 'kirki-multicolor':
			case 'kirki-sortable':
				$callback = array( $this, 'script_var_array' );
				break;
			case 'kirki-typography':
				$callback = array( $this, 'script_var_typography' );
				break;
			default:
				$callback = array( $this, 'script_var' );
		}
		return $callback;
	}
}
