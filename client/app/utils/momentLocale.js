// Vite only bundles the moment locales that are imported, and none are by
// default, so moment.locale('fr') silently stays English and every date
// formatted with moment (an event's "ddd Do MMM YYYY", "x days ago" on the
// dashboard) is English for every user. Import from moment/dist so the
// locales register on the same moment instance that `import moment from
// 'moment'` / 'moment-timezone' resolve to.
//
// This is the Nuxt counterpart of develop's resources/js/moment-locale.js, with
// one difference: develop set the locale once from <html lang>; here the user
// can switch language without a reload, so applyMomentLocale() is called again
// on every change (plugins/moment-locale.client.js).
import moment from 'moment'
import 'moment/dist/locale/fr'

// Each locale import above also switches moment's global locale as a side
// effect, so applyMomentLocale() must always be called afterwards.

// The app's locale codes are en, fr and fr-BE. moment has no fr-BE (it falls
// back to the base language 'fr' when given the regional code), and 'en' is
// built in.
export function applyMomentLocale(code) {
  const wanted = String(code || 'en')
  moment.locale(wanted === 'no' ? 'nb' : wanted)

  return moment.locale()
}

applyMomentLocale('en')
