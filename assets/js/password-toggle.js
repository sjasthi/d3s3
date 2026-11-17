document.addEventListener('DOMContentLoaded', function () {
	document.querySelectorAll('input[type="password"]').forEach(function (input) {
		if (input.dataset.passwordToggle === '1') {
			return;
		}
		input.dataset.passwordToggle = '1';

		var button = document.createElement('button');
		button.type = 'button';
		button.className = 'btn btn-outline-secondary';
		button.setAttribute('aria-label', 'Show password');
		button.innerHTML = '<i class="fas fa-eye" aria-hidden="true"></i>';

		button.addEventListener('click', function () {
			var visible = input.type === 'text';
			input.type = visible ? 'password' : 'text';
			button.innerHTML = visible
				? '<i class="fas fa-eye" aria-hidden="true"></i>'
				: '<i class="fas fa-eye-slash" aria-hidden="true"></i>';
			button.setAttribute('aria-label', visible ? 'Show password' : 'Hide password');
		});

		var group = input.closest('.input-group');
		if (!group) {
			group = document.createElement('div');
			group.className = 'input-group';
			input.parentNode.insertBefore(group, input);
			group.appendChild(input);
		}

		var append = document.createElement('div');
		append.className = 'input-group-append';
		append.appendChild(button);
		group.appendChild(append);
	});
});
