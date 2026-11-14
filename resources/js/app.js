import './bootstrap';
import { createIcons, icons } from 'lucide';
import ApexCharts from 'apexcharts';
import Swal from 'sweetalert2';
import Alpine from 'alpinejs';
import flatpickr from 'flatpickr';
import { Indonesian } from 'flatpickr/dist/l10n/id.js';
import TomSelect from 'tom-select';

// Localize flatpickr globally
flatpickr.localize(Indonesian);

// Expose tools to global window object
window.ApexCharts = ApexCharts;
window.Swal = Swal;
window.flatpickr = flatpickr;
window.TomSelect = TomSelect;

// Helper format mata uang Rupiah ringkas khas Indonesia
// >= 1 Miliar => 'Rp X M', >= 1 Juta => 'Rp X JT', >= 1 Ribu => 'Rp X RB'
window.formatRupiahCompact = function (value) {
    if (value === null || value === undefined || isNaN(value)) return 'Rp 0';
    const num = Math.abs(Number(value));
    const sign = Number(value) < 0 ? '-' : '';

    if (num >= 1000000000) {
        const val = num / 1000000000;
        const formatted = (val % 1 === 0 ? val.toFixed(0) : val.toFixed(1)).replace('.', ',');
        return sign + 'Rp ' + formatted + ' M';
    } else if (num >= 1000000) {
        const val = num / 1000000;
        const formatted = (val % 1 === 0 ? val.toFixed(0) : val.toFixed(1)).replace('.', ',');
        return sign + 'Rp ' + formatted + ' JT';
    } else if (num >= 1000) {
        const val = num / 1000;
        const formatted = (val % 1 === 0 ? val.toFixed(0) : val.toFixed(1)).replace('.', ',');
        return sign + 'Rp ' + formatted + ' RB';
    }
    return sign + 'Rp ' + num.toLocaleString('id-ID');
};

// Toast helper
window.showToast = function (icon, title, timer = 3000) {
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: timer,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer);
            toast.addEventListener('mouseleave', Swal.resumeTimer);
            // Ensure no rogue selects or TomSelect wrappers appear inside toast
            const rogueElements = toast.querySelectorAll('select, .ts-wrapper, .swal2-select');
            rogueElements.forEach(el => el.remove());
        }
    });

    Toast.fire({
        icon: icon,
        title: title
    });
};

// Global helper to initialize Flatpickr on date inputs
window.initDatepickers = function (container = document) {
    if (!window.flatpickr) return;
    const dateInputs = container.querySelectorAll('input[type="date"], input.datepicker, input[data-datepicker]');
    dateInputs.forEach(input => {
        if (input._flatpickr) return; // already initialized

        const existingVal = input.value;

        window.flatpickr(input, {
            locale: 'id',
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd M Y',
            altInputClass: input.className + ' flatpickr-alt-input',
            allowInput: false,
            monthSelectorType: 'static',
            defaultDate: existingVal || null,
            prevArrow: '<svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>',
            nextArrow: '<svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>',
            onReady: function(selectedDates, dateStr, instance) {
                if (instance.altInput) {
                    instance.altInput.placeholder = input.getAttribute('placeholder') || 'Pilih tanggal...';
                }
            },
            onChange: function (selectedDates, dateStr) {
                input.value = dateStr;
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
    });
};

// Global helper to initialize TomSelect on all dropdown selects (filters and CRUD forms)
window.initTomSelects = function (container = document) {
    if (!window.TomSelect) return;
    const selects = container.querySelectorAll(
        'select:not(.swal2-select):not(.flatpickr-monthDropdown-months):not(.no-tom-select):not([multiple])'
    );
    selects.forEach(sel => {
        if (sel.tomselect) return; // already initialized
        if (sel.closest('template')) return;
        if (sel.closest('.swal2-container') || sel.closest('.swal2-popup') || sel.classList.contains('swal2-select')) return;

        const emptyOption = sel.querySelector('option[value=""]');
        const emptyOptionText = emptyOption ? emptyOption.textContent.trim() : (sel.getAttribute('placeholder') || 'Pilih Opsi...');
        const optionCount = sel.options ? sel.options.length : 0;
        const plugins = optionCount > 4 ? ['dropdown_input'] : [];

        try {
            const ts = new window.TomSelect(sel, {
                plugins: plugins,
                create: false,
                allowEmptyOption: true,
                maxOptions: 100,
                copyClassesToDropdown: false,
                render: {
                    no_results: function () {
                        return '<div class="no-results text-xs text-slate-400 p-2.5 text-center">Tidak ada opsi ditemukan</div>';
                    }
                },
                onChange: function (value) {
                    sel.value = value;
                    sel.dispatchEvent(new Event('change', { bubbles: true }));
                    sel.dispatchEvent(new Event('input', { bubbles: true }));
                }
            });

            // Clean up copied classes on wrapper so wrapper doesn't produce an extra outer border/box
            if (ts.wrapper) {
                ts.wrapper.className = 'ts-wrapper single ' + (plugins.length > 0 ? 'plugin-dropdown_input' : '');
            }

            // Sync error state if present on the original select
            if (sel.classList.contains('border-rose-500')) {
                ts.control.classList.add('border-rose-500', 'bg-rose-50/20');
            }
        } catch (e) {
            console.warn('TomSelect init error:', e);
        }
    });
};

// Auto-render Lucide icons and UI pickers on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    createIcons({ icons });
    window.initDatepickers();
    window.initTomSelects();
});

// Re-render Lucide helper when dynamically injecting HTML
window.refreshLucideIcons = function () {
    createIcons({ icons });
    window.initDatepickers();
    window.initTomSelects();
};

/**
 * Alpine.js Live Filter Component
 * Automatically cleans query parameters (removes null, undefined, empty strings)
 * Dynamically fetches updated results and replaces target container(s)
 */
Alpine.data('liveFilter', (config = {}) => ({
    baseUrl: config.baseUrl || window.location.pathname,
    targetId: config.targetId || 'table-container',
    extraTargets: config.extraTargets || [],
    loading: false,

    submitFilter() {
        const form = this.$el.tagName === 'FORM' ? this.$el : this.$el.closest('form');
        if (!form) return;

        const formData = new FormData(form);
        const params = new URLSearchParams();

        for (const [key, value] of formData.entries()) {
            if (key === '_token' || key === '_method') continue;

            const trimmed = value !== null ? value.toString().trim() : '';
            // Only add non-empty query parameters to keep the query clean
            if (trimmed !== '') {
                params.append(key, trimmed);
            }
        }

        const queryString = params.toString();
        const fullUrl = this.baseUrl + (queryString ? '?' + queryString : '');

        // Update URL cleanly in address bar
        window.history.pushState({ path: fullUrl }, '', fullUrl);

        this.loading = true;

        fetch(fullUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html'
            }
        })
        .then(res => {
            if (!res.ok) throw new Error('Network response error');
            return res.text();
        })
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');

            const newContent = doc.getElementById(this.targetId);
            const currentContainer = document.getElementById(this.targetId);

            if (newContent && currentContainer) {
                currentContainer.innerHTML = newContent.innerHTML;
            } else {
                window.location.href = fullUrl;
                return;
            }

            if (this.extraTargets && this.extraTargets.length > 0) {
                this.extraTargets.forEach(id => {
                    const newEl = doc.getElementById(id);
                    const curEl = document.getElementById(id);
                    if (newEl && curEl) {
                        curEl.innerHTML = newEl.innerHTML;
                    }
                });
            }

            window.refreshLucideIcons();
        })
        .catch(err => {
            console.error('Live filter error:', err);
            window.location.href = fullUrl;
        })
        .finally(() => {
            this.loading = false;
        });
    },

    resetFilter() {
        this.loading = true;
        const form = this.$el.tagName === 'FORM' ? this.$el : this.$el.closest('form');
        if (form) {
            form.reset();
            form.querySelectorAll('input:not([type=hidden]), select').forEach(el => {
                el.value = '';
                if (el._flatpickr) {
                    el._flatpickr.clear();
                }
                if (el.tomselect) {
                    el.tomselect.setValue('', true);
                }
            });
        }

        window.history.pushState({ path: this.baseUrl }, '', this.baseUrl);

        fetch(this.baseUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html'
            }
        })
        .then(res => res.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');

            const newContent = doc.getElementById(this.targetId);
            const currentContainer = document.getElementById(this.targetId);

            if (newContent && currentContainer) {
                currentContainer.innerHTML = newContent.innerHTML;
            } else {
                window.location.href = this.baseUrl;
                return;
            }

            if (this.extraTargets && this.extraTargets.length > 0) {
                this.extraTargets.forEach(id => {
                    const newEl = doc.getElementById(id);
                    const curEl = document.getElementById(id);
                    if (newEl && curEl) {
                        curEl.innerHTML = newEl.innerHTML;
                    }
                });
            }

            window.refreshLucideIcons();
        })
        .catch(err => {
            console.error('Reset filter error:', err);
            window.location.href = this.baseUrl;
        })
        .finally(() => {
            this.loading = false;
        });
    }
}));

// Re-render when navigating browser history (back/forward)
window.addEventListener('popstate', () => {
    window.location.reload();
});

window.Alpine = Alpine;
Alpine.start();
