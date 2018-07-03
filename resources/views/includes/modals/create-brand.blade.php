<!-- Modal -->
<div class="modal modal-intro modal-brand modal-form fade" id="add-new-brand" tabindex="-1" role="dialog" aria-labelledby="createbrandLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">

        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>



        <form action="/brands/create" method="post">
          @csrf
            <legend id="createbrandLabel">@lang('admin.brand_modal_title')</legend>

            <div class="form-group">
                <label for="brand_name">@lang('admin.brand-name'):</label>
                <input require type="text" id="brand_name" name="brand_name" class="field form-control">
            </div>

            <div class="button-group">
                <div class="d-flex justify-content-end">
                <button class="btn btn-primary" type="submit">@lang('admin.create-new-brand')</button>
                </div>
            </div>


        </form>



    </div>
  </div>
</div>
