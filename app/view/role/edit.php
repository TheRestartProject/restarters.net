<div class="container">
    
    <div class="row">
        <div class="col-md-12">
            <h1><?php echo $title; ?></h1>
            
            <?php if(isset($response)) printResponse($response); ?>
            <form class="" method="post" action="/role/edit/<?php echo $formId; ?>">
                <input name="formId" value="<?php echo APPNAME . '_' . $formId; ?>" type="hidden">
                <!-- Checkbox List of Permissions -->
                <?php foreach($permissions as $p) { ?>
                <div class="checkbox">
                    <label>
                      <input
                        type="checkbox"
                        value="<?php echo $p->idpermissions; ?>" 
                        name="permissions[<?php echo $p->idpermissions; ?>]"
                        <?php echo (in_array($p->idpermissions, $activePermissions) ? ' checked' : '' ); ?> 
                        > <?php echo $p->permission; ?>
                    </label>
                </div>
                <?php } ?>
                <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> save</button>
            </form> 
            
        </div>
    </div>
</div>