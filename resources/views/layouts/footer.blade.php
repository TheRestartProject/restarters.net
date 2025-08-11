    @include('partials.onboarding')

    <footer></footer>

    @vite(['resources/js/app.js', 'resources/global/js/app.js'])

    @yield('scripts')
  </body>
</html>
