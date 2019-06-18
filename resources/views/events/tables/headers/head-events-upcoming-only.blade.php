{{-- Upcoming Events Table Heading --}}
<thead>
  <tr>
    <th class="hightlighted" width="10"></th>

    <th class="table-cell-icon" width="70"></th>

    <th scope="col" width="450" class="pl-0">@lang('events.event_name')</th>

    <th scope="col" width="250" class="cell-date" >@lang('events.event_date') / @lang('events.event_time')</th>

    <th scope="col" width="85" class="">
      <button type="button" class="btn btn-skills" data-container="body" data-toggle="popover" data-placement="top" data-content="Invited" data-original-title="" title="">
        <svg width="16" height="12" viewBox="0 0 12 9" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><g><g><ellipse cx="10.796" cy="1.139" rx="1.204" ry="1.139" style="fill:#0394a6;"></ellipse><ellipse cx="5.961" cy="4.5" rx="1.204" ry="1.139" style="fill:#0394a6;"></ellipse><ellipse cx="1.204" cy="1.139" rx="1.204" ry="1.139" style="fill:#0394a6;"></ellipse><path d="M10.796,0l-9.592,0l-0.753,2.031l4.823,3.409l0.687,0.199l0.643,-0.173l4.89,-3.397l-0.698,-2.069Z" style="fill:#0394a6;"></path></g><path d="M12,2.59c0,-0.008 0,5.271 0,5.271c0,0.628 -0.539,1.139 -1.204,1.139c-0.052,0 -0.104,-0.003 -0.155,-0.009l-0.02,0.009l-9.417,0c-0.665,0 -1.204,-0.511 -1.204,-1.139c0,0 0,-4.602 0,-5.096c0,-0.028 0,-0.175 0,-0.175c0,0.004 0.176,0.329 0.452,0.538l-0.001,0.003l4.823,3.408l0.012,0.003c0.193,0.124 0.425,0.197 0.675,0.197c0.233,0 0.45,-0.063 0.634,-0.171l0.009,-0.002l0.045,-0.032c0.016,-0.01 0.031,-0.021 0.047,-0.032l4.798,-3.334l0,-0.001c0.306,-0.206 0.506,-0.568 0.506,-0.577Z" style="fill:#0394a6;"></path></g>
        </svg>
      </button>
    </th>
    <th scope="col" width="85" class="">
      <button type="button" class="btn btn-skills" data-container="body" data-toggle="popover" data-placement="top" data-content="@lang('events.stat-2')">
        <svg width="17" height="20" viewBox="0 0 12 15" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><g><path d="M9.268,3.161c-0.332,-0.212 -0.776,-0.119 -0.992,0.207c-0.216,0.326 -0.122,0.763 0.21,0.975c1.303,0.834 2.08,2.241 2.08,3.766c0,1.523 -0.777,2.93 -2.078,3.764c-0.001,0.001 -0.001,0.001 -0.002,0.001c-0.741,0.475 -1.601,0.725 -2.486,0.725c-0.885,0 -1.745,-0.25 -2.486,-0.725c-0.001,0 -0.001,0 -0.001,0c-1.302,-0.834 -2.08,-2.241 -2.08,-3.765c0,-1.525 0.778,-2.932 2.081,-3.766c0.332,-0.212 0.426,-0.649 0.21,-0.975c-0.216,-0.326 -0.66,-0.419 -0.992,-0.207c-1.711,1.095 -2.732,2.945 -2.732,4.948c0,2.003 1.021,3.852 2.732,4.947c0,0 0.001,0.001 0.002,0.001c0.973,0.623 2.103,0.952 3.266,0.952c1.164,0 2.294,-0.33 3.268,-0.953c1.711,-1.095 2.732,-2.944 2.732,-4.947c0,-2.003 -1.021,-3.853 -2.732,-4.948" style="fill:#0394a6;fill-rule:nonzero;"></path><path d="M7.59,2.133c0.107,-0.36 -0.047,-1.227 -0.503,-1.758c-0.214,0.301 -0.335,0.688 -0.44,1.022c-0.182,0.066 -0.364,-0.014 -0.581,-0.082c-0.116,-0.037 -0.505,-0.121 -0.584,-0.245c-0.074,-0.116 0.073,-0.249 0.146,-0.388c0.051,-0.094 0.094,-0.231 0.136,-0.337c0.049,-0.126 0.07,-0.247 -0.006,-0.345c-0.462,0.034 -1.144,0.404 -1.394,0.906c-0.067,0.133 -0.101,0.393 -0.089,0.519c0.011,0.104 0.097,0.313 0.161,0.424c0.249,0.426 0.588,0.781 0.766,1.206c0.22,0.525 0.172,0.969 0.182,1.52c0.041,2.214 -0.006,2.923 -0.01,5.109c0,0.189 -0.014,0.415 0.031,0.507c0.26,0.527 1.029,0.579 1.29,-0.001c0.087,-0.191 0.028,-0.571 0.017,-0.843c-0.033,-0.868 -0.056,-1.708 -0.08,-2.526c-0.033,-1.142 -0.06,-0.901 -0.117,-1.97c-0.028,-0.529 -0.023,-1.117 0.275,-1.629c0.141,-0.24 0.657,-0.78 0.8,-1.089" style="fill:#0394a6;fill-rule:nonzero;"></path></g>
        </svg>
      </button>
    </th>

    <th scope="col" width="260" class="d-none d-sm-table-cell"></th>
  </tr>
</thead>
