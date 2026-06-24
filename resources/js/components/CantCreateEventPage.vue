<template>
  <section class="events events-page">
    <div class="container-fluid">
      <div class="row justify-content-center">
        <div class="col-lg-12">
          <header>
            <h2>{{ __('cantcreate.heading') }}</h2>
          </header>

          <p>{{ __('cantcreate.intro') }}</p>

          <img
              class="d-none d-md-block img-fluid img-thumbnail mb-4"
              src="/images/dashboard/dashboard__getting-started-in-community-repair-1.png"
              :alt="__('cantcreate.image_alt')"
          >

          <p>
            <strong>{{ __('cantcreate.not_host_q') }}</strong>
            <span v-html="__('cantcreate.not_host_body')" />
            <br>
            <em>{{ __('cantcreate.missing_event') }}</em>
          </p>

          <p>
            <strong>{{ __('cantcreate.want_to_add_group_q') }}</strong>
            <span v-html="__('cantcreate.want_to_add_group_body')" />
          </p>

          <p>
            <strong>{{ __('cantcreate.start_new_group_q') }}</strong>
            <span v-html="__('cantcreate.start_new_group_body')" />
          </p>
        </div>
      </div>
    </div>
  </section>
</template>

<script>
export default {
  name: 'CantCreateEventPage',
}
</script>
