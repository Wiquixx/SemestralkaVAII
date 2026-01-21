(function(){
    'use strict';

    function run(){
        var sel = document.getElementById('edit_type');
        if (!sel) return;
        var freq = document.getElementById('freq_block');
        var freqInput = document.getElementById('edit_frequency');
        var hidden; // single declaration to avoid duplicate var warnings

        sel.addEventListener('change', function(){
            if (this.value === 'plan'){
                if (freq) freq.style.display = 'none';
                // ensure frequency_days will be submitted as -1 by adding/updating a hidden input
                hidden = document.getElementById('hidden_freq_flag');
                if (!hidden && freq) {
                    hidden = document.createElement('input');
                    hidden.type = 'hidden'; hidden.name = 'frequency_days'; hidden.id = 'hidden_freq_flag'; hidden.value = '-1';
                    freq.parentNode.insertBefore(hidden, freq.nextSibling);
                } else if (hidden) {
                    hidden.value = '-1';
                }
                if (freqInput) freqInput.removeAttribute('required');
            } else {
                if (freq) freq.style.display = '';
                hidden = document.getElementById('hidden_freq_flag');
                if (hidden && hidden.parentNode) hidden.parentNode.removeChild(hidden);
                if (freqInput) freqInput.setAttribute('required','required');
            }
        });
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', run); else run();
})();
