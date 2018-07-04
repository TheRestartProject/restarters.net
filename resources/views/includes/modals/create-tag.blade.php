<!-- Modal -->
<div class="modal modal-intro modal-form fade" id="add-new-tag" tabindex="-1" role="dialog" aria-labelledby="createTagLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">

        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>

        <form action="/tags/create" method="POST">

            @csrf

            <legend id="createTagLabel">@lang('admin.tags_modal_title')</legend>

            <div class="form-group">
                <label for="tag-name">@lang('admin.tag-name'):</label>
                <input type="text" name="tag-name" id="tag-name" class="field form-control" required>
            </div>

            <div class="form-group">
                <label for="tag-description">@lang('admin.description_optional'):</label>
                <textarea name="tag-description" id="tag-description" class="form-control field" rows="6"></textarea>
            </div>

            <div class="button-group">
                <div class="d-flex justify-content-end">
                <button class="btn btn-primary" type="submit">@lang('admin.create-new-tag')</button>
                </div>
            </div>

        </form>

    </div>
  </div>
</div>
