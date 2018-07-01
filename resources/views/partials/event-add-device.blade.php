<div class="table-responsive">
    <form id="add-device">
    <table class="table table-add" role="table">
        <tbody>
            <tr>
                <th width="100">Add device</th>
                  <td>
                    <div class="form-control form-control__select">
                        <select name="repair_status" id="repair_status">
                            <option value="0">Status</option>
                            <option value="1">Fixed</option>
                            <option value="2">Repairable</option>
                            <option value="3">End of Life</option>
                        </select>
                    </div>
                </td>
                <td>
                    <div class="form-control form-control__select">
                        <select name="repair_details" id="repair_details" disabled>
                            <option value="0">Repair Details</option>
                            <option value="1">More time needed</option>
                            <option value="2">Professional help</option>
                            <option value="3">Do it yourself</option>
                        </select>
                    </div>
                </td>
                <td>
                <td>
                    <div class="form-control form-control__select">
                        <select name="spare_parts" id="spare_parts">
                            <option value="0">Spare Parts Needed?</option>
                            <option value="1">Yes</option>
                            <option value="2">No</option>
                        </select>
                    </div>
                </td>
                <td>
                    <div class="form-control form-control__select">
                        <select name="category" id="category">
                            <option value="0">--- Category ---</option>
                            @foreach($categories as $category)
                              <option value="{{ $category->idcategories }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </td>
                <td>
                    <div class="form-control form-control__select">
                        <select name="brand" id="brand">
                            <option value="0">--- Brand ---</option>
                            @foreach($brands as $brand)
                              <option value="{{ $brand->id }}">{{ $brand->brand_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </td>
                <td>
                    <div class="form-group">
                        <input type="text" class="form-control field" id="model" name="model" placeholder="Model" required>
                    </div>
                </td>
                <td>
                    <div class="form-group">
                        <input type="text" class="form-control field" id="age" name="age" placeholder="Age" required>
                    </div>
                </td>
                <td>
                    <div class="form-group">
                        <input type="text" class="form-control field" id="problem" name="problem" placeholder="Problem" required>
                    </div>
                </td>
                <td><input type="submit" class="btn btn-primary btn-add" id="submit-new-device" value="Add"></td>
            </tr>
        </tbody>
    </table>
    </form>

</div>
