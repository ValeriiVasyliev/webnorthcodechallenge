// Initialize i18n localization
if (typeof wp !== 'undefined' && wp.i18n && typeof __ === 'undefined') {
    wp.i18n.setLocaleData({}, 'webnorthcodechallenge');
    var __ = wp.i18n.__;
}

// Get the settings from the global variable
const getWebNorthCodeChallengeSettings = () => {
    return {
        'restUrl': webnorthCodeChallengeSettings?.rest_url ?? '',
        'nonce': webnorthCodeChallengeSettings?.nonce ?? ''
    };
}
const loadWeatherData = async (id, units = 'metric') => {
    const { restUrl, nonce } = getWebNorthCodeChallengeSettings();
    if (!restUrl || !nonce) {
        console.error('REST URL or nonce is not defined.');
        return null;
    }

    try {
        const response = await fetch(`${restUrl}weather-station/${id}?units=${units}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': nonce,
            },
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        if (!data) {
            console.warn('No weather data found for station ID:', id);
            return null;
        }

        const weatherData = data.weather;

        // Render weather data
        const sidebarContent = document.querySelector('#sidebarCityContent');
        if (sidebarContent) {

            sidebarContent.innerHTML = `
    <div class="loading">
        <div class="loader"></div>
    </div>
`;
            setTimeout(() => {
                // Simulate smooth transition (optional delay for demo effect)
                sidebarContent.innerHTML = '';

                // Add title
                const title = document.createElement('h3');
                title.textContent = data.title || __('Weather Data', 'webnorthcodechallenge');
                sidebarContent.appendChild(title);

                for (const [key, value] of Object.entries(weatherData.main)) {
                    const p = document.createElement('p');
                    const capitalizedKey = key.charAt(0).toUpperCase() + key.slice(1);
                    p.innerHTML = `<span class="fw-600">${capitalizedKey}:</span> ${value}`;
                    sidebarContent.appendChild(p);
                }
            }, 300);
        }

        return weatherData;
    } catch (error) {
        console.error('Error fetching weather data:', error);
        return null;
    }
};

document.addEventListener('DOMContentLoaded', () => {
    if (
        typeof webnorthCodeChallengeSettings === 'undefined' ||
        !Array.isArray(webnorthCodeChallengeSettings.weather_stations)
    ) {
        console.warn('No weather stations data found.');
        return;
    }

    const stations = webnorthCodeChallengeSettings.weather_stations;
    const firstStation = stations[0];
    const map = L.map('mapWrap').setView([firstStation.lat, firstStation.lng], 6);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution:
            '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    }).addTo(map);

    stations.forEach((station) => {
        const lat = parseFloat(station.lat);
        const lng = parseFloat(station.lng);
        if (!lat || !lng) return;

        const marker = L.marker([lat, lng])
            .addTo(map)
            .bindPopup(
                `${__('Latitude', 'webnorthcodechallenge')}: ${lat}<br>` +
                `${__('Longitude', 'webnorthcodechallenge')}: ${lng}<br>` +
                station.title
            );

        marker.on('click', async () => {
            marker
                .setPopupContent(
                    `${__('Latitude', 'webnorthcodechallenge')}: ${lat}<br>` +
                    `${__('Longitude', 'webnorthcodechallenge')}: ${lng}<br>` +
                    station.title
                )
                .openPopup();
            updateSidebar(lat, lng, station.title, station.id);

            const weatherData = await loadWeatherData(station.id);
            if (weatherData) {
                console.log('Weather data for station', station.id, weatherData);
            }
        });
    });

    // Handle map click to activate closest weather station
    map.on('click', async function (e) {
        const clickedLat = e.latlng.lat;
        const clickedLng = e.latlng.lng;

        // Calculate distance using the Haversine formula
        const getDistance = (lat1, lng1, lat2, lng2) => {
            const toRad = (deg) => deg * (Math.PI / 180);
            const R = 6371; // Earth's radius in km
            const dLat = toRad(lat2 - lat1);
            const dLng = toRad(lng2 - lng1);
            const a =
                Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) *
                Math.sin(dLng / 2) * Math.sin(dLng / 2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            return R * c;
        };

        let closestStation = null;
        let minDistance = Infinity;

        stations.forEach((station) => {
            const distance = getDistance(clickedLat, clickedLng, station.lat, station.lng);
            if (distance < minDistance) {
                minDistance = distance;
                closestStation = station;
            }
        });

        if (closestStation) {
            const { lat, lng, title, id } = closestStation;
            map.setView([lat, lng], 7);
            updateSidebar(lat, lng, title, id);
            const weatherData = await loadWeatherData(id);
            if (weatherData) {
                console.log('Weather data (map click) for station', id, weatherData);
            }
        }
    });


    function updateSidebar(lat, lng, title = '', stationId = '') {
        if (stationId) {
            window.location.hash = `#${stationId}`;
        }

        const sidebar = document.querySelector('#sidebarContent');
        if (!sidebar) return;

        sidebar.innerHTML = '';

        const header = document.createElement('div');
        header.className = 'sidebar-header';
        header.setAttribute('data-id', stationId);

        const tempBtns = document.createElement('div');
        tempBtns.className = 'temp-buttons';

        const cBtn = document.createElement('button');
        cBtn.id = 'celciusBtn';
        cBtn.classList.add('active');
        cBtn.textContent = __('Celcius', 'webnorthcodechallenge') + ' /';

        const fBtn = document.createElement('button');
        fBtn.id = 'fahrenheitBtn';
        fBtn.innerHTML = '&nbsp;' + __('Fahrenheit', 'webnorthcodechallenge');

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
        content.id = 'sidebarCityContent';
        sidebar.appendChild(content);

        // Attach click listeners for temperature unit buttons
        cBtn.addEventListener('click', async () => {
            cBtn.classList.add('active');
            fBtn.classList.remove('active');
            const id = header.getAttribute('data-id');
            if (id) await loadWeatherData(id, 'metric');
        });

        fBtn.addEventListener('click', async () => {
            fBtn.classList.add('active');
            cBtn.classList.remove('active');
            const id = header.getAttribute('data-id');
            if (id) await loadWeatherData(id, 'imperial');
        });
    }
});


// Initialize the sidebar toggle functionality
document.addEventListener("DOMContentLoaded", function () {
    // sidebar sliding
    const sidebar = document.querySelector('.sidebar');
    const trigger = document.getElementById('scroll-trigger');
    const observer = new IntersectionObserver((entries) => {
        if (entries[0].isIntersecting) {
            sidebar.classList.add('active');
        } else if (sidebar.classList.contains('active')) {
            sidebar.classList.remove('active');
        }
    }, {
        threshold: 0.01
    });
    observer.observe(trigger);

    // sidebar mobile curtain
    const sidebarCurtain = document.getElementById('sidebarMobileCurtain');
    let startY = 0;

    sidebarCurtain.addEventListener('touchstart', e => {
        startY = e.touches[0].clientY;
    });

    sidebarCurtain.addEventListener('touchend', e => {
        const endY = e.changedTouches[0].clientY;
        const delta = endY - startY;

        if (delta < -30) { //swipe up
            if (sidebar.classList.contains('close'))
                sidebar.classList.remove('close');
            else
                sidebar.classList.add('open');
        } else if (delta > 30) { // swipe down
            if (sidebar.classList.contains('open'))
                sidebar.classList.remove('open');
            else
                sidebar.classList.add('close');
        }
    });



    // scroll-linked animation for header(fallback for not chromium)
    const scrollAnim = (() => {
        const hasNative = CSS.supports('animation-timeline', 'scroll()');
        const anims = new Map();
        let ticking = false;
        const lerp = (a, b, t) => a + (b - a) * t;
        const parseVal = (val) => {
            const n = parseFloat(val);
            return { val: n, unit: val.replace(n, '') };
        };
        const update = () => {
            const progress = Math.min(1, window.scrollY / (document.body.scrollHeight - window.innerHeight));
            anims.forEach(({ el, frames }) => {
                const p = progress * 100;
                let [start, end] = [frames[0], frames[frames.length - 1]];
                for (let i = 0; i < frames.length - 1; i++) {
                    if (p >= frames[i].p && p <= frames[i + 1].p) {
                        [start, end] = [frames[i], frames[i + 1]];
                        break;
                    }
                }
                const t = start.p === end.p ? 0 : (p - start.p) / (end.p - start.p);
                Object.keys(start.styles).forEach(prop => {
                    const s = parseVal(start.styles[prop]);
                    const e = parseVal(end.styles[prop] || start.styles[prop]);
                    if (prop === 'opacity') {
                        el.style.opacity = lerp(s.val, e.val, t);
                    } else if (prop === 'visibility') {
                        console.log(t);

                        el.style.visibility = t > 0.99 ? 'hidden' : 'visible';
                    } else if (s.unit === e.unit) {
                        el.style[prop] = lerp(s.val, e.val, t) + s.unit;
                    }
                });
            });
            ticking = false;
        };
        if (!hasNative) {
            addEventListener('scroll', () => {
                if (!ticking) {
                    requestAnimationFrame(update);
                    ticking = true;
                }
            }, { passive: true });
        }
        return (el, keyframes) => {
            if (hasNative) {
                const name = 'sa' + Math.random().toString(36).slice(2, 8);
                const css = `@keyframes ${name}{${Object.entries(keyframes).map(([k, v]) =>
                    `${k}{${Object.entries(v).map(([p, val]) => `${p}:${val}`).join(';')}}`
                ).join('')}}`;
                document.head.appendChild(Object.assign(document.createElement('style'), { textContent: css }));
                Object.assign(el.style, {
                    animationName: name,
                    animationDuration: 'auto',
                    animationTimeline: 'scroll()'
                });
            } else {
                anims.set(el, {
                    el,
                    frames: Object.entries(keyframes).map(([k, styles]) => ({
                        p: parseFloat(k),
                        styles
                    })).sort((a, b) => a.p - b.p)
                });
                update();
            }
        };
    })();


    scrollAnim(document.querySelector('header.header'), {
        '0%': { opacity: '1', visibility: 'visible' },
        '100%': { opacity: '0', visibility: 'hidden' }
    });
});