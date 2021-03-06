<style>
    /*
       this is css shared between the main mobifix view,
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

    img#mobifix {
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
        background-color: #FFF;
    }

    .mobifix .btn {
        font-family: 'Open Sans';
    }

    #btn-info-open {
        float:right;
        cursor:pointer;
    }

    #btn-translate a {
        color: white;
        text-decoration: underline;
    }

    #fetch {
        margin-bottom: 2px;
    }

    .btn-fault-suggestion {
        margin-bottom: 3px;
    }

    .question, .statement {
        font-size: 1rem;
        font-weight: bold;
        text-transform: uppercase;
    }

    .translation {
        padding-top: 2%;
        background-color: #fff;
        border: 1px solid #eee;
        -webkit-box-shadow: 6px 6px 0 0 #000;
        box-shadow: 6px 6px 0 0 #eee;
    }
    .mobifix div.quest-closed {
        background-color:#4aaebc;
    }

    .mobifix .quest-closed li {
        list-style-type: disc;
    }

    .mobifix .quest-closed a {
        color: #111;
        text-decoration: underline;
    }

    .mobifix .quest-closed a:hover {
        color: #111;
        text-decoration: none;
        font-weight: 600;
    }

</style>
