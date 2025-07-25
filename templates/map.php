<?php
/**
 *  Template for displaying the map and weather data.
 *
 *  @package webnorthcodechallenge
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>
<!DOCTYPE html>
<html lang="<?php echo esc_attr( get_locale() ); ?>">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>"/>
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />

	<?php wp_head(); ?>

</head>
<body>

<header class="header">
	<div class="logo">
		<img src="<?php echo esc_url( plugins_url( 'img/Logo.svg', WEBNORTH_CODE_CHALLENGE_PLUGIN_FILE ) ); ?>"
			alt="<?php esc_attr_e( 'WebNorth', 'webnorthcodechallenge' ); ?>" />
	</div>
	<h1 class="title"><?php esc_html_e( 'WeatherWay', 'webnorthcodechallenge' ); ?></h1>
	<div class="scroll">
		<p class="scroll-tip"><?php esc_html_e( 'Scroll', 'webnorthcodechallenge' ); ?></p>
		<div class="scroll-down"></div>
	</div>
</header>

<div class="header-spacer"></div>

<main class="main">
	<div id="scroll-trigger"></div>

	<aside class="sidebar">
		<div id="sidebarMobileCurtain"></div>
		<div id="sidebarContent">
			<div class="logo">
				<img src="<?php echo esc_url( plugins_url( 'img/Logo.svg', WEBNORTH_CODE_CHALLENGE_PLUGIN_FILE ) ); ?>"
					alt="<?php esc_attr_e( 'WebNorth', 'webnorthcodechallenge' ); ?>" />
			</div>
			<p class="mt-50 fw-600 text-white">
				<?php esc_html_e( 'Click on the map to get weather data', 'webnorthcodechallenge' ); ?>
			</p>
		</div>
	</aside>

	<div class="map" id="mapWrap"></div>
</main>

<?php wp_footer(); ?>
</body>
</html>
