import fs from 'fs'
import path from 'path'
import { compile } from 'vue-template-compiler'

// The production build (vite-plugin-vue2) compiles templates with whitespace: 'condense', which drops
// whitespace-only text containing a newline between sibling elements. The headline and the
// "Search your repair data" sentence are sibling elements, so the gap must be an explicit text node.
describe('FixometerHeading', () => {
  it('keeps a space between the headline and the search sentence after condensing', () => {
    const source = fs.readFileSync(path.join(__dirname, 'FixometerHeading.vue'), 'utf8')
    const template = source.match(/<template>([\s\S]*)<\/template>\s*<script>/)[1]
    const { render } = compile(template, { whitespace: 'condense' })
    const start = render.indexOf("devices.huge_impact'")
    const end = render.indexOf('devices.huge_impact_2')

    expect(start).toBeGreaterThan(-1)
    expect(end).toBeGreaterThan(start)
    expect(render.slice(start, end)).toContain('_v(')
  })
})
