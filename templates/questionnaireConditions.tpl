<script>
{literal}
	(function (form) {
		if (!form) {
			return;
		}

		const consentNo = form.querySelector('[name="deiaDataConsent"][value="0"]');
		const questions = Array.from(form.querySelectorAll('[data-deia-question]'), (section) => ({
			section,
			controls: Array.from(section.querySelectorAll('input, textarea, select'), (control) => ({
				control,
				required: control.required,
			})),
			checkboxes: Array.from(section.querySelectorAll('input[type="checkbox"]')),
		}));
		const checkboxRequired = {/literal}{translate|json_encode key="plugins.generic.deiaSurvey.questionBlocks.questions.chooseOption"}{literal};

		function updateQuestions() {
			const responsesEnabled = !form.hasAttribute('data-deia-locked') && !consentNo?.checked;
			const selectedOptions = new Map();
			for (const {section, controls, checkboxes} of questions) {
				const {deiaQuestion, showWhenQuestion, showWhenOption} = section.dataset;
				const visible = !showWhenQuestion || selectedOptions.get(showWhenQuestion) === showWhenOption;
				section.hidden = !visible;

				const enabled = responsesEnabled && visible;
				for (const {control, required} of controls) {
					control.disabled = !enabled;
					control.required = enabled && required;
					if (required) {
						control.setAttribute('aria-required', String(enabled));
					}
					if (enabled && (control.tagName === 'SELECT' || (control.type === 'radio' && control.checked))) {
						selectedOptions.set(deiaQuestion, control.value);
					}
				}
				if (checkboxes.length) {
					const missing = enabled && !checkboxes.some((control) => control.checked);
					checkboxes[0].setCustomValidity(missing ? checkboxRequired : '');
				}
				for (const marker of section.querySelectorAll('span.req')) {
					marker.hidden = !enabled;
				}
			}
		}

		form.addEventListener('change', updateQuestions);
		// The profile's AJAX form handler disables native form validation.
		form.addEventListener('submit', (event) => {
			const invalid = questions.find(({checkboxes}) => checkboxes[0] && !checkboxes[0].validity.valid);
			if (invalid) {
				event.preventDefault();
				event.stopImmediatePropagation();
				invalid.checkboxes[0].reportValidity();
			}
		}, true);
		updateQuestions();
	})(document.getElementById('{/literal}{$conditionFormId|escape:"javascript"}{literal}'));
{/literal}
</script>
