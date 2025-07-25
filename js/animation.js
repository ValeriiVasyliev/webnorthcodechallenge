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