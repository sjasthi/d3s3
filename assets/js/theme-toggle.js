(() => {
	const storageKey = 'theme';
	const className = 'dark-mode';

	const getPreferredTheme = () => {
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

	const init = () => {
		applyTheme(getPreferredTheme());

		document.querySelectorAll('[data-theme-toggle]').forEach((toggle) => {
			toggle.addEventListener('change', (event) => {
				const isDark = event.target.checked;
				const next = isDark ? 'dark' : 'light';
				localStorage.setItem(storageKey, next);
				applyTheme(next);
			});
		});
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
