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

	<!-- Leaflet CSS -->
	<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

	<!-- Plugin Styles -->
	<link rel="stylesheet" href="<?php echo esc_url( plugins_url( 'styles/css/style.css', WEBNORTH_CODE_CHALLENGE_PLUGIN_FILE ) ); ?>" />
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

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<!-- Plugin JS -->
<script src="<?php echo esc_url( plugins_url( 'js/animation.js', WEBNORTH_CODE_CHALLENGE_PLUGIN_FILE ) ); ?>"></script>

<script>
	document.addEventListener('DOMContentLoaded', function () {
		const latitude = 38.8976763;
		const longitude = -77.0365298;
		const zoom = 14;

		const map = L.map('mapWrap').setView([latitude, longitude], zoom);

		const marker = L.marker([latitude, longitude])
			.addTo(map)
			.bindPopup(`Latitude: ${latitude}<br>Longitude: ${longitude}`)
			.openPopup();

		L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
			attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
		}).addTo(map);

		marker.on('click', function (e) {
			const { lat, lng } = e.latlng;
			marker.setPopupContent(`Latitude: ${lat}<br>Longitude: ${lng}`).openPopup();
			updateSidebar(lat, lng);
		});

		function updateSidebar(lat, lng) {
			const sidebar = document.querySelector('#sidebarContent');
			if (!sidebar) return;

			sidebar.innerHTML = '';

			const header = document.createElement('div');
			header.className = 'sidebar-header';

			const tempBtns = document.createElement('div');
			tempBtns.className = 'temp-buttons';

			const cBtn = document.createElement('button');
			cBtn.id = 'celciusBtn';
			cBtn.classList.add('active');
			cBtn.innerHTML = '<?php esc_html_e( 'Celsius /', 'webnorthcodechallenge' ); ?>';

			const fBtn = document.createElement('button');
			fBtn.id = 'fahrenheitBtn';
			fBtn.innerHTML = '&nbsp;<?php esc_html_e( 'Fahrenheit', 'webnorthcodechallenge' ); ?>';

			tempBtns.appendChild(cBtn);
			tempBtns.appendChild(fBtn);

			const saveBtn = document.createElement('button');
			saveBtn.id = 'saveBtn';
			saveBtn.innerHTML = `
				<svg width="19" height="25" viewBox="0 0 19 25" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path fill-rule="evenodd" clip-rule="evenodd"
							d="M0.53125 2.25L1.8125 0.96875H17.1875L18.4688 2.25V24.9985L9.5 19.6837L0.53125 24.9985V2.25ZM3.09375 3.53125V20.5015L9.5 16.7051L15.9062 20.5015V3.53125H3.09375Z"
							fill="#EEEEEE"/>
				</svg>`;

			header.appendChild(tempBtns);
			header.appendChild(saveBtn);
			sidebar.appendChild(header);

			const content = document.createElement('div');
			content.innerHTML = `
				<span class="fw-600">Latitude:</span> ${lat}<br><br>
				<span class="fw-600">Longitude:</span> ${lng}
			`;
			sidebar.appendChild(content);
		}
	});
</script>

<?php wp_footer(); ?>
</body>
</html>
