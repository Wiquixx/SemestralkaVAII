// filepath: c:\Users\filip\Desktop\School\VAII\Semestralka\public\js\admin_edit_plant.js
//Vytvorené s pomocou Github Copilot
document.addEventListener('DOMContentLoaded', function(){
    var form = document.getElementById('editPlantForm');
    var dateInput = document.getElementById('purchase_date');
    var clientErrorId = 'clientDateErrorEdit';

    if (dateInput && form) {
        function ensureClientErrorElement(){
            var existing = document.getElementById(clientErrorId);
            if(existing) return existing;
            var el = document.createElement('div');
            el.id = clientErrorId;
            el.className = 'alert alert-danger mt-2 d-none';
            dateInput.parentNode.appendChild(el);
            return el;
        }

        var clientError = ensureClientErrorElement();

        form.addEventListener('submit', function(e){
            var val = dateInput.value;
            if(val){
                var selected = new Date(val);
                var today = new Date();
                today.setHours(0,0,0,0);
                if(selected > today){
                    e.preventDefault();
                    clientError.textContent = 'Purchase date cannot be in the future.';
                    clientError.classList.remove('d-none');
                    dateInput.focus();
                    return false;
                } else {
                    clientError.classList.add('d-none');
                }
            }
        });
    }

    // Image preview logic
    var input = document.getElementById('image');
    var img = document.getElementById('currentImage');
    var removeCheckbox = document.getElementById('remove_image');
    var originalSrc = img ? img.src : '';
    if (input && img) {
        input.addEventListener('change', function(){
            // If user selects a new file, uncheck "remove image"
            if (removeCheckbox) removeCheckbox.checked = false;

            var file = input.files && input.files[0];
            if (!file) return;
            var allowed = ['image/jpeg','image/png','image/gif'];
            if (allowed.indexOf(file.type) === -1) {
                alert('Allowed image types: JPEG, PNG, GIF');
                input.value = '';
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                alert('File too large. Max 5MB');
                input.value = '';
                return;
            }
            var reader = new FileReader();
            reader.onload = function(e){
                img.src = e.target.result;
                img.style.display = 'block';
            };
            reader.readAsDataURL(file);
        });
    }

    // Handle remove image checkbox: hide preview and clear file input if checked
    if (removeCheckbox && img && input) {
        removeCheckbox.addEventListener('change', function(){
            if (removeCheckbox.checked) {
                // hide preview and clear src
                img.style.display = 'none';
                // clear current image src to avoid showing after form submit
                try { img.src = ''; } catch (e) {}
                // clear file input
                input.value = '';
            } else {
                // restore original src if available
                if (originalSrc) {
                    img.src = originalSrc;
                    img.style.display = 'block';
                }
            }
        });
    }
});
