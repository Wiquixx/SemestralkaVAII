// filepath: c:\Users\filip\Desktop\School\VAII\Semestralka\public\js\admin_edit_plant.js

document.addEventListener('DOMContentLoaded', function(){
    var form = document.getElementById('editPlantForm');
    if(!form) return;
    var dateInput = document.getElementById('purchase_date');
    if(!dateInput) return;
    var clientErrorId = 'clientDateErrorEdit';

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
});

