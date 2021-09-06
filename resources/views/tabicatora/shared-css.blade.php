<style>
    /*
       this is css shared between the main tabicat view,
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
        float: left;
        /* bootstrap pull-left class working locally but not on server!  falling back on this. */
    }

    img#tabicat {
        width: 48px;
        height: 48px;
        float: right;
        margin-left: 10px;
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

    .tabicat .btn {
        font-family: 'Open Sans';
    }

    #btn-info-open {
        float: right;
        cursor: pointer;
    }

    #btn-translate a {
        color: white;
        text-decoration: underline;
    }

    #btn-poordata {
        background-color: #F45B69;
    }
    #fetch {
        margin-bottom: 2px;
    }

    .btn-fault-suggestion {
        margin-bottom: 3px;
    }

    .question,
    .statement {
        font-size: 1rem;
        font-weight: bold;
        text-transform: uppercase;
    }
    .signpost {
        padding: 1em 1em 0 0;
        margin: 1em auto;
    }
    .signpost div,
    .signpost p {
        padding: 0;
    }
    .signpost img {
        display: inline-block;
        margin: auto;
        max-width: 36px;
    }
    .signpost h5 {
        display: inline-block;
        font-size: 1rem;
        font-weight: bold;
    }

    .translation {
        padding-top: 2%;
        background-color: #fff;
        border: 1px solid #eee;
        -webkit-box-shadow: 6px 6px 0 0 #000;
        box-shadow: 6px 6px 0 0 #eee;
    }

    #ora-partnership hr {
        width: 50%;
    }

    #ora-partnership p {
        font-size: medium;
        font-weight: bold;
    }

    #ora-partnership img {
        width: 300px;
    }

 div.quest-closed {
     background-color:#4aaebc !important;
 }

 .quest-closed li {
     list-style-type: disc;
 }

 .quest-closed a {
     color: #111;
     text-decoration: underline;
 }

 .quest-closed a:hover {
     color: #111;
     text-decoration: none;
     font-weight: 600;
 }
</style>
