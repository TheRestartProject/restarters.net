<!-- Modal -->
<div class="modal modal-intro modal-form fade" id="add-new-skill" tabindex="-1" role="dialog" aria-labelledby="createSkillLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">

        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>



        <form action="">

            <legend id="createSkillLabel">@lang('admin.skills_modal_title')</legend>

            <div class="form-group">
                <label for="skill_name">@lang('admin.skill_name'):</label>
                <input require type="text" id="skill_name" class="field form-control">
            </div>

            <div class="form-group">
                <label for="skill_desc">@lang('admin.description_optional'):</label>
                <textarea name="skill_desc" id="skill_desc" class="form-control field textarea-large"></textarea>
            </div>

            <div class="button-group">
                <div class="d-flex justify-content-end">
                <button class="btn btn-primary" type="submit">@lang('admin.create-new-skill')</button>
                </div>
            </div>


        </form>



    </div>
  </div>
</div>
