/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (style.scss in this case)

import '../styles/style.scss';
import '../styles/logo-ascii.scss';
import Swal from "sweetalert2";

export function fireConfirmationSwal(title, confirmAction, icon=null, callback=(r)=>{}) {
    Swal.fire({
        title: title,
        icon: icon,
        showCancelButton: true,
        cancelButtonText: "Annuler",
        confirmButtonText: confirmAction,
        reverseButtons: true
    }).then(callback);
}

export function fireBasicSwal(title, icon, callback=(r)=>{}) {
    Swal.fire(title, '', icon)
        .then(callback);
}

/*(result) => {
if (result.isConfirmed) {
    Swal.fire('Saved!', '', 'success')
} else if (result.isDenied) {
    Swal.fire('Changes are not saved', '', 'info')
}
}*/