<template>
  <div>
    <div class="row row-expanded pb-3">
      <div class="col-lg-6 d-flex">
        <form id="login-form" action="/login" method="post" class="card card__login col-12 panel" ref="form" @submit.prevent="submit">

          <input type="hidden" name="_token" :value="CSRF" />

          <div id="my_name_wrap" style="display:none;">
            <input name="my_name" type="text" value="" id="my_name">
            <input name="my_time" type="text" :value="time">
          </div>

          <legend>{{ translatedLoginTitle }}</legend>

          <div class="form-group">
            <label for="fp_email">{{ translatedEmailAddress }}:</label>
            <b-form-input type="email" name="email" id="fp_email" :value="email" required autofocus />
          </div>

          <div class="form-group">
            <label for="password">{{ translatedPassword }}:</label>
            <b-form-input type="password" name="password" id="password" required />
          </div>

          <div v-if="error">
            <div class="alert alert-danger" role="alert">
              {{ translatedAuthFailed }}
            </div>
          </div>
          <div class="row entry-panel__actions">
            <div class="col-6 col-md-8 align-content-center flex-column d-flex">
              <div class="row">
                <div class="col-12">
                  <a class="entry-panel__link" href="/user/recover">{{ translatedForgotPassword }}</a>
                </div>
                <div class="col-12">
                  <a class="entry-panel__link" href="/user/register">{{ translatedCreateAccount}}</a>
                </div>
              </div>
            </div>
            <div class="col-6 col-md-4 align-content-center flex-column justify-content-end d-flex">
              <b-button id="login-form-submit" type="submit" variant="primary">{{ translatedLogin }}</b-button>
            </div>
          </div>
        </form>
      </div>
      <div class="col-lg-6">
        <div class="card card__content col-12 panel panel__orange">
          <h3 style="font-weight:700">{{ translatedWhatIs }}</h3>
          <!-- eslint-disable-next-line -->
          <div v-html="translatedWhatIsContent" />
          <a href="/about" class="card__link">{{ translatedMore }}</a>
        </div>
      </div>
    </div>
  </div>
</template>
<script>
import auth from '../mixins/auth'

export default {
  components: {},
  mixins: [ auth ],
  props: {
    error: {
      type: Boolean,
      required: true
    },
    time: {
      type: String,
      required: true
    },
    email: {
      type: String,
      required: true
    }
  },
  data () {
    return {
      lastSubmit: null,
    }
  },
  computed: {
    CSRF() {
      return this.$store.getters['auth/CSRF']
    },
    translatedLoginTitle() {
      return this.$lang.get('login.login_title')
    },
    translatedEmailAddress() {
      return this.$lang.get('auth.email_address')
    },
    translatedPassword() {
      return this.$lang.get('auth.password')
    },
    translatedForgotPassword() {
      return this.$lang.get('auth.forgot_password')
    },
    translatedCreateAccount() {
      return this.$lang.get('auth.create_account')
    },
    translatedLogin() {
      return this.$lang.get('auth.login')
    },
    translatedWhatIs() {
      return this.$lang.get('login.whatis')
    },
    translatedWhatIsContent() {
      return this.$lang.get('login.whatis_content')
    },
    translatedMore() {
      return this.$lang.get('login.more')
    },
    translatedAuthFailed() {
      return this.$lang.get('auth.failed')
    }
  },
  methods: {
    submit() {
      // We've seen double submits of the login form, leading to 419 errors.  Prevent the user submitting twice by
      // double-clicking, or because an autosubmit happened and they didn't realise it.  Do this
      // by ignoring submits within 5 seconds of the last submit.
      //
      // The default event handler will proceed to validate the form (because of the required attributes) and
      // submit or show a native error.
      if (!this.lastSubmit || this.lastSubmit < Date.now() - 5000) {
        this.lastSubmit = Date.now()
        this.$refs.form.submit()
      } else {
        console.log('Ignore double submit')
      }
    }
  }
}
</script>