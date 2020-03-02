<style>
    .has-background-gold {background-color: #F69B05;}
    .has-background-teal {background-color: #21ACA7;}
    .has-background-pink {background-color: #EC2473;}
    .has-background-green {background-color: #16A765;}
    .has-text-white {color: #FFF;}
    .has-text-bold {font-weight: bold;}
    .is-horizontal-center {justify-content: center;}
    .is-left {justify-content: left;}
    ul, li {
        list-style-type: none;
    }
</style>
<!-- Modal -->
<div class="modal fade" data-backdrop="static" data-keyboard="false" id="taskctaModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">We collect data at repair events</h5>
            </div>
            <div class="modal-body">
                <div class="row problem p-2 mb-2 mx-1 mx-sm-0">
                    <div class="col">
                        <p>Our data is used for</p>
                        <div class="row has-text-white has-text-bold has-background-pink">
                            <div class="col">
                                <p>
                                    <span class="">Knowledge Base</span>
                                    <span class="">Repair events, public information, sharing economy</span>
                                </p>
                            </div>
                        </div>
                        <div class="row has-text-white has-text-bold has-background-teal">                            
                            <div class="col">
                                <p>
                                    <span class="">Community Statistics</span>
                                    <span class="">Public motivation, funding requirements, public relations, activism and campaigning</span>
                                </p>
                            </div>
                        </div>
                        <div class="row has-text-white has-text-bold has-background-gold">                            
                            <div class="col">
                                <p>
                                    <span class="">Product Statistics</span>
                                    <span class="">Reporting on environmental impact, policies, campaigning, lobbying</span>
                                </p>
                            </div>
                        </div>
                        <p>We want to improve the quality of our data. As a data volunteer you can</p>
                        <div class="row has-text-white has-text-bold has-background-green">                            
                            <div class="col">
                                Help us
                                <ul>
                                    <li>collect better data</li>
                                    <li>enrich our data</li>
                                    <li>create repair stories</li>
                                    <li>visualise and present</li>
                                    <li>share</li>
                                </ul>
                            </div>
                            <div class="col">
                                Learn about
                                <ul>
                                    <li>data collection</li>
                                    <li>crowdsourcing</li>
                                    <li>aggregation</li>
                                    <li>standards</li>
                                    <li>open data</li>
                                </ul>                                
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">                            
                <div class="col">
                    <p class="buttons">
                        <button class="btn btn-md btn-success btn-rounded" id="join">
                            <span class="underline">J</span>oin Restarters</button>
                        <a href="/misccat" id="skip" class="btn btn-md btn-warning btn-rounded">
                            S<span class="underline">k</span>ip for now
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
