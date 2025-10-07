    @include('partials.onboarding')

    <footer></footer>

    @vite(['resources/js/app.js', 'resources/global/js/app.js'])

    <!-- Select2 JS from CDN (jQuery loaded in header) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js" integrity="sha512-2ImtlRlf2VVmiGZsjm9bEyhjGW4dU7B6TNwh/hx/iSByxNENtj3WVE6o/9Lj4TJeVXPi4bnOIMXFIJJAeufa0A==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    <!-- Bootstrap JS from CDN (jQuery loaded in header) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-fQybjgWLrvvRgtW6bFlB7jaZrFsaBXjsOMm/tB9LTS58ONXgqbR9W8oWht/amnpF" crossorigin="anonymous"></script>

    <!-- Initialize slick carousel -->
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
