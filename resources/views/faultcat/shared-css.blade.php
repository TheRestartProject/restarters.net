<style>
 /*
    this is css shared between the main faultcat view,
 and the demographics view.  should be moved to a shared layout,
 or into sass.  just putting it here for now to remove duplication
 between the files.
  */
    body {
        text-align: center !important;
    }

    h1 {
        font-family: 'Asap' !important;
        font-weight: bold !important;
        float: left; /* bootstrap pull-left class working locally but not on server!  falling back on this. */
    }

    img#faultcat {
        width: 48px;
        height: 48px;
        float: right; /* bootstrap pull-right class working locally but not on server!  falling back on this. */
    }


    .title {
        font-weight: bold;
    }

    .is-horizontal-center {
        justify-content: center;
    }

    .hide {
        display: none;
    }

    .show {
        display: block;
    }

    .underline {
        text-decoration: underline;
    }

    .options {
        margin-top: 15px;
    }

    .problem {
        font-size: 1rem;
        background-color: #f5f5f5;
        border: 5px solid #FFDD57;
        border-radius: 5px;
    }

    .tag {
        margin-bottom: 2px;
    }

    .faultcat .btn {
        font-family: 'Open Sans';
    }

    .btn-fault-option-current {
        background-color: #bdbdbd !important;
    }

    #btn-info-open {
        float:right;
        cursor:pointer;
    }


    #btn-translate a {
        color: white;
        text-decoration: underline;
    }

    #Y,
    #N,
    #fetch,
    #change {
        margin-bottom: 2px;
    }

</style>
