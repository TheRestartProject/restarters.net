@include('layouts/header_plain') @yield('content')
<section class="cookie-policy">
    <div class="container">

        <div class="row row-expanded" id="logostats-header">
            <div class="col-lg-4">
                <header>
                    <a href="/">
            @include('includes.logo')
          </a>
                </header>
            </div>
        </div>

        <h1>Cookie Policy</h1>

        <p>
            Our website, like most, uses <strong>cookies</strong> to help the website work and to help us understand how visitors find and navigate the website. Cookies are small text files which are stored on your computer by your web browser. You can find more information about cookies at <a href="http://aboutcookies.org.uk/">aboutcookies.org.uk</a>.
        </p>

        <p>
            This page outlines the cookies that we use on our website, and what they do.
        </p>

        <h3>Managing cookies</h3>

        <p>
            When you first visit our site, you are able to opt-in to our use of cookies (as outlined in this policy) and for these cookies to be added to your browser. If you opt-in, and subsequently wish to opt-out, you can do so by editing your <a class="gdpr-cookie-notice-settings-button" href="#">cookie settings</a>.
        </p>

        <h2>How does restarters.net use cookies?</h2>

        <p>
            We use different types of cookies for different purposes. These are:
        </p>

        <ul>
            <li><strong>strictly necessary cookies.</strong> These are cookies that are required for the operation of our website. (For example, the cookie to remember opt-in consent for cookies!)</li>
            <li><strong>analytical/performance cookies</strong>: to analyse the use of our website and its performance, in order to improve it (for example, to count the number of visitors and to see how visitors move around our website when they are using it). This helps us to improve the way our website works, for example, by ensuring that users are finding what they are looking for easily.</li>
            <li><strong>marketing/targeting cookies</strong>: we do not use any marketing cookies.</li>
        </ul>

        <h2>Some different types of cookies</h2>

        <p>
            Cookies may be <strong>first party</strong> or <strong>third party</strong>. This refers to whether the cookie is set by our site or by another third party website.
        </p>

        <ul>
            <li><strong>First Party. </strong> First-party cookies are those set by the website that is being visited by the user at the time – the website displayed in the address bar of your browser.</li>
            <li><strong>Third Party.</strong> Third-party cookies are cookies that are set by a domain other than that of the website you are visiting. If you visit a website and another entity sets the cookie through that website, this would be a third-party cookie.</li>
        </ul>

        <p>
            Cookies may also be <strong>persistent</strong> or <strong>session</strong> cookies.
        </p>

        <ul>
            <li><strong>Persistent cookies.</strong> These cookies remain on your device for the period of time specified in the cookie. They are used each time you visit the website that created the cookie.</li>
            <li><strong>Session cookies. </strong>Session cookies are created temporarily. These cookies allow websites to link your actions during a browser session. A browser session starts you open the browser window and finishes when you close the browser window. Once you close the browser, all session cookies are deleted.</li>
        </ul>

        <h3>The cookies we use</h3>

        <h2>Cookies set by us (first party)</h2>

        <table class="table" style="border: 1px solid gray;">
            <tbody>
                <tr>
                    <th>Cookie Name</th>
                    <th>Domain</th>
                    <th>Description</th>
                    <th>Type</th>
                </tr>
                <tr>
                    <td>restarters_session</td>
                    <td>.restarters.net</td>
                    <td>Used to keep you logged in between visits to the application.</td>
                    <td>Necessary</td>
                </tr>
                <tr>
                    <td>XSRF-TOKEN</td>
                    <td>.restarters.net</td>
                    <td>Used to prevent data being posted to the application maliciously.</td>
                    <td>Necessary</td>
                </tr>
                <tr>
                    <td>UseCDNCache, UseDC</td>
                    <td>.restarters.net</td>
                    <td>Used by Mediawiki</td>
                    <td>Necessary</td>
                </tr>
                <tr>
                    <td>wiki_db_mw__session, wiki_db_mw_Token, wiki_db_mw_UserID, wiki_db_mw_UserName</td>
                    <td>.restarters.net</td>
                    <td>Used for automatic login to wiki.restarters.net</td>
                    <td>Necessary</td>
                </tr>
                <tr>
                    <td>_ga, _gat, _gid</td>
                    <td>.restarters.net</td>
                    <td>Used to distinguish users on the Google Analytics platform and to control the speed at which requests are made to the Google Analytics platform. Statistics derived from this information help us identify how well certain pages and aspects of the site perform, and where we can make improvements to help visitors access the information they need more quickly and with less hassle.</td>
                    <td>Analytical / Performance</td>
                </tr>
            </tbody>
        </table>

        <h2>Cookies set by others (third party)</h2>

        <p>
            There are currently no third party cookies.
        </p>

</section>

@include('layouts/footer')
