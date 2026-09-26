/*! SPDX-FileCopyrightText: 2026 Stephan Rickauer */
/* SPDX-License-Identifier: AGPL-3.0-only */
(() => {
    'use strict';
    function init() {
        const form = document.getElementById('mpi-settings-form');
        if (!form || form.dataset.bound) return;
        form.dataset.bound = 'true';
        const appearance = document.getElementById('mpi-custom-appearance').closest('fieldset');
        const custom = document.getElementById('mpi-custom-appearance');
        const backgroundMode = document.getElementById('mpi-background-mode');
        function showBackgroundOptions() {
            const mode = backgroundMode.value;
            document.getElementById('mpi-background-file').hidden = mode !== 'image';
            document.getElementById('mpi-nextcloud-hint').hidden = mode !== 'nextcloud';
            document.getElementById('mpi-background-overlay').hidden = mode === 'color';
            document.getElementById('mpi-background-image').required = custom.checked && mode === 'image';
            document.getElementById('mpi-background-image').disabled = !custom.checked || mode !== 'image';
        }
        appearance.addEventListener('input', event => {
            if (event.target !== custom) custom.checked = true;
            showBackgroundOptions();
        });
        appearance.addEventListener('change', showBackgroundOptions);
        showBackgroundOptions();
        form.querySelectorAll('input[type="color"][data-hex]').forEach(picker => {
            const hex = document.getElementById(picker.dataset.hex);
            picker.addEventListener('input', () => { hex.value = picker.value; hex.setCustomValidity(''); });
            hex.addEventListener('input', () => {
                const valid = /^#[0-9a-f]{6}$/i.test(hex.value);
                hex.setCustomValidity(valid ? '' : 'Bitte einen HEX-Code wie #8e2650 eingeben.');
                if (valid) picker.value = hex.value.toLowerCase();
            });
            hex.addEventListener('blur', () => {
                if (/^#[0-9a-f]{6}$/i.test(hex.value)) hex.value = hex.value.toLowerCase();
            });
        });
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const button = form.querySelector('button[type="submit"]');
            const status = document.getElementById('mpi-save-status');
            const body = new URLSearchParams(new FormData(form));
            form.querySelectorAll('[name^="metadataFields["]').forEach(input => body.set(input.name, input.checked ? '1' : '0'));
            body.set('cleanUrls', form.elements.cleanUrls.checked ? '1' : '0');
            body.set('restrictTools', form.elements.restrictTools.checked ? '1' : '0');
            body.set('enabled', form.elements.enabled.checked ? '1' : '0');
            body.set('appearance[enabled]', custom.checked ? '1' : '0');
            body.set('showHeader', form.elements.showHeader.checked ? '1' : '0');
            body.set('dateOptions[ancestors]', form.elements['dateOptions[ancestors]'].checked ? '1' : '0');
            button.disabled = true;
            status.textContent = 'Wird gespeichert …';
            try {
                const response = await fetch(form.action, {
                    method: 'POST', credentials: 'same-origin',
                    headers: { 'Accept': 'application/json', 'requesttoken': form.elements.requesttoken.value },
                    body,
                });
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Speichern fehlgeschlagen.');
                status.textContent = data.message;
            } catch (error) {
                status.textContent = error instanceof Error ? error.message : 'Speichern fehlgeschlagen.';
            } finally {
                button.disabled = false;
            }
        });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
