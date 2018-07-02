@if( isset($comment) )

  <td><button type="button" class="btn btn-skills" data-container="body" data-toggle="popover" data-placement="right" data-content="{{ $comment }}"><svg width="22" height="17" viewBox="0 0 17 13" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="1.414"><title>@lang('devices.graphic-comment')</title><path d="M16.5 1.496v6.418c0 .827-.759 1.497-1.694 1.497H7.562L4.125 12.75V9.411H1.694C.759 9.411 0 8.741 0 7.914V1.497C0 .67.759 0 1.694 0h13.111c.936 0 1.695.67 1.695 1.496z" fill="#0394A6" fill-rule="nonzero"/></svg></button></td>

@else

  <td><svg width="22" height="17" viewBox="0 0 17 13" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="1.414"><title>@lang('devices.graphic-comment')</title><path d="M16.5 1.496v6.418c0 .827-.759 1.497-1.694 1.497H7.562L4.125 12.75V9.411H1.694C.759 9.411 0 8.741 0 7.914V1.497C0 .67.759 0 1.694 0h13.111c.936 0 1.695.67 1.695 1.496z" fill="#DEDEDE" fill-rule="nonzero"/></svg></td>

@endif


@if( isset($photo) )

  <td><a href="https://unsplash.it/1200/768.jpg?image=251" data-toggle="lightbox"><svg width="22" height="17" viewBox="0 0 17 13" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="position:relative;z-index:1;fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><title>@lang('devices.graphic-camera')</title><path d="M8.25,4.781c-1.367,0 -2.475,1.071 -2.475,2.391c0,1.32 1.108,2.39 2.475,2.39c1.367,0 2.475,-1.07 2.475,-2.39c0,-1.32 -1.108,-2.391 -2.475,-2.391Zm6.6,-2.39l-1.98,0c-0.272,0 -0.566,-0.204 -0.652,-0.454l-0.511,-1.484c-0.087,-0.249 -0.38,-0.453 -0.652,-0.453l-5.61,0c-0.272,0 -0.566,0.204 -0.652,0.454l-0.511,1.483c-0.087,0.25 -0.38,0.454 -0.652,0.454l-1.98,0c-0.908,0 -1.65,0.717 -1.65,1.593l0,7.172c0,0.877 0.742,1.594 1.65,1.594l13.2,0c0.907,0 1.65,-0.717 1.65,-1.594l0,-7.172c0,-0.876 -0.743,-1.593 -1.65,-1.593Zm-6.6,8.765c-2.278,0 -4.125,-1.784 -4.125,-3.984c0,-2.2 1.847,-3.985 4.125,-3.985c2.278,0 4.125,1.785 4.125,3.985c0,2.2 -1.847,3.984 -4.125,3.984Zm6.022,-6.057c-0.318,0 -0.577,-0.25 -0.577,-0.558c0,-0.308 0.259,-0.558 0.577,-0.558c0.32,0 0.578,0.25 0.578,0.558c0,0.308 -0.259,0.558 -0.578,0.558Z" style="fill:#0394a6;fill-rule:nonzero;"></path></svg></a></td>

@else

  <td><svg width="22" height="17" viewBox="0 0 17 13" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="position:relative;z-index:1;fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><title>@lang('devices.graphic-camera')</title><path d="M8.25,4.781c-1.367,0 -2.475,1.071 -2.475,2.391c0,1.32 1.108,2.39 2.475,2.39c1.367,0 2.475,-1.07 2.475,-2.39c0,-1.32 -1.108,-2.391 -2.475,-2.391Zm6.6,-2.39l-1.98,0c-0.272,0 -0.566,-0.204 -0.652,-0.454l-0.511,-1.484c-0.087,-0.249 -0.38,-0.453 -0.652,-0.453l-5.61,0c-0.272,0 -0.566,0.204 -0.652,0.454l-0.511,1.483c-0.087,0.25 -0.38,0.454 -0.652,0.454l-1.98,0c-0.908,0 -1.65,0.717 -1.65,1.593l0,7.172c0,0.877 0.742,1.594 1.65,1.594l13.2,0c0.907,0 1.65,-0.717 1.65,-1.594l0,-7.172c0,-0.876 -0.743,-1.593 -1.65,-1.593Zm-6.6,8.765c-2.278,0 -4.125,-1.784 -4.125,-3.984c0,-2.2 1.847,-3.985 4.125,-3.985c2.278,0 4.125,1.785 4.125,3.985c0,2.2 -1.847,3.984 -4.125,3.984Zm6.022,-6.057c-0.318,0 -0.577,-0.25 -0.577,-0.558c0,-0.308 0.259,-0.558 0.577,-0.558c0.32,0 0.578,0.25 0.578,0.558c0,0.308 -0.259,0.558 -0.578,0.558Z" style="fill:#DEDEDE;fill-rule:nonzero;"></path></svg></td>

@endif
