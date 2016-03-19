---
layout: docs
title: Integrating Kirki
sections:
  - Using a custom setting in the customizer (recommended)
  - Using TGMPA
  - Embedding in your theme
  - Translating Kirki strings in embedded theme
edit: docs/advanced/integration.md
---

There are currently 2 ways to include Kirki in a theme:

* By recommending its installation
	* Using a custom setting in the customizer
	* Using [TGMPA](http://tgmpluginactivation.com/)
* By including a copy of the plugin files in your theme.


There are plans to add a dependencies manager in WordPress core but this is still under discussion so for the time being the best way to include Kirki is by using a custom setting in your customizer or using TGMPA.

This way your users will always have the latest version of the plugin, including all improvements and bugfixes that they would otherwise not get if the plugin files were included in your theme.

### Using a custom setting in the customizer (recommended)

This method requires you to add a few lines of code in your theme that will create a custom section and a custom control. 
When the user visits the customizer, if they don't have Kirki installed they will see a button prompting them to install it.
You can configure the description and add whatever you want so that it suits your use-case.

```php
<?php

if ( ! class_exists( 'Kirki' ) ) {

	if ( class_exists( 'WP_Customize_Section' ) && ! class_exists( 'Kirki_Installer_Control' ) ) {
		/**
		 * A simple control that will render the installer <iframe>.
		 * We'll apply some CSS in order to move the section to the top
		 * as well as style the section & the iframe.
		 */
		class Kirki_Installer_Control extends WP_Customize_Control {
			public $type = 'kirki-installer';
			public function render_content() { ?>
				<style>
				li#accordion-section-kirki_installer { background:#f3f3f3; margin:-15px 0; }
				li#accordion-section-kirki_installer .accordion-section-title,li#accordion-section-kirki_installer .customize-section-title { display: none; }
				li#accordion-section-kirki_installer ul.accordion-section-content { display: block; position: relative; left: 0; margin-top: 0 !important; padding-top: 0; padding-bottom: 0; }
				#customize-controls li#accordion-section-kirki_installer .description { font-size: 1em; }
				#customize-control-kirki_installer { margin-bottom: 0; }
				iframe#kirki-customizer-installer { margin-left: -15px; height: 158px; }
				</style>
				<?php $plugins   = get_plugins(); ?>
				<?php $installed = false; ?>
				<?php foreach ( $plugins as $plugin ) : ?>
					<?php if ( 'Kirki' == $plugin['Name'] || 'Kirki Toolkit' == $plugin['Name'] ) : ?>
						<?php $installed = true; ?>
					<?php endif; ?>
				<?php endforeach; ?>

				<?php if ( ! $installed ) : ?>

					<script>
					var installerStyles = '<style>#plugin-information-tabs,#plugin-information-content {display:none !important;}</style>';
					jQuery('iframe#kirki-customizer-installer').load( function() {
						jQuery('iframe#kirki-customizer-installer').contents().find("head").append( installerStyles );
					});
					</script>
					<iframe id="kirki-customizer-installer" src="<?php echo admin_url( 'plugin-install.php?tab=plugin-information&amp;plugin=kirki' ); ?>"></iframe>
				<?php else : ?>
					<hr>
					<p><?php printf( __( 'The plugin is installed but not activated. Please <a href="%s">activate it</a>.', 'textdomain' ), admin_url( 'plugins.php' ) ); ?></p>
				<?php endif;
			}
		}

	}

	if ( ! function_exists( 'kirki_installer_register' ) ) {
		/**
		 * Registers the section, setting & control for the kirki installer.
		 */
		function kirki_installer_register( $wp_customize ) {
			// Add the section/
			// You can add your description here.
			// Please note that the title will not be displayed.
			$wp_customize->add_section( 'kirki_installer', array(
				'title'       => '',
				'description' => esc_attr__( 'If you want to take full advantage of the options this theme has to provide, please install the Kirki plugin.', 'textdomain' ),
				'priority'    => -10,
				'capability'  => 'install_plugins',
			) );
			// Add the setting. This is required by WordPress in order to add our control.
			$wp_customize->add_setting( 'kirki_installer', array(
				'type'              => 'theme_mod',
				'capability'        => 'install_plugins',
				'default'           => '',
				'sanitize_callback' => '__return_true',
			));
			// Add our control. This is required in order to show the section.
			$wp_customize->add_control( new Kirki_Installer_Control( $wp_customize, 'kirki_installer', array(
				'section' => 'kirki_installer',
			) ) );

		}
		add_action( 'customize_register', 'kirki_installer_register' );
	}
}
```

### Using TGMPA

For instructions on how to use TGMPA, please [visit the TGMPA site](http://tgmpluginactivation.com/).

### Embedding in your theme

However in some cases we understand that you may need to instead include it as a library in your theme/plugin.

In order to properly do that, please follow the instructions below:

* Copy the plugin folder in your theme (for example in *{theme_folder}/includes/kirki*).
* Include the main plugin file in your theme's functions.php file:

```php
<?php include_once( dirname( __FILE__ ) . '/includes/kirki/kirki.php' ); ?>
```

Kirki will auto-detect that it's embedded in a theme and the URLs & paths will automatically be adjusted.

If for some reason the URLs are not properly detected in your setup, you can add the following code in your theme:

```php
<?php
if ( ! function_exists( 'my_theme_kirki_update_url' ) ) {
    function my_theme_kirki_update_url( $config ) {
        $config['url_path'] = get_stylesheet_directory_uri() . '/inc/kirki/';
        return $config;
    }
}
add_filter( 'kirki/config', 'my_theme_kirki_update_url' );
?>
```

### Translating Kirki strings in embedded theme

In case you decide to include Kirki in your theme, you may want to consider adding the translations there as well so that they use your own textdomain.

You can do that using the `kirki/{$config_id}/l10n` filter.

```php
<?php
add_filter( 'kirki/my_config/l10n', function( $l10n ) {

	$l10n['background-color']      => esc_attr__( 'Background Color', my_textdomain );
	$l10n['background-image']      => esc_attr__( 'Background Image', my_textdomain );
	$l10n['no-repeat']             => esc_attr__( 'No Repeat', my_textdomain );
	$l10n['repeat-all']            => esc_attr__( 'Repeat All', my_textdomain );
	$l10n['repeat-x']              => esc_attr__( 'Repeat Horizontally', my_textdomain );
	$l10n['repeat-y']              => esc_attr__( 'Repeat Vertically', my_textdomain );
	$l10n['inherit']               => esc_attr__( 'Inherit', my_textdomain );
	$l10n['background-repeat']     => esc_attr__( 'Background Repeat', my_textdomain );
	$l10n['cover']                 => esc_attr__( 'Cover', my_textdomain );
	$l10n['contain']               => esc_attr__( 'Contain', my_textdomain );
	$l10n['background-size']       => esc_attr__( 'Background Size', my_textdomain );
	$l10n['fixed']                 => esc_attr__( 'Fixed', my_textdomain );
	$l10n['scroll']                => esc_attr__( 'Scroll', my_textdomain );
	$l10n['background-attachment'] => esc_attr__( 'Background Attachment', my_textdomain );
	$l10n['left-top']              => esc_attr__( 'Left Top', my_textdomain );
	$l10n['left-center']           => esc_attr__( 'Left Center', my_textdomain );
	$l10n['left-bottom']           => esc_attr__( 'Left Bottom', my_textdomain );
	$l10n['right-top']             => esc_attr__( 'Right Top', my_textdomain );
	$l10n['right-center']          => esc_attr__( 'Right Center', my_textdomain );
	$l10n['right-bottom']          => esc_attr__( 'Right Bottom', my_textdomain );
	$l10n['center-top']            => esc_attr__( 'Center Top', my_textdomain );
	$l10n['center-center']         => esc_attr__( 'Center Center', my_textdomain );
	$l10n['center-bottom']         => esc_attr__( 'Center Bottom', my_textdomain );
	$l10n['background-position']   => esc_attr__( 'Background Position', my_textdomain );
	$l10n['background-opacity']    => esc_attr__( 'Background Opacity', my_textdomain );
	$l10n['on']                    => esc_attr__( 'ON', my_textdomain );
	$l10n['off']                   => esc_attr__( 'OFF', my_textdomain );
	$l10n['all']                   => esc_attr__( 'All', my_textdomain );
	$l10n['cyrillic']              => esc_attr__( 'Cyrillic', my_textdomain );
	$l10n['cyrillic-ext']          => esc_attr__( 'Cyrillic Extended', my_textdomain );
	$l10n['devanagari']            => esc_attr__( 'Devanagari', my_textdomain );
	$l10n['greek']                 => esc_attr__( 'Greek', my_textdomain );
	$l10n['greek-ext']             => esc_attr__( 'Greek Extended', my_textdomain );
	$l10n['khmer']                 => esc_attr__( 'Khmer', my_textdomain );
	$l10n['latin']                 => esc_attr__( 'Latin', my_textdomain );
	$l10n['latin-ext']             => esc_attr__( 'Latin Extended', my_textdomain );
	$l10n['vietnamese']            => esc_attr__( 'Vietnamese', my_textdomain );
	$l10n['hebrew']                => esc_attr__( 'Hebrew', my_textdomain );
	$l10n['arabic']                => esc_attr__( 'Arabic', my_textdomain );
	$l10n['bengali']               => esc_attr__( 'Bengali', my_textdomain );
	$l10n['gujarati']              => esc_attr__( 'Gujarati', my_textdomain );
	$l10n['tamil']                 => esc_attr__( 'Tamil', my_textdomain );
	$l10n['telugu']                => esc_attr__( 'Telugu', my_textdomain );
	$l10n['thai']                  => esc_attr__( 'Thai', my_textdomain );
	$l10n['serif']                 => _x( 'Serif', 'font style', my_textdomain );
	$l10n['sans-serif']            => _x( 'Sans Serif', 'font style', my_textdomain );
	$l10n['monospace']             => _x( 'Monospace', 'font style', my_textdomain );
	$l10n['font-family']           => esc_attr__( 'Font Family', my_textdomain );
	$l10n['font-size']             => esc_attr__( 'Font Size', my_textdomain );
	$l10n['font-weight']           => esc_attr__( 'Font Weight', my_textdomain );
	$l10n['line-height']           => esc_attr__( 'Line Height', my_textdomain );
	$l10n['font-style']            => esc_attr__( 'Font Style', my_textdomain );
	$l10n['letter-spacing']        => esc_attr__( 'Letter Spacing', my_textdomain );
	$l10n['top']                   => esc_attr__( 'Top', my_textdomain );
	$l10n['bottom']                => esc_attr__( 'Bottom', my_textdomain );
	$l10n['left']                  => esc_attr__( 'Left', my_textdomain );
	$l10n['right']                 => esc_attr__( 'Right', my_textdomain );
	$l10n['color']                 => esc_attr__( 'Color', my_textdomain );
	$l10n['add-image']             => esc_attr__( 'Add Image', my_textdomain );
	$l10n['change-image']          => esc_attr__( 'Change Image', my_textdomain );
	$l10n['remove']                => esc_attr__( 'Remove', my_textdomain );
	$l10n['no-image-selected']     => esc_attr__( 'No Image Selected', my_textdomain );
	$l10n['select-font-family']    => esc_attr__( 'Select a font-family', my_textdomain );
	$l10n['variant']               => esc_attr__( 'Variant', my_textdomain );
	$l10n['subsets']               => esc_attr__( 'Subset', my_textdomain );
	$l10n['size']                  => esc_attr__( 'Size', my_textdomain );
	$l10n['height']                => esc_attr__( 'Height', my_textdomain );
	$l10n['spacing']               => esc_attr__( 'Spacing', my_textdomain );
	$l10n['ultra-light']           => esc_attr__( 'Ultra-Light 100', my_textdomain );
	$l10n['ultra-light-italic']    => esc_attr__( 'Ultra-Light 100 Italic', my_textdomain );
	$l10n['light']                 => esc_attr__( 'Light 200', my_textdomain );
	$l10n['light-italic']          => esc_attr__( 'Light 200 Italic', my_textdomain );
	$l10n['book']                  => esc_attr__( 'Book 300', my_textdomain );
	$l10n['book-italic']           => esc_attr__( 'Book 300 Italic', my_textdomain );
	$l10n['regular']               => esc_attr__( 'Normal 400', my_textdomain );
	$l10n['italic']                => esc_attr__( 'Normal 400 Italic', my_textdomain );
	$l10n['medium']                => esc_attr__( 'Medium 500', my_textdomain );
	$l10n['medium-italic']         => esc_attr__( 'Medium 500 Italic', my_textdomain );
	$l10n['semi-bold']             => esc_attr__( 'Semi-Bold 600', my_textdomain );
	$l10n['semi-bold-italic']      => esc_attr__( 'Semi-Bold 600 Italic', my_textdomain );
	$l10n['bold']                  => esc_attr__( 'Bold 700', my_textdomain );
	$l10n['bold-italic']           => esc_attr__( 'Bold 700 Italic', my_textdomain );
	$l10n['extra-bold']            => esc_attr__( 'Extra-Bold 800', my_textdomain );
	$l10n['extra-bold-italic']     => esc_attr__( 'Extra-Bold 800 Italic', my_textdomain );
	$l10n['ultra-bold']            => esc_attr__( 'Ultra-Bold 900', my_textdomain );
	$l10n['ultra-bold-italic']     => esc_attr__( 'Ultra-Bold 900 Italic', my_textdomain );
	$l10n['invalid-value']         => esc_attr__( 'Invalid Value', my_textdomain );

	return $l10n;

} );
?>
```