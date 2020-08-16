<!-- Modal -->
<div class="modal fade" id="mobifixoraInfoModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="mobifixoraModalLabel">About MobiFix ORA</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <img src="{{ asset('/images/mobifix/whale-spouting.png') }}" alt="happy whale" width="48" height="48" />
                <p>
                    MobiFix ORA is a web app to <strong>categorise the type of faults in smartphones brought to community events</strong> held by partners of the <a href="https://openrepair.org/" target="_blank">Open Repair Alliance</a> - <a href="https://repaircafe.org/en/" target="_blank">Repair Café</a> and <a href="https://anstiftung.de/english" target="_blank">anstiftung</a>.
                </p>
                <hr/>
                <p>
                    <strong>To enable community repair to contribute to upcoming policy discussions on the right to repair for smartphones</strong>, we want to see what types of faults are commonly encountered.
                </p>
                <p>
                    Volunteers collect data about the devices that are brought into repair events. The data is uploaded to the partner databases who then share it as <a target="_blank" href="https://openrepair.org/open-data/downloads">Open Data</a>.
                </p>
                <p>
                    So we’d like to analyse the data collected so far, to learn about key faults seen at repair events - and this is where you can help, by playing MobiFixOra!
                </p>

                <hr/>
                <div class="text-left">
                    <h2>To Do: Items for review in v1.0-alpha</h2>
                    <ul>
                        <li>1. This app will likely be published for partner audiences rather than the general public. Partner data can be filtered using a url query i.e. <a href="http://restarters.test/mobifixora?partner=anstiftung">anstiftung</a> and <a href="http://restarters.test/mobifixora?partner=repaircafe">Repair Café</a></li>
                        <li>2. Suggestions rely on the (rather poor) Google sheet English translations. Should we expose the translation or hide it? (The Translate button produces much better translation).</li>
                        <li>3. This version has no Call To Action pop-up, do we want one and if so what should it do?</li>
                        <li>4. Do we want a Talk thread for this app?</li>
                        <li>5. What content do we want in this Info pop-up? Links? ORA, Open Data explanation, Kaggle, GitHub, blog posts, partners...</li>
                    </ul>
                    <h2>Notes:</h2>
                    <p>"anstiftung are happy to go with Restart's branding and if we decide not to translate the fault types into German they're happy to add translations to the fault types in an email to volunteers."</p>
                    <p>Repair Café data is mostly in Dutch with a few other languages present (English, French) but some of the detected languages are dubious, e.g. might be Dutch or Frisian. UI translation would require a language switcher.</p>
                    <p>Providing a language switcher for Dutch and German would be a new, time-consuming feature for Restarters.net.</p>
                    <p>This version includes new microtask features such as tracking viewed records so as not to present duplicates (per session) and better handling of the "no more records" scenario.</p>
                </div>
            </div>
        </div>
    </div>
</div>

