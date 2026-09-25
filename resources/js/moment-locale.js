// Vite doesn't bundle moment's locale data unless it's imported, so without this moment.locale('fr') silently
// stays in English and every date we format with moment (e.g. a group's next event) is English for all users.
//
// Import from moment/dist so the locales register on the same ESM moment instance that `import moment from
// 'moment'` resolves to.  Regional variants (fr-BE, nl-BE) fall back to the base language.
import moment from 'moment'
import 'moment/dist/locale/fr'
import 'moment/dist/locale/de'
import 'moment/dist/locale/es'
import 'moment/dist/locale/it'
import 'moment/dist/locale/nl'
import 'moment/dist/locale/nl-be'
import 'moment/dist/locale/nb'
import 'moment/dist/locale/ne'

// Each import above also switches moment's global locale as a side effect, so set it last.
// moment calls Norwegian Bokmål 'nb' rather than 'no'.
const locale = document.documentElement.lang || 'en'
moment.locale(locale === 'no' ? 'nb' : locale)
