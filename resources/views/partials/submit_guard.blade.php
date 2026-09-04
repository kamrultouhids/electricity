{{-- Stops a form being submitted twice — a double-clicked Save, or Enter
     pressed again while the first request is still in flight. The first
     submit disables the form's submit button(s) and shows a spinner; any
     further submit is dropped. Put a data-no-submit-guard attribute on a
     form to opt it out. --}}
<script>
    (function () {
        // Long enough that no real request is still pending, short enough that
        // a button never stays dead if the page somehow does not navigate.
        var FAILSAFE_MS = 15000;
        var BUSY_LABEL = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Loading...';

        // A button with no type attribute submits its form, same as type="submit".
        function submitButtons(form) {
            return form.querySelectorAll('button:not([type]), button[type="submit"], input[type="submit"]');
        }

        function lock(form) {
            form.dataset.submitting = '1';
            submitButtons(form).forEach(function (btn) {
                btn.disabled = true;
                if (btn.tagName === 'BUTTON') {
                    btn.dataset.guardLabel = btn.innerHTML;
                    btn.innerHTML = BUSY_LABEL;
                }
            });
        }

        function unlock(form) {
            delete form.dataset.submitting;
            submitButtons(form).forEach(function (btn) {
                btn.disabled = false;
                if (btn.dataset.guardLabel !== undefined) {
                    btn.innerHTML = btn.dataset.guardLabel;
                    delete btn.dataset.guardLabel;
                }
            });
        }

        // Bubble phase, so an inline onsubmit="return confirm(...)" has already
        // had its say — a cancelled confirm leaves the form untouched.
        document.addEventListener('submit', function (e) {
            var form = e.target;

            if (!(form instanceof HTMLFormElement) || form.hasAttribute('data-no-submit-guard')) {
                return;
            }

            if (e.defaultPrevented) {
                return;
            }

            if (form.dataset.submitting) {
                e.preventDefault();
                return;
            }

            lock(form);
            setTimeout(function () { unlock(form); }, FAILSAFE_MS);
        });

        // Coming back with the browser's back button restores the page from
        // cache exactly as it was left — including a locked form.
        window.addEventListener('pageshow', function (e) {
            if (e.persisted) {
                document.querySelectorAll('form[data-submitting]').forEach(unlock);
            }
        });
    })();
</script>
