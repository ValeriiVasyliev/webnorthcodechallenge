// Initialize i18n localization
if (typeof wp !== 'undefined' && wp.i18n && typeof __ === 'undefined') {
    wp.i18n.setLocaleData({}, 'webnorthcodechallenge');
    var __ = wp.i18n.__;
}

const LOCAL_STORAGE_KEY = 'savedStations';

// Get saved stations from local storage
const getSavedStations = () => {
    try {
        const raw = localStorage.getItem(LOCAL_STORAGE_KEY);
        return raw ? JSON.parse(raw) : [];
    } catch {
        return [];
    }
};

// Save a station to local storage
const saveStationToLocal = (id) => {
    const saved = new Set(getSavedStations());
    saved.add(String(id));
    localStorage.setItem(LOCAL_STORAGE_KEY, JSON.stringify([...saved]));
};

// Remove a station from local storage
const removeStationFromLocal = (id) => {
    const saved = new Set(getSavedStations());
    saved.delete(String(id));
    localStorage.setItem(LOCAL_STORAGE_KEY, JSON.stringify([...saved]));
};

// Check if a station is saved in local storage
const isStationSaved = (id) => {
    return getSavedStations().includes(String(id));
};

// Get WebNorth Code Challenge settings from global variable
const getWebNorthCodeChallengeSettings = () => ({
    restUrl: webnorthCodeChallengeSettings?.rest_url ?? '',
    nonce: webnorthCodeChallengeSettings?.nonce ?? ''
});

// Render weather data in the sidebar
const renderWeatherData = (data, unit) => {
    const sidebarContent = document.querySelector('#sidebarCityContent');
    if (!sidebarContent) return;

    // Show loading animation
    sidebarContent.innerHTML = `
        <div class="loading">
            <div class="loader"></div>
        </div>
    `;

    requestAnimationFrame(() => {
        setTimeout(() => {
            sidebarContent.innerHTML = '';

            const title = document.createElement('h3');
            title.textContent = data.title || __('Weather Data', 'webnorthcodechallenge');
            sidebarContent.appendChild(title);

            const weatherInfo = data.weather.weather?.[0] || {};
            const main = data.weather.main?.[unit] || {};
            const tempUnit = unit === 'imperial' ? '°F' : '°C';

            const lines = [
                `<p><span class="fw-600">Weather:</span> ${weatherInfo.main || 'N/A'} - ${weatherInfo.description || 'N/A'}</p>`,
                `<p><span class="fw-600">Temp:</span> ${main.temp ?? 'N/A'} / ${main.feels_like ?? 'N/A'} ${tempUnit}</p>`,
                `<p><span class="fw-600">Pressure:</span> ${main.pressure ?? 'N/A'}</p>`,
                `<p><span class="fw-600">Humidity:</span> ${main.humidity ?? 'N/A'}</p>`
            ];

            sidebarContent.innerHTML += lines.join('');
        }, 300);
    });
};


// Load weather data for a specific station ID
const loadWeatherData = async (id, units = 'metric') => {
    const { restUrl, nonce } = getWebNorthCodeChallengeSettings();
    if (!restUrl || !nonce) {
        console.error('REST URL or nonce is not defined.');
        return null;
    }

    try {
        const response = await fetch(`${restUrl}weather-station/${id}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': nonce
            }
        });

        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        const data = await response.json();
        if (!data || !data.weather) {
            console.warn('No weather data found for station ID:', id);
            return null;
        }

        renderWeatherData(data, units);
        return data.weather;
    } catch (error) {
        console.error('Error fetching weather data:', error);
        return null;
    }
};

// Get station by ID from the list of stations
const getStationById = (id, stations) => stations.find((s) => String(s.id) === String(id));

// Setup sidebar controls for temperature unit and save button
const setupSidebarControls = (header, stationId) => {
    const cBtn = document.querySelector('#celciusBtn');
    const fBtn = document.querySelector('#fahrenheitBtn');
    const saveBtn = document.querySelector('#saveBtn');

    const toggleBtn = document.getElementById('sidebarSavedLocationsBtn');
    toggleBtn.textContent = __('My Locations', 'webnorthcodechallenge');

    const sidebarContent = document.getElementById('sidebarContent');
    sidebarContent.classList.remove('active');

    const handleUnitClick = async (unit) => {
        cBtn.classList.toggle('active', unit === 'metric');
        fBtn.classList.toggle('active', unit === 'imperial');
        if (stationId) await loadWeatherData(stationId, unit);
    };

    const updateSaveIcon = () => {
        if (!saveBtn) return;
        saveBtn.classList.toggle('saved', isStationSaved(stationId));
        saveBtn.setAttribute('title', isStationSaved(stationId) ? 'Unsave' : 'Save');
    };

    cBtn?.addEventListener('click', () => handleUnitClick('metric'));
    fBtn?.addEventListener('click', () => handleUnitClick('imperial'));

    saveBtn?.addEventListener('click', () => {
        if (isStationSaved(stationId)) {
            removeStationFromLocal(stationId);
        } else {
            saveStationToLocal(stationId);
        }
        updateSaveIcon();
    });

    updateSaveIcon();
};

// Update sidebar with initial station data
const updateSidebar = (lat, lng, title = '', stationId = '') => {
    if (stationId) window.location.hash = `#${stationId}`;
    const sidebar = document.querySelector('#sidebarContent');
    if (!sidebar) return;

    sidebar.innerHTML = '';

    const header = document.createElement('div');
    header.className = 'sidebar-header';
    header.setAttribute('data-id', stationId);

    const tempBtns = document.createElement('div');
    tempBtns.className = 'temp-buttons';
    tempBtns.innerHTML = `
        <button id="celciusBtn" class="active">${__('Celcius', 'webnorthcodechallenge')} /</button>
        <button id="fahrenheitBtn">&nbsp;${__('Fahrenheit', 'webnorthcodechallenge')}</button>
    `;

    const saveBtn = document.createElement('button');
    saveBtn.id = 'saveBtn';
    saveBtn.innerHTML = `<svg width="19" height="25" viewBox="0 0 19 25" fill="none" xmlns="http://www.w3.org/2000/svg">
<path fill-rule="evenodd" clip-rule="evenodd" d="M0.53125 2.25L1.8125 0.96875H17.1875L18.4688 2.25V24.9985L9.5 19.6837L0.53125 24.9985V2.25ZM3.09375 3.53125V20.5015L9.5 16.7051L15.9062 20.5015V3.53125H3.09375Z" fill="#EEEEEE"/>
</svg>`;

    header.appendChild(tempBtns);
    header.appendChild(saveBtn);
    sidebar.appendChild(header);

    const content = document.createElement('div');
    content.id = 'sidebarCityContent';
    sidebar.appendChild(content);

    setupSidebarControls(header, stationId);
};

// Initialize the map and markers
document.addEventListener('DOMContentLoaded', () => {
    if (!Array.isArray(webnorthCodeChallengeSettings?.weather_stations)) {
        console.warn('No weather stations data found.');
        return;
    }

    const stations = webnorthCodeChallengeSettings.weather_stations;
    const firstStation = stations[0];
    const map = L.map('mapWrap').setView([firstStation.lat, firstStation.lng], 6);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(map);

    const markers = {};

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

        markers[station.id] = marker;

        marker.on('click', async () => {
            marker.openPopup();
            updateSidebar(lat, lng, station.title, station.id);
            await loadWeatherData(station.id);
        });
    });

    map.on('click', async (e) => {
        const { lat: clickedLat, lng: clickedLng } = e.latlng;

        const getDistance = (lat1, lng1, lat2, lng2) => {
            const toRad = deg => deg * (Math.PI / 180);
            const R = 6371;
            const dLat = toRad(lat2 - lat1);
            const dLng = toRad(lng2 - lng1);
            const a = Math.sin(dLat / 2) ** 2 +
                Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) *
                Math.sin(dLng / 2) ** 2;
            return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        };

        let closest = null;
        let minDist = Infinity;

        for (const station of stations) {
            const dist = getDistance(clickedLat, clickedLng, station.lat, station.lng);
            if (dist < minDist) {
                minDist = dist;
                closest = station;
            }
        }

        if (closest) {
            map.setView([closest.lat, closest.lng], 7);
            markers[closest.id]?.openPopup();
            updateSidebar(closest.lat, closest.lng, closest.title, closest.id);
            await loadWeatherData(closest.id);
        }
    });

    // Load station from hash (e.g., map#3)
    const loadFromHash = async () => {
        const hash = window.location.hash.replace('#', '');
        if (!hash) return;

        const station = getStationById(hash, stations);
        if (!station) return;

        const mapElement = document.getElementById('mapWrap');
        if (mapElement) {
            mapElement.scrollIntoView({ behavior: 'smooth' });
        }

        // Center map, open popup, and load data
        map.setView([station.lat, station.lng], 7);
        markers[station.id]?.openPopup();
        updateSidebar(station.lat, station.lng, station.title, station.id);
        await loadWeatherData(station.id);
    };

    window.addEventListener('hashchange', loadFromHash);
    loadFromHash();
});

// Saved locations sidebar toggle.
document.addEventListener('DOMContentLoaded', () => {
    const toggleBtn = document.getElementById('sidebarSavedLocationsBtn');
    const sidebarContent = document.getElementById('sidebarContent');

    toggleBtn.addEventListener('click', () => {
        sidebarContent.classList.toggle('active');
        const isActive = sidebarContent.classList.contains('active');

        // Update toggle button text
        toggleBtn.textContent = isActive
            ? __('Close', 'webnorthcodechallenge')
            : __('My Locations', 'webnorthcodechallenge');

        sidebarContent.innerHTML = ''; // Always clear first

        if (isActive) {
            const savedStations = getSavedStations();

            if (savedStations.length > 0) {
                savedStations.forEach((id) => {
                    const station = getStationById(id, webnorthCodeChallengeSettings.weather_stations);
                    if (station) {
                        const heading = document.createElement('h3');
                        heading.textContent = station.title;

                        heading.addEventListener('click', async () => {
                            const mapElement = document.getElementById('mapWrap');
                            if (mapElement) {
                                mapElement.scrollIntoView({ behavior: 'smooth' });
                            }
                            const lat = parseFloat(station.lat);
                            const lng = parseFloat(station.lng);
                            updateSidebar(lat, lng, station.title, station.id);
                            await loadWeatherData(station.id);
                        });

                        sidebarContent.appendChild(heading);
                    }
                });
            } else {
                const noLocationsText = document.createElement('p');
                noLocationsText.textContent = __('No locations', 'webnorthcodechallenge');
                sidebarContent.appendChild(noLocationsText);
            }
        } else {

            // Add logo + instruction text
            const logoDiv = document.createElement('div');
            logoDiv.className = 'logo';
            logoDiv.innerHTML = `
                <img src="${webnorthCodeChallengeSettings.logo}" alt="${__('WebNorth', 'webnorthcodechallenge')}" />
            `;

            const instructionText = document.createElement('p');
            instructionText.className = 'mt-50 fw-600 text-white';
            instructionText.textContent = __('Click on the map to get weather data', 'webnorthcodechallenge');

            sidebarContent.appendChild(logoDiv);
            sidebarContent.appendChild(instructionText);
        }
    });
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
    scrollAnim(document.querySelector('.main-gradient'), {
        '90%': { opacity: '1', visibility: 'visible' },
        '100%': { opacity: '0', visibility: 'hidden' }
    });
});
