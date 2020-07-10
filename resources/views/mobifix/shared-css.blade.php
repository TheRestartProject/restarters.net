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

    .tag {
        margin-bottom: 2px;
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
        background-colour: pink !important;
    }
    
    .text-is-italic {
        font-style: italic;
    }
    
    .border-grey {        
        border: 1px solid #EEE;
    }

    .question, .statement {
     font-size: 1rem;
     font-weight: bold;
     text-transform: uppercase;
    }
    
</style>
