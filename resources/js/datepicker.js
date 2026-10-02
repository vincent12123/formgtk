import flatpickr from 'flatpickr';
import { Indonesian } from 'flatpickr/dist/l10n/id.js';

/**
 * Date picker Indonesia untuk semua halaman publik (REQ-14): tampil `d F Y`, nilai terkirim `Y-m-d`,
 * selalu flatpickr (termasuk di ponsel) — bukan <input type="date"> bawaan browser.
 */
export function pasangDatepicker(input, { defaultDate = null, onChange = () => {}, onReady = () => {} } = {}) {
    return flatpickr(input, {
        locale: Indonesian,
        dateFormat: 'Y-m-d',
        altInput: true,
        altFormat: 'd F Y',
        altInputClass: 'field-input',
        allowInput: false,
        disableMobile: true,
        defaultDate: defaultDate || null,
        onChange: (_dates, value) => onChange(value),
        onReady: (_dates, _value, instance) => {
            instance.altInput.id = `${input.id}_tampil`;
            instance.altInput.placeholder = 'Pilih tanggal';
            if (input.getAttribute('aria-invalid')) {
                instance.altInput.setAttribute('aria-invalid', input.getAttribute('aria-invalid'));
            }
            onReady(instance);
        },
    });
}

/**
 * Komponen Alpine untuk satu input tanggal mandiri (mis. Cek Status).
 * Pemakaian: <div x-data="tanggalInput('2026-10-02')"><input x-ref="input" ...></div>
 */
export const tanggalInput = (nilaiAwal = '') => ({
    init() {
        pasangDatepicker(this.$refs.input, { defaultDate: nilaiAwal });
    },
});
