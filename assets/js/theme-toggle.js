(() => {
	const storageKey = 'theme';
	const className = 'dark-mode';

	const getPreferredTheme = () => {
		// Server-stored preference takes priority (set via data-theme-server on <body>)
		const serverPref = document.body.dataset.themeServer;
		if (serverPref === 'light' || serverPref === 'dark') {
			localStorage.setItem(storageKey, serverPref);
			return serverPref;
		}

		// Fall back to localStorage, then OS preference
		const stored = localStorage.getItem(storageKey);
		if (stored === 'light' || stored === 'dark') {
			return stored;
		}
		return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches
			? 'dark'
			: 'light';
	};

	const setNavbarTheme = (isDark) => {
		document.querySelectorAll('.main-header.navbar').forEach((navbar) => {
			if (isDark) {
				navbar.classList.add('navbar-dark');
				navbar.classList.remove('navbar-light', 'navbar-white');
			} else {
				navbar.classList.add('navbar-light', 'navbar-white');
				navbar.classList.remove('navbar-dark');
			}
		});
	};

	const applyTheme = (theme) => {
		const isDark = theme === 'dark';
		document.body.classList.toggle(className, isDark);
		setNavbarTheme(isDark);

		document.querySelectorAll('[data-theme-toggle]').forEach((toggle) => {
			toggle.checked = isDark;
			toggle.setAttribute('aria-checked', String(isDark));
		});
	};

	const persistThemeToServer = (theme) => {
		const csrfInput = document.querySelector('input[name="csrf_token"]');
		if (!csrfInput) return;
		fetch('settings.php?ajax=theme', {
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: 'csrf_token=' + encodeURIComponent(csrfInput.value) + '&theme=' + encodeURIComponent(theme),
		}).catch(() => {});
	};

	const init = () => {
		applyTheme(getPreferredTheme());

		document.querySelectorAll('[data-theme-toggle]').forEach((toggle) => {
			toggle.addEventListener('change', (event) => {
				const isDark = event.target.checked;
				const next = isDark ? 'dark' : 'light';
				localStorage.setItem(storageKey, next);
				applyTheme(next);
				persistThemeToServer(next);
			});
		});
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();

// ── Gear settings panel ───────────────────────────────────────────────────────
(function () {
	function gearInit() {
		var gearBtn = document.getElementById('gearBtn');
		var panel   = document.getElementById('settingsPanel');
		if (!gearBtn || !panel) return;

		function closePanel() { panel.classList.remove('open'); }
		function togglePanel(e) { e.stopPropagation(); panel.classList.toggle('open'); }

		gearBtn.addEventListener('click', togglePanel);

		document.addEventListener('click', function (e) {
			if (!panel.contains(e.target) && e.target !== gearBtn && !gearBtn.contains(e.target)) {
				closePanel();
			}
		});

		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape') closePanel();
		});

		panel.querySelectorAll('[data-lang]').forEach(function (btn) {
			btn.addEventListener('click', function () {
				var lang = this.dataset.lang;
				var csrfInput = document.querySelector('input[name="csrf_token"]');
				if (!csrfInput) return;
				fetch('settings.php?ajax=language', {
					method: 'POST',
					headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
					body: 'csrf_token=' + encodeURIComponent(csrfInput.value) +
					      '&language=' + encodeURIComponent(lang),
				}).then(function (r) { return r.json(); }).then(function (data) {
					if (data.ok) location.reload();
				}).catch(function () {});
			});
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', gearInit);
	} else {
		gearInit();
	}
}());
