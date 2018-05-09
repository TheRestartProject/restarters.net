
        <script src="/components/bootstrap-select/dist/js/bootstrap-select.min.js"></script>
        <script src="/components/bootstrap-sortable/Scripts/bootstrap-sortable.js"></script>
        <script src="/components/eonasdan-bootstrap-datetimepicker/build/js/bootstrap-datetimepicker.min.js"></script>
        <script src="/components/summernote/dist/summernote.min.js"></script>
        <script src="/components/summernote-cleaner/summernote-cleaner.js"></script>
        <script src="/components/bootstrap-fileinput/js/fileinput.min.js"></script>
        @if(isset($js) && isset($js['foot']) && !empty($js['foot']))
            @foreach($js['foot'] as $script)
        <script src="<?php echo $script; ?>"></script>
             @endforeach
        @endif

        @if(env('APP_ENV') == 'development' || env('APP_ENV') == 'local')
        <script src="/dist/js/fixometer.js"></script>
        @else
        <script src="/dist/js/script.min.js"></script>
        @endif

        <script>
            $(document).ready(function() {
                $('.fileinput').fileinput({
                    showCaption: false,
                    showUpload: false,
                    showRemove: false,
                    browseIcon:  '<i class="fa fa-folder-open"></i> &nbsp;',
                    browseLabel: 'Choose image...',
                    previewFileIcon: '<i class="fa fa-file"></i>',
                    browseIcon: '<i class="fa fa-folder-open"></i> &nbsp;',
                    browseClass: 'btn btn-primary',
                    removeIcon: '<i class="fa fa-trash"></i> ',
                    removeClass: 'btn btn-default',
                    cancelIcon: '<i class="fa fa-ban-circle"></i> ',
                    cancelClass: 'btn btn-default',
                    uploadIcon: '<i class="fa fa-upload"></i> ',

                });
            } );
        </script>

    </body>
</html>
