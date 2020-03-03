<style>
    /*
       this is css shared between the main misccat view,
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

    img#misccat {
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
        background-color: black;
        color: white;
        border: 5px solid #FFDD57;
        padding: 15px;
    }

    .problem {
        font-size: 1rem;
        background-color: #F5F5F5;
        border: 5px solid #FFDD57;
        border-radius: 5px;
    }

    .misccat .btn {
        font-family: 'Open Sans';
    }

    #btn-info-open {
        float:right;
        cursor:pointer;
    }


    #btn-translate a {
        color: #FFF;        
        text-decoration: underline;
    }

    #btn-translate {
        background-color: #000 !important;
    }

    .cat-is-unselected {
        background-color: #FFF4CC !important; 
        color: #000;
        font-weight: 500;
        margin: 3px 0;
    }
    .cat-is-selected {
        background-color: #FFDD57 !important;
        color: #FFF;
        font-weight: 500;
        margin: 3px 0;
    }

    .has-text-yellow {
        color: #FFDD57;
    }

    .has-text-grey {
        color: #BBB;
    }

    .question, .statement {
        font-weight: bolder;font-style: italic;
    }      
    .question {
        color: #96FFEF;
    }
    .statement {
        color: #00AA91;
    }


</style>
