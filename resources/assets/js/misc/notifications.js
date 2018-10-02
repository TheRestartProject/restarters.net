function toggleRead(event) {
    event.preventDefault();
    $(this).parents('.card').toggleClass('status-read');
}

jQuery('.btn-marked').on('click',toggleRead);

// function loadOlder(event) {
//     event.preventDefault();
//     // AJAX call
// }

// jQuery('.js-load').on('click', loadOlder);