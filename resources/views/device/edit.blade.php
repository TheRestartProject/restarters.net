@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-4">
            <h1><?php echo $title; ?></h1>
        </div>
    </div>
    <div class="row">
        <div class="container">
            <div class="row">
                <div class="col-md-12">

                    @if(isset($response))
                      @php( FixometerHelper::printResponse($response) )
                    @endif

                    <form action="/device/edit/<?php echo $formdata->iddevices; ?>" method="post">
                      @csrf
                        <div class="row">
                            <div class="col-md-6">

                                <div class="form-group <?php if(isset($error) && isset($error['event']) && !empty($error['event'])) { echo "has-error"; } ?>">

                                    <label for="event">Restart Party:</label>
                                    <select id="event" name="event"  class="form-control selectpicker" data-live-search="true">
                                        <option></option>
                                        @if(isset($events))
                                          <?php foreach($events as $event){ ?>
                                          <option value="<?php echo $event->id; ?>"<?php echo ($event->id == $formdata->event ? ' selected' : ''); ?>><?php echo $event->location . ' [' . date('d/m/Y', $event->event_timestamp) . ']'; ?></option>
                                          <?php } ?>
                                        @endif
                                    </select>
                                    <?php if(isset($error) && isset($error['event']) && !empty($error['event'])) { echo '<span class="help-block text-danger">' . $error['event'] . '</span>'; } ?>
                                </div>



                                <div class="form-group <?php if(isset($error) && isset($error['category']) && !empty($error['category'])) { echo "has-error"; } ?>">

                                    <label for="category">Category:</label>
                                    <select id="category" name="category"  class="form-control selectpicker" data-live-search="true">
                                        <option></option>
                                        @if(isset($categories))
                                          <?php foreach($categories as $category){ ?>
                                          <option value="<?php echo $category->idcategories; ?>"<?php echo ($category->idcategories == $formdata->category ? ' selected' : ''); ?>><?php echo $category->name; ?></option>
                                          <?php } ?>
                                        @endif
                                    </select>
                                    <?php if(isset($error) && isset($error['category']) && !empty($error['category'])) { echo '<span class="help-block text-danger">' . $error['category'] . '</span>'; } ?>
                                </div>


                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Repair Status:</label>
                                            <div class="radio">
                                                <label>
                                                    <input type="radio" name="repair_status" id="repair_status_1" value="1" <?php echo ($formdata->repair_status == 1 ? ' checked' : ''); ?>> Fixed
                                                </label>
                                            </div>
                                            <div class="radio">
                                                <label>
                                                    <input type="radio" name="repair_status" id="repair_status_2" value="2" <?php echo ($formdata->repair_status == 2 ? ' checked' : ''); ?>> Repairable
                                                </label>
                                            </div>
                                            <div id="repairable-details">
                                                <div class="checkbox">
                                                    <label>
                                                        <input type="checkbox" name="more_time_needed" id="more_time_needed" value="1" <?php echo ($formdata->repair_status == 3 ? ' checked' : ''); ?>> More time needed
                                                    </label>
                                                </div>
                                                <div class="checkbox">
                                                    <label>
                                                        <input type="checkbox" name="professional_help" id="professional_help" value="1"  <?php echo ($formdata->professioanl_help == 1 ? ' checked' : ''); ?>> Professional help
                                                    </label>
                                                </div>
                                                <div class="checkbox">
                                                    <label>
                                                        <input type="checkbox" name="do_it_yourself" id="do_it_yourself" value="1" <?php echo ($formdata->do_it_yourself == 1 ? ' checked' : ''); ?>> Do it yourself
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="radio">
                                                <label>
                                                    <input type="radio" name="repair_status" id="repair_status_3" value="3" <?php echo ($formdata->end_of_lifecycle == 1 ? ' checked' : ''); ?>> End of lifecycle
                                                </label>
                                            </div>
                                            <?php if(isset($error) && isset($error['repair_status']) && !empty($error['repair_status'])) { echo '<span class="help-block text-danger">' . $error['repair_status'] . '</span>'; } ?>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label>Spare parts needed:</label>
                                        <div class="radio">
                                            <label>
                                                <input type="radio" name="spare_parts" id="spare_parts_1" value="1"  <?php echo ($formdata->spare_parts == 1 ? ' checked' : ''); ?>> Yes
                                            </label>
                                        </div>
                                        <div class="radio">
                                            <label>
                                                <input type="radio" name="spare_parts" id="spare_parts_2" value="2"  <?php echo ($formdata->spare_parts == 2 ? ' checked' : ''); ?>> No
                                            </label>
                                        </div>
                                    </div>

                                </div>

                                <div class="form-group">
                                    <label for="brand">Brand:</label>
                                    <input type="text" name="brand" id="brand" class="form-control" value="<?php echo $formdata->brand; ?>">
                                </div>
                                <div class="form-group">
                                    <label for="model">Model:</label>
                                    <input type="text" name="model" id="model" class="form-control" value="<?php echo $formdata->model; ?>">
                                </div>


                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="problem">Problem:</label>
                                    <textarea class="form-control rte" rows="6" name="problem" id="problem"><?php echo $formdata->problem; ?></textarea>
                                </div>



                            </div>

                            <div class="col-md-12 buttons">
                                <div class="form-group">
                                    <button class="btn btn-default" type="reset"><i class="fa fa-refresh"></i> reset</button>
                                    <button class="btn btn-primary" type="submit"><i class="fa fa-save"></i> save</button>
                                </div>
                            </div>
                        </div>


                    </form>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
