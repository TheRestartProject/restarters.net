@extends('fixometer.layouts.app')
@section('content')
<section class="events">
  <div class="container-fluid">

    <div class="alert alert-attendance" role="alert">
    Excellent! You are joining us for this party
        <a href="#" class="btn">Sorry, I can no longer attend</a>
    </div>

      <div class="events__header row align-content-top">
          <div class="col-lg-7 d-flex flex-column">

            <header>
                <h1>Bank holiday restart @ The Old Chapel</h1>
                <p>Hosted by <a href="">The Mighty Restarters</a>, East of England, United Kingdom</p>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{{ route('dashboard') }}}">FIXOMETER</a></li>
                        <li class="breadcrumb-item"><a href="{{ url('/') }}/groups">@lang('groups.groups')</a></li>
                        <li class="breadcrumb-item active" aria-current="page">The Mighty Restarters</li>
                    </ol>
                </nav>
                <img src="{{ url('/') }}/images/placeholder.png" alt="Placeholder" class="event-icon">
            </header>

          </div>
          <div class="col-lg-5">

            <div class="button-group button-group__r">
                <a href="{{ url('/') }}/events/invite" class="btn btn-primary">Invite</a>
                <a href="{{ url('/') }}/events/add-to-calender" class="btn btn-primary">Add to calendar</a>
            </div>

          </div>
      </div>

        <div class="row">
            <div class="col-lg-4">

                <aside class="sidebar-lg-offset">

                    <h2>Event details</h2>
                    <div class="card events-card">
                        <div id="event-map" class="map" data-latitude="51.4985812" data-longitude="-0.0778824" data-zoom="14"></div>

                        <div class="events-card__details">

                            <div class="row flex-row d-flex">

                                <div class="col-4 d-flex flex-column"><strong>Date: </strong></div>
                                <div class="col-8 d-flex flex-column">Mon 7th May 2018</div>

                                <div class="col-4 d-flex flex-column"><strong>Time: </strong></div>
                                <div class="col-8 d-flex flex-column">13:00pm - 18:30pm</div>

                                <div class="col-4 d-flex flex-column"><strong>Address: </strong></div>
                                <div class="col-8 d-flex flex-column"><address>The Old Chapel<br>33 Church Street<br>Coggeshall, Colchester<br>Essex, CO6 1TX</address></div>
                                
                                <div class="col-4 d-flex flex-column"><strong>Host: </strong></div>
                                <div class="col-8 d-flex flex-column">Dean Appleton-Claydon</div>

                            </div>

                        </div>

                    </div>

                </aside>
            </div>
            <div class="col-lg-8">
                <h2 id="description">Description</h2>
                <div class="events__description">
                    <div class="truncate">
                    <p>Nulla vitae elit libero, a pharetra augue. Morbi leo risus, porta ac consectetur ac, vestibulum at eros. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Etiam porta sem malesuada magna mollis euismod. Vestibulum id ligula porta felis euismod semper. Etiam porta sem malesuada magna mollis euismod. Integer posuere erat a ante venenatis dapibus posuere velit aliquet.</p>
                    <p>Nulla vitae elit libero, a pharetra augue. Morbi leo risus, porta ac consectetur ac, vestibulum at eros. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Etiam porta sem malesuada magna mollis euismod. Vestibulum id ligula porta felis euismod semper. Etiam porta sem malesuada magna mollis euismod. Integer posuere erat a ante venenatis dapibus posuere velit aliquet.</p>
                    <p>Nulla vitae elit libero, a pharetra augue. Morbi leo risus, porta ac consectetur ac, vestibulum at eros. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Etiam porta sem malesuada magna mollis euismod. Vestibulum id ligula porta felis euismod semper. Etiam porta sem malesuada magna mollis euismod. Integer posuere erat a ante venenatis dapibus posuere velit aliquet.</p>
                    </div>
                    <button class="expand truncate__button"><span>Read more</span></button>
                </div>
                <h2 id="attendance">Attendance</h2>
                <ul class="nav nav-tabs" id="events-attendance" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link" id="attended-tab" data-toggle="tab" href="#attended" role="tab" aria-controls="attended" aria-selected="false"><svg width="16" height="18" viewBox="0 0 12 15" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="top: 3px;fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><g><path d="M9.268,3.161c-0.332,-0.212 -0.776,-0.119 -0.992,0.207c-0.216,0.326 -0.122,0.763 0.21,0.975c1.303,0.834 2.08,2.241 2.08,3.766c0,1.523 -0.777,2.93 -2.078,3.764c-0.001,0.001 -0.001,0.001 -0.002,0.001c-0.741,0.475 -1.601,0.725 -2.486,0.725c-0.885,0 -1.745,-0.25 -2.486,-0.725c-0.001,0 -0.001,0 -0.001,0c-1.302,-0.834 -2.08,-2.241 -2.08,-3.765c0,-1.525 0.778,-2.932 2.081,-3.766c0.332,-0.212 0.426,-0.649 0.21,-0.975c-0.216,-0.326 -0.66,-0.419 -0.992,-0.207c-1.711,1.095 -2.732,2.945 -2.732,4.948c0,2.003 1.021,3.852 2.732,4.947c0,0 0.001,0.001 0.002,0.001c0.973,0.623 2.103,0.952 3.266,0.952c1.164,0 2.294,-0.33 3.268,-0.953c1.711,-1.095 2.732,-2.944 2.732,-4.947c0,-2.003 -1.021,-3.853 -2.732,-4.948" style="fill:#0394a6;fill-rule:nonzero;"/><path d="M7.59,2.133c0.107,-0.36 -0.047,-1.227 -0.503,-1.758c-0.214,0.301 -0.335,0.688 -0.44,1.022c-0.182,0.066 -0.364,-0.014 -0.581,-0.082c-0.116,-0.037 -0.505,-0.121 -0.584,-0.245c-0.074,-0.116 0.073,-0.249 0.146,-0.388c0.051,-0.094 0.094,-0.231 0.136,-0.337c0.049,-0.126 0.07,-0.247 -0.006,-0.345c-0.462,0.034 -1.144,0.404 -1.394,0.906c-0.067,0.133 -0.101,0.393 -0.089,0.519c0.011,0.104 0.097,0.313 0.161,0.424c0.249,0.426 0.588,0.781 0.766,1.206c0.22,0.525 0.172,0.969 0.182,1.52c0.041,2.214 -0.006,2.923 -0.01,5.109c0,0.189 -0.014,0.415 0.031,0.507c0.26,0.527 1.029,0.579 1.29,-0.001c0.087,-0.191 0.028,-0.571 0.017,-0.843c-0.033,-0.868 -0.056,-1.708 -0.08,-2.526c-0.033,-1.142 -0.06,-0.901 -0.117,-1.97c-0.028,-0.529 -0.023,-1.117 0.275,-1.629c0.141,-0.24 0.657,-0.78 0.8,-1.089" style="fill:#0394a6;fill-rule:nonzero;"/></g></svg> Confirmed <span class="badge badge-pill badge-primary">5</span></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" id="invited-tab" data-toggle="tab" href="#invited" role="tab" aria-controls="invited" aria-selected="true"><svg width="16" height="12" viewBox="0 0 12 9" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="top:0px;fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><g><g><ellipse cx="10.796" cy="1.139" rx="1.204" ry="1.139" style="fill:#0394a6;"/><ellipse cx="5.961" cy="4.5" rx="1.204" ry="1.139" style="fill:#0394a6;"/><ellipse cx="1.204" cy="1.139" rx="1.204" ry="1.139" style="fill:#0394a6;"/><path d="M10.796,0l-9.592,0l-0.753,2.031l4.823,3.409l0.687,0.199l0.643,-0.173l4.89,-3.397l-0.698,-2.069Z" style="fill:#0394a6;"/></g><path d="M12,2.59c0,-0.008 0,5.271 0,5.271c0,0.628 -0.539,1.139 -1.204,1.139c-0.052,0 -0.104,-0.003 -0.155,-0.009l-0.02,0.009l-9.417,0c-0.665,0 -1.204,-0.511 -1.204,-1.139c0,0 0,-4.602 0,-5.096c0,-0.028 0,-0.175 0,-0.175c0,0.004 0.176,0.329 0.452,0.538l-0.001,0.003l4.823,3.408l0.012,0.003c0.193,0.124 0.425,0.197 0.675,0.197c0.233,0 0.45,-0.063 0.634,-0.171l0.009,-0.002l0.045,-0.032c0.016,-0.01 0.031,-0.021 0.047,-0.032l4.798,-3.334l0,-0.001c0.306,-0.206 0.506,-0.568 0.506,-0.577Z" style="fill:#0394a6;"/></g></svg> Invited <span class="badge badge-pill badge-primary">15</span></a>
                    </li>
                </ul>
                <div class="tab-content" id="events-attendance-tabs">
                    <div class="tab-pane fade" id="attended" role="tabpanel" aria-labelledby="attended-tab">
                        <div class="users-list-wrap">
                            <ul class="users-list">

                                <li>
                                    <h3>Dean Appleton-Claydon</h3>
                                    <p><svg width="14" height="14" viewBox="0 0 11 11" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><path d="M4.739,0.367c0.081,-0.221 0.284,-0.367 0.511,-0.367c0.227,0 0.43,0.146 0.511,0.367l1.113,3.039l3.107,0.168c0.227,0.013 0.422,0.17 0.492,0.395c0.07,0.225 0,0.473 -0.176,0.622l-2.419,2.046l0.807,3.142c0.059,0.229 -0.024,0.472 -0.207,0.612c-0.183,0.139 -0.43,0.146 -0.62,0.017l-2.608,-1.774l-2.608,1.774c-0.19,0.129 -0.437,0.122 -0.62,-0.017c-0.183,-0.14 -0.266,-0.383 -0.207,-0.612l0.807,-3.142l-2.419,-2.046c-0.176,-0.149 -0.246,-0.397 -0.176,-0.622c0.07,-0.225 0.265,-0.382 0.492,-0.395l3.107,-0.168l1.113,-3.039Z"/></svg> <button type="button" class="btn btn-skills" data-container="body" data-toggle="popover" data-placement="bottom" data-content="Mobile Devices, Audio &amp; Visual, Screens &amp; TVs, Home Appliances">5 skills</button></p>
                                    
                                    <img src="{{ url('/') }}/images/placeholder.png" alt="Placeholder" class="users-list__icon">
                                </li>
                                <li>
                                    <h3>Dean Appleton-Claydon</h3>
                                    <p><svg width="14" height="14" viewBox="0 0 11 11" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><path d="M4.739,0.367c0.081,-0.221 0.284,-0.367 0.511,-0.367c0.227,0 0.43,0.146 0.511,0.367l1.113,3.039l3.107,0.168c0.227,0.013 0.422,0.17 0.492,0.395c0.07,0.225 0,0.473 -0.176,0.622l-2.419,2.046l0.807,3.142c0.059,0.229 -0.024,0.472 -0.207,0.612c-0.183,0.139 -0.43,0.146 -0.62,0.017l-2.608,-1.774l-2.608,1.774c-0.19,0.129 -0.437,0.122 -0.62,-0.017c-0.183,-0.14 -0.266,-0.383 -0.207,-0.612l0.807,-3.142l-2.419,-2.046c-0.176,-0.149 -0.246,-0.397 -0.176,-0.622c0.07,-0.225 0.265,-0.382 0.492,-0.395l3.107,-0.168l1.113,-3.039Z"/></svg> <button type="button" class="btn btn-skills" data-container="body" data-toggle="popover" data-placement="bottom" data-content="Mobile Devices, Audio &amp; Visual, Screens &amp; TVs, Home Appliances">5 skills</button></p>
                                    
                                    <img src="{{ url('/') }}/images/placeholder.png" alt="Placeholder" class="users-list__icon">
                                </li>
                                <li>
                                    <h3>Dean Appleton-Claydon</h3>
                                    <p><svg width="14" height="14" viewBox="0 0 11 11" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><path d="M4.739,0.367c0.081,-0.221 0.284,-0.367 0.511,-0.367c0.227,0 0.43,0.146 0.511,0.367l1.113,3.039l3.107,0.168c0.227,0.013 0.422,0.17 0.492,0.395c0.07,0.225 0,0.473 -0.176,0.622l-2.419,2.046l0.807,3.142c0.059,0.229 -0.024,0.472 -0.207,0.612c-0.183,0.139 -0.43,0.146 -0.62,0.017l-2.608,-1.774l-2.608,1.774c-0.19,0.129 -0.437,0.122 -0.62,-0.017c-0.183,-0.14 -0.266,-0.383 -0.207,-0.612l0.807,-3.142l-2.419,-2.046c-0.176,-0.149 -0.246,-0.397 -0.176,-0.622c0.07,-0.225 0.265,-0.382 0.492,-0.395l3.107,-0.168l1.113,-3.039Z"/></svg> <button type="button" class="btn btn-skills" data-container="body" data-toggle="popover" data-placement="bottom" data-content="Mobile Devices, Audio &amp; Visual, Screens &amp; TVs, Home Appliances">5 skills</button></p>
                                    
                                    <img src="{{ url('/') }}/images/placeholder.png" alt="Placeholder" class="users-list__icon">
                                </li>
                                <li>
                                    <h3>Dean Appleton-Claydon</h3>
                                    <p><svg width="14" height="14" viewBox="0 0 11 11" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><path d="M4.739,0.367c0.081,-0.221 0.284,-0.367 0.511,-0.367c0.227,0 0.43,0.146 0.511,0.367l1.113,3.039l3.107,0.168c0.227,0.013 0.422,0.17 0.492,0.395c0.07,0.225 0,0.473 -0.176,0.622l-2.419,2.046l0.807,3.142c0.059,0.229 -0.024,0.472 -0.207,0.612c-0.183,0.139 -0.43,0.146 -0.62,0.017l-2.608,-1.774l-2.608,1.774c-0.19,0.129 -0.437,0.122 -0.62,-0.017c-0.183,-0.14 -0.266,-0.383 -0.207,-0.612l0.807,-3.142l-2.419,-2.046c-0.176,-0.149 -0.246,-0.397 -0.176,-0.622c0.07,-0.225 0.265,-0.382 0.492,-0.395l3.107,-0.168l1.113,-3.039Z"/></svg> <button type="button" class="btn btn-skills" data-container="body" data-toggle="popover" data-placement="bottom" data-content="Mobile Devices, Audio &amp; Visual, Screens &amp; TVs, Home Appliances">5 skills</button></p>
                                    
                                    <img src="{{ url('/') }}/images/placeholder.png" alt="Placeholder" class="users-list__icon">
                                </li>

                            </ul>
                            <a class="users-list__more" href="{{ url('/') }}/all-confirmed">See all confirmed</a>
                        </div>
                    </div>
                    <div class="tab-pane fade show active" id="invited" role="tabpanel" aria-labelledby="invited-tab">
                        <div class="users-list-wrap">
                            <ul class="users-list">

                                <li>
                                    <h3>Dean Appleton-Claydon</h3>
                                    <p><svg width="14" height="14" viewBox="0 0 11 11" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><path d="M4.739,0.367c0.081,-0.221 0.284,-0.367 0.511,-0.367c0.227,0 0.43,0.146 0.511,0.367l1.113,3.039l3.107,0.168c0.227,0.013 0.422,0.17 0.492,0.395c0.07,0.225 0,0.473 -0.176,0.622l-2.419,2.046l0.807,3.142c0.059,0.229 -0.024,0.472 -0.207,0.612c-0.183,0.139 -0.43,0.146 -0.62,0.017l-2.608,-1.774l-2.608,1.774c-0.19,0.129 -0.437,0.122 -0.62,-0.017c-0.183,-0.14 -0.266,-0.383 -0.207,-0.612l0.807,-3.142l-2.419,-2.046c-0.176,-0.149 -0.246,-0.397 -0.176,-0.622c0.07,-0.225 0.265,-0.382 0.492,-0.395l3.107,-0.168l1.113,-3.039Z"/></svg> <button type="button" class="btn btn-skills" data-container="body" data-toggle="popover" data-placement="bottom" data-content="Mobile Devices, Audio &amp; Visual, Screens &amp; TVs, Home Appliances">5 skills</button></p>
                                    
                                    <img src="{{ url('/') }}/images/placeholder.png" alt="Placeholder" class="users-list__icon">
                                </li>
                                <li>
                                    <h3>Dean Appleton-Claydon</h3>
                                    <p><svg width="14" height="14" viewBox="0 0 11 11" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><path d="M4.739,0.367c0.081,-0.221 0.284,-0.367 0.511,-0.367c0.227,0 0.43,0.146 0.511,0.367l1.113,3.039l3.107,0.168c0.227,0.013 0.422,0.17 0.492,0.395c0.07,0.225 0,0.473 -0.176,0.622l-2.419,2.046l0.807,3.142c0.059,0.229 -0.024,0.472 -0.207,0.612c-0.183,0.139 -0.43,0.146 -0.62,0.017l-2.608,-1.774l-2.608,1.774c-0.19,0.129 -0.437,0.122 -0.62,-0.017c-0.183,-0.14 -0.266,-0.383 -0.207,-0.612l0.807,-3.142l-2.419,-2.046c-0.176,-0.149 -0.246,-0.397 -0.176,-0.622c0.07,-0.225 0.265,-0.382 0.492,-0.395l3.107,-0.168l1.113,-3.039Z"/></svg> <button type="button" class="btn btn-skills" data-container="body" data-toggle="popover" data-placement="bottom" data-content="Mobile Devices, Audio &amp; Visual, Screens &amp; TVs, Home Appliances">5 skills</button></p>
                                    
                                    <img src="{{ url('/') }}/images/placeholder.png" alt="Placeholder" class="users-list__icon">
                                </li>
                                <li>
                                    <h3>Dean Appleton-Claydon</h3>
                                    <p><svg width="14" height="14" viewBox="0 0 11 11" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><path d="M4.739,0.367c0.081,-0.221 0.284,-0.367 0.511,-0.367c0.227,0 0.43,0.146 0.511,0.367l1.113,3.039l3.107,0.168c0.227,0.013 0.422,0.17 0.492,0.395c0.07,0.225 0,0.473 -0.176,0.622l-2.419,2.046l0.807,3.142c0.059,0.229 -0.024,0.472 -0.207,0.612c-0.183,0.139 -0.43,0.146 -0.62,0.017l-2.608,-1.774l-2.608,1.774c-0.19,0.129 -0.437,0.122 -0.62,-0.017c-0.183,-0.14 -0.266,-0.383 -0.207,-0.612l0.807,-3.142l-2.419,-2.046c-0.176,-0.149 -0.246,-0.397 -0.176,-0.622c0.07,-0.225 0.265,-0.382 0.492,-0.395l3.107,-0.168l1.113,-3.039Z"/></svg> <button type="button" class="btn btn-skills" data-container="body" data-toggle="popover" data-placement="bottom" data-content="Mobile Devices, Audio &amp; Visual, Screens &amp; TVs, Home Appliances">5 skills</button></p>
                                    
                                    <img src="{{ url('/') }}/images/placeholder.png" alt="Placeholder" class="users-list__icon">
                                </li>
                                <li>
                                    <h3>Dean Appleton-Claydon</h3>
                                    <p><svg width="14" height="14" viewBox="0 0 11 11" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><path d="M4.739,0.367c0.081,-0.221 0.284,-0.367 0.511,-0.367c0.227,0 0.43,0.146 0.511,0.367l1.113,3.039l3.107,0.168c0.227,0.013 0.422,0.17 0.492,0.395c0.07,0.225 0,0.473 -0.176,0.622l-2.419,2.046l0.807,3.142c0.059,0.229 -0.024,0.472 -0.207,0.612c-0.183,0.139 -0.43,0.146 -0.62,0.017l-2.608,-1.774l-2.608,1.774c-0.19,0.129 -0.437,0.122 -0.62,-0.017c-0.183,-0.14 -0.266,-0.383 -0.207,-0.612l0.807,-3.142l-2.419,-2.046c-0.176,-0.149 -0.246,-0.397 -0.176,-0.622c0.07,-0.225 0.265,-0.382 0.492,-0.395l3.107,-0.168l1.113,-3.039Z"/></svg> <button type="button" class="btn btn-skills" data-container="body" data-toggle="popover" data-placement="bottom" data-content="Mobile Devices, Audio &amp; Visual, Screens &amp; TVs, Home Appliances">5 skills</button></p>
                                    
                                    <img src="{{ url('/') }}/images/placeholder.png" alt="Placeholder" class="users-list__icon">
                                </li>
                                <li>
                                        <h3>Dean Appleton-Claydon</h3>
                                        <p><svg width="14" height="14" viewBox="0 0 11 11" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><path d="M4.739,0.367c0.081,-0.221 0.284,-0.367 0.511,-0.367c0.227,0 0.43,0.146 0.511,0.367l1.113,3.039l3.107,0.168c0.227,0.013 0.422,0.17 0.492,0.395c0.07,0.225 0,0.473 -0.176,0.622l-2.419,2.046l0.807,3.142c0.059,0.229 -0.024,0.472 -0.207,0.612c-0.183,0.139 -0.43,0.146 -0.62,0.017l-2.608,-1.774l-2.608,1.774c-0.19,0.129 -0.437,0.122 -0.62,-0.017c-0.183,-0.14 -0.266,-0.383 -0.207,-0.612l0.807,-3.142l-2.419,-2.046c-0.176,-0.149 -0.246,-0.397 -0.176,-0.622c0.07,-0.225 0.265,-0.382 0.492,-0.395l3.107,-0.168l1.113,-3.039Z"/></svg> <button type="button" class="btn btn-skills" data-container="body" data-toggle="popover" data-placement="bottom" data-content="Mobile Devices, Audio &amp; Visual, Screens &amp; TVs, Home Appliances">5 skills</button></p>
                                        
                                        <img src="{{ url('/') }}/images/placeholder.png" alt="Placeholder" class="users-list__icon">
                                </li>
                                <li class="users-list__invite">
                                    <button>Invite to join event</button>
                                </li>

                            </ul>
                            <a class="users-list__more" href="{{ url('/') }}/all-invited">See all invited</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
  </div>
</section>
@endsection