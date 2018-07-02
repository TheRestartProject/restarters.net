@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h1>Create New Group</h1>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            @if(isset($response))
              @php( FixometerHelper::printResponse($response) )
            @endif

            <div class="alert alert-info" >
                <p>
                This page allows you to create a new Restart Group.
                </p>
                <p>
                 For guidance on completing each piece of information, you can click on the <i class="fa fa-question-circle"></i> icon next to the name of the field.
                </p>
            </div>

            <form action="/group/create" method="post" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group <?php if(isset($error) && isset($error['name']) && !empty($error['name'])) { echo "has-error"; } ?>">
                            <label for="name">Name: </label>
                            <i class="fa fa-question-circle" data-toggle="popover" title="Name of the group" data-content="This is the name of the Restart group.  This name is used to refer to the group within the Fixometer and on the public-facing website.  Examples: Hackney Fixers; Restarters Oslo."></i>
                            <input type="text" name="name" id="name" class="form-control" <?php if(isset($error) && !empty($error) && !empty($udata)) echo 'value="'.$udata['name'].'"' ; ?>>
                            <?php if(isset($error) && isset($error['name']) && !empty($error['name'])) { echo '<span class="help-block text-danger">' . $error['name'] . '</span>'; } ?>
                        </div>

                        <div class="form-group">
                            <label for="name">Website:</label>
                            <i class="fa fa-question-circle" data-toggle="popover" title="Group website address" data-content="This is the address of the group's own public website, if they have one.  If it exists, it could for example be a website, such as http://sustainablehackney.org.uk/hackney-fixers, or a Facebook group, such as https://www.facebook.com/restartpartytorino.  The website is linked to from the group's page on The Restart Project website."></i>
                            <input type="text" name="website" id="website" class="form-control" <?php if(isset($error) && !empty($error) && !empty($udata)) echo 'value="'.$udata['website'].'"' ; ?>>
                        </div>

                        <div class="form-group">
                            <label for="free_text">Description:</label>
                            <i class="fa fa-question-circle" data-toggle="popover" title="Description of the group" data-content="This is a free text description of the group.  It is displayed on the group's public on The Restart Project website.  You can add some basic HTML formatting using the formatting buttons."></i>
                            <textarea class="form-control rte" rows="6" name="free_text" id="free_text"></textarea>
                        </div>

                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <i class="fa fa-question-circle" data-toggle="popover" title="Location of the group" data-content="This is the location that is considered to be the home of the group.  It can be used to display groups on a map.  If it is an exact address, enter that address and press 'geocode'.  If the group does not have a fixed address, enter an area (e.g. Hackney, London).  After pressing 'geocode', the map below will show the location.  Please check that it is correct, and if not, try entering a more specific location and pressing 'geocode' again."></i>
                            <label for="location">Location: where do you keep your fixing tools and supplies?</label>

                            <div class="input-group">
                                <input type="text" name="location" id="location" class="form-control" <?php if(isset($error) && !empty($error) && !empty($udata)) echo 'value="'.$udata['location'].'"' ; ?>>
                                <span class="input-group-btn">
                                    <button type="button" class="btn btn-primary" onclick="codeAddress()"><i class="fa fa-map-marker"></i> geocode</button>
                                </span>
                            </div>

                        </div>


                        <div class="" id="map-canvas" style="height: 350px; ">
                            <i class="fa fa-spinner"></i>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <input type="text" name="latitude" id="latitude" class="form-control" placeholder="latitude..." <?php if(isset($error) && !empty($error) && !empty($udata)) echo 'value="'.$udata['latitude'].'"' ; ?>>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <input type="text" name="longitude" id="longitude" class="form-control" placeholder="longitude..." <?php if(isset($error) && !empty($error) && !empty($udata)) echo 'value="'.$udata['longitude'].'"' ; ?>>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <div class="input-group">
                                        <input type="text" name="area" id="area" class="form-control" placeholder="city..." <?php if(isset($error) && !empty($error) && !empty($udata)) echo 'value="'.$udata['area'].'"' ; ?>>
                                        <span class="input-group-btn">
                                            <p class="btn">
                                                <i class="fa fa-question-circle" data-toggle="popover" title="Area of the group" data-content="This is a the area that the group is based in, and is used when displaying information about specific parties for the group.  Examples: London; Nottingham; Turin."></i>
                                            </p>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="image">Image:</label>
                                    <i class="fa fa-question-circle" data-toggle="popover" title="Image of the group" data-content="A logo style image for the group.  This image will be displayed on the group's page on The Restart Project website."></i>
                                    <input type="file" class="form-control file" name="image" data-show-upload="false" data-show-caption="true">
                                    <small>Icon, Avatar or Logo of the Group</small>
                                </div>
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
@endsection
