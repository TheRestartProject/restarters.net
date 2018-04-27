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

                    <?php if(isset($response)) { printResponse($response); } ?>

                    <form action="/device/create" method="post">
                        <div class="row">
                            <div class="col-md-6">

                                <div class="form-group <?php if(isset($error) && isset($error['event']) && !empty($error['event'])) { echo "has-error"; } ?>">

                                    <label for="event"><?php _t("Restart Party:");?></label>
                                    <select id="event" name="event"  class="form-control selectpicker" data-live-search="true">
                                        <option></option>
                                        <?php foreach($events as $event){ ?>
                                        <option value="<?php echo $event->idevents; ?>"><?php echo $event->name . ' [' . date('d/m/Y', strtotime($event->event_date)) . ']'; ?></option>
                                        <?php } ?>
                                    </select>
                                    <?php if(isset($error) && isset($error['event']) && !empty($error['event'])) { echo '<span class="help-block text-danger">' . $error['event'] . '</span>'; } ?>
                                </div>



                                <div class="form-group <?php if(isset($error) && isset($error['category']) && !empty($error['category'])) { echo "has-error"; } ?>">

                                    <label for="category"><?php _t("Category:");?></label>
                                    <select id="category" name="category"  class="form-control selectpicker" data-live-search="true">
                                        <option></option>
                                        <?php foreach($categories as $category){ ?>
                                        <option value="<?php echo $category->idcategories; ?>"><?php echo $category->name; ?></option>
                                        <?php } ?>
                                    </select>
                                    <?php if(isset($error) && isset($error['category']) && !empty($error['category'])) { echo '<span class="help-block text-danger">' . $error['category'] . '</span>'; } ?>
                                </div>


                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label><?php _t("Repair Status:");?></label>
                                            <div class="radio">
                                                <label>
                                                    <input type="radio" name="repair_status" id="repair_status_1" value="1" checked> <?php _t("Fixed");?>
                                                </label>
                                            </div>
                                            <div class="radio">
                                                <label>
                                                    <input type="radio" name="repair_status" id="repair_status_2" value="2"> <?php _t("Repairable");?>
                                                </label>
                                            </div>
                                            <div id="repairable-details">
                                                <div class="checkbox">
                                                    <label>
                                                        <input type="checkbox" name="more_time_needed" id="more_time_needed" value="1"> <?php _t("More time needed");?>
                                                    </label>
                                                </div>
                                                <div class="checkbox">
                                                    <label>
                                                        <input type="checkbox" name="professional_help" id="professional_help" value="1"> <?php _t("Professional help");?>
                                                    </label>
                                                </div>
                                                <div class="checkbox">
                                                    <label>
                                                        <input type="checkbox" name="do_it_yourself" id="do_it_yourself" value="1"> <?php _t("Do it yourself");?>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="radio">
                                                <label>
                                                    <input type="radio" name="repair_status" id="repair_status_3" value="3"> <?php _t("End of lifecycle");?>
                                                </label>
                                            </div>
                                            <?php if(isset($error) && isset($error['repair_status']) && !empty($error['repair_status'])) { echo '<span class="help-block text-danger">' . $error['repair_status'] . '</span>'; } ?>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label><?php _t("Spare parts needed:");?></label>
                                        <div class="radio">
                                            <label>
                                                <input type="radio" name="spare_parts" id="repair_status_1" value="1"> <?php _t("Yes");?>
                                            </label>
                                        </div>
                                        <div class="radio">
                                            <label>
                                                <input type="radio" name="spare_parts" id="repair_status_1" value="2" checked><?php _t("No");?>
                                            </label>
                                        </div>
                                    </div>

                                </div>

                                <div class="form-group">
                                    <label for="brand">Brand:</label>
                                    <input type="text" name="brand" id="brand" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label for="model">Model:</label>
                                    <input type="text" name="model" id="model" class="form-control">
                                </div>


                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="problem">Problem:</label>
                                    <textarea class="form-control rte" rows="6" name="problem" id="problem"></textarea>
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
