// Regenerates the brand mark:  npm run logo
// An anjarai petti (Tamil spice box, top view: one cup in the centre, six around) on a kolam floor.
import { writeFileSync } from 'node:fs'
import { pulliDots, sikkuPath } from '../src/lib/kolam.ts'

// Faint 1-3-5-7-5-3-1 diamond kolam on the floor (4 x 4 sikku turned 45°, plus the in-between pulli).
const k = 4
const inner = Array.from({ length: (k - 1) ** 2 }, (_, i) => [(i % (k - 1)) + 1, Math.floor(i / (k - 1)) + 1])
const kolamScale = 64 / (Math.SQRT2 * k + 1.0)
const kolam = `<g transform="translate(32 32) scale(${kolamScale.toFixed(3)}) rotate(45) translate(${-k / 2} ${-k / 2})" opacity=".38">`
  + `<path d="${sikkuPath(k, k)}" fill="none" stroke="#fffaf3" stroke-width=".07" stroke-linecap="round"/>`
  + `<g fill="#fffaf3">${[...pulliDots(k, k), ...inner].map(([x, y]) => `<circle cx="${x}" cy="${y}" r=".07"/>`).join('')}</g></g>`

// Spices: colour + optional seed colour for texture.
const spices: [string, string?][] = [
  ['#c8321f'], // centre: red chilli powder
  ['#f2b705'], // turmeric
  ['#3a2518', '#6b4a33'], // mustard seeds
  ['#a97b47', '#7d5630'], // cumin
  ['#efe4cc', '#d9c9a6'], // urad dal
  ['#c49a55'], // coriander powder
  ['#2a2522', '#4a403a'], // black pepper
]
const R = 9.4 // centre-to-cup distance
const r = 4.3 // cup radius
const cups = spices.map(([fill, seed], i) => {
  const [cx, cy] = i === 0 ? [32, 32] : [32 + R * Math.cos(((i - 1) * Math.PI) / 3 - Math.PI / 2), 32 + R * Math.sin(((i - 1) * Math.PI) / 3 - Math.PI / 2)]
  const seeds = seed
    ? [[-1.3, -1], [1.2, -1.3], [0.2, 0.3], [-1, 1.5], [1.5, 1.2]].map(([dx, dy]) => `<circle cx="${(cx + dx).toFixed(2)}" cy="${(cy + dy).toFixed(2)}" r=".48" fill="${seed}"/>`).join('')
    : ''
  return `<circle cx="${cx.toFixed(2)}" cy="${cy.toFixed(2)}" r="${r + 0.9}" fill="url(#cupRim)"/>`
    + `<circle cx="${cx.toFixed(2)}" cy="${cy.toFixed(2)}" r="${r}" fill="${fill}"/>${seeds}`
    + `<ellipse cx="${(cx - 1.4).toFixed(2)}" cy="${(cy - 1.9).toFixed(2)}" rx="1.4" ry=".65" fill="#fff" opacity=".22" transform="rotate(-30 ${(cx - 1.4).toFixed(2)} ${(cy - 1.9).toFixed(2)})"/>`
}).join('')

const logo = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" role="img" aria-label="Adupangarai">
<defs>
  <linearGradient id="floor" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#8a4c26"/><stop offset="1" stop-color="#5c3118"/></linearGradient>
  <radialGradient id="steel" cx=".38" cy=".32" r=".8"><stop offset="0" stop-color="#fbfaf6"/><stop offset=".55" stop-color="#dcd7cc"/><stop offset="1" stop-color="#aaa396"/></radialGradient>
  <radialGradient id="cupRim" cx=".4" cy=".35" r=".8"><stop offset="0" stop-color="#f4f2ec"/><stop offset="1" stop-color="#a39c8f"/></radialGradient>
</defs>
<rect width="64" height="64" rx="15" fill="url(#floor)"/>
${kolam}
<circle cx="32.7" cy="33.4" r="20.2" fill="#2e160a" opacity=".35"/>
<circle cx="32" cy="32" r="20" fill="url(#steel)"/>
<circle cx="32" cy="32" r="18.6" fill="none" stroke="#9d968a" stroke-width=".5" opacity=".7"/>
${cups}
</svg>
`
writeFileSync(new URL('../public/logo.svg', import.meta.url), logo)
writeFileSync(new URL('../public/favicon.svg', import.meta.url), logo)
console.log('logo.svg + favicon.svg written')
