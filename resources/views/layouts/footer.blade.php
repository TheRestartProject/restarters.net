    @include('partials.onboarding')

    <footer></footer>

    @vite(['resources/js/app.js', 'resources/global/js/app.js'])
    
    <!-- Initialize slick carousel (libraries loaded in header) -->
    <script>
    if (typeof jQuery !== 'undefined' && jQuery('.slideshow').length > 0) {
        jQuery('.slideshow').slick({
            dots: true,
            arrows: true,
            infinite: false,
            responsive: [
                {
                    breakpoint: 1024,
                    settings: {
                        arrows: false
                    }
                },
                {
                    breakpoint: 480,
                    settings: {
                        autoplay: true,
                        arrows: false
                    }
                }
            ]
        });
    }
    </script>

    @yield('scripts')
  </body>
</html>
