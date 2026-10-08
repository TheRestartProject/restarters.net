<div class="d-flex justify-content-around w-100">
    <canvas
        data-stats-share
        data-count="{{ (int) $co2 }}"
        data-target="Facebook"
        data-t-weve-saved="{{ __('partials.share_modal_weve_saved') }}"
        data-t-of-co2="{{ __('partials.share_modal_of_co2') }}"
        data-t-by-repairing="{{ __('partials.share_modal_by_repairing') }}"
        data-t-broken-stuff="{{ __('partials.share_modal_broken_stuff') }}"
        data-t-thats-like="{{ __('partials.share_modal_thats_like') }}"
        data-t-growing-about="{{ __('partials.share_modal_growing_about') }}"
        data-t-seedling-singular="{{ trans_choice('partials.share_modal_seedlings', 1) }}"
        data-t-seedling-plural="{{ trans_choice('partials.share_modal_seedlings', 2) }}"
        data-t-planting-around="{{ __('partials.share_modal_planting_around') }}"
        data-t-hectare-singular="{{ trans_choice('partials.share_modal_hectares', 1) }}"
        data-t-hectare-plural="{{ trans_choice('partials.share_modal_hectares', 2) }}"
        style="max-width: 100%;"
    ></canvas>
</div>
<script>
    // We don't want the cookie notice in the IFRAME.
    window.noCookieNotice = true
</script>
@vite(['resources/global/js/widgets/stats-share.js'])
