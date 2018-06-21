<!-- Modal -->
<div class="modal fade" id="group-volunteers" tabindex="-1" role="dialog" aria-labelledby="groupVolunteersLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">

    <div class="modal-content">

      <div class="modal-header">

        <h5 id="groupVolunteersLabel">@lang('groups.all_volunteers_group_name_header', ['group' => 'The Mighty Restarters'])</h5>
        @include('fixometer/partials/cross')

      </div>

      <div class="modal-body">

        <div class="row">
          <div class="col-md-12 col-lg-7">
            <p>@lang('groups.all_volunteers_group_name_message')</p>
          </div>
        </div>

        <table role="table" class="table table-striped table-hover">
          <thead>
            <tr>
              <th></th>
              <th scope="col">@lang('groups.restarter_column_table')</th>
              <th scope="col">@lang('groups.skills_column_table')</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td class="table-cell-icon"><img src="{{{ url('images/placeholder.png') }}}" class="rounded" alt="Placeholder"></td>
              <td><a href="">Dean Appleton-Claydon</a></td>
              <td>
                Communication<br>
                Communication
              </td>
            </tr>
          </tbody>
        </table>

        <button type="submit" class="btn btn-primary float-right">@lang('groups.join_group_button')</button>

      </div>


    </div>
  </div>
</div>
