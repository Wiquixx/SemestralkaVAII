(function(){
    'use strict';

    function eyeOpenSvg() {
        return '\n<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">\n  <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8z"/>\n  <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5z" fill="#fff"/>\n</svg>';
    }
    function eyeSlashSvg() {
        return '\n<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">\n  <path d="M13.359 11.238 15 12.879 13.879 14 2 2.121 3.121 1 6.042 3.921A7.941 7.941 0 0 1 8 3c3.523 0 6.5 2.167 8 5-1.002 1.87-2.72 3.331-3.641 3.238z"/>\n  <path d="M11.646 9.146a3 3 0 0 1-4.292-4.292l4.292 4.292z"/>\n</svg>';
    }

    function run(){
        document.querySelectorAll('.pwd-toggle').forEach(function(btn){
            var targetId = btn.getAttribute('data-target');
            var input = document.getElementById(targetId);
            // initialize with eye-slash icon (hidden) only if empty
            if (!btn.innerHTML || btn.innerHTML.trim() === '') btn.innerHTML = eyeSlashSvg();
            btn.addEventListener('click', function(){
                if (!input) return;
                if (input.type === 'password') {
                    input.type = 'text';
                    btn.innerHTML = eyeOpenSvg();
                    btn.setAttribute('aria-pressed','true');
                    btn.setAttribute('aria-label','Hide password');
                } else {
                    input.type = 'password';
                    btn.innerHTML = eyeSlashSvg();
                    btn.setAttribute('aria-pressed','false');
                    btn.setAttribute('aria-label','Show password');
                }
            });
        });
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', run); else run();
})();

