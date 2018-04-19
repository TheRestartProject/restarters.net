<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h1>Edit Group <span class="orange"><?php echo $formdata->name; ?></span></h1>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <?php if(isset($response)) { printResponse($response); } ?>

            <div class="alert alert-info" >
                <p>
                This page allows you to edit the details of your group.
                </p>
                <p>
                 For guidance on completing each piece of information, you can click on the <i class="fa fa-question-circle"></i> icon next to the name of the field.
                </p>
            </div>

            <form action="/group/edit/<?php echo $formdata->idgroups; ?>" method="post" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group <?php if(isset($error) && isset($error['name']) && !empty($error['name'])) { echo "has-error"; } ?>">
                            <label for="name">Name:</label>
                            <i class="fa fa-question-circle" data-toggle="popover" title="Name of the group" data-content="This is the name of your group.  This name is used to refer to the group within the Fixometer and on the public-facing website.  "></i>
                            <input type="text" name="name" id="name" class="form-control" value="<?php echo $formdata->name; ?>">
                            <?php if(isset($error) && isset($error['name']) && !empty($error['name'])) { echo '<span class="help-block text-danger">' . $error['name'] . '</span>'; } ?>
                        </div>


                        <div class="form-group">
                            <label for="name">Website:</label>
                            <i class="fa fa-question-circle" data-toggle="popover" title="Group website address" data-content="This is the link to your group's own public website, if you have one.  If it exists, it could for example be a website, or a Facebook group. The website is linked to from your group's page on The Restart Project website."></i>
                            <input type="text" name="website" id="website" class="form-control" value="<?php echo $formdata->website; ?>">                          
                        </div>


                        <div class="form-group">
                            <label for="free_text">Description:</label>
                            <i class="fa fa-question-circle" data-toggle="popover" title="Description of your group" data-content="This is a free text description of your group.  It is displayed on your group's public on The Restart Project website.  You can add some basic HTML formatting using the formatting buttons."></i>
                            <textarea class="form-control rte" rows="6" name="free_text" id="free_text"><?php echo $formdata->free_text; ?></textarea>
                        </div>

                    </div>

                    <div class="col-md-6">

                        <div class="form-group">

                            <i class="fa fa-question-circle" data-toggle="popover" title="Location of your group" data-content="This is the location that you consider to be the home of your group.  It can be used to display groups on a map.  If it is an exact address, enter that address and press 'geocode'.  If the group does not have a fixed address, enter an area (e.g. Hackney, London).  After pressing 'geocode', the map below will show the location.  Please check that it is correct, and if not, try entering a more specific location and pressing 'geocode' again."></i>
                            <label for="location">Location: where do you keep your fixing tools and supplies?</label>

                            <div class="input-group">
                                <input type="text" name="location" id="location" class="form-control"  value="<?php echo $formdata->location; ?>">
                                <span class="input-group-btn">
                                    <button type="button" class="btn btn-primary" onclick="codeAddress()"><i class="fa fa-map-marker"></i> geocode</button>
                                </span>
                            </div>
                        </div>


                        <div class="" id="map-canvas" style="height: 350px; padding: 25px 0px; ">
                            <i class="fa fa-spinner fa-pulse"></i>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <input type="text" name="latitude" id="latitude" class="form-control" placeholder="latitude..." value="<?php echo $formdata->latitude; ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <input type="text" name="longitude" id="longitude" class="form-control" placeholder="longitude..." value="<?php echo $formdata->longitude; ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <div class="input-group">
                                        <input type="text" name="area" id="area" class="form-control" placeholder="city..." value="<?php echo $formdata->area; ?>">
                                        <span class="input-group-btn">
                                            <p class="btn">
                                                <i class="fa fa-question-circle" data-toggle="popover" title="Area of the group" data-content="This is a the area that your group is based in, and is used when displaying information about specific parties for your group.  Examples: London; Nottingham; Turin."></i>
                                            </p>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group image-wrap">
                            <small>Current Group Logo:</small>
                            <?php
                            if(!empty($formdata->path)){
                                echo '<img src="/uploads/mid_' . $formdata->path . '" class="img-responsive" style="width: 25%; height: 25%; ">';

                            }
                            else {
                                echo '<img src="/uploads/mid_1474993329ef38d3a4b9478841cc2346f8e131842fdcfd073b307.jpg" class="img-responsive" style="width: 25%; height: 25%; ">';
                                // echo '<div class="alert alert-info">No Image</div>';
                            }
                            ?>
                        </div>

                        <div class="form-group">
                            <label for="image">Image:</label>
                            <i class="fa fa-question-circle" data-toggle="popover" title="Logo of the group" data-content="A logo style image for your group.  This image will be displayed on your group's page on The Restart Project website."></i>
                            <input type="file" class="form-control file" name="image" data-show-upload="false" data-show-caption="true">
                            <small>Icon, Avatar or Logo of the Group</small>
                        </div>
                    </div>


                </div>
                <div class="row buttons">

                            <div class="col-md-6 col-md-offset-6">

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
