import jQuery from 'jquery';
const $ = jQuery;

$('.toggle-dropdown-menu').click(function() {

  // If item is already active then close all.
  if ( $(this).hasClass('dropdown-active')) {
    $('.toggle-dropdown-menu').each(function() {
      $(this).removeClass('dropdown-active');
      $(this).parents().children('.dropdown-menu-items').hide();
    });

    return false;
  }

  // Close all existing items except current.
  $('.toggle-dropdown-menu').not(this).each(function() {
    $(this).removeClass('dropdown-active');
    $(this).parents().children('.dropdown-menu-items').hide();
  });

  // Show items.
  $(this).toggleClass('dropdown-active');
  $(this).parents().children('.dropdown-menu-items').show();
});
