(function () {
    'use strict';

    function init() {
    if (!document.body.classList.contains('is-longread')) return;

    var BREAKPOINT = 1024;
    var SCROLL_OFFSET = 90;

    /* Ensure viewport-fit=cover is active so iOS Safari reports a
     * non-zero env(safe-area-inset-bottom). Without this the mobile
     * bar's background does not extend into the home indicator area
     * and a visible gap appears between the bar and the bottom edge
     * of the screen. Most themes (Astra included) ship a viewport
     * meta without viewport-fit, so we patch it here. */
    var vp = document.querySelector('meta[name="viewport"]');
    if (vp && vp.content.indexOf('viewport-fit') === -1) {
        vp.content = vp.content.replace(/\s*,?\s*$/, '') + ', viewport-fit=cover';
    } else if (!vp) {
        vp = document.createElement('meta');
        vp.name = 'viewport';
        vp.content = 'width=device-width, initial-scale=1, viewport-fit=cover';
        document.head.appendChild(vp);
    }

    var content = document.querySelector('.entry-content')
        || document.querySelector('main')
        || document.querySelector('.site-content');
    if (!content) return;

    var allHeadings = Array.prototype.slice.call(content.querySelectorAll('h2, h3'));
    var h2s = Array.prototype.slice.call(content.querySelectorAll('h2'));
    if (h2s.length === 0) return;

    /* Assign IDs to headings that lack them */
    allHeadings.forEach(function (h, i) {
        if (!h.id) {
            var slug = h.textContent.toLowerCase()
                .replace(/[^a-z0-9\s-]/g, '').trim()
                .replace(/\s+/g, '-').substring(0, 60);
            h.id = 'lr-' + (slug || i);
        }
    });

    var sidebar      = document.getElementById('sfp-lr-sidebar');
    var tocList      = document.getElementById('sfp-lr-toc-list');
    var bar          = document.getElementById('sfp-lr-bar');
    var prevBtn      = document.getElementById('sfp-lr-prev');
    var nextBtn      = document.getElementById('sfp-lr-next');
    var topBtn       = document.getElementById('sfp-lr-top');
    var chapterLabel = document.getElementById('sfp-lr-chapter-label');

    /* Build TOC */
    allHeadings.forEach(function (h) {
        var item = document.createElement('div');
        item.className = 'sfp-lr-toc__item sfp-lr-toc__item--' + h.tagName.toLowerCase();
        var a = document.createElement('a');
        a.href = '#' + h.id;
        a.className = 'sfp-lr-toc__link';
        a.textContent = h.textContent;
        a.addEventListener('click', function (e) {
            e.preventDefault();
            scrollToHeading(h);
        });
        item.appendChild(a);
        tocList.appendChild(item);
    });

    var activeH2Index  = 0;
    var activeAllIndex = 0;

    function syncActiveStates() {
        var scrollTop  = window.pageYOffset + window.innerHeight * 0.5;
        var pageBottom = document.documentElement.scrollHeight - window.innerHeight;
        var atBottom   = pageBottom > 0 && window.pageYOffset >= pageBottom - 80;

        var closestAll = atBottom ? allHeadings.length - 1 : 0;
        if (!atBottom) {
            allHeadings.forEach(function (h, i) {
                if (h.getBoundingClientRect().top + window.pageYOffset <= scrollTop) {
                    closestAll = i;
                }
            });
        }
        activeAllIndex = closestAll;

        var closestH2 = atBottom ? h2s.length - 1 : 0;
        if (!atBottom) {
            h2s.forEach(function (h, i) {
                if (h.getBoundingClientRect().top + window.pageYOffset <= scrollTop) {
                    closestH2 = i;
                }
            });
        }
        activeH2Index = closestH2;

        var activeId = allHeadings[activeAllIndex] ? allHeadings[activeAllIndex].id : null;
        var links = tocList.querySelectorAll('.sfp-lr-toc__link');
        links.forEach(function (link) {
            var isActive = activeId && link.getAttribute('href') === '#' + activeId;
            link.classList.toggle('is-active', isActive);
        });

        if (chapterLabel && h2s[activeH2Index]) {
            chapterLabel.textContent = h2s[activeH2Index].textContent;
        }
        if (prevBtn) prevBtn.disabled = activeH2Index === 0;
        if (nextBtn) nextBtn.disabled = activeH2Index === h2s.length - 1;
    }

    function scrollToHeading(h) {
        var top = h.getBoundingClientRect().top + window.pageYOffset - SCROLL_OFFSET;
        window.scrollTo({ top: top, behavior: 'smooth' });
    }

    if (prevBtn) prevBtn.addEventListener('click', function () {
        if (activeH2Index > 0) { closeDrawer(); scrollToHeading(h2s[--activeH2Index]); }
    });
    if (nextBtn) nextBtn.addEventListener('click', function () {
        if (activeH2Index < h2s.length - 1) { closeDrawer(); scrollToHeading(h2s[++activeH2Index]); }
    });

    /* Drawer */
    var chapterWrap = document.getElementById('sfp-lr-chapter-wrap');
    var drawer      = document.getElementById('sfp-lr-drawer');
    var drawerOpen  = false;

    function buildDrawer() {
        if (!drawer) return;
        drawer.innerHTML = '';
        var currentH2 = h2s[activeH2Index];
        var nextH2    = h2s[activeH2Index + 1] || null;
        var inSection = false;
        var items     = [];
        allHeadings.forEach(function (h) {
            if (h === currentH2) { inSection = true; return; }
            if (nextH2 && h === nextH2) { inSection = false; return; }
            if (inSection && h.tagName === 'H3') items.push(h);
        });
        items.forEach(function (h) {
            var a = document.createElement('a');
            a.href = '#' + h.id;
            a.className = 'sfp-lr-drawer__item' + (h === allHeadings[activeAllIndex] ? ' is-active' : '');
            a.textContent = h.textContent;
            a.addEventListener('click', function (e) {
                e.preventDefault();
                closeDrawer();
                scrollToHeading(h);
            });
            drawer.appendChild(a);
        });
    }

    function openDrawer() {
        buildDrawer();
        if (!drawer.children.length) return;
        bar.classList.add('drawer-open');
        if (chapterWrap) chapterWrap.setAttribute('aria-expanded', 'true');
        drawerOpen = true;
    }

    function closeDrawer() {
        bar.classList.remove('drawer-open');
        if (chapterWrap) chapterWrap.setAttribute('aria-expanded', 'false');
        drawerOpen = false;
    }

    if (chapterWrap) {
        chapterWrap.addEventListener('click', function () {
            drawerOpen ? closeDrawer() : openDrawer();
        });
        chapterWrap.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                drawerOpen ? closeDrawer() : openDrawer();
            }
        });
    }

    if (topBtn) topBtn.addEventListener('click', function () {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    document.addEventListener('click', function (e) {
        if (drawerOpen && !bar.contains(e.target)) closeDrawer();
    });

    /* Sidebar controls */
    var sidebarTopBtn = document.getElementById('sfp-lr-sidebar-top');
    if (sidebarTopBtn) sidebarTopBtn.addEventListener('click', function () {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    var sidebarToggle = document.getElementById('sfp-lr-sidebar-toggle');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function () {
            var minimized = sidebar.classList.toggle('is-minimized');
            sidebarToggle.setAttribute('aria-expanded', minimized ? 'false' : 'true');
        });
    }

    /* Sidebar positioning */
    var STICKY_OFFSET  = 160;
    var sidebarHasRoom = false;

    function updateSidebarTop() {
        var firstH2Offset = h2s[0].getBoundingClientRect().top + window.pageYOffset;
        var naturalTop    = firstH2Offset - window.pageYOffset + 40;
        var top           = Math.max(STICKY_OFFSET, naturalTop);
        sidebar.style.top       = top + 'px';
        sidebar.style.bottom    = 'auto';
        sidebar.style.maxHeight = (window.innerHeight - top - 24) + 'px';

        var activeLink = tocList.querySelector('.sfp-lr-toc__link.is-active');
        if (activeLink) {
            var linkTop      = activeLink.offsetTop;
            var sidebarH     = sidebar.clientHeight;
            var targetScroll = linkTop - (sidebarH / 2);
            sidebar.scrollTop = Math.max(0, targetScroll);
        }
    }

    function positionSidebar() {
        var sidebarW = 300;
        var gap      = 36;
        var ref      = null;

        var candidates = document.querySelectorAll('.uagb-container-inner-blocks-wrap');
        for (var i = 0; i < candidates.length; i++) {
            var w = candidates[i].offsetWidth;
            if (w > 0 && w < window.innerWidth - 100) { ref = candidates[i]; break; }
        }
        if (!ref) ref = document.querySelector('.ast-container') || content;

        var contentLeft  = Math.round(ref.getBoundingClientRect().left);
        var contentRight = Math.round(ref.getBoundingClientRect().right);
        var leftSpace    = contentLeft;
        var rightSpace   = window.innerWidth - contentRight;

        if (leftSpace >= sidebarW + gap) {
            sidebar.style.left  = Math.max(20, Math.round(contentLeft - sidebarW - gap)) + 'px';
            sidebar.style.right = 'auto';
            sidebarHasRoom = true;
        } else if (rightSpace >= sidebarW + gap) {
            sidebar.style.left  = 'auto';
            sidebar.style.right = Math.round(rightSpace - sidebarW - gap) + 'px';
            sidebarHasRoom = true;
        } else {
            sidebarHasRoom = false;
        }
    }

    var sidebarVisible = false;
    var realFooter = document.querySelector('footer.site-footer')
        || document.querySelector('.site-footer')
        || document.querySelector('#colophon');

    function isPastHero() {
        var trigger = document.getElementById('content-start');
        if (trigger) {
            return window.pageYOffset >= trigger.getBoundingClientRect().top + window.pageYOffset - window.innerHeight * 0.5;
        }
        var firstH2Top = h2s[0].getBoundingClientRect().top + window.pageYOffset;
        return window.pageYOffset >= firstH2Top - window.innerHeight * 0.5;
    }

    function isAtRealFooter() {
        if (!realFooter) return false;
        return realFooter.getBoundingClientRect().top <= window.innerHeight;
    }

    function updateSidebarVisibility() {
        if (!sidebarHasRoom) return;
        var show = isPastHero() && !isAtRealFooter();
        if (show && !sidebarVisible) {
            updateSidebarTop();
            sidebar.style.display = 'block';
            requestAnimationFrame(function () { sidebar.classList.add('is-visible'); });
            sidebarVisible = true;
        } else if (!show && sidebarVisible) {
            sidebar.classList.remove('is-visible');
            sidebar.style.display = 'none';
            sidebarVisible = false;
        }
    }

    function applyLayout() {
        var isDesktop = window.innerWidth >= BREAKPOINT;
        if (isDesktop) {
            positionSidebar();
            if (!sidebarHasRoom) {
                /* Tablet/narrow-desktop fallback: no room for the sidebar,
                 * so keep the mobile bar available. Without this branch,
                 * pages between roughly 768px and 1100px (tablets in
                 * landscape, narrow laptops) ended up with no navigation
                 * at all. The body class flips so CSS can match. */
                document.body.classList.remove('sfp-lr-has-sidebar');
                sidebar.style.display = 'none';
                sidebarVisible = false;
                bar.classList.toggle('is-visible', isPastHero() && !isAtRealFooter());
            } else {
                document.body.classList.add('sfp-lr-has-sidebar');
                bar.classList.remove('is-visible');
                updateSidebarVisibility();
            }
        } else {
            document.body.classList.remove('sfp-lr-has-sidebar');
            sidebar.style.display = 'none';
            sidebar.classList.remove('is-visible');
            sidebarVisible = false;
            bar.classList.toggle('is-visible', isPastHero() && !isAtRealFooter());
        }
    }

    /* Pass mouse-wheel on sidebar through to the page */
    sidebar.addEventListener('wheel', function (e) {
        e.preventDefault();
        window.scrollBy({ top: e.deltaY, behavior: 'auto' });
    }, { passive: false });

    /* Scroll listener
     *
     * The sidebar is only used when it is shown on desktop AND has
     * room next to the content. In every other case (mobile, tablet,
     * narrow desktop without margins) the mobile bar + drawer takes
     * over. That mirrors the fallback inside applyLayout(). */
    window.addEventListener('scroll', function () {
        var prevH2 = activeH2Index;
        syncActiveStates();
        var useSidebar = window.innerWidth >= BREAKPOINT && sidebarHasRoom;
        if (useSidebar) {
            updateSidebarVisibility();
            if (sidebarVisible) updateSidebarTop();
        } else {
            bar.classList.toggle('is-visible', isPastHero() && !isAtRealFooter());
            if (drawerOpen) {
                if (activeH2Index !== prevH2) {
                    buildDrawer();
                } else {
                    var activeH3     = allHeadings[activeAllIndex];
                    var drawerItems  = drawer.querySelectorAll('.sfp-lr-drawer__item');
                    drawerItems.forEach(function (item) {
                        item.classList.toggle('is-active', item.getAttribute('href') === '#' + (activeH3 ? activeH3.id : ''));
                    });
                }
            }
        }
    }, { passive: true });

    /* Resize listener */
    var resizeTimer;
    window.addEventListener('resize', function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(applyLayout, 80);
    });

    /* Init */
    applyLayout();
    syncActiveStates();
    setTimeout(applyLayout, 300);
    setTimeout(applyLayout, 1000);
    window.addEventListener('load', applyLayout);
    }

    // Wait for the DOM to be ready before looking up elements.
    // The longread HTML is printed on wp_footer priority 20, which can
    // happen AFTER wp_print_footer_scripts (same priority). Without this
    // guard, document.getElementById(...) calls would return null and
    // the sidebar/bar would silently fail to render.
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
