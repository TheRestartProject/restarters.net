<div class="vue">
    <StatsShare :count="{{{ $co2 }}}" :target="'Facebook'"/>
</div>
<script>
    // We don't want the cookie notice in the IFRAME.
    window.noCookieNotice = true
</script>
@vite(['resources/js/app.js'])
