import { defineConfig } from 'vitepress'
import fs from 'fs'
import path from 'path'

function loadManifest() {
  const manifestPath = path.resolve(__dirname, '../../docs/specs/manifest.json')
  if (!fs.existsSync(manifestPath)) {
    return { features: {}, personas: {}, coverage: { annotatedStories: 0, storiesWithTests: 0 } }
  }
  return JSON.parse(fs.readFileSync(manifestPath, 'utf-8'))
}

function buildSidebar() {
  const manifest = loadManifest()
  const featureItems = Object.keys(manifest.features).sort().map(name => ({
    text: `${name} (${manifest.features[name].storyCount})`,
    link: `/features/${name.toLowerCase().replace(/\s+/g, '-')}`
  }))
  const personaItems = Object.keys(manifest.personas).sort().map(name => ({
    text: `${name} (${manifest.personas[name].storyCount})`,
    link: `/personas/${name.toLowerCase().replace(/\s+/g, '-')}`
  }))

  return [
    {
      text: 'Features',
      items: featureItems
    },
    {
      text: 'Personas',
      items: personaItems
    }
  ]
}

export default defineConfig({
  title: 'Restarters Specifications',
  description: 'Living documentation of what Restarters.net does, organised by feature and persona',
  base: '/restarters.net/',
  themeConfig: {
    sidebar: buildSidebar(),
    nav: [
      { text: 'Features', link: '/features/' },
      { text: 'Personas', link: '/personas/' }
    ],
    socialLinks: [
      { icon: 'github', link: 'https://github.com/TheRestartProject/restarters.net' }
    ],
    search: {
      provider: 'local'
    }
  }
})
