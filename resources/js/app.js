import Alpine from 'alpinejs';
import ptkForm from './ptk-form';
import { tanggalInput } from './datepicker';

window.Alpine = Alpine;

Alpine.data('ptkForm', ptkForm);
Alpine.data('tanggalInput', tanggalInput);

Alpine.start();
