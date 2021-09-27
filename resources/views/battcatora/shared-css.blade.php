<style>
    h1 {
        font-family: 'Asap' !important;
        font-weight: bold !important;
        float: left;
        /* bootstrap pull-left class working locally but not on server!  falling back on this. */
    }

    img#battcat {
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

    .device {
        background-color: #eee;
        border-radius: 15px;
    }

    .device-meta {
        padding: 10px;
        border-right: 2px #FFF solid;
    }

    .device-meta li {
        padding-top: 3px;
    }

    .device-problem {
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .battcat .btn {
        font-family: 'Open Sans';
    }

    #btn-info-open {
        float: right;
        cursor: pointer;
    }

    #btn-translate {
        float: right;
    }

    #btn-translate a {
        color: #000;
        text-decoration: none;
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

    .progress {
        font-size: small;
        height: 26px;
        border: 2px solid #19A4B9;
        border-radius: 15px;
        background-color: #fff;
    }

    .progress-bar {
        background-color: #19A4B9;
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
