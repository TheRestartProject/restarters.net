import fs from 'fs'
import path from 'path'
import { fileURLToPath } from 'url'

const __dirname = path.dirname(fileURLToPath(import.meta.url))
const manifestPath = path.resolve(__dirname, '../docs/specs/manifest.json')
const narrativesDir = path.resolve(__dirname, '../docs/specs/narratives')
const featuresDir = path.resolve(__dirname, 'features')
const personasDir = path.resolve(__dirname, 'personas')

const GITHUB_BASE = 'https://github.com/TheRestartProject/restarters.net/blob/develop'

function loadManifest() {
  if (!fs.existsSync(manifestPath)) {
    console.error('No manifest found at docs/specs/manifest.json. Run php artisan specs:extract first.')
    process.exit(1)
  }
  return JSON.parse(fs.readFileSync(manifestPath, 'utf-8'))
}

function loadNarrative(featureName) {
  const slug = featureName.toLowerCase().replace(/\s+/g, '-')
  const filePath = path.join(narrativesDir, `${slug}.md`)
  if (!fs.existsSync(filePath)) {
    return null
  }
  let content = fs.readFileSync(filePath, 'utf-8')
  // Strip the specs:hash comment
  content = content.replace(/<!--\s*specs:hash\s+\S+\s+\([^)]+\)\s*-->\n?/, '')
  // Strip the top-level heading (we generate our own)
  content = content.replace(/^#\s+.+\n+/, '')
  return content.trim()
}

function coverageIndicator(tests) {
  if (!tests || tests.length === 0) return ':x: Uncovered'
  const hasPhp = tests.some(t => t.file.endsWith('.php'))
  const hasJs = tests.some(t => t.file.endsWith('.ts') || t.file.endsWith('.js'))
  if (hasPhp && hasJs) return ':white_check_mark: Multi-layer'
  return ':white_check_mark: Covered'
}

function coveragePercent(stories) {
  if (stories.length === 0) return '0%'
  const covered = stories.filter(s => s.tests && s.tests.length > 0).length
  return `${Math.round((covered / stories.length) * 100)}%`
}

function ensureDir(dir) {
  if (!fs.existsSync(dir)) {
    fs.mkdirSync(dir, { recursive: true })
  }
}

function generateFeaturePages(manifest) {
  ensureDir(featuresDir)

  // Index page
  const featureNames = Object.keys(manifest.features).sort()
  let indexContent = `# Features\n\nRestarters.net functionality organised by feature area.\n\n`
  indexContent += `| Feature | Stories | Personas | Coverage |\n|---------|---------|----------|----------|\n`

  for (const name of featureNames) {
    const f = manifest.features[name]
    const slug = name.toLowerCase().replace(/\s+/g, '-')
    const covered = f.stories.filter(s => s.tests && s.tests.length > 0).length
    indexContent += `| [${name}](/features/${slug}) | ${f.storyCount} | ${f.personas.join(', ')} | ${covered}/${f.storyCount} (${coveragePercent(f.stories)}) |\n`
  }

  fs.writeFileSync(path.join(featuresDir, 'index.md'), indexContent)

  // Individual feature pages
  for (const name of featureNames) {
    const f = manifest.features[name]
    const slug = name.toLowerCase().replace(/\s+/g, '-')
    const narrative = loadNarrative(name)

    let content = `# ${name}\n\n`

    if (f.description) {
      content += `> ${f.description}\n\n`
    }

    const covered = f.stories.filter(s => s.tests && s.tests.length > 0).length
    content += `**${f.storyCount} stories** across ${f.personas.length} personas | **Coverage:** ${covered}/${f.storyCount} (${coveragePercent(f.stories)})\n\n`

    if (narrative) {
      content += `## Overview\n\n${narrative}\n\n`
    }

    // Group stories by persona
    const byPersona = {}
    for (const story of f.stories) {
      if (!byPersona[story.persona]) {
        byPersona[story.persona] = []
      }
      byPersona[story.persona].push(story)
    }

    for (const persona of Object.keys(byPersona).sort()) {
      const stories = byPersona[persona]
      content += `## ${persona}\n\n`
      content += `| Story | Method | Tests |\n|-------|--------|-------|\n`

      for (const story of stories) {
        const methodLink = `[\`${story.method}\`](${GITHUB_BASE}/${story.file})`
        content += `| ${story.story} | ${methodLink} | ${coverageIndicator(story.tests)} |\n`
      }

      content += `\n`
    }

    // Sources
    content += `## Source Files\n\n`
    for (const source of f.sources.sort()) {
      content += `- [\`${source}\`](${GITHUB_BASE}/${source})\n`
    }

    fs.writeFileSync(path.join(featuresDir, `${slug}.md`), content)
  }
}

function generatePersonaPages(manifest) {
  ensureDir(personasDir)

  // Index page
  const personaNames = Object.keys(manifest.personas).sort()
  let indexContent = `# Personas\n\nRestarters.net functionality organised by user persona.\n\n`
  indexContent += `| Persona | Features | Stories | Coverage |\n|---------|----------|---------|----------|\n`

  for (const name of personaNames) {
    const p = manifest.personas[name]
    const slug = name.toLowerCase().replace(/\s+/g, '-')

    // Calculate coverage for this persona across all features
    let totalStories = 0
    let coveredStories = 0
    for (const featureName of p.features) {
      const feature = manifest.features[featureName]
      if (!feature) continue
      for (const story of feature.stories) {
        if (story.persona === name) {
          totalStories++
          if (story.tests && story.tests.length > 0) coveredStories++
        }
      }
    }

    indexContent += `| [${name}](/personas/${slug}) | ${p.features.length} | ${p.storyCount} | ${coveredStories}/${totalStories} (${totalStories > 0 ? Math.round((coveredStories / totalStories) * 100) : 0}%) |\n`
  }

  fs.writeFileSync(path.join(personasDir, 'index.md'), indexContent)

  // Individual persona pages
  for (const name of personaNames) {
    const p = manifest.personas[name]
    const slug = name.toLowerCase().replace(/\s+/g, '-')

    let content = `# ${name}\n\n`
    content += `**${p.storyCount} stories** across ${p.features.length} features\n\n`

    for (const featureName of p.features.sort()) {
      const feature = manifest.features[featureName]
      if (!feature) continue

      const personaStories = feature.stories.filter(s => s.persona === name)
      if (personaStories.length === 0) continue

      const featureSlug = featureName.toLowerCase().replace(/\s+/g, '-')
      content += `## [${featureName}](/features/${featureSlug})\n\n`
      content += `| Story | Method | Tests |\n|-------|--------|-------|\n`

      for (const story of personaStories) {
        const methodLink = `[\`${story.method}\`](${GITHUB_BASE}/${story.file})`
        content += `| ${story.story} | ${methodLink} | ${coverageIndicator(story.tests)} |\n`
      }

      content += `\n`
    }

    fs.writeFileSync(path.join(personasDir, `${slug}.md`), content)
  }
}

function generateHomePage(manifest) {
  const totalStories = manifest.coverage.annotatedStories
  const withTests = manifest.coverage.storiesWithTests
  const featureCount = Object.keys(manifest.features).length
  const personaCount = Object.keys(manifest.personas).length

  let content = `---
layout: home
hero:
  name: Restarters Specifications
  tagline: Living documentation of what Restarters.net does
  actions:
    - theme: brand
      text: Browse by Feature
      link: /features/
    - theme: alt
      text: Browse by Persona
      link: /personas/
---

## About Restarters.net

Restarters.net is the platform behind the global community repair movement. It brings together volunteer fixers, event hosts, and repair networks to organise community repair events and measure their environmental impact.

The platform combines three core modules: **The Fixometer** for organising repair events and recording their impact, **Restarters Talk** for community discussion, and **Restarters Wiki** for collectively produced repair knowledge. Groups around the world use it to run events, log device repairs, track waste prevented, and coordinate through regional networks.

This site is the living specification — every user story listed here is extracted directly from the codebase and linked to its test coverage. It updates automatically as the code changes.

## At a Glance

| | |
|---|---|
| **Features** | ${featureCount} |
| **Personas** | ${personaCount} |
| **User Stories** | ${totalStories} |
| **Stories with Tests** | ${withTests} (${totalStories > 0 ? Math.round((withTests / totalStories) * 100) : 0}%) |
| **Generated** | ${manifest.generatedAt || 'Unknown'} |

## Features

`

  for (const name of Object.keys(manifest.features).sort()) {
    const f = manifest.features[name]
    const slug = name.toLowerCase().replace(/\s+/g, '-')
    content += `- [**${name}**](/features/${slug}) — ${f.description || `${f.storyCount} stories`} (${f.storyCount} stories)\n`
  }

  content += `\n## Personas\n\n`

  for (const name of Object.keys(manifest.personas).sort()) {
    const p = manifest.personas[name]
    const slug = name.toLowerCase().replace(/\s+/g, '-')
    content += `- [**${name}**](/personas/${slug}) — ${p.storyCount} stories across ${p.features.join(', ')}\n`
  }

  fs.writeFileSync(path.join(__dirname, 'index.md'), content)
}

// Main
const manifest = loadManifest()
console.log(`Generating pages from manifest (${manifest.coverage.annotatedStories} stories, ${Object.keys(manifest.features).length} features, ${Object.keys(manifest.personas).length} personas)`)
generateHomePage(manifest)
generateFeaturePages(manifest)
generatePersonaPages(manifest)
console.log('Pages generated successfully')
